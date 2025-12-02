<?php

namespace App\Services;

use Aws\Sns\SnsClient;
use App\Models\Setting;
use App\Services\SmsProvider;
use Illuminate\Support\Facades\Log;
use Exception;

class AwsSnsSMS extends SmsProvider
{
    protected ?SnsClient $client = null;
    protected string $region;

    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
        $this->region = config('services.sns.region', config('services.ses.region', 'us-east-1'));
    }

    /**
     * Get or create SNS client instance
     */
    protected function getClient(): SnsClient
    {
        if ($this->client === null) {
            $this->client = new SnsClient([
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->username,
                    'secret' => $this->password,
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Send SMS via AWS SNS
     *
     * @param array $data Contains 'sms' (message) and 'mobiles' (phone number)
     * @return array Response with 'responsecode' (1 for success, 0 for failure)
     */
    public function sendSMS(array $data): array
    {
        $response = ['responsecode' => 0];

        try {
            // Validate phone number before formatting
            if (empty($data['mobiles'])) {
                throw new Exception('Phone number is required');
            }

            $phoneNumber = $this->formatPhoneNumber($data['mobiles']);
            
            // Validate formatted phone number
            if (!$this->isValidE164Format($phoneNumber)) {
                throw new Exception('Invalid phone number format. Phone number must be in E.164 format (e.g., +1234567890)');
            }

            $message = $data['sms'] ?? '';
            if (empty($message)) {
                throw new Exception('SMS message cannot be empty');
            }

            $client = $this->getClient();

            $result = $client->publish([
                'PhoneNumber' => $phoneNumber,
                'Message' => $message,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType' => 'String',
                        'StringValue' => 'Transactional',
                    ],
                ],
            ]);

            if (isset($result['MessageId'])) {
                $response['responsecode'] = 1;
                $response['message_id'] = $result['MessageId'];
                Log::info('AWS SNS SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'message_id' => $result['MessageId'],
                ]);
            }
        } catch (Exception $e) {
            Log::error('AWS SNS SMS sending failed', [
                'error' => $e->getMessage(),
                'phone' => $data['mobiles'] ?? 'unknown',
                'formatted_phone' => $phoneNumber ?? 'unknown',
            ]);
            $response['responsecode'] = 0;
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * Get SMS balance/attributes from AWS SNS
     * Note: AWS SNS doesn't have a simple balance check like other providers
     * This method returns account attributes instead
     *
     * @return array Response with 'responsecode' and 'credit' (always 1 for AWS SNS)
     */
    public function getBalance(): array
    {
        $response = ['responsecode' => 0, 'credit' => 0];

        try {
            $client = $this->getClient();

            // Get account attributes to verify credentials
            $result = $client->getSMSAttributes();

            // If we can get attributes, credentials are valid
            if (isset($result['attributes'])) {
                $response['responsecode'] = 1;
                // AWS SNS doesn't have a balance concept, so we return 1 to indicate service is available
                $response['credit'] = 1;
                $response['attributes'] = $result['attributes'];
            }
        } catch (Exception $e) {
            Log::error('AWS SNS balance check failed', [
                'error' => $e->getMessage(),
            ]);
            $response['responsecode'] = 0;
            $response['credit'] = 0;
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * Format phone number for AWS SNS
     * AWS SNS requires E.164 format (e.g., +1234567890)
     * E.164 format: +[country code][subscriber number]
     * Total length: 1-15 digits after the + sign
     *
     * @param string $phoneNumber
     * @return string Formatted phone number in E.164 format
     * @throws Exception If phone number cannot be formatted
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        if (empty(trim($phoneNumber))) {
            throw new Exception('Phone number cannot be empty');
        }

        // Remove all whitespace and common formatting characters, keep only digits and +
        $phoneNumber = preg_replace('/[\s\-\(\)\.]/', '', $phoneNumber);
        
        // Remove all non-numeric characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If phone already starts with +, validate and clean it
        if (str_starts_with($phoneNumber, '+')) {
            // Get digits after +
            $digits = substr($phoneNumber, 1);
            if (empty($digits) || !ctype_digit($digits)) {
                throw new Exception('Invalid phone number format after + sign');
            }
            
            // Check if country code starts with 0 (invalid in E.164)
            if (str_starts_with($digits, '0')) {
                throw new Exception('Country code cannot start with 0 in E.164 format');
            }
            
            // Remove leading zeros (these are typically trunk prefixes that should be removed in E.164)
            // This handles cases like +2370123456789 -> +237123456789
            $originalDigits = $digits;
            $digits = ltrim($digits, '0');
            
            // Ensure we still have digits after removing zeros
            if (empty($digits)) {
                throw new Exception('Phone number must contain at least one non-zero digit');
            }
            
            // If removing zeros changed the first digit significantly, it might indicate an issue
            // But we'll allow it as trunk prefix removal is common
            return '+' . $digits;
        }

        // Remove leading zeros for numbers without + prefix
        $phoneNumber = ltrim($phoneNumber, '0');
        if (empty($phoneNumber)) {
            throw new Exception('Phone number cannot be all zeros');
        }

        // Handle phone numbers without country code
        // Try to use senderid as country code if available
        $countryCode = null;
        if (!empty($this->senderid) && is_numeric($this->senderid)) {
            // If senderid is a short numeric string (1-3 digits), treat as country code
            if (strlen($this->senderid) <= 3 && ctype_digit($this->senderid)) {
                $countryCode = $this->senderid;
            }
        }

        // If no country code from senderid, try to detect from phone number
        if ($countryCode === null) {
            // Common country codes (1-3 digits)
            // This is a basic detection - you might want to enhance this based on your use case
            $countryCode = $this->detectCountryCode($phoneNumber);
        }

        // If we have a country code, prepend it
        if ($countryCode !== null) {
            $phoneNumber = '+' . $countryCode . $phoneNumber;
        } else {
            // If no country code detected, just add + prefix
            // AWS SNS will attempt to send, but may fail if country code is required
            $phoneNumber = '+' . $phoneNumber;
        }

        // Final validation: ensure we have digits after +
        $digits = substr($phoneNumber, 1);
        if (empty($digits) || !ctype_digit($digits)) {
            throw new Exception('Phone number must contain only digits after country code');
        }

        return $phoneNumber;
    }

    /**
     * Detect country code from phone number
     * This is a basic implementation - enhance based on your specific needs
     *
     * @param string $phoneNumber Phone number without + prefix
     * @return string|null Detected country code or null
     */
    protected function detectCountryCode(string $phoneNumber): ?string
    {
        // Common country codes for reference (you may want to expand this)
        $commonCountryCodes = [
            '1' => ['US', 'CA'], // North America
            '33' => ['FR'],      // France
            '44' => ['GB'],      // UK
            '237' => ['CM'],     // Cameroon
            '225' => ['CI'],     // Côte d'Ivoire
            '226' => ['BF'],     // Burkina Faso
            '229' => ['BJ'],     // Benin
            '242' => ['CG'],     // Republic of the Congo
            '243' => ['CD'],     // DR Congo
            '236' => ['CF'],     // Central African Republic
        ];

        // If phone number starts with a known country code, return it
        foreach ($commonCountryCodes as $code => $countries) {
            if (str_starts_with($phoneNumber, $code)) {
                // Verify it's actually a country code (not part of local number)
                // This is a simple check - you might want more sophisticated logic
                $remainingDigits = substr($phoneNumber, strlen($code));
                if (strlen($remainingDigits) >= 7 && strlen($remainingDigits) <= 12) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Validate phone number is in E.164 format
     * E.164 format: +[country code][subscriber number]
     * Rules:
     * - Must start with +
     * - Must contain only digits after +
     * - Total length: 1-15 digits after the + sign
     * - Minimum: +1 (country code + at least 1 digit)
     * - Maximum: + followed by 15 digits
     *
     * @param string $phoneNumber Phone number to validate
     * @return bool True if valid E.164 format, false otherwise
     */
    protected function isValidE164Format(string $phoneNumber): bool
    {
        // Must start with +
        if (!str_starts_with($phoneNumber, '+')) {
            return false;
        }

        // Get digits after +
        $digits = substr($phoneNumber, 1);

        // Must contain only digits
        if (empty($digits) || !ctype_digit($digits)) {
            return false;
        }

        // E.164 allows 1-15 digits after the +
        $digitCount = strlen($digits);
        if ($digitCount < 1 || $digitCount > 15) {
            return false;
        }

        // Additional validation: country code should be 1-3 digits typically
        // But we allow up to 15 as per E.164 spec
        return true;
    }
}

