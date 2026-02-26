<?php
if (!defined("VERSION")) exit;
class ControllerExtensionPaymentNkolayPos extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/nkolaypos');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_nkolaypos', $this->request->post);

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
			'href' => $this->url->link('extension/payment/nkolaypos', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/nkolaypos', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

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

		if (isset($this->request->post['payment_nkolaypos_status'])) {
			$data['payment_nkolaypos_status'] = $this->request->post['payment_nkolaypos_status'];
		} else {
			$data['payment_nkolaypos_status'] = $this->config->get('payment_nkolaypos_status');
		}

		if (isset($this->request->post['payment_nkolaypos_sort_order'])) {
			$data['payment_nkolaypos_sort_order'] = $this->request->post['payment_nkolaypos_sort_order'];
		} else {
			$data['payment_nkolaypos_sort_order'] = $this->config->get('payment_nkolaypos_sort_order');
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

		if (!$this->request->post['payment_nkolaypos_sx']) {
			$this->error['sx'] = $this->language->get('error_sx');
		}

		if (!$this->request->post['payment_nkolaypos_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}