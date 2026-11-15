<?php

namespace App\Services;

use App\Mail\SendAnnualDeclaration;
use App\Models\AnnualDeclaration;
use App\Models\Payslip;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AnnualDeclarationNotifier
{
    /**
     * Email the declaration then (on success) send the SMS notification.
     * $force re-sends even when a previous attempt succeeded.
     */
    public function send(AnnualDeclaration $declaration, bool $force = false): void
    {
        if ($declaration->page_status !== AnnualDeclaration::PAGE_MATCHED) {
            return;
        }

        if (!$force && (int) $declaration->email_sent_status === AnnualDeclaration::STATUS_SUCCESSFUL) {
            return;
        }

        $employee = User::find($declaration->employee_id);
        if (!$employee) {
            $this->failEmail($declaration, __('annual_declarations.note_employee_not_found'));
            return;
        }

        if ((int) $declaration->encryption_status !== AnnualDeclaration::STATUS_SUCCESSFUL
            || empty($declaration->file)
            || !Storage::disk('modified')->exists($declaration->file)) {
            $this->failEmail($declaration, __('annual_declarations.note_file_not_ready'));
            return;
        }

        if ($employee->receive_email_notifications === false) {
            $declaration->update([
                'email_sent_status' => AnnualDeclaration::STATUS_DISABLED,
                'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee'),
                'sms_sent_status' => AnnualDeclaration::STATUS_SKIPPED,
                'sms_status_note' => __('payslips.sms_not_attempted_email_disabled'),
            ]);
            return;
        }

        if ($employee->email_bounced) {
            $this->failEmail($declaration, __('payslips.email_address_has_bounced_previously'), ['email_bounced' => true]);
            return;
        }

        $emailTo = !empty($employee->email) ? $employee->email : $employee->alternative_email;
        if (empty($emailTo)) {
            $this->failEmail($declaration, __('payslips.no_valid_email_address'));
            return;
        }

        try {
            setSavedSmtpCredentials();
            Mail::to(cleanString($emailTo))->send(new SendAnnualDeclaration($employee, $declaration));

            $declaration->update([
                'email_sent_status' => AnnualDeclaration::STATUS_SUCCESSFUL,
                'email_delivery_status' => 'sent',
                'email_sent_at' => now(),
                'email_status_note' => null,
                'failure_reason' => null,
            ]);
        } catch (\Throwable $e) {
            $declaration->increment('email_retry_count');
            $this->failEmail($declaration, __('payslips.email_error') . ': ' . $e->getMessage(), ['last_email_retry_at' => now()]);
            return;
        }

        $this->sendSms($declaration, $employee);
    }

    public function sendSms(AnnualDeclaration $declaration, ?User $employee = null): void
    {
        $employee ??= User::find($declaration->employee_id);
        if (!$employee) {
            return;
        }

        if ($employee->receive_sms_notifications === false) {
            $declaration->update([
                'sms_sent_status' => AnnualDeclaration::STATUS_DISABLED,
                'sms_status_note' => __('payslips.sms_notifications_disabled_for_this_employee'),
            ]);
            return;
        }

        $phone = $employee->professional_phone_number ?: $employee->personal_phone_number;
        $setting = Setting::first();

        if (empty($phone)) {
            $declaration->update(['sms_sent_status' => AnnualDeclaration::STATUS_SKIPPED, 'sms_status_note' => __('annual_declarations.note_no_phone')]);
            return;
        }

        if (empty($setting?->sms_provider_username) || empty($setting?->sms_provider_password)) {
            $declaration->update([
                'sms_sent_status' => AnnualDeclaration::STATUS_FAILED,
                'sms_status_note' => __('payslips.sms_provider_credentials_not_configured'),
            ]);
            return;
        }

        try {
            $client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                'orange_cm' => new OrangeCameroonSMS($setting),
                default => new Nexah($setting),
            };

            $locale = $employee->preferred_language === 'en' ? 'en' : 'fr';
            $message = __('annual_declarations.sms_content', [
                'name' => trim($employee->name),
                'year' => $declaration->year,
                'pdf_password' => $employee->pdf_password,
            ], $locale);

            $response = $client->sendSMS(['sms' => $message, 'mobiles' => $phone]);

            if (($response['responsecode'] ?? null) === 1) {
                $txnId = data_get($response, 'body.txnId');
                $clientTxnId = data_get($response, 'body.clientTxnId');
                $declaration->update([
                    'sms_sent_status' => AnnualDeclaration::STATUS_SUCCESSFUL,
                    'sms_sent_at' => now(),
                    'sms_status_note' => null,
                    'sms_txn_id' => $txnId,
                    'sms_client_txn_id' => $clientTxnId,
                    'sms_delivery_status' => $txnId || $clientTxnId ? Payslip::SMS_DELIVERY_STATUS_PENDING : null,
                ]);
            } else {
                $declaration->update([
                    'sms_sent_status' => AnnualDeclaration::STATUS_FAILED,
                    'sms_status_note' => __('payslips.failed_sending_sms'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Annual declaration SMS failed', ['declaration_id' => $declaration->id, 'error' => $e->getMessage()]);
            $declaration->update(['sms_sent_status' => AnnualDeclaration::STATUS_FAILED, 'sms_status_note' => $e->getMessage()]);
        }
    }

    private function failEmail(AnnualDeclaration $declaration, string $reason, array $extra = []): void
    {
        $declaration->update(array_merge([
            'email_sent_status' => AnnualDeclaration::STATUS_FAILED,
            'email_status_note' => $reason,
            'failure_reason' => $reason,
            'sms_sent_status' => AnnualDeclaration::STATUS_SKIPPED,
            'sms_status_note' => __('payslips.sms_not_attempted_email_failed'),
        ], $extra));
    }
}
