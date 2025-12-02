<?php
# See nexah api docs for more info

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Str;
use App\Services\SmsProvider;
use Illuminate\Support\Facades\Log;
use Exception;

class TwilioSMS extends SmsProvider
{
    public function sendSMS(array $data): array
    {
        $response = ['responsecode' => 0];
        try {
            $client = new Client($this->username, $this->password);
            $client->messages->create($data['mobiles'], [
                'from' => $this->senderid,
                'body' => $data['sms']
            ]);

            $response['responsecode'] = 1;

        } catch (Exception $e) {
            Log::error('Twilio SMS sending failed', [
                'error' => $e->getMessage(),
                'phone' => $data['mobiles'] ?? 'unknown',
            ]);
            $response['responsecode'] = 0;
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * Get SMS balance from Twilio
     * Note: Twilio doesn't provide a simple balance API
     * This returns service availability status instead
     *
     * @return array Response with 'responsecode' and 'credit'
     */
    public function getBalance(): array
    {
        // Twilio doesn't have a simple balance check
        // Return service available if credentials are set
        return [
            'responsecode' => !empty($this->username) && !empty($this->password) ? 1 : 0,
            'credit' => !empty($this->username) && !empty($this->password) ? 1 : 0,
        ];
    }
}