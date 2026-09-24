<?php
if (!defined('VERSION')) exit;

class ModelExtensionPaymentNkolayPos extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/nkolaypos');

        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone
             WHERE geo_zone_id = '" . (int)$this->config->get('payment_nkolaypos_geo_zone_id') . "'
             AND country_id = '" . (int)$address['country_id'] . "'
             AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')"
        );

        if ($this->config->get('payment_nkolaypos_total') > 0 && $this->config->get('payment_nkolaypos_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_nkolaypos_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        // Hide the method when the cart currency can't be charged: not TRY/USD/EUR and no TRY to convert to.
        require_once(DIR_SYSTEM . 'library/paynkolay.php');
        $currency = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        if (PayNKolayClient::currencyNumber((string)$currency) === '' && !$this->currency->has('TRY')) {
            $status = false;
        }

        if (!$status) return [];

        return [
            'code'       => 'nkolaypos',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('payment_nkolaypos_sort_order'),
        ];
    }
}
