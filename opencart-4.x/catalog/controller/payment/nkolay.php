<?php
namespace Opencart\Catalog\Controller\Extension\Nkolay\Payment;

if (!defined('VERSION')) exit;

class Nkolay extends \Opencart\System\Engine\Controller
{
    /** Order status of an order created by checkout/confirm but not yet paid. */
    const ORDER_STATUS_MISSING = 0;

    public function index(): string
    {
        $client = $this->getClient();
        \PayNKolayClient::fixCookieSameSite(['OCSESSID', 'PHPSESSID']);

        $this->load->model('checkout/order');
        // One-page checkouts (e.g. Journal 3) may load this template outside the
        // stock confirm step; render nothing instead of erroring without an order.
        $orderId = $this->session->data['order_id'] ?? 0;
        $order = $orderId ? $this->model_checkout_order->getOrder($orderId) : false;
        if (!$order) {
            return '';
        }

        // Charge in the order's own currency; the gateway would treat a bare amount as TRY.
        $currencyNumber = \PayNKolayClient::currencyNumber($order['currency_code']);
        if ($currencyNumber === '') {
            return '';
        }

        $amount = \PayNKolayClient::formatAmount($this->orderAmount($order));

        $sep = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';
        $callbackUrl = $this->url->link('extension/nkolay/payment/nkolay' . $sep . 'callback');
        $clientRefCode = \PayNKolayClient::buildClientRefCode('Opencart4x', $order['order_id']);

        // Remember what we ask the gateway to charge; the callback is checked against this row.
        $this->load->model('extension/nkolay/payment/nkolay');
        $this->model_extension_nkolay_payment_nkolay->ensureTable();
        $this->model_extension_nkolay_payment_nkolay->addPayment(
            $order['order_id'], $clientRefCode, \PayNKolayClient::toMinorUnits($amount), $order['currency_code']
        );

        $formData = $client->buildRedirectFormData([
            'clientRefCode' => $clientRefCode,
            'amount'        => $amount,
            'currencyCode'  => $currencyNumber,
            'successUrl'    => $callbackUrl,
            'failUrl'       => $callbackUrl,
            'use3D'         => $this->config->get('payment_nkolaypos_type') == '3D' ? 'true' : 'false',
            'platform'      => \PayNKolayClient::platformTag('Opencart4x', VERSION),
        ]);

        $this->load->language('extension/nkolay/payment/nkolay');

        $data['action']    = $client->getRedirectUrl();
        $data['form_data'] = $formData;

        return $this->load->view('extension/nkolay/payment/nkolay', $data);
    }

    /**
     * successUrl / failUrl handler. The POST comes through the customer's browser, so
     * every field is untrusted until the hash is verified, and even then
     * CLIENT_REFERENCE_CODE is unsigned: it only selects the payment row we stored in
     * index(), and the order id, amount and currency are taken from that row and the order.
     * Any failed check makes no order change and shows a fixed message.
     */
    public function callback(): void
    {
        $client   = $this->getClient();
        $postData = $this->request->post;

        $this->load->model('checkout/order');
        $this->load->model('extension/nkolay/payment/nkolay');
        $payments = $this->model_extension_nkolay_payment_nkolay;
        $payments->ensureTable();

        if (!$client->verifyCallback($postData)) {
            $this->reject('invalid hash', $postData);
            return;
        }

        $clientRefCode = isset($postData['CLIENT_REFERENCE_CODE']) && is_string($postData['CLIENT_REFERENCE_CODE']) ? $postData['CLIENT_REFERENCE_CODE'] : '';
        $referenceCode = isset($postData['REFERENCE_CODE']) && is_string($postData['REFERENCE_CODE']) ? trim($postData['REFERENCE_CODE']) : '';

        $payment = $payments->getPaymentByClientRef($clientRefCode);
        if (!$payment) {
            $this->reject('unknown CLIENT_REFERENCE_CODE', $postData);
            return;
        }

        if (!\PayNKolayClient::isSuccess($postData)) {
            // Declined: leave the order untouched so the customer can retry.
            $this->reject('declined', $postData, 'Odeme basarisiz.');
            return;
        }

        if ($referenceCode === '' || strlen($referenceCode) > 64) {
            $this->reject('missing REFERENCE_CODE', $postData);
            return;
        }

        // REFERENCE_CODE is single-use: a replay of this same paid callback is fine,
        // any other reuse is rejected.
        $used = $payments->getPaymentByReference($referenceCode);
        if ($used) {
            if ($used['payment_id'] == $payment['payment_id'] && $used['status'] == 'paid') {
                $this->response->redirect($this->url->link('checkout/success'));
                return;
            }
            $this->reject('REFERENCE_CODE already used', $postData);
            return;
        }

        $order = $this->model_checkout_order->getOrder($payment['order_id']);
        $expected = $this->expectedMinor($order, $payment);
        if ($expected === null) {
            $this->reject('order not awaiting this payment', $postData);
            return;
        }

        if (!$this->currencyOk($postData, $order)) {
            $this->reject('currency mismatch', $postData);
            return;
        }

        // An empty signed amount is tolerated only on a 2-step 3D callback; completePayment's
        // amount is checked below in that case.
        $authorized = \PayNKolayClient::toMinorUnits(isset($postData['AUTHORIZATION_AMOUNT']) ? $postData['AUTHORIZATION_AMOUNT'] : '');
        $captured = \PayNKolayClient::isPaymentSuccessful($postData);
        if (($authorized === null && ($captured || !\PayNKolayClient::is3D($postData))) || ($authorized !== null && $authorized < $expected)) {
            $this->reject('amount below order total', $postData);
            return;
        }

        if (!$payments->claimPayment($payment['payment_id'], $referenceCode)) {
            $this->reject('payment already processed', $postData);
            return;
        }

        if (!$captured) {
            // RESPONSE_CODE=2 without a provision code: non-3D is not a payment; 3D with
            // AUTO_COMPLETE off still needs the server-side CompletePayment call.
            $result = \PayNKolayClient::is3D($postData) ? (array) $client->completePayment($referenceCode) : [];
            $completedAmount = \PayNKolayClient::toMinorUnits(isset($result['AUTHORIZATION_AMOUNT']) ? $result['AUTHORIZATION_AMOUNT'] : '');
            $completedAmount = $completedAmount === null ? $authorized : $completedAmount;
            if (!\PayNKolayClient::isPaymentSuccessful($result)
                || (isset($result['REFERENCE_CODE']) && (string) $result['REFERENCE_CODE'] !== '' && (string) $result['REFERENCE_CODE'] !== $referenceCode)
                || $completedAmount === null || $completedAmount < $expected) {
                $payments->setPaymentStatus($payment['payment_id'], 'failed');
                $this->reject('payment not completed', $postData, 'Odeme tamamlanamadi.');
                return;
            }
        }

        $payments->setPaymentStatus($payment['payment_id'], 'paid');
        $this->model_checkout_order->addHistory(
            (int) $payment['order_id'],
            (int) $this->config->get('payment_nkolaypos_order_status_id'),
            'paynkolay REFERENCE_CODE: ' . $referenceCode
        );
        $this->response->redirect($this->url->link('checkout/success'));
    }

