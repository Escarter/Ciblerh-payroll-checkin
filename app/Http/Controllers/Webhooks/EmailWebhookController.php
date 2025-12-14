<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class EmailWebhookController extends Controller
{
    /**
     * Handle Mailgun webhooks
     */
    public function mailgun(Request $request)
    {
        // Verify Mailgun webhook signature if configured
        if ($this->verifyMailgunSignature($request)) {
            $event = $request->input('event-data.event');
            $recipient = $request->input('event-data.recipient');
            $messageId = $request->input('event-data.message.headers.message-id');

            $this->processEmailEvent($event, $recipient, $messageId, 'mailgun');
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle SES webhooks
     */
    public function ses(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if ($payload && isset($payload['Type'])) {
            switch ($payload['Type']) {
                case 'Notification':
                    $message = json_decode($payload['Message'], true);
                    $eventType = $message['eventType'] ?? null;
                    $recipient = $message['mail']['destination'][0] ?? null;

                    if ($eventType && $recipient) {
                        $this->processEmailEvent($eventType, $recipient, null, 'ses', $message);
                    }
                    break;
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Postmark webhooks
     */
    public function postmark(Request $request)
    {
        $events = $request->all();

        foreach ($events as $event) {
            $eventType = $event['RecordType'] ?? null;
            $recipient = $event['Recipient'] ?? null;
            $messageId = $event['MessageID'] ?? null;

            if ($eventType && $recipient) {
                $this->processEmailEvent($eventType, $recipient, $messageId, 'postmark', $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process email events from different providers
     */
    private function processEmailEvent($eventType, $recipient, $messageId = null, $provider = 'unknown', $eventData = null)
    {
        // Map provider event types to our internal status
        $status = $this->mapEventToStatus($eventType, $provider);

        if (!$status) {
            Log::info("Ignoring email event: {$eventType} from {$provider} for {$recipient}");
            return;
        }

        // Find payslip by recipient email and recent sends
        $payslip = Payslip::whereHas('employee', function($query) use ($recipient) {
            $query->where('email', $recipient);
        })
        ->where('email_delivery_status', '!=', Payslip::DELIVERY_STATUS_DELIVERED)
        ->where('email_sent_at', '>', now()->subDays(7)) // Only check recent sends
        ->orderBy('email_sent_at', 'desc')
        ->first();

        if ($payslip) {
            $updateData = [
                'email_delivery_status' => $status,
            ];

            // Set appropriate timestamp
            switch ($status) {
                case Payslip::DELIVERY_STATUS_DELIVERED:
                    $updateData['email_delivered_at'] = now();
                    break;
                case Payslip::DELIVERY_STATUS_BOUNCED:
                    $updateData['email_bounced_at'] = now();
                    $updateData['email_sent_status'] = Payslip::STATUS_FAILED;
                    break;
                case Payslip::DELIVERY_STATUS_COMPLAINED:
                    $updateData['email_bounced_at'] = now();
                    break;
            }

            // Add delivery note
            $updateData['email_delivery_note'] = "Event: {$eventType} from {$provider}";

            $payslip->update($updateData);

            Log::info("Updated payslip {$payslip->id} delivery status to {$status} for {$recipient}");
        } else {
            Log::warning("Could not find payslip for email event: {$eventType} from {$provider} for {$recipient}");
        }
    }

    /**
     * Map provider-specific event types to our internal status
     */
    private function mapEventToStatus($eventType, $provider)
    {
        $mappings = [
            'mailgun' => [
                'delivered' => Payslip::DELIVERY_STATUS_DELIVERED,
                'bounced' => Payslip::DELIVERY_STATUS_BOUNCED,
                'complained' => Payslip::DELIVERY_STATUS_COMPLAINED,
                'unsubscribed' => Payslip::DELIVERY_STATUS_COMPLAINED,
            ],
            'ses' => [
                'Delivery' => Payslip::DELIVERY_STATUS_DELIVERED,
                'Bounce' => Payslip::DELIVERY_STATUS_BOUNCED,
                'Complaint' => Payslip::DELIVERY_STATUS_COMPLAINED,
            ],
            'postmark' => [
                'Delivered' => Payslip::DELIVERY_STATUS_DELIVERED,
                'Bounced' => Payslip::DELIVERY_STATUS_BOUNCED,
                'SpamComplaint' => Payslip::DELIVERY_STATUS_COMPLAINED,
            ],
        ];

        return $mappings[$provider][$eventType] ?? null;
    }

    /**
     * Verify Mailgun webhook signature
     */
    private function verifyMailgunSignature(Request $request)
    {
        $apiKey = config('services.mailgun.secret');
        if (!$apiKey) {
            return true; // Skip verification if not configured
        }

        $signature = $request->header('X-Mailgun-Signature');
        $timestamp = $request->header('X-Mailgun-Timestamp');
        $token = $request->header('X-Mailgun-Token');

        if (!$signature || !$timestamp || !$token) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $token, $apiKey);

        return hash_equals($expectedSignature, $signature);
    }
}