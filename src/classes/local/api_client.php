<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Ifthenpay API client for Moodle 4.3+.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_ifthenpay\local;

use moodle_exception;

/**
 * Notes:
 * - Performs remote validation of the Backoffice Key during construction.
 * - Input format validation happens outside this class (e.g., admin settings).
 * - Methods do not accept a backoffice key parameter; the validated key is stored.
 */
final class api_client
{
    // Public API (web) base and endpoints.
    /** @var string Public API base URL. */
    public const BASE_API_PUBLIC = 'https://api.ifthenpay.com';
    /** @var string Endpoint to list available payment methods. */
    public const ENDPOINT_AVAILABLE_METHODS = '/gateway/methods/available';
    /** @var string Endpoint to create Pay-by-Link. */
    public const ENDPOINT_PAY_BY_LINK = '/gateway/pinpay';
    /** @var string Endpoint to fetch transaction status. */
    public const ENDPOINT_TRANSACTION_STATUS = '/gateway/transaction/status';
    /** @var string Endpoint to get gateway details. */
    public const ENDPOINT_GATEWAY_GET = '/gateway/get';
    /** @var string Endpoint to activate callback URL. */
    public const ENDPOINT_CALLBACK_ACTIVATION = '/endpoint/callback/activation';

    // Entities/subentities (single URL).
    /** @var string Service URL for entities/subentities JSON. */
    public const ENTITIES_SUBENTIDADES_URL = 'https://ifthenpay.com/IfmbWS/ifmbws.asmx/getEntidadeSubentidadeJsonV2';

    // Mobile gateway base and endpoints.
    /** @var string Mobile API base URL. */
    public const BASE_API_MOBILE = 'https://ifthenpay.com/IfmbWS/ifthenpaymobile.asmx';
    /** @var string Endpoint to retrieve gateway keys. */
    public const ENDPOINT_GATEWAY_KEYS = '/GetGatewayKeys';
    /** @var string Endpoint to list accounts by gateway key. */
    public const ENDPOINT_ACCOUNTS_BY_GATEWAY = '/GetAccountsByGatewayKey';

    /** @var string Backoffice Key (validated). */
    protected string $backofficekey;

    /** @var int Request timeout (seconds). */
    protected int $timeout;

    /**
     * Constructor.
     *
     * @param string $backofficekey Backoffice Key (format already validated).
     * @param int $timeout Timeout in seconds (default 10).
     * @throws moodle_exception If remote validation fails or on transport/JSON errors.
     */
    public function __construct(string $backofficekey, int $timeout = 10) {
        $backofficekey = trim($backofficekey);
        if ($backofficekey === '') {
            // This class requires a configured key.
            throw new moodle_exception('api:nobackofficekey_error', 'paygw_ifthenpay');
        }
        $this->timeout = max(1, $timeout);

        // 1) Remote validation (business logic; format checks live elsewhere).
        if (!$this->remote_validate_backoffice_key($backofficekey)) {
            throw new moodle_exception('error_invalid_backoffice_key', 'paygw_ifthenpay');
        }

        // 2) Persist the validated key.
        $this->backofficekey = $backofficekey;
    }

