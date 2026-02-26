<?php
if (!defined("VERSION")) exit;
class ControllerExtensionPaymentNkolayPos extends Controller {
	public function index() {
		$this->checkAndSetCookieSameSite();
		

		$route = $this->db->query("SELECT * FROM `" . DB_PREFIX . "url_alias` WHERE `query` = 'extension/payment/nkolaypos/callback'");

		// if(!$route->num_rows){
		// 	$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'extension/payment/nkolaypos/callback', keyword = 'nkolay-back-4875875454545'");
		// }

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$url = "https://paynkolay.nkolayislem.com.tr/Vpos";
		

		if($this->config->get('nkolaypos_mode') == 1){
			$url = "https://paynkolaytest.nkolayislem.com.tr/VposNew";
		}

		$data['action'] = $url;
		$callback = $this->url->link('extension/payment/nkolaypos/callback', '', true);

		$callback = str_replace('index.php?route=', '', $callback);
		$sx = $this->config->get('nkolaypos_sx');
		$merchantSecretKey = $this->config->get('nkolaypos_secret');
		$successUrl = $callback;
		$failUrl = $callback;
		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$clientRefCode = $this->session->data['order_id'] . "|OP|" . uniqid();
		$use3D = $this->config->get('nkolaypos_type') == '3D' ? 'true' : 'false'; 
		$rnd = date("d.m.Y H:i:s");
		$detail = "false";
		$transactionType = "SALES";
	
		$hashstr = $sx . $clientRefCode . $amount . $successUrl . $failUrl . $rnd . $merchantSecretKey; 
		
		$hashData = base64_encode(pack('H*',sha1($hashstr)));

		$data['data'] = [
			'sx' => $sx,
			'successUrl' => $successUrl,
			'failUrl' => $failUrl,
			'amount' => $amount,
			'clientRefCode' => $clientRefCode,

			'use3D' => $use3D,
			'rnd' => $rnd,

			'detail' => $detail,
			'transactionType' => $transactionType,
			'hashData' => $hashData,
		];



		return $this->load->view('extension/payment/nkolaypos', $data);
	}

	public function callback() {

		$url = "https://paynkolay.nkolayislem.com.tr/Vpos/Payment/CompletePayment";

		if($this->config->get('nkolaypos_mode') == 1){
			$url = "https://paynkolaytest.nkolayislem.com.tr/VposNew/Payment/CompletePayment";
		}


		$merchantSecretKey = $this->config->get('nkolaypos_secret');

		$REFERENCE_CODE = $this->request->post['REFERENCE_CODE'];
		$RESPONSE_CODE = $this->request->post['RESPONSE_CODE'];
		$USE_3D = $this->request->post['USE_3D'];
		$MERCHANT_NO = $this->request->post['MERCHANT_NO'];
		$AUTH_CODE = $this->request->post['AUTH_CODE'];
		$CLIENT_REFERENCE_CODE = $this->request->post['CLIENT_REFERENCE_CODE'];
		$AUTHORIZATION_AMOUNT = $this->request->post['AUTHORIZATION_AMOUNT'];
		$INSTALLMENT = $this->request->post['INSTALLMENT'];
		$RND = $this->request->post['RND'];
		$hashData = $this->request->post['hashData'];
		$merchantSecretKey;
		$order_id = explode('|OP|', $CLIENT_REFERENCE_CODE);

		$hashtr = $MERCHANT_NO . $REFERENCE_CODE . $AUTH_CODE . $RESPONSE_CODE . $USE_3D . $RND . $INSTALLMENT . $AUTHORIZATION_AMOUNT . $merchantSecretKey ;
		$hashDatauretilen = base64_encode(pack('H*',sha1($hashtr)));

		if($this->request->post['USE_3D'] == 'false' && $this->request->post['hashData'] == $hashDatauretilen){
			if($this->request->post['RESPONSE_CODE'] == 2 && $this->request->post['hashData'] == $hashDatauretilen){
				$this->load->model('checkout/order');
	
				$this->model_checkout_order->addOrderHistory(current($order_id), $this->config->get('nkolaypos_order_status_id'));
				$this->response->redirect($this->url->link('checkout/success', '', true));
			} else {
				$this->session->data['error'] = $this->request->post['RESPONSE_DATA'];
	
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		} else {
			if($this->request->post['RESPONSE_CODE'] == 2 && $this->request->post['hashData'] == $hashDatauretilen){
				$ch = curl_init(); 
				$arrData = array( 
					"sx" => $this->config->get('nkolaypos_sx'), 
					"referenceCode" => $REFERENCE_CODE 
				); 

				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_POST, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, $arrData); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				$server_output = curl_exec($ch); 

				$result = json_decode($server_output); 

	
				$result = json_decode($result->result);

	
				if($result->RESPONSE_CODE == 2){
					$this->load->model('checkout/order');
	
					$this->model_checkout_order->addOrderHistory(current($order_id), $this->config->get('nkolaypos_order_status_id'));
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

	private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly) {

        if (PHP_VERSION_ID < 70300) {

            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        }
        else {
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

    private function checkAndSetCookieSameSite(){

        $checkCookieNames = array('PHPSESSID','OCSESSID','default','PrestaShop-','wp_woocommerce_session_');

        foreach ($_COOKIE as $cookieName => $value) {
            foreach ($checkCookieNames as $checkCookieName){
                if (stripos($cookieName,$checkCookieName) === 0) {
                    $this->setcookieSameSite($cookieName,$_COOKIE[$cookieName], time() + 86400, "/", $_SERVER['SERVER_NAME'],true, true);
                }
            }
        }
    }
}