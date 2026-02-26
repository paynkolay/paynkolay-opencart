<?php
namespace Opencart\Catalog\Model\Extension\Nkolay\Payment;
if (!defined("VERSION")) exit;class Nkolay extends \Opencart\System\Engine\Model {
	public function getMethod(array $address):array {
		$this->load->language('extension/nkolay/payment/nkolay');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_nkolaypos_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		
		if (!$this->config->get('payment_nkolaypos_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'nkolay',
				'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('payment_nkolaypos_sort_order')
			);
		}

		return $method_data;
	}
	public function getMethods(array $address = []):array {
		$this->load->language('extension/nkolay/payment/nkolay');

		if (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_nkolaypos_geo_zone_id')) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_nkolaypos_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}
		
		$method_data = [];

		if ($status) {
			$option_data['nkolay'] = [
				'code' => 'nkolay.nkolay',
				'name' => $this->language->get('text_title')
			];

			$method_data = [
				'code'       => 'nkolay',
				'name'       => $this->language->get('text_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_nkolay_sort_order')
			];
		
		}

		return $method_data;
	}
}