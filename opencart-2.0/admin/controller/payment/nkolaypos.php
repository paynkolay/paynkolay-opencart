<?php
if (!defined('VERSION')) exit;

class ControllerPaymentNkolayPos extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->language('payment/nkolaypos');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('nkolaypos', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(
                'payment/nkolaypos',
                'token=' . $this->session->data['token'],
                'SSL'
            ));
        }

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_sx']      = $this->error['sx'] ?? '';
        $data['error_secret']  = $this->error['secret'] ?? '';

        $token = 'token=' . $this->session->data['token'];

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard', $token, true)],
            ['text' => $this->language->get('text_extension'),  'href' => $this->url->link('extension/payment', $token, true)],
            ['text' => $this->language->get('heading_title'),   'href' => $this->url->link('payment/nkolaypos', $token, true)],
        ];

        $data['action'] = $this->url->link('payment/nkolaypos', $token, 'SSL');
        $data['cancel'] = $this->url->link('extension/payment', $token, true);

        $settings = [
            'nkolaypos_sx', 'nkolaypos_secret', 'nkolaypos_mode',
            'nkolaypos_type', 'nkolaypos_total', 'nkolaypos_order_status_id',
            'nkolaypos_geo_zone_id', 'nkolaypos_status', 'nkolaypos_sort_order',
        ];
        foreach ($settings as $key) {
            $data[$key] = $this->request->post[$key] ?? $this->config->get($key);
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/nkolaypos.tpl', $data));
    }

    protected function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'payment/nkolaypos')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (empty($this->request->post['nkolaypos_sx'])) {
            $this->error['sx'] = $this->language->get('error_sx');
        }
        if (empty($this->request->post['nkolaypos_secret'])) {
            $this->error['secret'] = $this->language->get('error_secret');
        }
        return empty($this->error);
    }
}
