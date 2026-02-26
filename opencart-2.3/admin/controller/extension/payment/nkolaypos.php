<?php
if (!defined("VERSION")) exit;
class ControllerExtensionPaymentNkolayPos extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/nkolaypos');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('nkolaypos', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
		}
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('heading_title');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_save'] = $this->language->get('button_save');
		$data['entry_sx'] = $this->language->get('entry_sx');
		$data['entry_secret'] = $this->language->get('entry_secret');
		$data['entry_payment'] = $this->language->get('entry_payment');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['text_pay'] = $this->language->get('text_pay');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_card'] = $this->language->get('text_card');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['help_total'] = $this->language->get('help_total');
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
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/nkolaypos', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/nkolaypos', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);

		if (isset($this->request->post['nkolaypos_sx'])) {
			$data['nkolaypos_sx'] = $this->request->post['nkolaypos_sx'];
		} else {
			$data['nkolaypos_sx'] = $this->config->get('nkolaypos_sx');
		}

		if (isset($this->request->post['nkolaypos_secret'])) {
			$data['nkolaypos_secret'] = $this->request->post['nkolaypos_secret'];
		} else {
			$data['nkolaypos_secret'] = $this->config->get('nkolaypos_secret');
		}

		if (isset($this->request->post['nkolaypos_mode'])) {
			$data['nkolaypos_mode'] = $this->request->post['nkolaypos_mode'];
		} else {
			$data['nkolaypos_mode'] = $this->config->get('nkolaypos_mode');
		}

		if (isset($this->request->post['nkolaypos_type'])) {
			$data['nkolaypos_type'] = $this->request->post['nkolaypos_type'];
		} else {
			$data['nkolaypos_type'] = $this->config->get('nkolaypos_type');
		}

		if (isset($this->request->post['nkolaypos_total'])) {
			$data['nkolaypos_total'] = $this->request->post['nkolaypos_total'];
		} else {
			$data['nkolaypos_total'] = $this->config->get('nkolaypos_total');
		}

		if (isset($this->request->post['nkolaypos_order_status_id'])) {
			$data['nkolaypos_order_status_id'] = $this->request->post['nkolaypos_order_status_id'];
		} else {
			$data['nkolaypos_order_status_id'] = $this->config->get('nkolaypos_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['nkolaypos_geo_zone_id'])) {
			$data['nkolaypos_geo_zone_id'] = $this->request->post['nkolaypos_geo_zone_id'];
		} else {
			$data['nkolaypos_geo_zone_id'] = $this->config->get('nkolaypos_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['nkolaypos_status'])) {
			$data['nkolaypos_status'] = $this->request->post['nkolaypos_status'];
		} else {
			$data['nkolaypos_status'] = $this->config->get('nkolaypos_status');
		}

		if (isset($this->request->post['nkolaypos_sort_order'])) {
			$data['nkolaypos_sort_order'] = $this->request->post['nkolaypos_sort_order'];
		} else {
			$data['nkolaypos_sort_order'] = $this->config->get('nkolaypos_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/nkolaypos', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/nkolaypos')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['nkolaypos_sx']) {
			$this->error['sx'] = $this->language->get('error_sx');
		}

		if (!$this->request->post['nkolaypos_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}