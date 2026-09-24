<?php
namespace Opencart\Catalog\Controller\Extension\Nkolay\Payment;

if (!defined('VERSION')) exit;

class Nkolay extends \Opencart\System\Engine\Controller
{
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

        $amount = $this->currency->format(
            $order['total'], $order['currency_code'], $order['currency_value'], false
        );

        $sep = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';
        $callbackUrl = $this->url->link('extension/nkolay/payment/nkolay' . $sep . 'callback');

        $formData = $client->buildRedirectFormData([
            'clientRefCode' => \PayNKolayClient::buildClientRefCode('Opencart4x', $this->session->data['order_id']),
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

    public function callback(): void
    {
        $client   = $this->getClient();
        $postData = $this->request->post;

        if (!$client->verifyCallback($postData)) {
            $this->session->data['error'] = 'Hash dogrulama hatasi.';
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $orderId = \PayNKolayClient::extractOrderId($postData['CLIENT_REFERENCE_CODE'] ?? '');

        // Non-3D: direct result
        if (!\PayNKolayClient::is3D($postData)) {
            if (\PayNKolayClient::isSuccess($postData)) {
                $this->confirmOrder($orderId);
                $this->response->redirect($this->url->link('checkout/success'));
            } else {
                $this->session->data['error'] = $postData['RESPONSE_DATA'] ?? 'Odeme basarisiz.';
                $this->response->redirect($this->url->link('checkout/cart'));
            }
            return;
        }

        // 3D: complete the payment
        if (\PayNKolayClient::isSuccess($postData)) {
            $result = $client->completePayment($postData['REFERENCE_CODE']);

            if (($result->RESPONSE_CODE ?? '') == \PayNKolayClient::RESPONSE_SUCCESS) {
                $this->confirmOrder($orderId);
                $this->response->redirect($this->url->link('checkout/success'));
            } else {
                $this->session->data['error'] = $result->RESPONSE_DATA ?? 'Odeme tamamlanamadi.';
                $this->response->redirect($this->url->link('checkout/cart'));
            }
        } else {
            $this->session->data['error'] = $postData['RESPONSE_DATA'] ?? 'Odeme basarisiz.';
            $this->response->redirect($this->url->link('checkout/cart'));
        }
    }

    private function confirmOrder(string $orderId): void
    {
        $this->load->model('checkout/order');
        $this->model_checkout_order->addHistory($orderId, $this->config->get('payment_nkolaypos_order_status_id'));
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
