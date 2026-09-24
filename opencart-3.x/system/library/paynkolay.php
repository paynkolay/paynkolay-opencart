<?php
/**
 * paynkolay Payment Gateway PHP SDK
 *
 * Single-file client for the paynkolay virtual POS API.
 * Uses hashDatav2 (SHA-512, pipe-separated) for all hash operations.
 *
 * @see https://paynkolay.com.tr/entegrasyon
 */
class PayNKolayClient
{
    /** Plugin release version; keep in sync with the GitHub release tag. */
    const PLUGIN_VERSION = '1.1.0';

    const PROD_BASE_URL = 'https://paynkolay.nkolayislem.com.tr';
    const TEST_BASE_URL = 'https://paynkolaytest.nkolayislem.com.tr';

    const RESPONSE_SUCCESS = '2';
    const RESPONSE_ERROR   = '0';

    /** Longest ECOMM_PLATFORM the gateway accepts; 121+ chars rejects the whole payment (AB0001). */
    const ECOMM_PLATFORM_MAX = 120;

    /**
     * ISO 4217 alpha => numeric codes sent as currencyCode. The gateway charges TRY
     * when currencyCode is omitted and rejects currencies the merchant isn't enabled for.
     */
    const CURRENCY_NUMBERS = [
        'TRY' => '949', 'USD' => '840', 'EUR' => '978', 'GBP' => '826', 'CHF' => '756',
        'JPY' => '392', 'CNY' => '156', 'RUB' => '643', 'AUD' => '036', 'CAD' => '124',
        'NZD' => '554', 'SEK' => '752', 'NOK' => '578', 'DKK' => '208', 'PLN' => '985',
        'CZK' => '203', 'HUF' => '348', 'RON' => '946', 'BGN' => '975', 'UAH' => '980',
        'GEL' => '981', 'AZN' => '944', 'KZT' => '398', 'SAR' => '682', 'AED' => '784',
        'QAR' => '634', 'KWD' => '414', 'BHD' => '048', 'OMR' => '512', 'JOD' => '400',
        'ILS' => '376', 'EGP' => '818', 'IQD' => '368', 'INR' => '356', 'KRW' => '410',
        'SGD' => '702', 'HKD' => '344', 'ZAR' => '710', 'BRL' => '986', 'MXN' => '484',
    ];


    private $sx;
    private $sxList;
    private $sxCancel;
    private $secretKey;
    private $testMode;

