<?php
if (!defined("VERSION")) exit;

    class ControllerExtensionPaymentNkolayPos extends Controller
    {
        public function index()
        {
            $this->checkAndSetCookieSameSite();
            $store_id = $this->config->get('config_store_id');
            $language_id = $this->config->get('config_language_id');

            $data['button_confirm'] = $this->language->get('button_confirm');

            $route = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'extension/payment/nkolaypos/callback' AND `store_id` = '" . $store_id . "' AND `language_id` = '" . $language_id . "'");

            $this->load->model('checkout/order');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            /*
             * Eğer Form türü ödeme formu ise
             */
            if ($this->config->get('payment_nkolaypos_form_turu') == 1) {
                $url = "index.php?route=extension/payment/nkolaypos/d3Baslat";
            } else {
                $url = "https://paynkolay.nkolayislem.com.tr/Vpos";


                if ($this->config->get('payment_nkolaypos_mode') == 1) {
                    $url = "https://paynkolaytest.nkolayislem.com.tr/Vpos";
                }
            }

            $data['action'] = $url;
            $data['payment_nkolaypos_form_turu'] = $this->config->get('payment_nkolaypos_form_turu');

            $callBack           = $this->url->link('extension/payment/nkolaypos/callback', '', true);
            $failBack           = $this->url->link('extension/payment/nkolaypos/callback', '', true);
            $sx                 = $this->config->get('payment_nkolaypos_sx');
            $merchantSecretKey  = $this->config->get('payment_nkolaypos_secret');
            $successUrl         = $callBack;
            $failUrl            = $failBack;
            $amount             = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $clientRefCode      = 'Opencartv3|'.$this->session->data['order_id'] . "|OP|" . uniqid();
            $productId          = $this->config->get('payment_nkolaypos_payment');
            $use3D              = $this->config->get('payment_nkolaypos_type') == '3D' ? 'true' : 'false';
            $rnd                = date("d.m.Y H:i:s");
            $detail             = "false";
            $transactionType    = "SALES";

            $hashstr = $sx . $clientRefCode . $amount . $successUrl . $failUrl . $rnd . $merchantSecretKey;

            $hashData = base64_encode(pack('H*', sha1($hashstr)));

            $data['amount'] = $amount;
            $data['clientRefCode'] = $clientRefCode;
            $data['data'] = [
                'sx'            => $sx,
                'successUrl'    => $successUrl,
                'failUrl'       => $failUrl,
                'amount'        => $amount,
                'clientRefCode' => $clientRefCode,
                'productId'     => $productId,
                'use3D'         => $use3D,
                'rnd'           => $rnd,
                'detail'        => $detail,
                'transactionType' => $transactionType,
                'hashData'      => $hashData,
                'ECOMM_PLATFORM'=> 'Opencartv3'
            ];

            $base_url = explode("index.php", $_SERVER['HTTP_REFERER'])[0];
            $data['css1'] = $base_url . "catalog/view/javascript/nkolaypos/nkolaypos_form.css";
            $data['css2'] = $base_url . "catalog/view/javascript/nkolaypos/nkolaypos_icons.css";

            return $this->load->view('extension/payment/nkolaypos', $data);
        }

        public function callback()
        {

            $url = "https://paynkolay.nkolayislem.com.tr/Vpos/Payment/CompletePayment";
            if ($this->config->get('payment_nkolaypos_mode') == 1) {
                $url = "https://paynkolaytest.nkolayislem.com.tr/Vpos/Payment/CompletePayment";
            }


            $merchantSecretKey      = $this->config->get('payment_nkolaypos_secret');
            $REFERENCE_CODE         = $this->request->post['REFERENCE_CODE'];
            $RESPONSE_CODE          = $this->request->post['RESPONSE_CODE'];
            $USE_3D                 = $this->request->post['USE_3D'];
            $MERCHANT_NO            = $this->request->post['MERCHANT_NO'];
            $AUTH_CODE              = $this->request->post['AUTH_CODE'];
            $CLIENT_REFERENCE_CODE  = $this->request->post['CLIENT_REFERENCE_CODE'];
            $AUTHORIZATION_AMOUNT   = $this->request->post['AUTHORIZATION_AMOUNT'];
            $INSTALLMENT            = $this->request->post['INSTALLMENT'];
            $RND                    = $this->request->post['RND'];
            $hashData               = $this->request->post['hashData'];
            
            $order_id               = explode('|OP|', $CLIENT_REFERENCE_CODE);
            $order_id               = str_replace('Opencartv3|','', current($order_id));
           



            $hashtr = $MERCHANT_NO . $REFERENCE_CODE . $AUTH_CODE . $RESPONSE_CODE . $USE_3D . $RND . $INSTALLMENT . $AUTHORIZATION_AMOUNT . $merchantSecretKey;
            $hashDatauretilen = base64_encode(pack('H*', sha1($hashtr)));


            if ($this->request->post['USE_3D'] == 'false' && $this->request->post['hashData'] == $hashDatauretilen) {

                if ($this->request->post['RESPONSE_CODE'] == 2 && $this->request->post['hashData'] == $hashDatauretilen) {
                    $this->load->model('checkout/order');

                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_nkolaypos_order_status_id'));

                    $this->response->redirect($this->url->link('checkout/success', '', true));
                } else {
                    $this->session->data['error'] = $this->request->post['RESPONSE_DATA'];

                    $this->response->redirect($this->url->link('checkout/cart'));
                }
            } else {
                if ($this->request->post['RESPONSE_CODE'] == 2 && $this->request->post['hashData'] == $hashDatauretilen) {

                    $arrData = array(
                        "sx" => $this->config->get('payment_nkolaypos_sx'),
                        "referenceCode" => $REFERENCE_CODE
                    );
                    $result = $this->getCurl($url,$arrData);

                    if ($result->RESPONSE_CODE == 2) {
                        $this->load->model('checkout/order');
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_nkolaypos_order_status_id'));
                        $this->response->redirect($this->url->link('checkout/success', '', true));
                    } else {
                        $this->session->data['error'] = $result->RESPONSE_DATA;
                        $this->response->redirect($this->url->link('checkout/cart'));
                    }


                } else {
                    $this->session->data['error'] = $this->request->post['RESPONSE_DATA'];

                    $this->response->redirect($this->url->link('checkout/cart'));
                }
            }

        }


        public function ondalikEkle($total)
        {
            if (empty(explode(".", $total)[1])) {
                $total = $total . ".00";
            } else {
                if (strlen(explode(".", $total)[1]) == 1) {
                    $total = $total . "0";
                }
            }
            return $total;
        }

        public function getInstall()
        {
            $url = "https://paynkolay.nkolayislem.com.tr/Vpos/Payment/PaymentInstallments";
            if ($this->config->get('payment_nkolaypos_mode') == 1) {
                $url = "https://paynkolaytest.nkolayislem.com.tr/Vpos/Payment/PaymentInstallments";
            }
            ?>


            <div class="col wid100" id="tlist">
                <table class="table border rounded bg-white">
                    <tbody>
                    <?php
                        $sx = $this->config->get('payment_nkolaypos_sx');
                        $par = [
                            'sx' => $sx,
                            'amount' => $this->ondalikEkle($_REQUEST['amount']),
                            'cardNumber' => trim($_REQUEST['cardnumber']),
                            'hosturl' => $_SERVER['HTTP_HOST'],
                            'iscardvalid' => true,
                        ];
                        $sonuc = json_decode($this->curl($par, $url));

                        foreach ($sonuc->PAYMENT_BANK_LIST as $key => $taksitler) {
                            if ($taksitler->INSTALLMENT == 1) {
                                $text_value = "Tek Çekim";
                            } else {
                                $text_value = $taksitler->INSTALLMENT . " x " . $taksitler->INSTALLMENT_AMOUNT;
                            }
                            $total = $this->ondalikEkle($taksitler->AUTHORIZATION_AMOUNT);
                            ?>
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="form-check">
                                        <input
                                                type="radio" data-sayi="<?php echo $taksitler->INSTALLMENT ?>"
                                                id="tno<?php echo $taksitler->INSTALLMENT ?>"
                                                onclick="updateSubmitButtonAmount('<?php echo $total ?>')"
                                                class="form-check-input p-1"
                                                name="tno" <?php if ($taksitler->INSTALLMENT == 1) {
                                            echo 'checked';
                                        } ?> value="<?php echo $taksitler->INSTALLMENT ?>"
                                                tag="<?php echo $total ?>">
                                        <label class="form-check-label"
                                               for="tno<?php echo $taksitler->INSTALLMENT ?>"><strong><?php echo $text_value ?></strong></label>
                                    </div>
                                </td>
                                <td class="px-3 py-2" id="instalmentTd<?php echo $key ?>"><label
                                            for="tno1"><?php echo $total ?> ₺ </label></td>
                                <td class="px-3 py-2 text-primary"><strong></strong></td>
                            </tr>
                            <input type="hidden" name="tsx<?php echo $taksitler->INSTALLMENT ?>"
                                   value="<?php echo $taksitler->EncodedValue ?>">
                            <?php
                        } ?>
                    </tbody>
                </table>
            </div>

            <?php


        }


        /*
         * 3d ödemeyi başlatır
         */
        public function d3Baslat()
        {
            $url = "https://paynkolay.nkolayislem.com.tr/Vpos/Payment/Payment";
            if ($this->config->get('payment_nkolaypos_mode') == 1) {
                $url = "https://paynkolaytest.nkolayislem.com.tr/Vpos/Payment/Payment";
            }


            if ($this->config->get('payment_nkolaypos_type') == "Normal") {
                $use3D = "false";
            } else {
                $use3D = "true";
            }

            $sx             = $this->config->get('payment_nkolaypos_sx');
            $callBack       = $this->url->link('extension/payment/nkolaypos/d3Bitir', '', true);
            $callFail       = $this->url->link('extension/payment/nkolaypos/d3Bitir', '', true);
            $okUrl          = $callBack;
            $failUrl        = $callFail;
            $merchant_secret_key = $this->config->get('payment_nkolaypos_secret');
            $rnd            = time();
            $amount         = $_REQUEST['odenecek_tutar'];
            $clientRefCode  = 'Opencartv3|'.$this->session->data['order_id'] . "|OP|" . uniqid();

            $hashstr = $sx . $clientRefCode . $this->ondalikEkle($amount) . $okUrl . $failUrl . $rnd . $merchant_secret_key;
            $hashData = base64_encode(pack('H*', sha1($hashstr)));
            $vars = [
                'sx'                => $sx,
                'clientRefCode'     => $clientRefCode,
                'successUrl'        => $okUrl,
                'failUrl'           => $failUrl,
                'amount'            => $this->ondalikEkle($amount),
                'installmentNo'     => $_REQUEST['secilen_taksit'],
                'cardHolderName'     => $_REQUEST['cardHolderName'],
                'month'             => $_REQUEST['ay'],
                'year'              => $_REQUEST['yil'],
                'cvv'               => $_REQUEST['cvv'],
                'cardNumber'        => $_REQUEST['cardNumber'],
                'EncodedValue'      => $_REQUEST['tsx' . $_REQUEST['secilen_taksit']],
                'use3D'             => $use3D,
                'transactionType'   => 'SALES',
                'hosturl'           => "https://" . $_SERVER['HTTP_HOST'],
                'rnd'               => $rnd,
                'hashData'          => $hashData,
                'environment'       => 'API',
                'ECOMM_PLATFORM'    => 'Opencartv3'
            ];


            $result = json_decode($this->curl($vars, $url));

            $d3 = $this->config->get('payment_nkolaypos_type');
            if ($result->USE_3D == "false") {
                /*
                 * 2d ödemede yapılacak işlem
                 */

                if ($result->RESPONSE_CODE == 2) {
                    $this->load->model('checkout/order');
                    $this->model_checkout_order->addOrderHistory(current(array($this->session->data['order_id'])), $this->config->get('payment_nkolaypos_order_status_id'));
                    $this->response->redirect($this->url->link('checkout/success', '', true));
                } else {
                    $this->session->data['error'] = $result->RESPONSE_DATA;
                    $this->response->redirect($this->url->link('checkout/cart'));
                }

            } else {
                /*
                 * 3d ödeme'de yapılacak işlem
                 */
                if ($result->RESPONSE_CODE != 2) {
                    $error = $result->RESPONSE_DATA;
                    echo $error;
                    exit;
                } else {
                    print_r($result->BANK_REQUEST_MESSAGE);
                    exit;
                }
            }

        }

        /*
         * 3d ödemeyi tamamlar ve ödeme başarılı sayfasına yönlendirir
         */
        public function d3Bitir()
        {

            $merchantSecretKey = $this->config->get('payment_nkolaypos_secret');


            $hashstr = $_REQUEST['MERCHANT_NO'] . $_REQUEST['REFERENCE_CODE'] . $_REQUEST['AUTH_CODE'] . $_REQUEST['RESPONSE_CODE'] . $_REQUEST['USE_3D'] . $_REQUEST['RND'] . $_REQUEST['INSTALLMENT'] . $_REQUEST['AUTHORIZATION_AMOUNT'] . $merchantSecretKey;
            $hashDatauretilen = base64_encode(pack('H*', sha1($hashstr)));

            $CLIENT_REFERENCE_CODE = $this->request->post['CLIENT_REFERENCE_CODE'];
            $order_id = explode('|OP|', $CLIENT_REFERENCE_CODE);


            if ($hashDatauretilen == $_REQUEST['hashData']) {
                $url = "https://paynkolay.nkolayislem.com.tr/Vpos/v1/CompletePayment";
                if ($this->config->get('payment_nkolaypos_mode') == 1) {
                    $url = "https://paynkolaytest.nkolayislem.com.tr/Vpos/v1/CompletePayment";
                }
                $array = [
                    'sx' => $this->config->get('payment_nkolaypos_sx'),
                    'referenceCode' => $_REQUEST['REFERENCE_CODE'],
                ];
                $sonuc = json_decode($this->curl($array, $url));
                if ($sonuc->RESPONSE_CODE == 2) {
                    $this->load->model('checkout/order');

                    $this->model_checkout_order->addOrderHistory(current($order_id), $this->config->get('payment_nkolaypos_order_status_id'));
                    $this->response->redirect($this->url->link('checkout/success', '', true));
                } else {

                    $this->session->data['error'] = $sonuc->RESPONSE_DATA;
                    $this->response->redirect($this->url->link('checkout/cart'));
                }
                exit;
            }

        }

        public function curl($par, $url)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array());
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $par);
            $output = curl_exec($ch);
            curl_close($ch);
            return $output;
        }


        private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
        {

            if (PHP_VERSION_ID < 70300) {

                setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
            } else {
                setcookie($name, $value, [
                    'expires' => $expire,
                    'path' => $path,
                    'domain' => $domain,
                    'samesite' => 'None',
                    'secure' => $secure,
                    'httponly' => $httponly
                ]);


            }
        }

        private function checkAndSetCookieSameSite()
        {

            $checkCookieNames = array('PHPSESSID', 'OCSESSID', 'default', 'PrestaShop-', 'wp_woocommerce_session_');

            foreach ($_COOKIE as $cookieName => $value) {
                foreach ($checkCookieNames as $checkCookieName) {
                    if (stripos($cookieName, $checkCookieName) === 0) {
                        $this->setcookieSameSite($cookieName, $_COOKIE[$cookieName], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);
                    }
                }
            }
        }
        private function getCurl($url,$data)
        {
            $ch = curl_init();
                   
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);

            $result = json_decode($server_output);
            $result = json_decode($result->result);

            return $result;
        }
    }