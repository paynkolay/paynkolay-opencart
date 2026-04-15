<?php
if (!defined('VERSION')) exit;

class ControllerExtensionPaymentNkolayPos extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->language('extension/payment/nkolaypos');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_nkolaypos', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=payment',
                true
            ));
        }

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_sx']      = $this->error['sx'] ?? '';
        $data['error_secret']  = $this->error['secret'] ?? '';

        $token = 'user_token=' . $this->session->data['user_token'];

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard', $token, true)],
            ['text' => $this->language->get('text_extension'),  'href' => $this->url->link('marketplace/extension', $token . '&type=payment', true)],
            ['text' => $this->language->get('heading_title'),   'href' => $this->url->link('extension/payment/nkolaypos', $token, true)],
        ];

        $data['action'] = $this->url->link('extension/payment/nkolaypos', $token, true);
        $data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=payment', true);

        $settings = [
            'payment_nkolaypos_sx', 'payment_nkolaypos_secret', 'payment_nkolaypos_mode',
            'payment_nkolaypos_type', 'payment_nkolaypos_total', 'payment_nkolaypos_order_status_id',
            'payment_nkolaypos_geo_zone_id', 'payment_nkolaypos_status', 'payment_nkolaypos_sort_order',
        ];
        $defaults = [
            'payment_nkolaypos_sx'     => '118591467|bScbGDYCtPf7SS1N6PQ6/+58rFhW1WpsWINqvkJFaJlu6bMH2tgPKDQtjeA5vClpzJP24uA0vx7OX53cP3SgUspa4EvYix+1C3aXe++8glUvu9Oyyj3v300p5NP7ro/9K57Zcw==',
            'payment_nkolaypos_secret' => '_YckdxUbv4vrnMUZ6VQsr',
            'payment_nkolaypos_mode'   => '1',
        ];
        foreach ($settings as $key) {
            $data[$key] = $this->request->post[$key] ?? $this->config->get($key) ?? ($defaults[$key] ?? '');
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/nkolaypos', $data));
    }

    protected function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/nkolaypos')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (empty($this->request->post['payment_nkolaypos_sx'])) {
            $this->error['sx'] = $this->language->get('error_sx');
        }
        if (empty($this->request->post['payment_nkolaypos_secret'])) {
            $this->error['secret'] = $this->language->get('error_secret');
        }
        return empty($this->error);
    }
}
