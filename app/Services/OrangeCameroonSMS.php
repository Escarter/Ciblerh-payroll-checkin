<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orange Cameroun SMS via the Business Messaging Ngage API.
 *
 * Implements the integration described in "MODOP Business Messaging — Interface API SMS Orange Cameroun v1":
 *  - Authenticate: POST https://businessmessaging.orange.cm/api/v1/accounts/users/login  ({email, password} → {access_token})
 *  - Send simple : POST https://businessmessaging.orange.cm/api/v1/sms/send  (Bearer access_token)
 *
 * The Ngage login email/password are the same credentials used on the Ngage web interface and are stored in the
 * generic SMS settings: sms_provider_username = login email, sms_provider_password = password,
 * sms_provider_senderid = sender (alphanumeric or short code registered with Orange).
 */
class OrangeCameroonSMS extends SmsProvider
{
    /** Bearer access token from the Ngage login endpoint. */
    protected ?string $accessToken = null;
    protected ?Carbon $accessTokenExpiresAt = null;

    protected ?Client $httpClient = null;

    /** Per-company send options (fall back to config/env when not set in settings). */
    protected ?string $category = null;
    protected ?string $country = null;
    protected ?string $drCallback = null;

    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
        $this->category = ! empty($setting->sms_orange_category) ? trim((string) $setting->sms_orange_category) : null;
        $this->country = ! empty($setting->sms_orange_country) ? trim((string) $setting->sms_orange_country) : null;
        $this->drCallback = ! empty($setting->sms_orange_dr_callback) ? trim((string) $setting->sms_orange_dr_callback) : null;
    }

    /**
     * Send a simple (instant) SMS — POST /api/v1/sms/send with the mandatory, case-sensitive body fields:
     * msg, recipient, sender, category, clientTxnId, country, drCallback.
     */
    public function sendSMS(array $data): array
    {
        $response = ['responsecode' => 0];

        try {
            $message = trim((string) ($data['sms'] ?? ''));

            if ($message === '') {
                throw new Exception('SMS message cannot be empty');
            }

            $maxLen = (int) config('services.orange_cm.message_max_length', 160);
            if ($maxLen > 0 && mb_strlen($message) > $maxLen) {
                $message = mb_substr($message, 0, $maxLen);
            }

            $recipient = $this->toMsisdn((string) ($data['mobiles'] ?? ''));

            $sender = trim($this->senderid ?? '');
            if ($sender === '') {
                $sender = trim((string) config('services.orange_cm.default_sender', ''));
            }
            if ($sender === '') {
                throw new Exception('Sender is required for Orange SMS delivery. Configure the sender in SMS settings.');
            }

            $category = $this->category ?? (string) config('services.orange_cm.category', 'Promo');
            $country = $this->country ?? (string) config('services.orange_cm.country', 'CM');

            $payload = [
                'msg' => $message,
                'recipient' => $recipient,
                'sender' => $sender,
                'category' => $category,
                'clientTxnId' => $this->generateClientTxnId($data),
                'country' => $country,
            ];

            $drCallback = trim($this->drCallback ?? (string) config('services.orange_cm.dr_callback', ''));
            if ($drCallback === '') {
                $drCallback = $this->defaultDrCallbackUrl();
            }
            if ($drCallback !== '') {
                $payload['drCallback'] = $drCallback;
            }

            $endpoint = (string) config('services.orange_cm.send_url', 'https://businessmessaging.orange.cm/api/v1/sms/send');

            $result = $this->authorizedRequest('post', $endpoint, ['json' => $payload]);

            $status = (int) ($result['status'] ?? 0);
            $body = is_array($result['body'] ?? null) ? $result['body'] : [];

            // Per the spec, success is 200/201/202 with statusCode 0 ("SUCCESS").
            if (! in_array($status, [200, 201, 202], true)) {
                $detail = $body['statusMsg'] ?? $body['message'] ?? $result['raw_body'] ?? '';
                throw new Exception(
                    'Orange Ngage SMS request failed with status '.$status.($detail !== '' ? ': '.$detail : '')
                );
            }

            if ($this->sendResponseIndicatesFailure($body)) {
                $response['responsecode'] = 0;
                $response['error'] = $body['statusMsg'] ?? $body['message'] ?? 'Orange Ngage reported a failure for this request.';
                $response['body'] = $body;

                return $response;
            }

            Log::info('Orange Ngage SMS accepted.', [
                'recipient' => $recipient,
                'http_status' => $status,
                'txn_id' => $body['txnId'] ?? null,
                'campaign_id' => $body['campaignId'] ?? null,
                'status_msg' => $body['statusMsg'] ?? null,
            ]);

            $response['responsecode'] = 1;
            $response['body'] = $body;
        } catch (\Throwable $th) {
            Log::error('Orange Cameroon SMS sending failed', [
                'error' => $th->getMessage(),
                'phone' => $data['mobiles'] ?? 'unknown',
            ]);
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    /**
     * Connectivity diagnostic: confirm login works, then optionally send a test SMS.
     *
     * @return array{auth: array{ok: bool, error?: string}, send?: array{ok: bool, responsecode?: int, error?: string}}
     */
    public function runDiagnostics(string $phone, string $message, bool $dryRun = true): array
    {
        $result = ['auth' => ['ok' => false]];

        try {
            $this->getAccessToken();
            $result['auth']['ok'] = true;
        } catch (\Throwable $e) {
            $result['auth']['error'] = $e->getMessage();

            return $result;
        }

        if ($dryRun) {
            return $result;
        }

        $send = $this->sendSMS(['mobiles' => $phone, 'sms' => $message]);
        $result['send'] = [
            'ok' => (int) ($send['responsecode'] ?? 0) === 1,
            'responsecode' => (int) ($send['responsecode'] ?? 0),
        ];
        if (! empty($send['error'])) {
            $result['send']['error'] = (string) $send['error'];
        }

        return $result;
    }

    /**
     * The Ngage API spec does not expose a balance/credit endpoint. Report success with an unknown balance so the
     * UI shows "N/A" rather than a misleading zero; bundle volume is visible on the Ngage web interface.
     */
    public function getBalance(): array
    {
        return [
            'responsecode' => 1,
            'credit' => null,
            'balance_unknown' => true,
        ];
    }

    /**
     * True if the JSON response carries an explicit failure signal (non-zero statusCode, or a failure statusMsg).
     */
    protected function sendResponseIndicatesFailure(array $body): bool
    {
        if ($body === []) {
            return false;
        }

        if (array_key_exists('statusCode', $body) && is_numeric($body['statusCode']) && (int) $body['statusCode'] !== 0) {
            return true;
        }

        $statusMsg = strtolower((string) ($body['statusMsg'] ?? ''));
        if ($statusMsg !== '' && (str_contains($statusMsg, 'fail') || str_contains($statusMsg, 'error') || str_contains($statusMsg, 'reject'))) {
            return true;
        }

        return false;
    }

    /**
     * Attach the Bearer access token and JSON headers, then perform the request.
     */
    protected function authorizedRequest(string $method, string $url, array $options = []): array
    {
        $token = $this->getAccessToken();

        $options['headers'] = array_merge([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);
        $options['timeout'] = $options['timeout'] ?? 20;

        return $this->request($method, $url, $options);
    }

    /**
     * Obtain (and cache) the Ngage Bearer token via POST /api/v1/accounts/users/login.
     * Token validity is 3600s (1h) per the spec; we refresh slightly early.
     */
    protected function getAccessToken(): string
    {
        if (
            $this->accessToken !== null
            && $this->accessTokenExpiresAt !== null
            && now()->lessThan($this->accessTokenExpiresAt)
        ) {
            return $this->accessToken;
        }

        $email = trim((string) ($this->username ?? ''));
        $password = (string) ($this->password ?? '');
        if ($email === '' || $password === '') {
            throw new Exception('Orange Cameroon (Ngage) login email and password are required.');
        }

        $loginUrl = (string) config('services.orange_cm.login_url', 'https://businessmessaging.orange.cm/api/v1/accounts/users/login');

        $result = $this->request('post', $loginUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
            'timeout' => 20,
        ]);

        if (($result['status'] ?? 0) !== 200) {
            throw new Exception('Orange Ngage authentication failed (HTTP '.($result['status'] ?? 'unknown').'): '.($result['raw_body'] ?? ''));
        }

        $token = $result['body']['access_token'] ?? null;
        if (empty($token) || ! is_string($token)) {
            throw new Exception('Orange Ngage login response did not include an access_token.');
        }

        // Spec: token validity 3600s. Refresh 60s early to avoid edge-of-expiry failures.
        $ttl = max((int) config('services.orange_cm.token_ttl', 3600) - 60, 60);
        $this->accessToken = $token;
        $this->accessTokenExpiresAt = now()->addSeconds($ttl);

        return $token;
    }

    protected function request(string $method, string $url, array $options = []): array
    {
        $options = $this->mergeDefaultTransportOptions($url, $options);

        if ($this->shouldUsePhpCurlWithoutResponseHeaders($url)) {
            return $this->requestViaPhpCurl($method, $url, $options);
        }

        try {
            $response = $this->makeHttpClient()->request(strtoupper($method), $url, $options);
        } catch (GuzzleException $e) {
            throw new Exception($this->formatGuzzleFailure($method, $url, $e), 0, $e);
        }

        $rawBody = (string) $response->getBody();
        $decodedBody = json_decode($rawBody, true);

        return [
            'status' => $response->getStatusCode(),
            'body' => is_array($decodedBody) ? $decodedBody : [],
            'raw_body' => $rawBody,
        ];
    }

    /**
     * Some Orange edges return responses that confuse Guzzle's PSR-7 header-line parser (a CSP directive folded into
     * a line with no name is mistaken for a header *name*). cURL with CURLOPT_HEADER false returns only the body, and
     * the status comes from curl_getinfo, so those headers are never parsed.
     */
    protected function shouldUsePhpCurlWithoutResponseHeaders(string $url): bool
    {
        if (! function_exists('curl_init')) {
            return false;
        }
        if (config('services.orange_cm.use_php_curl_for_orange_cm_host', true) !== true) {
            return false;
        }
        if (app()->runningUnitTests()) {
            return false;
        }

        return parse_url($url, PHP_URL_HOST) === 'businessmessaging.orange.cm';
    }

    /**
     * @param  array<string, mixed>  $options  Merged Guzzle-style options (headers, json, timeout, verify).
     * @return array{status: int, body: array, raw_body: string}
     */
    protected function requestViaPhpCurl(string $method, string $url, array $options): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new Exception('Unable to initialize cURL for '.$url);
        }

        $method = strtoupper($method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if (defined('CURL_HTTP_VERSION_1_1')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }

        $verify = $options['verify'] ?? true;
        if ($verify === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (is_string($verify)) {
                curl_setopt($ch, CURLOPT_CAINFO, $verify);
            }
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, (int) ($options['timeout'] ?? 30));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) ($options['connect_timeout'] ?? 15));

        $headerLines = [];
        foreach ($options['headers'] ?? [] as $name => $value) {
            if (! is_string($name)) {
                continue;
            }
            $headerLines[] = $name.': '.$value;
        }
        if ($headerLines !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if (isset($options['json'])) {
                $encoded = json_encode($options['json']);
                if ($encoded === false) {
                    throw new Exception('Unable to JSON-encode Orange SMS request body');
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
            } elseif (isset($options['form_params'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['form_params']));
            } elseif (isset($options['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $options['body']);
            }
        }

        $rawBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            Log::error('OrangeCameroonSMS cURL failure', ['url' => $url, 'curl_errno' => $errno, 'curl_error' => $error]);
            throw new Exception('Orange businessmessaging.orange.cm cURL error: '.$error.' ('.$errno.')');
        }

        $decodedBody = json_decode((string) $rawBody, true);

        return [
            'status' => $status,
            'body' => is_array($decodedBody) ? $decodedBody : [],
            'raw_body' => (string) $rawBody,
        ];
    }

    /**
     * Guzzle often wraps the real fault as {@see RequestException::getPrevious()} — the generic
     * "An error was encountered while creating the response" message is not actionable alone.
     */
    protected function formatGuzzleFailure(string $method, string $url, GuzzleException $e): string
    {
        $bits = [trim($e->getMessage())];
        $prev = $e->getPrevious();
        if ($prev !== null) {
            $bits[] = 'Detail: '.$prev->getMessage();
        }
        if ($e instanceof RequestException && $e->hasResponse()) {
            $r = $e->getResponse();
            $body = substr((string) $r->getBody(), 0, 600);
            $bits[] = 'HTTP '.$r->getStatusCode().($body !== '' ? ': '.$body : '');
        }

        $summary = implode(' ', array_filter($bits));

        Log::error('OrangeCameroonSMS HTTP failure', [
            'method' => $method,
            'url' => $url,
            'summary' => $summary,
        ]);

        return 'Orange API HTTP failure ('.$method.' '.$url.'): '.$summary;
    }

    /**
     * Force HTTP/1.1 and a User-Agent; some Orange edges break Guzzle/PSR-7 on HTTP/2 or odd header folding.
     */
    protected function mergeDefaultTransportOptions(string $url, array $options): array
    {
        $headers = array_merge(
            [
                'User-Agent' => (string) config('services.orange_cm.http_user_agent', 'CiblerhPayroll/1.0 (Laravel; Orange SMS)'),
            ],
            $options['headers'] ?? []
        );

        $curlDefaults = [];
        if (defined('CURL_HTTP_VERSION_1_1')) {
            $curlDefaults[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_1;
        }
        $curl = array_replace($curlDefaults, $options['curl'] ?? []);

        return array_merge($options, [
            'version' => '1.1',
            'headers' => $headers,
            'curl' => $curl,
        ]);
    }

    protected function makeHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client([
                'timeout' => 30,
                'connect_timeout' => 15,
                'http_errors' => true,
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Build this app's own delivery-report callback URL (APP_URL + the webhooks.orange-sms.dr route),
     * appending the shared token when one is configured. Used when no explicit drCallback is set, so DR
     * processing works out of the box. Returns '' if disabled or the route cannot be resolved.
     */
    protected function defaultDrCallbackUrl(): string
    {
        if (config('services.orange_cm.dr_callback_auto', true) !== true) {
            return '';
        }

        try {
            $url = route('webhooks.orange-sms.dr');
        } catch (\Throwable $e) {
            return '';
        }

        $token = trim((string) config('services.orange_cm.dr_callback_token', ''));
        if ($token !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').'token='.urlencode($token);
        }

        return $url;
    }

    /**
     * A unique client transaction id for idempotency/tracing on Orange's side (returned as clientTxnId).
     */
    protected function generateClientTxnId(array $data): string
    {
        if (! empty($data['client_txn_id'])) {
            return (string) $data['client_txn_id'];
        }

        return (string) Str::uuid();
    }

    /**
     * Recipient MSISDN for the Ngage API: international, digits only, no leading "+" (e.g. 2376XXXXXXXX).
     */
    protected function toMsisdn(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new Exception('Phone number cannot be empty');
        }

        if (str_starts_with($value, 'tel:')) {
            $value = substr($value, 4);
        }

        $value = preg_replace('/\s+/', '', $value);
        $value = preg_replace('/[^0-9+]/', '', $value);

        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        }

        if (str_starts_with($value, '+')) {
            $value = substr($value, 1);
        } else {
            $value = ltrim($value, '0');
            if ($value === '') {
                throw new Exception('Phone number cannot be all zeros');
            }

            $defaultCountryCode = preg_replace('/\D/', '', (string) config('services.orange_cm.default_country_code', '237'));
            if ($defaultCountryCode === '') {
                throw new Exception('Orange Cameroon default country code is invalid');
            }

            if (! str_starts_with($value, $defaultCountryCode)) {
                $value = $defaultCountryCode.$value;
            }
        }

        if (! preg_match('/^\d{8,15}$/', $value)) {
            throw new Exception('Invalid phone number format for Orange Cameroon API');
        }

        return $value;
    }
}
