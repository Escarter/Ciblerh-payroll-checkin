<?php
# See nexah api docs for more info

namespace App\Services;

use App\Models\Company;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

abstract class SmsProvider {

    protected string $username;
    protected string $password;
    protected string $senderid;

    public function __construct(Setting $setting)
    {
        $this->username = $setting->sms_provider_username;
        $this->password = $setting->sms_provider_password;
        $this->senderid = $setting->sms_provider_senderid;
    }
    
    abstract protected function sendSMS(array $data): array;
}