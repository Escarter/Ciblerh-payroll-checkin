<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OrangeCameroonSMS extends SmsProvider
{
    protected ?string $accessToken = null;
    protected ?Carbon $accessTokenExpiresAt = null;

    /** JWT from Messaging Pro authenticate (api.orange.cm); required for X-MSP-Authorization-Key on send. */
    protected ?string $mspToken = null;
    protected ?Carbon $mspTokenExpiresAt = null;

    /** Messaging Pro bundle login from settings (not OAuth Client ID/Secret). */
    protected ?string $mspLoginUsername = null;
    protected ?string $mspLoginPassword = null;
    protected ?string $applicationId = null;

    protected ?Client $httpClient = null;

    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
        $this->mspLoginUsername = ! empty($setting->sms_msp_username) ? (string) $setting->sms_msp_username : null;
        $this->mspLoginPassword = ! empty($setting->sms_msp_password) ? (string) $setting->sms_msp_password : null;
        $this->applicationId = ! empty($setting->sms_provider_app_id) ? (string) $setting->sms_provider_app_id : null;
    }

    /**
     * Send SMS via Orange [Messaging Pro Cameroon](https://developer.orange.com/apis/messagingpro-cameroon/getting-started) —
     * POST `/messaging/v1/sms/simple` with OAuth Bearer + `X-MSP-Authorization-Key: Bearer <MSP token>`.
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

            $recipientE164 = $this->toE164String((string) ($data['mobiles'] ?? ''));

            $campaignTitle = $this->normalizeMessagingProCampaignTitle(
                (string) config('services.orange_cm.campaign_title', 'Payslip')
            );
            $projectName = $this->normalizeMessagingProProjectName(
                (string) config('services.orange_cm.project_name', 'Payroll')
            );

            $payload = [
                'campaignTitle' => $campaignTitle,
                'projectName' => $projectName,
                'messageContent' => $message,
                'recipients' => [$recipientE164],
            ];

            $endpoint = config('services.orange_cm.sms_endpoint', '/messaging/v1/sms/simple');

            $result = $this->authorizedMessagingProRequest('post', $this->buildUrl($endpoint), [
                'json' => $payload,
            ]);

            $status = (int) ($result['status'] ?? 0);
            if ($status !== 201) {
                $detail = $result['body']['message'] ?? $result['raw_body'] ?? '';
                throw new Exception(
                    'Orange Messaging Pro SMS request failed with status '.$status.($detail !== '' ? ': '.$detail : '')
                );
            }

            $body = is_array($result['body'] ?? null) ? $result['body'] : [];
            Log::info('Orange Messaging Pro SMS API accepted request (201); delivery to handset is asynchronous.', [
                'recipient' => $recipientE164,
                'response_keys' => array_keys($body),
            ]);

            if ($this->messagingProSendResponseIndicatesFailure($body)) {
                $response['responsecode'] = 0;
                $response['error'] = $body['message'] ?? $body['description'] ?? 'Orange Messaging Pro reported a failure for one or more recipients.';
                $response['body'] = $body;

                return $response;
            }

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
     * Run an end-to-end connectivity diagnostic for Orange Messaging Pro.
     *
     * @return array{
     *   oauth: array{ok: bool, error?: string},
     *   msp: array{ok: bool, error?: string},
     *   send?: array{ok: bool, responsecode?: int, error?: string}
     * }
     */
    public function runDiagnostics(string $phone, string $message, bool $dryRun = true): array
    {
        $result = [
            'oauth' => ['ok' => false],
            'msp' => ['ok' => false],
        ];

        try {
            $this->getAccessToken();
            $result['oauth']['ok'] = true;
        } catch (\Throwable $e) {
            $result['oauth']['error'] = $e->getMessage();

            return $result;
        }

        try {
            $this->getMspToken();
            $result['msp']['ok'] = true;
        } catch (\Throwable $e) {
            $result['msp']['error'] = $e->getMessage();

            return $result;
        }

        if ($dryRun) {
            return $result;
        }

        $send = $this->sendSMS([
            'mobiles' => $phone,
            'sms' => $message,
        ]);

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
     * Get SMS balance/available units from Orange Cameroon contracts endpoint.
     */
    public function getBalance(): array
    {
        $response = ['responsecode' => 0, 'credit' => 0];

        try {
            $country = trim((string) config('services.orange_cm.country', 'CMR'));
            $query = $country !== '' ? ['country' => $country] : [];

            $result = $this->authorizedRequest(
                'get',
                $this->buildUrl(config('services.orange_cm.contracts_endpoint', '/sms/admin/v1/contracts')),
                ['query' => $query]
            );

            if (($result['status'] ?? 0) !== 200) {
                throw new Exception('Orange SMS balance request failed with status ' . ($result['status'] ?? 'unknown'));
            }

            $rawBody = $result['body'] ?? [];
            $credit = $this->extractAvailableUnits($rawBody);
            if ($credit === 0) {
                $credit = $this->extractAlternateBalanceFigures($rawBody);
            }

            $response['responsecode'] = 1;

            // Only show N/A when the payload has no recognizable SMS unit fields anywhere (e.g. empty `[]`,
            // or Messaging Pro–only account shapes). If Orange returns `availableUnits` (etc.) with value 0,
            // that is a real zero balance, not "unknown".
            $hasUnitFields = $this->jsonTreeContainsRecognizedSmsUnitKey($rawBody);
            if ($credit === 0 && ! $hasUnitFields && ! config('services.orange_cm.trust_zero_balance_from_contracts_api', false)) {
                $response['credit'] = null;
                $response['balance_unknown'] = true;
                Log::info('Orange SMS balance: contracts JSON had no recognizable unit fields; balance unavailable in app (check Orange developer portal).', [
                    'top_level_keys' => is_array($rawBody) ? array_slice(array_keys($rawBody), 0, 12) : [],
                ]);
            } else {
                $response['credit'] = $credit;
            }
        } catch (\Throwable $th) {
            Log::error('Orange Cameroon SMS balance check failed', [
                'error' => $th->getMessage(),
            ]);
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    /**
     * True if JSON has explicit per-recipient or aggregate failure hints.
     */
    protected function messagingProSendResponseIndicatesFailure(array $body): bool
    {
        if ($body === []) {
            return false;
        }

        if (! empty($body['code']) && is_numeric($body['code']) && (int) $body['code'] >= 400) {
            return true;
        }

        $failed = $body['failedCount'] ?? $body['failureCount'] ?? $body['nbFailed'] ?? null;
        if ($failed !== null && (int) $failed > 0) {
            return true;
        }

        foreach (['list', 'recipients', 'recipientList', 'messages'] as $key) {
            if (! isset($body[$key]) || ! is_array($body[$key])) {
                continue;
            }
            foreach ($body[$key] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $st = strtolower((string) ($row['status'] ?? $row['deliveryStatus'] ?? $row['state'] ?? $row['result'] ?? ''));
                if ($st !== '' && (str_contains($st, 'fail') || str_contains($st, 'reject') || str_contains($st, 'error'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Other possible numeric keys in Orange contract/balance JSON.
     */
    protected function extractAlternateBalanceFigures(mixed $node): int
    {
        if (! is_array($node)) {
            return 0;
        }

        $keys = [
            'remainingSms', 'remainingUnits', 'totalAvailableUnits', 'smsBalance', 'balance',
            'availableQuantity', 'credit', 'newAvailableUnits', 'oldAvailableUnits',
        ];
        $sum = 0;
        foreach ($node as $key => $value) {
            if (is_string($key) && in_array($key, $keys, true) && is_numeric($value)) {
                $sum += (int) $value;
            }
            $sum += $this->extractAlternateBalanceFigures($value);
        }

        return $sum;
    }

    protected function authorizedRequest(string $method, string $url, array $options = []): array
    {
        $token = $this->getAccessToken();
        $applicationId = trim((string) ($this->applicationId ?? ''));
        $options['headers'] = array_merge([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);
        if ($applicationId !== '') {
            $options['headers']['X-Orange-Application-ID'] = $applicationId;
            $options['headers']['x-ibm-client-id'] = $applicationId;
        }
        $options['timeout'] = $options['timeout'] ?? 20;

        return $this->request($method, $url, $options);
    }

    /**
     * Messaging Pro send endpoint: OAuth (Developer app) + MSP token (bundle login) per Orange getting-started curl.
     */
    protected function authorizedMessagingProRequest(string $method, string $url, array $options): array
    {
        $oauthToken = $this->getAccessToken();
        $mspJwt = $this->getMspToken();

        $applicationId = trim((string) ($this->applicationId ?? ''));
        $options['headers'] = array_merge([
            'Authorization' => 'Bearer '.$oauthToken,
            'X-MSP-Authorization-Key' => 'Bearer '.$mspJwt,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);
        if ($applicationId !== '') {
            $options['headers']['X-Orange-Application-ID'] = $applicationId;
            $options['headers']['x-ibm-client-id'] = $applicationId;
        }
        $options['timeout'] = $options['timeout'] ?? 20;

        return $this->request($method, $url, $options);
    }

    /**
     * Token from POST api.orange.cm/messaging/api/v1/authenticate (Messaging Pro username/password).
     */
    protected function getMspToken(): string
    {
        if (
            $this->mspToken !== null
            && $this->mspTokenExpiresAt !== null
            && now()->lessThan($this->mspTokenExpiresAt)
        ) {
            return $this->mspToken;
        }

        // Priority: DB settings → env → OAuth fields (only if no dedicated MSP login).
        $user = trim((string) ($this->mspLoginUsername ?? ''));
        $pass = (string) ($this->mspLoginPassword ?? '');
        if ($user === '') {
            $user = trim((string) (config('services.orange_cm.msp_username') ?? ''));
        }
        if ($pass === '') {
            $pass = trim((string) (config('services.orange_cm.msp_password') ?? ''));
        }
        if ($user === '') {
            $user = trim($this->username);
        }
        if ($pass === '') {
            $pass = trim($this->password);
        }
        if ($user === '' || $pass === '') {
            throw new Exception('Messaging Pro login is required: enter Messaging Pro username and password in SMS settings (or set ORANGE_CM_MSP_USERNAME / ORANGE_CM_MSP_PASSWORD in .env). These are separate from the OAuth Client ID and Client secret.');
        }

        $authUrl = (string) config('services.orange_cm.msp_auth_url', 'https://api.orange.cm/messaging/api/v1/authenticate');
        $result = $this->request('post', $authUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'username' => $user,
                'password' => $pass,
            ],
            'timeout' => 20,
        ]);

        if (($result['status'] ?? 0) !== 200) {
            throw new Exception('Messaging Pro authentication failed (api.orange.cm): '.($result['raw_body'] ?? ''));
        }

        $token = $result['body']['token'] ?? null;
        if (empty($token) || ! is_string($token)) {
            throw new Exception('Messaging Pro authenticate response did not include a token');
        }

        $ttlSeconds = 3000;
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode((string) base64_decode((string) strtr($parts[1], '-_', '+/'), true), true);
            if (is_array($payload) && isset($payload['exp'])) {
                $ttlSeconds = max((int) $payload['exp'] - time() - 120, 120);
            }
        }

        $this->mspToken = $token;
        $this->mspTokenExpiresAt = now()->addSeconds($ttlSeconds);

        return $token;
    }

    protected function getAccessToken(): string
    {
        if (
            $this->accessToken !== null
            && $this->accessTokenExpiresAt !== null
            && now()->lessThan($this->accessTokenExpiresAt)
        ) {
            return $this->accessToken;
        }

        if (trim($this->username) === '' || trim($this->password) === '') {
            throw new Exception('Orange Cameroon credentials are required');
        }

        $result = $this->request(config('services.orange_cm.token_method', 'post'), config('services.orange_cm.token_url', 'https://api.orange.com/oauth/v3/token'), [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
            'timeout' => 20,
        ]);

        if (($result['status'] ?? 0) !== 200) {
            throw new Exception('Unable to authenticate with Orange Cameroon API');
        }

        $token = $result['body']['access_token'] ?? null;
        if (empty($token)) {
            throw new Exception('Orange Cameroon API did not return an access token');
        }

        $expiresIn = (int) ($result['body']['expires_in'] ?? 3600);
        $ttl = max($expiresIn - 60, 60);
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
     * Guzzle builds PSR-7 headers with {@see \GuzzleHttp\Utils::headersFromLines} using the first ":" only.
     * Content-Security-Policy (and similar) values contain ":" / ";" — malformed or folded lines from
     * api.orange.cm can yield a line with no name, so the CSP directive is mistaken for a header *name*
     * ("script-src ... is not valid header name"). cURL with CURLOPT_HEADER false returns only the body;
     * HTTP status comes from curl_getinfo, so we never parse those headers.
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

        return parse_url($url, PHP_URL_HOST) === 'api.orange.cm';
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
            throw new Exception('Orange api.orange.cm cURL error: '.$error.' ('.$errno.')');
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
     * api.orange.cm (and some Orange edges) can return responses that confuse Guzzle/PSR-7 when using
     * HTTP/2 or odd header folding — e.g. a CSP directive line parsed as a header *name*
     * ("script-src ... is not valid header name"). Forcing HTTP/1.1 + cURL HTTP version avoids that.
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
     * Orange Messaging Pro rejects invalid lengths; API returns 400 if rules are not met.
     */
    protected function normalizeMessagingProProjectName(string $value): string
    {
        $min = 2;
        $max = 8;
        $fallback = 'Payroll';
        $value = trim($value);
        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }
        if (mb_strlen($value) < $min) {
            $value = $fallback;
        }

        return $value;
    }

    /**
     * campaignTitle limits are looser than projectName; keep a safe upper bound.
     */
    protected function normalizeMessagingProCampaignTitle(string $value): string
    {
        $max = (int) config('services.orange_cm.campaign_title_max_length', 255);
        $value = trim($value);
        if ($value === '') {
            $value = 'Payslip';
        }
        if ($max > 0 && mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }

        return $value;
    }

    protected function buildUrl(string $path): string
    {
        $base = rtrim((string) config('services.orange_cm.api_url', 'https://api.orange.com'), '/');
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }

    /**
     * E.164 international number for Messaging Pro `recipients` (e.g. +2376XXXXXXXX), without `tel:` prefix.
     */
    protected function toE164String(string $value): string
    {
        $tel = $this->toTelAddress($value);

        return substr($tel, 4);
    }

    protected function toTelAddress(string $value): string
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
            $value = '+' . substr($value, 2);
        }

        if (!str_starts_with($value, '+')) {
            $value = ltrim($value, '0');
            if ($value === '') {
                throw new Exception('Phone number cannot be all zeros');
            }

            $defaultCountryCode = preg_replace('/\D/', '', (string) config('services.orange_cm.default_country_code', '237'));
            if ($defaultCountryCode === '') {
                throw new Exception('Orange Cameroon default country code is invalid');
            }

            if (!str_starts_with($value, $defaultCountryCode)) {
                $value = $defaultCountryCode . $value;
            }

            $value = '+' . $value;
        }

        if (!preg_match('/^\+\d{8,15}$/', $value)) {
            throw new Exception('Invalid phone number format for Orange Cameroon API');
        }

        return 'tel:' . $value;
    }

    /**
     * Keys Orange uses in `/sms/admin/v1/contracts` and related payloads (see Orange SMS getting-started).
     */
    protected function smsUnitFieldNames(): array
    {
        return [
            'availableUnits',
            'newAvailableUnits',
            'oldAvailableUnits',
            'remainingUnits',
        ];
    }

    /**
     * True if any recognized SMS unit counter appears with a numeric value (including 0).
     */
    protected function jsonTreeContainsRecognizedSmsUnitKey(mixed $node, int $depth = 0): bool
    {
        if ($depth > 40 || ! is_array($node)) {
            return false;
        }

        $direct = array_merge($this->smsUnitFieldNames(), [
            'remainingSms', 'totalAvailableUnits', 'smsBalance', 'balance',
            'availableQuantity', 'credit',
        ]);

        foreach ($node as $key => $value) {
            if (is_string($key) && in_array($key, $direct, true) && is_numeric($value)) {
                return true;
            }
            if (is_array($value) && $this->jsonTreeContainsRecognizedSmsUnitKey($value, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    protected function extractAvailableUnits(mixed $node): int
    {
        if (! is_array($node)) {
            return 0;
        }

        $sum = 0;
        $unitKeys = $this->smsUnitFieldNames();

        foreach ($node as $key => $value) {
            if (is_string($key) && in_array($key, $unitKeys, true) && is_numeric($value)) {
                $sum += (int) $value;

                continue;
            }

            $sum += $this->extractAvailableUnits($value);
        }

        return $sum;
    }
}
