<?php

use App\Models\Setting;
use App\Services\OrangeCameroonSMS;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Config::set('services.orange_cm.api_url', 'https://api.orange.com');
    Config::set('services.orange_cm.token_url', 'https://api.orange.com/oauth/v3/token');
    Config::set('services.orange_cm.sms_endpoint', '/smsmessaging/v1/outbound/{senderAddress}/requests');
    Config::set('services.orange_cm.contracts_endpoint', '/sms/admin/v1/contracts');
    Config::set('services.orange_cm.country', 'CMR');
    Config::set('services.orange_cm.default_country_code', '237');
    Log::spy();
});

function makeOrangeSetting(array $overrides = []): Setting
{
    return new Setting(array_merge([
        'company_id' => 1,
        'sms_provider' => 'orange_cm',
        'sms_provider_app_id' => 'app-id-123',
        'sms_provider_username' => 'client-id',
        'sms_provider_password' => 'client-secret',
        'sms_provider_senderid' => '+237699000001',
    ], $overrides));
}

test('orange cameroon sms sendSMS succeeds', function () {
    $setting = makeOrangeSetting();
    Config::set('services.orange_cm.application_id', $setting->sms_provider_app_id);
    $service = new OrangeCameroonSMS($setting);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.com/oauth/v3/token'
                && ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'token-123',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $payload = $options['json']['outboundSMSMessageRequest'] ?? [];
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);
            return $method === 'POST'
                && str_contains($url, '/smsmessaging/v1/outbound/tel%3A%2B237699000001/requests')
                && ($payload['address'] ?? null) === 'tel:+237677001122'
                && ($payload['senderAddress'] ?? null) === 'tel:+237699000001'
                && ($payload['outboundSMSTextMessage']['message'] ?? null) === 'Hello'
                && ($headers['x-orange-application-id'] ?? null) === 'app-id-123'
                && ($headers['x-ibm-client-id'] ?? null) === 'app-id-123';
        })
        ->andReturn(new Response(201, [], json_encode([
            'outboundSMSMessageRequest' => [
                'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+237699000001/requests/abc',
            ],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS(['mobiles' => '0677001122', 'sms' => 'Hello']);

    if (($response['responsecode'] ?? 0) !== 1) {
        throw new RuntimeException('sendSMS error: ' . ($response['error'] ?? 'unknown'));
    }
    expect($response['responsecode'])->toBe(1);
    expect($response['resource_url'])->not->toBeNull();
});

test('orange cameroon getBalance sums available units', function () {
    $setting = makeOrangeSetting();
    Config::set('services.orange_cm.application_id', $setting->sms_provider_app_id);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.com/oauth/v3/token'
                && ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'token-123',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);
            return $method === 'GET'
                && $url === 'https://api.orange.com/sms/admin/v1/contracts'
                && ($options['query']['country'] ?? null) === 'CMR'
                && ($headers['x-orange-application-id'] ?? null) === 'app-id-123'
                && ($headers['x-ibm-client-id'] ?? null) === 'app-id-123';
        })
        ->andReturn(new Response(200, [], json_encode([
            'partnerContracts' => [
                [
                    'contracts' => [
                        [
                            'serviceContracts' => [
                                ['availableUnits' => 25],
                                ['availableUnits' => 75],
                            ],
                        ],
                    ],
                ],
            ],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->getBalance();

    if (($response['responsecode'] ?? 0) !== 1) {
        throw new RuntimeException('getBalance error: ' . ($response['error'] ?? 'unknown'));
    }
    expect($response['responsecode'])->toBe(1);
    expect($response['credit'])->toBe(100);
});

test('orange cameroon sendSMS fails for empty sms message', function () {
    $setting = makeOrangeSetting();
    $service = new OrangeCameroonSMS($setting);

    $response = $service->sendSMS([
        'mobiles' => '+237677001122',
        'sms' => '   ',
    ]);

    expect($response['responsecode'])->toBe(0);
    expect($response['error'])->toContain('SMS message cannot be empty');
});

test('orange cameroon sendSMS includes config fallback app id header', function () {
    Config::set('services.orange_cm.application_id', 'config-app-id');

    $setting = makeOrangeSetting(['sms_provider_app_id' => null]);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.com/oauth/v3/token'
                && ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'token-123',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);
            return $method === 'POST'
                && str_contains($url, '/smsmessaging/v1/outbound/')
                && ($headers['x-orange-application-id'] ?? null) === 'config-app-id'
                && ($headers['x-ibm-client-id'] ?? null) === 'config-app-id';
        })
        ->andReturn(new Response(201, [], json_encode([
            'outboundSMSMessageRequest' => ['resourceURL' => 'ok'],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS([
        'mobiles' => '+237677001122',
        'sms' => 'Hello',
    ]);

    expect($response['responsecode'])->toBe(1);
});

afterEach(function () {
    Mockery::close();
});
