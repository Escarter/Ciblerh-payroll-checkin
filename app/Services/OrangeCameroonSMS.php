<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OrangeCameroonSMS extends SmsProvider
{
    protected ?string $accessToken = null;
    protected ?Carbon $accessTokenExpiresAt = null;
    protected ?Client $httpClient = null;

    /**
     * Send SMS via Orange Cameroon SMS API.
     */
    public function sendSMS(array $data): array
    {
        $response = ['responsecode' => 0];
        $senderAddress = null;

        try {
            $recipientAddress = $this->toTelAddress((string) ($data['mobiles'] ?? ''));
            $senderAddress = $this->resolveSenderAddress();
            $message = trim((string) ($data['sms'] ?? ''));

            if ($message === '') {
                throw new Exception('SMS message cannot be empty');
            }

            $payload = [
                'outboundSMSMessageRequest' => [
                    'address' => $recipientAddress,
                    'senderAddress' => $senderAddress,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ];

            $senderAddressEncoded = rawurlencode($senderAddress);
            $endpoint = str_replace(
                '{senderAddress}',
                $senderAddressEncoded,
                config('services.orange_cm.sms_endpoint', '/smsmessaging/v1/outbound/{senderAddress}/requests')
            );

            $result = $this->authorizedRequest('post', $this->buildUrl($endpoint), [
                'json' => $payload,
            ]);

            if (($result['status'] ?? 0) !== 201) {
                throw new Exception('Orange SMS request failed with status ' . ($result['status'] ?? 'unknown'));
            }

            $response['responsecode'] = 1;
            $response['resource_url'] = $result['body']['outboundSMSMessageRequest']['resourceURL'] ?? null;
        } catch (\Throwable $th) {
            Log::error('Orange Cameroon SMS sending failed', [
                'error' => $th->getMessage(),
                'phone' => $data['mobiles'] ?? 'unknown',
                'sender_address' => $senderAddress,
            ]);
            $response['error'] = $th->getMessage();
        }

        return $response;
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

            $credit = $this->extractAvailableUnits($result['body']);
            $response['responsecode'] = 1;
            $response['credit'] = $credit;
        } catch (\Throwable $th) {
            Log::error('Orange Cameroon SMS balance check failed', [
                'error' => $th->getMessage(),
            ]);
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    protected function authorizedRequest(string $method, string $url, array $options = []): array
    {
        $token = $this->getAccessToken();
        $applicationId = trim((string) config('services.orange_cm.application_id', ''));
        $options['headers'] = array_merge([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);
        if ($applicationId !== '') {
            // Orange gateways may require app id headers depending on subscription profile.
            $options['headers']['X-Orange-Application-ID'] = $applicationId;
            $options['headers']['x-ibm-client-id'] = $applicationId;
        }
        $options['timeout'] = $options['timeout'] ?? 20;

        return $this->request($method, $url, $options);
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
        $response = $this->makeHttpClient()->request(strtoupper($method), $url, $options);
        $rawBody = (string) $response->getBody();
        $decodedBody = json_decode($rawBody, true);

        return [
            'status' => $response->getStatusCode(),
            'body' => is_array($decodedBody) ? $decodedBody : [],
            'raw_body' => $rawBody,
        ];
    }

    protected function makeHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    protected function buildUrl(string $path): string
    {
        $base = rtrim((string) config('services.orange_cm.api_url', 'https://api.orange.com'), '/');
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }

    protected function resolveSenderAddress(): string
    {
        $sender = trim($this->senderid);
        if ($sender === '') {
            $sender = trim((string) config('services.orange_cm.default_sender_address', ''));
        }

        if ($sender === '') {
            throw new Exception('Orange Cameroon sender address is required');
        }

        return $this->toTelAddress($sender);
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

    protected function extractAvailableUnits(mixed $node): int
    {
        if (!is_array($node)) {
            return 0;
        }

        $sum = 0;
        foreach ($node as $key => $value) {
            if ($key === 'availableUnits' && is_numeric($value)) {
                $sum += (int) $value;
                continue;
            }

            $sum += $this->extractAvailableUnits($value);
        }

        return $sum;
    }
}
