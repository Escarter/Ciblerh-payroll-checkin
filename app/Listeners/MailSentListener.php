<?php

namespace App\Listeners;

use App\Models\Payslip;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class MailSentListener
{
    /**
     * Handle the event.
     *
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        // Check if this is a SendPayslip email by looking at the subject or recipients
        $subject = $event->message->getSubject();
        $recipients = $event->message->getTo();

        // Look for payslip emails by subject pattern (contains month and year)
        if (preg_match('/\w+-\d{4}/', $subject)) {
            foreach ($recipients as $recipient) {
                $email = $recipient->getAddress();

                // Find payslips that might be related to this email
                // We need to match by email and potentially by the attachment filename
                $attachments = $event->message->getChildren();
                foreach ($attachments as $attachment) {
                    if ($attachment instanceof \Symfony\Component\Mime\Part\DataPart) {
                        $filename = $attachment->getFilename();

                        // Extract matricule and month-year from filename (format: MATRICULE_MONTH-YEAR.pdf)
                        if (preg_match('/^([^_]+)_(\w+-\d{4})\.pdf$/', $filename, $matches)) {
                            $matricule = $matches[1];
                            $monthYear = $matches[2];

                            // Find the payslip and update its delivery status
                            $payslip = Payslip::whereHas('employee', function($query) use ($matricule, $email) {
                                $query->where('matricule', $matricule)
                                      ->where('email', $email);
                            })
                            ->where('month', $monthYear)
                            ->where('email_sent_status', Payslip::STATUS_SUCCESSFUL) // Only update if marked as sent
                            ->first();

                            if ($payslip) {
                                $payslip->update([
                                    'email_delivery_status' => 'sent',
                                    'email_sent_at' => now(),
                                    'email_delivery_confirmed_at' => now(),
                                ]);

                                Log::info("Email delivery confirmed for payslip ID {$payslip->id} to {$email}");
                            }
                        }
                    }
                }
            }
        }
    }
}