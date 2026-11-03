<?php

use App\Models\Setting;
use App\Services\OrangeCameroonSMS;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Config::set('services.orange_cm.login_url', 'https://businessmessaging.orange.cm/api/v1/accounts/users/login');
    Config::set('services.orange_cm.send_url', 'https://businessmessaging.orange.cm/api/v1/sms/send');
    Config::set('services.orange_cm.category', 'Promo');
    Config::set('services.orange_cm.country', 'CM');
    Config::set('services.orange_cm.dr_callback', '');
    Config::set('services.orange_cm.default_country_code', '237');
    Config::set('services.orange_cm.token_ttl', 3600);
    Log::spy();
});

function makeOrangeSetting(array $overrides = []): Setting
{
    return new Setting(array_merge([
        'company_id' => 1,
        'sms_provider' => 'orange_cm',
        'sms_provider_username' => 'lilian.kue@orange.com',
        'sms_provider_password' => 'secret-password',
        'sms_provider_senderid' => 'Corporate',
    ], $overrides));
}

test('orange cameroon sms sendSMS succeeds', function () {
    $setting = makeOrangeSetting();
    $mockClient = Mockery::mock(Client::class);

    // 1) Login to obtain the Bearer access token.
    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $json = $options['json'] ?? [];

            return $method === 'POST'
                && $url === 'https://businessmessaging.orange.cm/api/v1/accounts/users/login'
                && ($json['email'] ?? null) === 'lilian.kue@orange.com'
                && ($json['password'] ?? null) === 'secret-password';
        })
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'ngage-access-token',
            'refresh_token' => 'ngage-refresh-token',
        ])));

    // 2) Send the SMS with the mandatory, case-sensitive body fields + Bearer token.
    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $payload = $options['json'] ?? [];
            $headers = array_change_key_case($options['headers'] ?? [], CASE_LOWER);

            return $method === 'POST'
                && $url === 'https://businessmessaging.orange.cm/api/v1/sms/send'
                && ($payload['msg'] ?? null) === 'Hello'
                && ($payload['recipient'] ?? null) === '237677001122'
                && ($payload['sender'] ?? null) === 'Corporate'
                && ($payload['category'] ?? null) === 'Promo'
                && ($payload['country'] ?? null) === 'CM'
                && ! empty($payload['clientTxnId'])
                && ($headers['authorization'] ?? null) === 'Bearer ngage-access-token';
        })
        ->andReturn(new Response(202, [], json_encode([
            'txnId' => '42765dbf-fc06-4248-b58f-05fb0c581ac6',
            'campaignId' => '42765dbf-fc06-4248-b58f-05fb0c581ac6',
            'statusCode' => 0,
            'statusMsg' => 'SUCCESS',
            'clientTxnId' => 'Sample Transaction',
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

test('orange cameroon sms sendSMS includes drCallback when configured', function () {
    Config::set('services.orange_cm.dr_callback', 'https://example.test/dr');
    $setting = makeOrangeSetting();
    $mockClient = Mockery::mock(Client::class);

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(fn (string $method, string $url): bool => $method === 'POST' && str_contains($url, '/accounts/users/login'))
        ->andReturn(new Response(200, [], json_encode(['access_token' => 'tok'])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(function (string $method, string $url, array $options): bool {
            $payload = $options['json'] ?? [];

            return str_contains($url, '/sms/send')
                && ($payload['drCallback'] ?? null) === 'https://example.test/dr';
        })
        ->andReturn(new Response(202, [], json_encode(['statusCode' => 0, 'statusMsg' => 'SUCCESS'])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS(['mobiles' => '+237677001122', 'sms' => 'Hi']);

    expect($response['responsecode'])->toBe(1);
});

test('orange cameroon sms sendSMS fails on non-zero statusCode', function () {
    $setting = makeOrangeSetting();
    $mockClient = Mockery::mock(Client::class);

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(fn (string $method, string $url): bool => str_contains($url, '/accounts/users/login'))
        ->andReturn(new Response(200, [], json_encode(['access_token' => 'tok'])));

    $mockClient->shouldReceive('request')
        ->once()
        ->ordered()
        ->withArgs(fn (string $method, string $url): bool => str_contains($url, '/sms/send'))
        ->andReturn(new Response(202, [], json_encode(['statusCode' => 1, 'statusMsg' => 'FAILED'])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS(['mobiles' => '+237677001122', 'sms' => 'Hi']);

    expect($response['responsecode'])->toBe(0);
    expect($response['error'])->toContain('FAILED');
});

test('orange cameroon sms sendSMS fails for empty sms message', function () {
    $setting = makeOrangeSetting();
    $service = new OrangeCameroonSMS($setting);

    $response = $service->sendSMS([
        'mobiles' => '+237677001122',
        'sms' => '   ',
    ]);

    expect($response['responsecode'])->toBe(0);
    expect($response['error'])->toContain('SMS message cannot be empty');
});

test('orange cameroon sms sendSMS fails when credentials missing', function () {
    $setting = makeOrangeSetting(['sms_provider_username' => '', 'sms_provider_password' => '']);
    $service = new OrangeCameroonSMS($setting);

    $response = $service->sendSMS([
        'mobiles' => '+237677001122',
        'sms' => 'Hello',
    ]);

    expect($response['responsecode'])->toBe(0);
    expect($response['error'])->toContain('login email and password are required');
});

test('orange cameroon getBalance reports unknown balance', function () {
    $setting = makeOrangeSetting();
    $service = new OrangeCameroonSMS($setting);

    $response = $service->getBalance();

    expect($response['responsecode'])->toBe(1);
    expect($response['credit'])->toBeNull();
    expect($response['balance_unknown'])->toBeTrue();
});

afterEach(function () {
    Mockery::close();
});
