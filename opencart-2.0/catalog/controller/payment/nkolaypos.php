<?php
if (!defined('VERSION')) exit;

class ControllerPaymentNkolayPos extends Controller
{
    public function index()
    {
        $client = $this->getClient();
        PayNKolayClient::fixCookieSameSite(['OCSESSID', 'PHPSESSID']);

        $this->load->model('checkout/order');
        // One-page checkouts (e.g. Journal 3) may load this template outside the
        // stock confirm step; render nothing instead of erroring without an order.
        $orderId = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0;
        $order = $orderId ? $this->model_checkout_order->getOrder($orderId) : false;
        if (!$order) {
            return '';
        }

        // Charge in the order's own currency; the gateway would treat a bare amount as TRY.
        $currencyNumber = PayNKolayClient::currencyNumber($order['currency_code']);
        if ($currencyNumber === '') {
            return '';
        }

        $amount = $this->currency->format(
            $order['total'], $order['currency_code'], $order['currency_value'], false
        );

        $callbackUrl = $this->url->link('payment/nkolaypos/callback', '', true);

        $formData = $client->buildRedirectFormData([
            'clientRefCode' => PayNKolayClient::buildClientRefCode('Opencart20', $this->session->data['order_id']),
            'amount'        => $amount,
            'currencyCode'  => $currencyNumber,
            'successUrl'    => $callbackUrl,
            'failUrl'       => $callbackUrl,
            'use3D'         => $this->config->get('nkolaypos_type') == '3D' ? 'true' : 'false',
            'platform'      => PayNKolayClient::platformTag('Opencart20', VERSION),
        ]);

        $this->load->language('payment/nkolaypos');

        $data['action']    = $client->getRedirectUrl();
        $data['form_data'] = $formData;

        return $this->load->view('default/template/payment/nkolaypos.tpl', $data);
    }

    public function callback()
    {
        $client   = $this->getClient();
        $postData = $this->request->post;

        if (!$client->verifyCallback($postData)) {
            $this->session->data['error'] = 'Hash dogrulama hatasi.';
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $orderId = PayNKolayClient::extractOrderId($postData['CLIENT_REFERENCE_CODE'] ?? '');

        if (!PayNKolayClient::is3D($postData)) {
            if (PayNKolayClient::isSuccess($postData)) {
                $this->confirmOrder($orderId);
                $this->response->redirect($this->url->link('checkout/success', '', true));
            } else {
                $this->session->data['error'] = $postData['RESPONSE_DATA'] ?? 'Odeme basarisiz.';
                $this->response->redirect($this->url->link('checkout/cart'));
            }
            return;
        }

        if (PayNKolayClient::isSuccess($postData)) {
            $result = $client->completePayment($postData['REFERENCE_CODE']);
            if (($result->RESPONSE_CODE ?? '') == PayNKolayClient::RESPONSE_SUCCESS) {
                $this->confirmOrder($orderId);
                $this->response->redirect($this->url->link('checkout/success', '', true));
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
        $this->model_checkout_order->addOrderHistory(
            $orderId,
            $this->config->get('nkolaypos_order_status_id')
        );
    }

    private function getClient(): PayNKolayClient
    {
        require_once(DIR_SYSTEM . 'library/paynkolay.php');
        return new PayNKolayClient([
            'sx'         => $this->config->get('nkolaypos_sx'),
            'secret_key' => $this->config->get('nkolaypos_secret'),
            'test_mode'  => $this->config->get('nkolaypos_mode') == '1',
        ]);
    }
}