    /**
     * Set a new timeout (seconds). Does not re-validate the key.
     *
     * @param int $seconds Timeout value.
     * @return self
     */
    public function with_timeout(int $seconds): self {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    /**
     * Get the validated Backoffice Key.
     *
     * @return string
     */
    public function get_backoffice_key(): string {
        return $this->backofficekey;
    }

    /* =========================
     * Business operations
     * ========================= */

    /**
     * Get globally available payment methods.
     *
     * @return array Decoded response or empty array on non-array payloads.
     * @throws moodle_exception On transport/JSON errors.
     */
    public function get_available_payment_methods(): array {
        $url = self::BASE_API_PUBLIC . self::ENDPOINT_AVAILABLE_METHODS;
        $data = $this->get_json($url);
        return is_array($data) ? $data : [];
    }

    /**
     * Get Gateway Keys for the validated Backoffice Key (Moodle context).
     *
     * @return array Decoded response or empty array on non-array payloads.
     * @throws moodle_exception On transport/JSON errors.
     */
    public function get_gateway_keys(): array {
        $url = self::BASE_API_PUBLIC . self::ENDPOINT_GATEWAY_GET
            . '?boKey=' . rawurlencode($this->backofficekey)
            . '&type=Moodle';
        $data = $this->get_json($url);
        return is_array($data) ? $data : [];
    }

    /**
     * Get payment accounts bound to a Gateway Key.
     *
     * @param string $gatewaykey Gateway Key.
     * @return array Decoded response or empty array on non-array payloads.
     * @throws moodle_exception On transport/JSON errors.
     */
    public function get_payment_accounts_by_gateway(string $gatewaykey): array {
        $url = self::BASE_API_MOBILE . self::ENDPOINT_ACCOUNTS_BY_GATEWAY
            . '?backofficekey=' . rawurlencode($this->backofficekey)
            . '&gatewayKey=' . rawurlencode($gatewaykey);
        $data = $this->get_json($url);
        return is_array($data) ? $data : [];
    }

    /**
     * Create a Pay-by-Link.
     *
     * @param string $gatewaykey Gateway Key.
     * @param array $payload Request payload.
     * @return object Object with properties: pin_code, pinpay_url, redirect_url.
     * @throws moodle_exception On transport/JSON errors or invalid response shape.
     */
    public function create_pay_by_link(string $gatewaykey, array $payload): object {
        $url = rtrim(self::BASE_API_PUBLIC, '/') . self::ENDPOINT_PAY_BY_LINK
            . '/' . rawurlencode($gatewaykey);

        $resp = $this->post_json($url, $payload);

        if (empty($resp['PinCode']) || empty($resp['PinpayUrl']) || empty($resp['RedirectUrl'])) {
            throw new moodle_exception('api:error_invalid_pbl_response', 'paygw_ifthenpay');
        }

        return (object)[
            'pin_code'     => $resp['PinCode'],
            'pinpay_url'   => $resp['PinpayUrl'],
            'redirect_url' => $resp['RedirectUrl'],
        ];
    }

    /**
     * Activate the callback for a gateway context.
     *
     * Constructs payload internally:
     *  - apKey: base64-encoded gateway key
     *  - chave: raw gateway key
     *  - urlCb: callback URL template with placeholders
     *
     * Expects HTTP 200 with plain text "OK" (success) or "INVALID" (failure).
     *
     * @param string $gatewaykey The Ifthenpay Gateway Key.
     * @return bool True on "OK", false otherwise.
     * @throws \moodle_exception On transport or JSON errors (via post_json()).
     */
    public function activate_callback_by_gateway_context(string $gatewaykey): bool {
        $url = rtrim(self::BASE_API_PUBLIC, '/') . self::ENDPOINT_CALLBACK_ACTIVATION . '/?cms=moodle';

        $payload = [
            'apKey' => base64_encode($gatewaykey),
            'chave' => $gatewaykey,
            'urlCb' => (new \moodle_url('/payment/gateway/ifthenpay/webhook.php'))->out(false) .
                '?amount=[AMOUNT]&reference=[ORDER_ID]&apk=[ANTI_PHISHING_KEY]',
        ];

        // Encodes JSON and decodes response; returns decoded body (array|string).
        $response = $this->post_json($url, $payload);

        return trim((string)$response) === 'OK';
    }

    /**
     * Retrieve the current status of a transaction.
     *
     * Contract:
     * - API always returns HTTP 200.
     * - Response body is a JSON boolean:
     *     true  = payment validated successfully
     *     false = payment failed / not validated
     *
     * @param string $txid Transaction ID.
     * @return bool True if validated successfully, false otherwise.
     * @throws moodle_exception On transport/JSON errors.
     */
    public function get_transaction_status(string $txid): bool {
        $url = rtrim(self::BASE_API_PUBLIC, '/') . '/'
            . ltrim(self::ENDPOINT_TRANSACTION_STATUS, '/')
            . '?transactionId=' . rawurlencode($txid);

        $response = $this->get_json($url);
        if (!is_bool($response)) {
            throw new \moodle_exception(
                'api:error_invalid_json_get',
                'paygw_ifthenpay',
                '',
                'transaction status not boolean'
            );
        }

        // Only literal true indicates success (defensive cast).
        return $response === true;
    }

    /* =========================
     * Remote validation (private)
     * ========================= */

    /**
     * Validate that the Backoffice Key has at least one Entity + SubEntity.
     *
     * @param string $key Backoffice Key.
     * @return bool True if recognized, false otherwise.
     * @throws moodle_exception On transport/JSON errors.
     */
    private function remote_validate_backoffice_key(string $key): bool {
        $url  = self::ENTITIES_SUBENTIDADES_URL . '?chavebackoffice=' . rawurlencode($key);
        $data = $this->get_json($url);

        if (!is_array($data) || !$data) {
            return false;
        }

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $ent = isset($item['Entidade']) ? trim((string)$item['Entidade']) : '';
            $subs = isset($item['SubEntidade']) && is_array($item['SubEntidade'])
                ? array_filter($item['SubEntidade'], static fn($s) => trim((string)$s) !== '')
                : [];

            if ($ent !== '' && !empty($subs)) {
                return true;
            }
        }
        return false;
    }

