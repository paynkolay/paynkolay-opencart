<?php
if (!defined('VERSION')) exit;

class ModelPaymentNkolayPos extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('payment/nkolaypos');

        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone
             WHERE geo_zone_id = '" . (int)$this->config->get('nkolaypos_geo_zone_id') . "'
             AND country_id = '" . (int)$address['country_id'] . "'
             AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')"
        );

        if ($this->config->get('nkolaypos_total') > 0 && $this->config->get('nkolaypos_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('nkolaypos_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if (!$status) return [];

        return [
            'code'       => 'nkolaypos',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('nkolaypos_sort_order'),
        ];
    }
}
