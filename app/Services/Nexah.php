<?php
# See nexah api docs for more info

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Str;
use App\Services\SmsProvider;
use Illuminate\Support\Facades\Log;

class Nexah extends SmsProvider
{
        
    public function sendSMS(array $data): array
    {
        try {
            $response = $this->client('get', 'sendsms', [
                'user' => $this->username,
                'password' => $this->password,
                'senderid' => $this->senderid,
                'sms' => $data['sms'],
                'mobiles' => $data['mobiles'],
                'scheduletime' => isset($data['scheduletime']) ?? now(),
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw $th;
        }
        return $response;
    }

    public function getBalance(): array
    {
        try {
            $reponse = $this->client('post', 'smscredit',  [
                'user' => $this->username,
                'password' => $this->password,
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw $th;
        }
        return $reponse;
    }

    protected function client(string $method, string $route, array $params = []): ?array
    {
        $response = (new Client)->{$method}(config('services.nexah.api_url') . ltrim($route, '/'), [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'=> 'application/json',
            ],
            'json' => $params,
            'timeout' => 20,
        ]);
        return json_decode((string) $response->getBody(), true);
    }
}