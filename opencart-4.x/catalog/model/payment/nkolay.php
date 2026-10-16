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

        // Hide the method when the cart currency has no ISO 4217 numeric code to send.
        require_once(DIR_EXTENSION . 'nkolay/system/library/paynkolay.php');
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        if (\PayNKolayClient::currencyNumber((string)$currency) === '') {
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

    // ─── Payment attempts (server-side record of what was sent to the gateway) ──
    //
    // One row per payment form rendered. The callback's CLIENT_REFERENCE_CODE is
    // unsigned, so it is only used to find the row; the order id, expected amount
    // and currency come from here. reference_code (the gateway's signed, unique
    // REFERENCE_CODE) is UNIQUE so one gateway transaction can confirm one order only.
    // Status: pending -> processing (reference claimed) -> paid | failed.

    public function ensureTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nkolaypos_payment` (
            `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `client_ref_code` VARCHAR(128) NOT NULL,
            `amount_minor` BIGINT NOT NULL,
            `currency_code` VARCHAR(3) NOT NULL,
            `reference_code` VARCHAR(64) DEFAULT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`payment_id`),
            UNIQUE KEY `client_ref_code` (`client_ref_code`),
            UNIQUE KEY `reference_code` (`reference_code`),
            KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='paynkolay payment attempts: expected amount/currency per clientRefCode, gateway REFERENCE_CODE single-use'");
    }

    public function addPayment($orderId, $clientRefCode, $amountMinor, $currencyCode)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "nkolaypos_payment` SET
            `order_id` = '" . (int)$orderId . "',
            `client_ref_code` = '" . $this->db->escape($clientRefCode) . "',
            `amount_minor` = '" . (int)$amountMinor . "',
            `currency_code` = '" . $this->db->escape($currencyCode) . "',
            `status` = 'pending',
            `date_added` = NOW(),
            `date_modified` = NOW()");
    }

    public function getPaymentByClientRef($clientRefCode)
    {
        if ($clientRefCode === '' || strlen($clientRefCode) > 128) {
            return array();
        }
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "nkolaypos_payment` WHERE `client_ref_code` = '" . $this->db->escape($clientRefCode) . "' LIMIT 1");

        return $query->row;
    }

    public function getPaymentByReference($referenceCode)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "nkolaypos_payment` WHERE `reference_code` = '" . $this->db->escape($referenceCode) . "' LIMIT 1");

        return $query->row;
    }

    /**
     * Atomically bind a gateway REFERENCE_CODE to a pending attempt.
     * Returns false if the attempt was already claimed (concurrent or replayed callback).
     */
    public function claimPayment($paymentId, $referenceCode)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "nkolaypos_payment` SET
            `reference_code` = '" . $this->db->escape($referenceCode) . "',
            `status` = 'processing',
            `date_modified` = NOW()
            WHERE `payment_id` = '" . (int)$paymentId . "' AND `status` = 'pending' AND `reference_code` IS NULL");

        return $this->db->countAffected() == 1;
    }

    public function setPaymentStatus($paymentId, $status)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "nkolaypos_payment` SET
            `status` = '" . $this->db->escape($status) . "',
            `date_modified` = NOW()
            WHERE `payment_id` = '" . (int)$paymentId . "'");
    }
}
