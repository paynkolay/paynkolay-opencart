<?php
namespace Opencart\Admin\Controller\Extension\Nkolay\Payment;
if (!defined("VERSION")) exit;
class Nkolay extends \Opencart\System\Engine\Controller {

	private $error = [];
	private $separator = '';
	
	public function __construct($registry) {
        parent::__construct($registry);

		if (VERSION >= '4.0.2.0') {
			$this->separator = '.';
		} else {
			$this->separator = '|';
		}
    }


	public function index() {
		$this->load->language('extension/nkolay/payment/nkolay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_nkolay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['sx'])) {
			$data['error_sx'] = $this->error['sx'];
		} else {
			$data['error_sx'] = '';
		}

		if (isset($this->error['secret'])) {
			$data['error_secret'] = $this->error['secret'];
		} else {
			$data['error_secret'] = '';
		}

		if (isset($this->error['type'])) {
			$data['error_type'] = $this->error['type'];
		} else {
			$data['error_type'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/nkolay/payment/nkolay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['save'] = $this->url->link('extension/nkolay/payment/nkolay' . $this->separator . 'save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		if (isset($this->request->post['payment_nkolaypos_sx'])) {
			$data['payment_nkolaypos_sx'] = $this->request->post['payment_nkolaypos_sx'];
		} else {
			$data['payment_nkolaypos_sx'] = $this->config->get('payment_nkolaypos_sx');
		}

        if (isset($this->request->post['payment_nkolaypos_form_turu'])) {
            $data['payment_nkolaypos_form_turu'] = $this->request->post['payment_nkolaypos_form_turu'];
        } else {
            $data['payment_nkolaypos_form_turu'] = $this->config->get('payment_nkolaypos_form_turu');
        }

        if (isset($this->request->post['payment_nkolaypos_secret'])) {
            $data['payment_nkolaypos_secret'] = $this->request->post['payment_nkolaypos_secret'];
        } else {
            $data['payment_nkolaypos_secret'] = $this->config->get('payment_nkolaypos_secret');
        }

		if (isset($this->request->post['payment_nkolaypos_mode'])) {
			$data['payment_nkolaypos_mode'] = $this->request->post['payment_nkolaypos_mode'];
		} else {
			$data['payment_nkolaypos_mode'] = $this->config->get('payment_nkolaypos_mode');
		}

		if (isset($this->request->post['payment_nkolaypos_type'])) {
			$data['payment_nkolaypos_type'] = $this->request->post['payment_nkolaypos_type'];
		} else {
			$data['payment_nkolaypos_type'] = $this->config->get('payment_nkolaypos_type');
		}

		if (isset($this->request->post['payment_nkolaypos_total'])) {
			$data['payment_nkolaypos_total'] = $this->request->post['payment_nkolaypos_total'];
		} else {
			$data['payment_nkolaypos_total'] = $this->config->get('payment_nkolaypos_total');
		}

		if (isset($this->request->post['payment_nkolaypos_order_status_id'])) {
			$data['payment_nkolaypos_order_status_id'] = $this->request->post['payment_nkolaypos_order_status_id'];
		} else {
			$data['payment_nkolaypos_order_status_id'] = $this->config->get('payment_nkolaypos_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_nkolaypos_geo_zone_id'])) {
			$data['payment_nkolaypos_geo_zone_id'] = $this->request->post['payment_nkolaypos_geo_zone_id'];
		} else {
			$data['payment_nkolaypos_geo_zone_id'] = $this->config->get('payment_nkolaypos_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_nkolay_status'])) {
			$data['payment_nkolay_status'] = $this->request->post['payment_nkolay_status'];
		} else {
			$data['payment_nkolay_status'] = $this->config->get('payment_nkolay_status');
		}

		if (isset($this->request->post['payment_nkolay_sort_order'])) {
			$data['payment_nkolay_sort_order'] = $this->request->post['payment_nkolay_sort_order'];
		} else {
			$data['payment_nkolay_sort_order'] = $this->config->get('payment_nkolay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/nkolay/payment/nkolay', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/nkolay/payment/nkolay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_nkolaypos_sx']) {
			$this->error['sx'] = $this->language->get('error_sx');
		}

		if (!$this->request->post['payment_nkolaypos_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}