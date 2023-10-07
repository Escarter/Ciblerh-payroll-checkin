<?php
# See nexah api docs for more info

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Str;
use App\Services\SmsProvider;
use Illuminate\Support\Facades\Log;

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

            $response['responsecode'] === 1;

        } catch(Exception $e) {
            Log::error($e->getMessage());
            $response['responsecode'] === 0;
        }
        return $response;
    }
}