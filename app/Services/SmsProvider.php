<?php
# See nexah api docs for more info

namespace App\Services;

use App\Models\Company;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

abstract class SmsProvider {

    protected ?string $username;
    protected ?string $password;
    protected ?string $senderid;

    public function __construct(Setting $setting)
    {
        $this->username = $setting->sms_provider_username;
        $this->password = $setting->sms_provider_password;
        $this->senderid = $setting->sms_provider_senderid;
    }
    
    abstract protected function sendSMS(array $data): array;

    /**
     * Get SMS balance/credit from provider
     * Default implementation returns service available status
     * Override in providers that support balance checking
     *
     * @return array Response with 'responsecode' and 'credit'
     */
    public function getBalance(): array
    {
        // Default: assume service is available if credentials are set
        return [
            'responsecode' => !empty($this->username) && !empty($this->password) ? 1 : 0,
            'credit' => !empty($this->username) && !empty($this->password) ? 1 : 0,
        ];
    }
}