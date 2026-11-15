<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Handle SES webhooks (delivered via AWS SNS).
     *
     * SNS always sends a SubscriptionConfirmation first.
     * We must GET the SubscribeURL it provides or deliveries will never arrive.
     */
    public function ses(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if ($payload && isset($payload['Type'])) {
            switch ($payload['Type']) {
                case 'SubscriptionConfirmation':
                    $subscribeUrl = $payload['SubscribeURL'] ?? null;
                    if ($subscribeUrl) {
                        try {
                            Http::get($subscribeUrl);
                            Log::info('SES/SNS subscription confirmed', [
                                'topic_arn' => $payload['TopicArn'] ?? 'unknown',
                            ]);
                        } catch (\Throwable $e) {
                            Log::error('SES/SNS subscription confirmation failed', [
                                'error'         => $e->getMessage(),
                                'subscribe_url' => $subscribeUrl,
                                'topic_arn'     => $payload['TopicArn'] ?? 'unknown',
                            ]);
                        }
                    }
                    break;

                case 'Notification':
                    $message = json_decode($payload['Message'], true);
                    $eventType = $message['eventType'] ?? null;
                    $recipient = $message['mail']['destination'][0] ?? null;

                    if ($eventType && $recipient) {
                        $this->processEmailEvent($eventType, $recipient, null, 'ses', $message);
                    }
                    break;

                case 'UnsubscribeConfirmation':
                    Log::info('SES/SNS unsubscribe confirmation received', [
                        'topic_arn' => $payload['TopicArn'] ?? 'unknown',
                    ]);
                    break;
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Postmark webhooks.
     *
     * Postmark sends one JSON object per request, not an array.
     * Each object has a top-level RecordType field (e.g. "Delivery", "Bounce").
     */
    public function postmark(Request $request)
    {
        $event     = $request->all();
        $eventType = $event['RecordType'] ?? null;
        $recipient = $event['Recipient'] ?? null;
        $messageId = $event['MessageID'] ?? null;

        if ($eventType && $recipient) {
            $this->processEmailEvent($eventType, $recipient, $messageId, 'postmark', $event);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Mailchimp Transactional (Mandrill) webhooks
     */
    public function mailchimp(Request $request)
    {
        if (!$this->verifyMailchimpSignature($request)) {
            Log::warning('Mailchimp webhook signature verification failed');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $eventsRaw = $request->input('mandrill_events', '[]');
        $events = json_decode($eventsRaw, true) ?: [];

        foreach ($events as $event) {
            $eventType = $event['event'] ?? null;
            $msg       = $event['msg'] ?? [];
            $recipient = $msg['email'] ?? null;
            $messageId = $msg['_id'] ?? null;

            if ($eventType && $recipient) {
                $this->processEmailEvent($eventType, $recipient, $messageId, 'mailchimp', $event);
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

        // Find payslip by recipient email (primary or alternative) and recent sends
        $payslip = Payslip::whereHas('employee', function($query) use ($recipient) {
            $query->where('email', $recipient)
                  ->orWhere('alternative_email', $recipient);
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
                'Delivered'    => Payslip::DELIVERY_STATUS_DELIVERED,
                'Bounced'      => Payslip::DELIVERY_STATUS_BOUNCED,
                'SpamComplaint' => Payslip::DELIVERY_STATUS_COMPLAINED,
            ],
            'mailchimp' => [
                // Mandrill has no true "delivered" event; hard/soft bounce and spam are trackable
                'hard_bounce'  => Payslip::DELIVERY_STATUS_BOUNCED,
                'soft_bounce'  => Payslip::DELIVERY_STATUS_BOUNCED,
                'spam'         => Payslip::DELIVERY_STATUS_COMPLAINED,
                'reject'       => Payslip::DELIVERY_STATUS_BOUNCED,
            ],
        ];

        return $mappings[$provider][$eventType] ?? null;
    }

    /**
     * Verify Mailchimp Transactional (Mandrill) webhook signature.
     *
     * Mandrill signs using HMAC-SHA1 where the signed string is:
     * webhook_url + sorted POST key/value pairs concatenated.
     */
    private function verifyMailchimpSignature(Request $request): bool
    {
        $apiKey = \App\Models\Setting::value('mailchimp_api_key');
        if (!$apiKey) {
            return true; // Skip verification if not configured
        }

        $signature = $request->header('X-Mandrill-Signature');
        if (!$signature) {
            return false;
        }

        $webhookUrl  = $request->url();
        $postParams  = $request->post();
        ksort($postParams);

        $signedString = $webhookUrl;
        foreach ($postParams as $key => $value) {
            $signedString .= $key . $value;
        }

        $expectedSignature = base64_encode(hash_hmac('sha1', $signedString, $apiKey, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Mailgun webhook signature.
     *
     * Mailgun v3 webhooks embed signature data in the JSON body under the
     * "signature" key — NOT in HTTP headers.
     * Signing key = Mailgun Webhook Signing Key (stored in settings or env).
     * Docs: https://documentation.mailgun.com/docs/mailgun/user-manual/webhooks/#webhook-security
     */
    private function verifyMailgunSignature(Request $request): bool
    {
        // Prefer the key saved in settings; fall back to environment
        $signingKey = \App\Models\Setting::value('mailgun_secret') ?: config('services.mailgun.secret');
        if (!$signingKey) {
            return true; // Skip verification if not configured
        }

        // All three fields live in the JSON body, not in HTTP headers
        $timestamp = $request->input('signature.timestamp');
        $token     = $request->input('signature.token');
        $signature = $request->input('signature.signature');

        if (!$timestamp || !$token || !$signature) {
            Log::warning('Mailgun webhook missing signature fields in request body');
            return false;
        }

        // Reject requests older than 10 minutes to prevent replay attacks
        if (abs(time() - (int) $timestamp) > 600) {
            Log::warning('Mailgun webhook timestamp too old (possible replay)', [
                'timestamp' => $timestamp,
            ]);
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $token, $signingKey);

        return hash_equals($expectedSignature, $signature);
    }
}