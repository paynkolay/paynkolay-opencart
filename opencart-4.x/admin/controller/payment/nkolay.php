<?php
namespace Opencart\Admin\Controller\Extension\Nkolay\Payment;

if (!defined('VERSION')) exit;

class Nkolay extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        $this->load->language('extension/nkolay/payment/nkolay');
        $this->document->setTitle($this->language->get('heading_title'));

        $token = 'user_token=' . $this->session->data['user_token'];
        $sep = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard', $token)],
            ['text' => $this->language->get('text_extension'),  'href' => $this->url->link('marketplace/extension', $token . '&type=payment')],
            ['text' => $this->language->get('heading_title'),   'href' => $this->url->link('extension/nkolay/payment/nkolay', $token)],
        ];

        $data['save'] = $this->url->link('extension/nkolay/payment/nkolay' . $sep . 'save', $token);
        $data['back'] = $this->url->link('marketplace/extension', $token . '&type=payment');

        $settings = [
            'payment_nkolaypos_sx',
            'payment_nkolaypos_secret',
            'payment_nkolaypos_mode',
            'payment_nkolaypos_type',
            'payment_nkolaypos_total',
            'payment_nkolaypos_order_status_id',
            'payment_nkolaypos_geo_zone_id',
            'payment_nkolay_status',
            'payment_nkolay_sort_order',
        ];

        foreach ($settings as $key) {
            $data[$key] = $this->config->get($key);
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/nkolay/payment/nkolay', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/nkolay/payment/nkolay');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/nkolay/payment/nkolay')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['payment_nkolaypos_sx'])) {
            $json['error'] = $this->language->get('error_sx');
        }

        if (empty($this->request->post['payment_nkolaypos_secret'])) {
            $json['error'] = $this->language->get('error_secret');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_nkolay', $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
