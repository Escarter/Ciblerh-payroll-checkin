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
    Config::set('services.orange_cm.sms_endpoint', '/messaging/v1/sms/simple');
    Config::set('services.orange_cm.msp_auth_url', 'https://api.orange.cm/messaging/api/v1/authenticate');
    Config::set('services.orange_cm.campaign_title', 'Payslip');
    Config::set('services.orange_cm.project_name', 'Payroll');
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
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.com/oauth/v3/token'
                && ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'oauth-access-token',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $json = $options['json'] ?? [];

            return $method === 'POST'
                && $url === 'https://api.orange.cm/messaging/api/v1/authenticate'
                && ($json['username'] ?? null) === 'client-id'
                && ($json['password'] ?? null) === 'client-secret';
        })
        ->andReturn(new Response(200, [], json_encode([
            'token' => 'msp-jwt-token',
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $payload = $options['json'] ?? [];
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);
            return $method === 'POST'
                && $url === 'https://api.orange.com/messaging/v1/sms/simple'
                && ($payload['campaignTitle'] ?? null) === 'Payslip'
                && ($payload['projectName'] ?? null) === 'Payroll'
                && ($payload['messageContent'] ?? null) === 'Hello'
                && ($payload['recipients'] ?? null) === ['+237677001122']
                && ($headers['authorization'] ?? null) === 'Bearer oauth-access-token'
                && ($headers['x-msp-authorization-key'] ?? null) === 'Bearer msp-jwt-token'
                && ($headers['x-orange-application-id'] ?? null) === 'app-id-123'
                && ($headers['x-ibm-client-id'] ?? null) === 'app-id-123';
        })
        ->andReturn(new Response(201, [], json_encode([
            'campaignTitle' => 'Payslip',
            'messageContent' => 'Hello',
            'recipients' => ['+237677001122'],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS(['mobiles' => '0677001122', 'sms' => 'Hello']);

    if (($response['responsecode'] ?? 0) !== 1) {
        throw new RuntimeException('sendSMS error: ' . ($response['error'] ?? 'unknown'));
    }
    expect($response['responsecode'])->toBe(1);
    expect($response['body'])->toBeArray();
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

test('orange cameroon getBalance returns zero when API includes unit fields that sum to zero', function () {
    Config::set('services.orange_cm.trust_zero_balance_from_contracts_api', false);
    $setting = makeOrangeSetting();
    Config::set('services.orange_cm.application_id', $setting->sms_provider_app_id);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url): bool => $method === 'POST' && str_contains($url, 'oauth'))
        ->andReturn(new Response(200, [], json_encode(['access_token' => 'token-123', 'expires_in' => 3600])));

    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url): bool => $method === 'GET' && str_contains($url, 'contracts'))
        ->andReturn(new Response(200, [], json_encode([
            'partnerContracts' => [
                ['contracts' => [['serviceContracts' => [['availableUnits' => 0]]]]],
            ],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->getBalance();

    expect($response['responsecode'])->toBe(1);
    expect($response['credit'])->toBe(0);
    expect($response['balance_unknown'] ?? false)->toBeFalse();
});

test('orange cameroon getBalance returns null when contracts payload has no unit fields', function () {
    Config::set('services.orange_cm.trust_zero_balance_from_contracts_api', false);
    $setting = makeOrangeSetting();
    Config::set('services.orange_cm.application_id', $setting->sms_provider_app_id);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url): bool => $method === 'POST' && str_contains($url, 'oauth'))
        ->andReturn(new Response(200, [], json_encode(['access_token' => 'token-123', 'expires_in' => 3600])));

    $mockClient->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $url): bool => $method === 'GET' && str_contains($url, 'contracts'))
        ->andReturn(new Response(200, [], json_encode([])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->getBalance();

    expect($response['responsecode'])->toBe(1);
    expect($response['credit'])->toBeNull();
    expect($response['balance_unknown'] ?? false)->toBeTrue();
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
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.com/oauth/v3/token'
                && ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'oauth-access-token',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            return $method === 'POST'
                && $url === 'https://api.orange.cm/messaging/api/v1/authenticate';
        })
        ->andReturn(new Response(200, [], json_encode([
            'token' => 'msp-jwt-token',
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);
            return $method === 'POST'
                && $url === 'https://api.orange.com/messaging/v1/sms/simple'
                && ($headers['x-orange-application-id'] ?? null) === 'config-app-id'
                && ($headers['x-ibm-client-id'] ?? null) === 'config-app-id'
                && ($headers['x-msp-authorization-key'] ?? null) === 'Bearer msp-jwt-token';
        })
        ->andReturn(new Response(201, [], json_encode([
            'messageContent' => 'Hello',
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