    /* =========================
     * HTTP helpers (\curl)
     * ========================= */

    /**
     * GET and decode JSON.
     *
     * @param string $url URL.
     * @return mixed Decoded JSON (array|bool|null|scalar).
     * @throws moodle_exception On transport errors or invalid JSON.
     */
    protected function get_json(string $url) {
        [$code, $body] = $this->request('GET', $url, null, $this->json_headers(false));
        $decoded = json_decode($body, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new moodle_exception('api:error_invalid_json_get', 'paygw_ifthenpay', '', json_last_error_msg());
        }
        return $decoded;
    }

    /**
     * POST JSON payload and decode JSON response, or fallback when not JSON.
     *
     * For endpoints expected to return raw "OK"/"INVALID" rather than JSON array,
     * this method handles both array and string responses gracefully.
     *
     * @param string $url     Endpoint URL.
     * @param array  $payload Payload to be JSON-encoded.
     * @return array|string   Decoded array if JSON, else raw string body.
     * @throws moodle_exception On encoding or transport errors.
     */
    protected function post_json(string $url, array $payload) {
        $json = json_encode($payload);
        if ($json === false) {
            throw new moodle_exception('api:error_invalid_json_post', 'paygw_ifthenpay');
        }

        [$code, $body] = $this->request('POST', $url, $json, $this->json_headers(true));
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Not JSON array — return raw string for textual endpoints.
        return $body;
    }

    /**
     * Execute HTTP request using Moodle's \curl wrapper.
     *
     * @param string $method HTTP method ('GET' or 'POST').
     * @param string $url URL.
     * @param string|null $rawbody Raw body for POST requests.
     * @param array $headers Request headers (Name => Value).
     * @return array{int,string} Tuple [httpcode, body].
     * @throws moodle_exception On unsupported method, transport failure, or HTTP >= 400.
     */
    protected function request(string $method, string $url, ?string $rawbody = null, array $headers = []): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl(['timeout' => $this->timeout]);

        if (!empty($headers)) {
            $curl->setHeader($this->normalize_headers($headers));
        }

        try {
            if ($method === 'GET') {
                $body = $curl->get($url);
            } else if ($method === 'POST') {
                $body = $curl->post($url, $rawbody ?? '');
            } else {
                throw new moodle_exception('unsupportedmethod', 'error', '', $method);
            }
        } catch (\Throwable $e) {
            throw new moodle_exception('api:error_http_request_failed', 'paygw_ifthenpay', '', $e->getMessage());
        }

        $info = $curl->get_info();
        $code = (int)($info['http_code'] ?? 0);

        if ($code >= 400) {
            throw new moodle_exception('api:error_http_status', 'paygw_ifthenpay', '', "HTTP $code: $body");
        }

        return [$code, (string)$body];
    }

    /**
     * Default JSON headers.
     *
     * @param bool $ispost Whether the request is POST.
     * @return array Header map (Name => Value).
     */
    protected function json_headers(bool $ispost): array {
        $h = ['Accept' => 'application/json'];
        if ($ispost) {
            $h['Content-Type'] = 'application/json';
        }
        return $h;
    }

    /**
     * Normalize a header map into "Name: Value" strings for \curl::setHeader().
     *
     * @param array  $headers Header map (name => value).
     * @return string[] List of "Name: Value" header strings.
     */
    protected function normalize_headers(array $headers): array {
        $out = [];
        foreach ($headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }
        return $out;
    }
}
