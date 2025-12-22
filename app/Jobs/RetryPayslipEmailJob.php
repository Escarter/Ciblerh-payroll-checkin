<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\User;
use App\Mail\SendPayslip;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;

class RetryPayslipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Only try once per retry job instance

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    protected $payslip_id;

    /**
     * Create a new job instance.
     */
    public function __construct($payslip_id)
    {
        $this->payslip_id = $payslip_id;
        $this->queue = 'high-priority';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('RetryPayslipEmailJob: Starting execution', [
            'payslip_id' => $this->payslip_id,
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'queue' => $this->queue ?? 'unknown'
        ]);

        $payslip = Payslip::find($this->payslip_id);
        
        if (empty($payslip)) {
            Log::warning('RetryPayslipEmailJob: Payslip not found', ['payslip_id' => $this->payslip_id]);
            return;
        }

        Log::info('RetryPayslipEmailJob: Payslip found', [
            'payslip_id' => $this->payslip_id,
            'email_sent_status' => $payslip->email_sent_status,
            'email_retry_count' => $payslip->email_retry_count,
            'encryption_status' => $payslip->encryption_status
        ]);

        // Don't retry if encryption failed - no point
        if ($payslip->encryption_status === Payslip::STATUS_FAILED) {
            Log::info('RetryPayslipEmailJob: Skipping retry - encryption failed', [
                'payslip_id' => $this->payslip_id
            ]);
            return;
        }

        // Don't retry if already successful
        if ($payslip->email_sent_status === Payslip::STATUS_SUCCESSFUL) {
            Log::info('RetryPayslipEmailJob: Skipping retry - email already sent', [
                'payslip_id' => $this->payslip_id
            ]);
            return;
        }

        // Check if file exists
        if (empty($payslip->file) || !Storage::disk('modified')->exists($payslip->file)) {
            Log::warning('RetryPayslipEmailJob: File not found', [
                'payslip_id' => $this->payslip_id,
                'file' => $payslip->file
            ]);
            
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_failed_payslip_not_found')
            ]);
            return;
        }

        $employee = User::find($payslip->employee_id);
        
        if (empty($employee)) {
            Log::warning('RetryPayslipEmailJob: Employee not found', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $payslip->employee_id
            ]);
            
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_failed_employee_not_found')
            ]);
            return;
        }

        // Check if employee has email notifications enabled
        // Refresh employee to ensure we have the latest notification preferences
        $employee->refresh();
        if ($employee->receive_email_notifications === false) {
            Log::info('RetryPayslipEmailJob: Email notifications disabled', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $employee->id
            ]);
            
            // Update SMS status to skipped with clear message (SMS not attempted when email notifications disabled)
            $smsStatusNote = __('payslips.sms_not_attempted_email_disabled');
            
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_DISABLED,
                'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee'),
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote
            ]);
            return;
        }

        // Check if email has bounced
        if ($employee->email_bounced) {
            Log::warning('RetryPayslipEmailJob: Email previously bounced', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $employee->id
            ]);
            
            // Update SMS status to skipped with clear message (SMS not attempted when email bounces)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'email_bounced' => true,
                'failure_reason' => __('payslips.email_bounced_update_address')
            ]);
            return;
        }

        // Use alternative email if primary email is empty
        $emailToUse = !empty($employee->email) ? $employee->email : $employee->alternative_email;
        
        if (empty($emailToUse)) {
            Log::warning('RetryPayslipEmailJob: No valid email address', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $payslip->employee_id
            ]);

            // Update SMS status to skipped with clear message (SMS not attempted when no email)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            $existingReason = $payslip->failure_reason ?? '';
            
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_failed_no_valid_email')
            ]);
            return;
        }

        Log::info('RetryPayslipEmailJob: Attempting to send email', [
            'payslip_id' => $this->payslip_id,
            'email' => $emailToUse,
            'employee_id' => $employee->id,
            'current_retry_count' => $payslip->email_retry_count
        ]);

        try {
            setSavedSmtpCredentials();

            Mail::to(cleanString($emailToUse))->send(new SendPayslip($employee, $payslip->file, $payslip->month));

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
                'email_retry_count' => 0, // Reset retry count on success
                'last_email_retry_at' => null,
                'failure_reason' => null // Clear failure reason on success
            ]);

            sendSmsAndUpdateRecord($employee, $payslip->month, $payslip);

            Log::info('RetryPayslipEmailJob: Email retry successful', [
                'payslip_id' => $this->payslip_id,
                'retry_count' => $payslip->email_retry_count
            ]);
        } catch (\Swift_TransportException $e) {
            $this->handleRetryFailure($e, 'Swift Transport Exception');
        } catch (\Swift_RfcComplianceException $e) {
            $this->handleRetryFailure($e, 'Swift RFC Exception', true);
        } catch (Exception $e) {
            $this->handleRetryFailure($e, 'General Exception');
        }
    }

    /**
     * Handle retry failure and schedule next retry if max retries not reached
     */
    private function handleRetryFailure($exception, $exceptionType, $isRfcError = false)
    {
        // Reload payslip from database to ensure we have latest data
        $payslip = Payslip::find($this->payslip_id);
        if (empty($payslip)) {
            Log::error("RetryPayslipEmailJob: Payslip not found during exception handling", [
                'payslip_id' => $this->payslip_id
            ]);
            return;
        }

        $maxRetries = config('ciblerh.email_retry_attempts', 3);
        $currentRetryCount = $payslip->email_retry_count ?? 0;

        // Update SMS status to skipped with clear message (SMS not attempted when email retry fails)
        $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
        $existingReason = $payslip->failure_reason ?? '';
        
        $errorMessage = $isRfcError 
            ? __('payslips.retry_rfc_error', ['error' => $exception->getMessage()])
            : __('payslips.retry_error', ['error' => $exception->getMessage()]);

        if ($currentRetryCount < $maxRetries) {
            // Schedule next retry
            $nextRetryCount = $currentRetryCount + 1;
            $retryDelay = config('ciblerh.email_retry_delay', 60) * pow(2, $currentRetryCount);
            
            // Use direct database update to ensure persistence
            Payslip::where('id', $this->payslip_id)->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'email_retry_count' => $nextRetryCount,
                'last_email_retry_at' => now(),
                'failure_reason' => $existingReason . ' | ' . $errorMessage . '. ' . __('payslips.retry_scheduled', [
                    'error' => $exception->getMessage(),
                    'retry' => $nextRetryCount,
                    'max' => $maxRetries
                ])
            ]);

            // Schedule next retry job
            RetryPayslipEmailJob::dispatch($this->payslip_id)
                ->delay(now()->addSeconds($retryDelay));

            Log::warning("RetryPayslipEmailJob: {$exceptionType} - Scheduling retry {$nextRetryCount}/{$maxRetries}", [
                'payslip_id' => $this->payslip_id,
                'error' => $exception->getMessage(),
                'current_retry_count' => $currentRetryCount,
                'next_retry_count' => $nextRetryCount,
                'max_retries' => $maxRetries,
                'retry_delay_seconds' => $retryDelay
            ]);
        } else {
            // Max retries reached - mark as failed permanently
            Payslip::where('id', $this->payslip_id)->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => $existingReason . ' | ' . ($isRfcError 
                    ? __('payslips.email_rfc_error_after_max_retries', ['max' => $maxRetries, 'error' => $exception->getMessage()])
                    : __('payslips.email_error_after_max_retries', ['max' => $maxRetries, 'error' => $exception->getMessage()])
                )
            ]);

            Log::error("RetryPayslipEmailJob: {$exceptionType} - Max retries ({$maxRetries}) reached", [
                'payslip_id' => $this->payslip_id,
                'error' => $exception->getMessage(),
                'retry_count' => $currentRetryCount,
                'max_retries' => $maxRetries
            ]);
        }

        // Verify the update was successful
        $payslip = Payslip::find($this->payslip_id);
        if ($payslip && $payslip->sms_sent_status !== Payslip::STATUS_SKIPPED) {
            Log::warning('RetryPayslipEmailJob: SMS status update failed - retrying with model update', [
                'payslip_id' => $this->payslip_id,
                'expected_status' => Payslip::STATUS_SKIPPED,
                'actual_status' => $payslip->sms_sent_status
            ]);
            // Fallback: try model update
            $payslip->sms_sent_status = Payslip::STATUS_SKIPPED;
            $payslip->sms_status_note = $smsStatusNote;
            $payslip->save();
        }
    }

    /**
     * Detect if email failure is a bounce
     * 
     * @param array $failures
     * @param string $email
     * @return array
     */
    private function detectEmailBounce($failures, $email)
    {
        // Check if email is in failures array
        if (!in_array($email, $failures)) {
            return ['is_bounce' => false];
        }

        // Common bounce indicators
        $reason = __('payslips.email_invalid_or_does_not_exist');
        $type = 'hard'; // Assume hard bounce by default

        return [
            'is_bounce' => true,
            'reason' => $reason,
            'type' => $type
        ];
    }
}