    public function __construct(array $config)
    {
        $this->sx        = $config['sx'] ?? '';
        $this->sxList    = $config['sx_list'] ?? '';
        $this->sxCancel  = $config['sx_cancel'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->testMode  = !empty($config['test_mode']);
    }

    // ─── URL helpers ─────────────────────────────────────────────────────────────

    public function getBaseUrl(): string
    {
        return $this->testMode ? self::TEST_BASE_URL : self::PROD_BASE_URL;
    }

    public function getRedirectUrl(): string
    {
        return $this->getBaseUrl() . '/Vpos';
    }

    // ─── Hash computation (hashDatav2 — SHA-512, pipe-separated) ─────────────────

    /**
     * Compute hashDatav2: base64(SHA-512(field1|field2|...|merchantSecretKey))
     *
     * @param array $fields Values to hash (secretKey is appended automatically)
     */
    public function computeHash(array $fields): string
    {
        $fields[] = $this->secretKey;
        $str = implode('|', $fields);
        return base64_encode(hash('sha512', $str, true));
    }

    // ─── Redirect flow (hosted payment page at /Vpos) ────────────────────────────

    /**
     * Build hidden form fields for redirect to paynkolay hosted payment page.
     *
     * Hash: sx|clientRefCode|amount|successUrl|failUrl|rnd|csCustomerKey
     *
     * @param array $params Required: clientRefCode, amount, successUrl, failUrl
     *                      Optional: use3D, platform, currencyCode, csCustomerKey
     * @return array Form field name => value pairs
     */
    public function buildRedirectFormData(array $params): array
    {
        $rnd = date('d.m.Y H:i:s');

        $hash = $this->computeHash([
            $this->sx,
            $params['clientRefCode'],
            self::formatAmount($params['amount']),
            $params['successUrl'],
            $params['failUrl'],
            $rnd,
            $params['csCustomerKey'] ?? '',
        ]);

        return [
            'sx'              => $this->sx,
            'clientRefCode'   => $params['clientRefCode'],
            'amount'          => self::formatAmount($params['amount']),
            'successUrl'      => $params['successUrl'],
            'failUrl'         => $params['failUrl'],
            'use3D'           => $params['use3D'] ?? 'true',
            'rnd'             => $rnd,
            'detail'          => $params['detail'] ?? 'false',
            'transactionType' => $params['transactionType'] ?? 'SALES',
            'hashDatav2'      => $hash,
            'currencyCode'    => $params['currencyCode'] ?? '',
            'ECOMM_PLATFORM'  => substr((string) ($params['platform'] ?? ''), 0, self::ECOMM_PLATFORM_MAX),
        ];
    }

    // ─── API flow (/Vpos/v1/Payment) ─────────────────────────────────────────────

    /**
     * Create a payment via the v1 API (merchant collects card data).
     *
     * Hash: sx|clientRefCode|amount|successUrl|failUrl|rnd|csCustomerKey
     */
    public function createPayment(array $params)
    {
        $rnd = date('d-m-Y H:i:s');

        $hash = $this->computeHash([
            $this->sx,
            $params['clientRefCode'],
            self::formatAmount($params['amount']),
            $params['successUrl'],
            $params['failUrl'],
            $rnd,
            $params['csCustomerKey'] ?? '',
        ]);

        $data = [
            'sx'              => $this->sx,
            'clientRefCode'   => $params['clientRefCode'],
            'amount'          => self::formatAmount($params['amount']),
            'successUrl'      => $params['successUrl'],
            'failUrl'         => $params['failUrl'],
            'cardNumber'      => $params['cardNumber'],
            'month'           => $params['month'],
            'year'            => $params['year'],
            'cvv'             => $params['cvv'],
            'cardHolderName'  => $params['cardHolderName'] ?? '',
            'installmentNo'   => $params['installmentNo'] ?? '1',
            'use3D'           => $params['use3D'] ?? 'true',
            'transactionType' => $params['transactionType'] ?? 'SALES',
            'rnd'             => $rnd,
            'hashDatav2'      => $hash,
            'environment'     => 'API',
            'ECOMM_PLATFORM'  => substr((string) ($params['platform'] ?? ''), 0, self::ECOMM_PLATFORM_MAX),
        ];

        if (isset($params['EncodedValue'])) {
            $data['EncodedValue'] = $params['EncodedValue'];
            $data['hosturl'] = $params['hosturl'] ?? '';
        }

        if (isset($params['currencyNumber'])) {
            $data['currencyNumber'] = $params['currencyNumber'];
        }

        $endpoint = isset($params['EncodedValue'])
            ? '/Vpos/Payment/Payment'
            : '/Vpos/v1/Payment';

        return $this->post($this->getBaseUrl() . $endpoint, $data);
    }

    /**
     * Complete a 3D Secure payment.
     */
    public function completePayment(string $referenceCode)
    {
        return $this->post(
            $this->getBaseUrl() . '/Vpos/v1/CompletePayment',
            [
                'sx'            => $this->sx,
                'referenceCode' => $referenceCode,
            ]
        );
    }

    /**
     * Get installment options for a card number and amount.
     */
    public function getInstallments(string $cardNumber, string $amount, bool $validateCard = false)
    {
        return $this->post(
            $this->getBaseUrl() . '/Vpos/Payment/PaymentInstallments',
            [
                'sx'          => $this->sx,
                'amount'      => self::formatAmount($amount),
                'cardNumber'  => $cardNumber,
                'iscardvalid' => $validateCard ? 'true' : 'false',
            ]
        );
    }

    // ─── Callback verification ───────────────────────────────────────────────────

    /**
     * Verify the hash from a paynkolay callback (POST to successUrl/failUrl).
     *
     * Hash: MERCHANT_NO|REFERENCE_CODE|AUTH_CODE|RESPONSE_CODE|USE_3D|RND|INSTALLMENT|AUTHORIZATION_AMOUNT
     */
    public function verifyCallback(array $post): bool
    {
        $computed = $this->computeHash([
            $post['MERCHANT_NO'] ?? '',
            $post['REFERENCE_CODE'] ?? '',
            $post['AUTH_CODE'] ?? '',
            $post['RESPONSE_CODE'] ?? '',
            $post['USE_3D'] ?? '',
            $post['RND'] ?? '',
            $post['INSTALLMENT'] ?? '',
            $post['AUTHORIZATION_AMOUNT'] ?? '',
        ]);

        $received = $post['hashDatav2'] ?? $post['hashData'] ?? '';
        return hash_equals($computed, $received);
    }

    /**
     * Check if callback response indicates success.
     */
    public static function isSuccess(array $post): bool
    {
        return ($post['RESPONSE_CODE'] ?? '') == self::RESPONSE_SUCCESS;
    }

    /**
     * Check if callback used 3D Secure.
     */
    public static function is3D(array $post): bool
    {
        return ($post['USE_3D'] ?? 'false') !== 'false';
    }

    // ─── Cancel / Refund ─────────────────────────────────────────────────────────

    /**
     * Cancel (same-day) or refund (past) a payment.
     *
     * Hash: sx|referenceCode|type|amount|trxDate
     *
     * @param array $params Required: referenceCode, type (cancel|refund), amount, trxDate (YYYY.MM.DD)
     */
    public function cancelOrRefund(array $params)
    {
        $hash = $this->computeHash([
            $this->sxCancel,
            $params['referenceCode'],
            $params['type'],
            self::formatAmount($params['amount']),
            $params['trxDate'],
        ]);

        return $this->post(
            $this->getBaseUrl() . '/Vpos/v1/CancelRefundPayment',
            [
                'sx'            => $this->sxCancel,
                'referenceCode' => $params['referenceCode'],
                'type'          => $params['type'],
                'amount'        => self::formatAmount($params['amount']),
                'trxDate'       => $params['trxDate'],
                'hashDatav2'    => $hash,
            ]
        );
    }

    // ─── Payment list ────────────────────────────────────────────────────────────

    /**
     * List payments in a date range.
     *
     * Hash: sx|startDate|endDate|clientRefCode
     */
    public function listPayments(string $startDate, string $endDate, string $clientRefCode = '')
    {
        $hash = $this->computeHash([
            $this->sxList,
            $startDate,
            $endDate,
            $clientRefCode,
        ]);

        $data = [
            'sx'           => $this->sxList,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'hashDatav2'   => $hash,
        ];

        if ($clientRefCode !== '') {
            $data['clientRefCode'] = $clientRefCode;
        }

        return $this->post($this->getBaseUrl() . '/Vpos/Payment/PaymentList', $data);
    }

    // ─── Client reference code helpers ───────────────────────────────────────────

    /**
     * Build a client reference code.
     * Format: Platform|OrderId|OP|UniqueId
     */
    public static function buildClientRefCode(string $platform, string $orderId): string
    {
        return $platform . '|' . $orderId . '|OP|' . uniqid();
    }

    /**
     * Extract the order ID from a client reference code.
     * "Opencart3x|42|OP|abc123" → "42"
     */
    public static function extractOrderId(string $clientRefCode): string
    {
        $parts = explode('|OP|', $clientRefCode);
        $beforeOp = $parts[0] ?? '';
        $pipePos = strpos($beforeOp, '|');
        if ($pipePos !== false) {
            return substr($beforeOp, $pipePos + 1);
        }
        return $beforeOp;
    }

    // ─── Cookie fix for 3D Secure redirects ──────────────────────────────────────

    /**
     * Set SameSite=None on session cookies so they survive 3D Secure bank redirects.
     * Call this before rendering the payment form.
     *
     * @param array $cookieNames Cookie name prefixes to fix (e.g., ['OCSESSID', 'PHPSESSID'])
     */
    public static function fixCookieSameSite(array $cookieNames = ['PHPSESSID', 'OCSESSID'])
    {
        foreach ($_COOKIE as $name => $value) {
            foreach ($cookieNames as $prefix) {
                if (stripos($name, $prefix) === 0) {
                    if (PHP_VERSION_ID >= 70300) {
                        setcookie($name, $value, [
                            'expires'  => time() + 86400,
                            'path'     => '/',
                            'domain'   => $_SERVER['SERVER_NAME'] ?? '',
                            'samesite' => 'None',
                            'secure'   => true,
                            'httponly' => true,
                        ]);
                    } else {
                        // PHP < 7.3 setcookie() has no samesite option (the array
                        // signature warns and sets nothing) — smuggle the attribute
                        // through the path parameter instead.
                        setcookie($name, $value, time() + 86400, '/; SameSite=None', $_SERVER['SERVER_NAME'] ?? '', true, true);
                    }
                }
            }
        }
    }

    // ─── Utilities ───────────────────────────────────────────────────────────────

    /**
     * Build the ECOMM_PLATFORM tag reported with each payment, e.g.
     * "Opencart4x;plugin=1.2.0;oc=4.0.2.3;php=8.2.29". Distro PHP_VERSION suffixes are dropped.
     */
    public static function platformTag(string $platform, string $hostVersion): string
    {
        $php = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        $tag = $platform . ';plugin=' . self::PLUGIN_VERSION . ';oc=' . $hostVersion . ';php=' . $php;
        return substr($tag, 0, self::ECOMM_PLATFORM_MAX);
    }

    /**
     * Map an ISO 4217 alpha code (e.g. an OpenCart currency code) to the numeric
     * currencyCode the gateway expects. Returns '' for codes not in CURRENCY_NUMBERS.
     */
    public static function currencyNumber(string $isoCode): string
    {
        $isoCode = strtoupper(trim($isoCode));
        if ($isoCode === 'TL') {
            $isoCode = 'TRY';
        }
        return self::CURRENCY_NUMBERS[$isoCode] ?? '';
    }

    /**
     * Format amount to exactly 2 decimal places.
     */
    public static function formatAmount(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    // ─── HTTP ────────────────────────────────────────────────────────────────────

    private function post(string $url, array $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return (object) [
                'RESPONSE_CODE' => self::RESPONSE_ERROR,
                'RESPONSE_DATA' => 'cURL error: ' . $error,
            ];
        }

        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return (object) [
                'RESPONSE_CODE' => self::RESPONSE_ERROR,
                'RESPONSE_DATA' => 'Invalid JSON response',
            ];
        }

        // Some endpoints return double-encoded JSON: {"result": "{...}"}
        if (isset($decoded->result) && is_string($decoded->result)) {
            $inner = json_decode($decoded->result);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $inner;
            }
        }

        return $decoded;
    }

    // ─── Getters ─────────────────────────────────────────────────────────────────

    public function getSx(): string        { return $this->sx; }
    public function getSxList(): string     { return $this->sxList; }
    public function getSxCancel(): string   { return $this->sxCancel; }
    public function getSecretKey(): string  { return $this->secretKey; }
    public function isTestMode(): bool      { return $this->testMode; }
}
