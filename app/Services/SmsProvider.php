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
        // Ensure we never assign null to nullable string properties
        // Convert null/empty to empty string to avoid type errors
        $this->username = !empty($setting->sms_provider_username) ? (string) $setting->sms_provider_username : '';
        $this->password = !empty($setting->sms_provider_password) ? (string) $setting->sms_provider_password : '';
        $this->senderid = !empty($setting->sms_provider_senderid) ? (string) $setting->sms_provider_senderid : '';
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