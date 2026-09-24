<?php
namespace Opencart\Catalog\Model\Extension\Nkolay\Payment;

if (!defined('VERSION')) exit;

class Nkolay extends \Opencart\System\Engine\Model
{
    public function getMethods(array $address = []): array
    {
        $this->load->language('extension/nkolay/payment/nkolay');

        if ($this->config->get('payment_nkolaypos_total') > 0
            && $this->config->get('payment_nkolaypos_total') > $this->cart->getTotal()) {
            return [];
        }

        if (!$this->config->get('config_checkout_payment_address')) {
            $status = true;
        } elseif (!$this->config->get('payment_nkolaypos_geo_zone_id')) {
            $status = true;
        } else {
            $query = $this->db->query(
                "SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
                 WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_nkolaypos_geo_zone_id') . "'
                 AND `country_id` = '" . (int)($address['country_id'] ?? 0) . "'
                 AND (`zone_id` = '" . (int)($address['zone_id'] ?? 0) . "' OR `zone_id` = '0')"
            );
            $status = $query->num_rows > 0;
        }

        // Hide the method when the cart currency can't be charged: not TRY/USD/EUR and no TRY to convert to.
        require_once(DIR_EXTENSION . 'nkolay/system/library/paynkolay.php');
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        if (\PayNKolayClient::currencyNumber((string)$currency) === '' && !$this->currency->has('TRY')) {
            $status = false;
        }

        if (!$status) {
            return [];
        }

        return [
            'code'       => 'nkolay',
            'name'       => $this->language->get('text_title'),
            'option'     => [
                'nkolay' => [
                    'code' => 'nkolay.nkolay',
                    'name' => $this->language->get('text_title'),
                ],
            ],
            'sort_order' => $this->config->get('payment_nkolay_sort_order'),
        ];
    }
}
