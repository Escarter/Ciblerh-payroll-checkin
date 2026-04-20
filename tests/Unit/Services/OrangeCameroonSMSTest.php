<?php

use App\Models\Setting;
use App\Services\OrangeCameroonSMS;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.orange_cm.api_url', 'https://api.orange.com');
    config()->set('services.orange_cm.token_url', 'https://api.orange.com/oauth/v3/token');
    config()->set('services.orange_cm.sms_endpoint', '/smsmessaging/v1/outbound/{senderAddress}/requests');
    config()->set('services.orange_cm.contracts_endpoint', '/sms/admin/v1/contracts');
    config()->set('services.orange_cm.country', 'CMR');
    config()->set('services.orange_cm.default_country_code', '237');
});

function makeOrangeSetting(array $overrides = []): Setting
{
    return Setting::factory()->create(array_merge([
        'sms_provider' => 'orange_cm',
        'sms_provider_username' => 'client-id',
        'sms_provider_password' => 'client-secret',
        'sms_provider_senderid' => '+237699000001',
    ], $overrides));
}

test('orange cameroon sms sendSMS succeeds', function () {
    $setting = makeOrangeSetting();
    $service = new OrangeCameroonSMS($setting);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->with('POST', 'https://api.orange.com/oauth/v3/token', Mockery::on(function (array $options): bool {
            return ($options['form_params']['grant_type'] ?? null) === 'client_credentials';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'token-123',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->with('POST', Mockery::on(function (string $url): bool {
            return str_contains($url, '/smsmessaging/v1/outbound/tel%3A%2B237699000001/requests');
        }), Mockery::on(function (array $options): bool {
            $payload = $options['json']['outboundSMSMessageRequest'] ?? [];
            return ($payload['address'] ?? null) === 'tel:+237677001122'
                && ($payload['senderAddress'] ?? null) === 'tel:+237699000001'
                && ($payload['outboundSMSTextMessage']['message'] ?? null) === 'Hello';
        }))
        ->andReturn(new Response(201, [], json_encode([
            'outboundSMSMessageRequest' => [
                'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+237699000001/requests/abc',
            ],
        ])));

    $serviceMock = Mockery::mock(OrangeCameroonSMS::class, [$setting])->makePartial();
    $serviceMock->shouldAllowMockingProtectedMethods();
    $serviceMock->shouldReceive('makeHttpClient')->andReturn($mockClient);

    $response = $serviceMock->sendSMS([
        'mobiles' => '0677001122',
        'sms' => 'Hello',
    ]);

    expect($response['responsecode'])->toBe(1);
    expect($response['resource_url'])->not->toBeNull();
});

test('orange cameroon getBalance sums available units', function () {
    $setting = makeOrangeSetting();

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'token-123',
            'expires_in' => 3600,
        ])));

    $mockClient->shouldReceive('request')
        ->once()
        ->with('GET', 'https://api.orange.com/sms/admin/v1/contracts', Mockery::on(function (array $options): bool {
            return ($options['query']['country'] ?? null) === 'CMR';
        }))
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

afterEach(function () {
    Mockery::close();
});