    /**
     * Amount (in minor units) the callback must at least authorize, or null when the
     * order is not an unpaid order of this payment method matching the stored attempt.
     * Takes the larger of the amount sent and the current order total, so editing
     * the order after the form was rendered cannot lower what has to be paid.
     */
    private function expectedMinor($order, array $payment)
    {
        if (!$order || (int) $order['order_id'] !== (int) $payment['order_id']) {
            return null;
        }
        // 4.0.2+ stores the chosen method as an array, 4.0.0/4.0.1 as payment_code.
        $code = isset($order['payment_method']['code']) ? $order['payment_method']['code'] : ($order['payment_code'] ?? '');
        if (strtok((string) $code, '.') !== 'nkolay' || $order['currency_code'] !== $payment['currency_code']) {
            return null;
        }
        $status = (int) $order['order_status_id'];
        if ($status !== self::ORDER_STATUS_MISSING && $status !== (int) $this->config->get('config_order_status_id')) {
            return null;
        }
        $current = \PayNKolayClient::toMinorUnits(\PayNKolayClient::formatAmount($this->orderAmount($order)));
        if ($current === null || $current <= 0) {
            return null;
        }

        return max($current, (int) $payment['amount_minor']);
    }

    /**
     * With a v2 hash the signed CURRENCY_CODE must be the order currency. Without one
     * the currency is unsigned, so only TRY (the gateway default) orders are accepted.
     */
    private function currencyOk(array $postData, array $order): bool
    {
        $currency = isset($postData['CURRENCY_CODE']) ? $postData['CURRENCY_CODE'] : '';
        if (\PayNKolayClient::hasV2Hash($postData) && $currency !== '') {
            return \PayNKolayClient::currencyMatches($currency, $order['currency_code']);
        }

        return \PayNKolayClient::currencyNumber($order['currency_code']) === '949';
    }

    private function orderAmount(array $order): string
    {
        return (string) $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
    }

    /**
     * Log the reason and send the customer back to the cart with a fixed message.
     * Gateway text (RESPONSE_DATA) is never shown: it is unsigned and attacker-controlled.
     */
    private function reject(string $reason, array $postData, string $message = 'Odeme dogrulanamadi.')
    {
        $ref = isset($postData['CLIENT_REFERENCE_CODE']) && is_string($postData['CLIENT_REFERENCE_CODE']) ? $postData['CLIENT_REFERENCE_CODE'] : '';
        $this->log->write('paynkolay callback rejected: ' . $reason . ' (CLIENT_REFERENCE_CODE=' . substr($ref, 0, 128) . ')');
        $this->session->data['error'] = $message;
        $this->response->redirect($this->url->link('checkout/cart'));
    }

    private function getClient(): \PayNKolayClient
    {
        require_once(DIR_EXTENSION . 'nkolay/system/library/paynkolay.php');

        return new \PayNKolayClient([
            'sx'         => $this->config->get('payment_nkolaypos_sx'),
            'secret_key' => $this->config->get('payment_nkolaypos_secret'),
            'test_mode'  => $this->config->get('payment_nkolaypos_mode') == '1',
        ]);
    }
}
