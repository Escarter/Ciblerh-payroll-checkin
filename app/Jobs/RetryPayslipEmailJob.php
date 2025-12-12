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
        $payslip = Payslip::find($this->payslip_id);
        
        if (empty($payslip)) {
            Log::warning('RetryPayslipEmailJob: Payslip not found', ['payslip_id' => $this->payslip_id]);
            return;
        }

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
        if (isset($employee->receive_email_notifications) && !$employee->receive_email_notifications) {
            Log::info('RetryPayslipEmailJob: Email notifications disabled', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $employee->id
            ]);
            
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_DISABLED,
                'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee')
            ]);
            return;
        }

        // Check if email has bounced
        if ($employee->email_bounced) {
            Log::warning('RetryPayslipEmailJob: Email previously bounced', [
                'payslip_id' => $this->payslip_id,
                'employee_id' => $employee->id
            ]);
            
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
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
            
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_failed_no_valid_email')
            ]);
            return;
        }

        try {
            setSavedSmtpCredentials();

            Mail::to(cleanString($emailToUse))->send(new SendPayslip($employee, $payslip->file, $payslip->month));

            // Validate if email was actually sent
            if (Mail::failures()) {
                $failures = Mail::failures();
                
                // Check if this is a bounce
                $isBounce = $this->detectEmailBounce($failures, $emailToUse);
                
                if ($isBounce['is_bounce']) {
                    // Mark email as bounced
                    $employee->update([
                        'email_bounced' => true,
                        'email_bounced_at' => now(),
                        'email_bounce_reason' => $isBounce['reason']
                    ]);
                    
                    $payslip->update([
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'email_bounced' => true,
                        'email_bounced_at' => now(),
                        'email_bounce_reason' => $isBounce['reason'],
                        'email_bounce_type' => $isBounce['type'] ?? 'hard',
                        'failure_reason' => __('payslips.email_bounced', ['reason' => $isBounce['reason']])
                    ]);
                    
                    Log::warning('RetryPayslipEmailJob: Email bounced', [
                        'payslip_id' => $this->payslip_id,
                        'email' => $emailToUse,
                        'bounce_reason' => $isBounce['reason']
                    ]);
                    
                    return; // Don't retry bounced emails
                }
                
                $maxRetries = config('ciblerh.email_retry_attempts', 3);
                $currentRetryCount = $payslip->email_retry_count ?? 0;
                
                if ($currentRetryCount < $maxRetries) {
                    // Schedule another retry
                    $retryDelay = config('ciblerh.email_retry_delay', 60) * pow(2, $currentRetryCount);
                    
                    $existingReason = $payslip->failure_reason ?? '';
                    $payslip->update([
                        'email_retry_count' => $currentRetryCount + 1,
                        'last_email_retry_at' => now(),
                        'failure_reason' => $existingReason . ' | ' . __('payslips.retry_attempt_failed_with_next', [
                            'retry' => $currentRetryCount,
                            'next' => $currentRetryCount + 1,
                            'max' => $maxRetries
                        ])
                    ]);
                    
                    RetryPayslipEmailJob::dispatch($payslip->id)
                        ->delay(now()->addSeconds($retryDelay));
                    
                    Log::info('RetryPayslipEmailJob: Email retry failed, scheduling next retry', [
                        'payslip_id' => $this->payslip_id,
                        'retry_count' => $currentRetryCount + 1,
                        'max_retries' => $maxRetries,
                        'delay_seconds' => $retryDelay,
                        'failures' => Mail::failures()
                    ]);
                } else {
                    // Max retries reached
                    $existingReason = $payslip->failure_reason ?? '';
                    $payslip->update([
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => $existingReason . ' | ' . __('payslips.retry_attempt_failed_after_max', [
                            'max' => $maxRetries
                        ])
                    ]);
                    
                    Log::warning('RetryPayslipEmailJob: Email retry limit reached', [
                        'payslip_id' => $this->payslip_id,
                        'retry_count' => $currentRetryCount,
                        'max_retries' => $maxRetries,
                        'failures' => Mail::failures()
                    ]);
                }
            } else {
                // Success! Update status and send SMS
                $payslip->update([
                    'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                    'email_retry_count' => 0, // Reset retry count on success
                    'last_email_retry_at' => null,
                    'failure_reason' => null // Clear failure reason on success
                ]);

                sendSmsAndUpdateRecord($employee, $payslip->month, $payslip);

                Log::info('RetryPayslipEmailJob: Email retry successful', [
                    'payslip_id' => $this->payslip_id,
                    'retry_count' => $payslip->email_retry_count
                ]);
            }
        } catch (\Swift_TransportException $e) {
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_error', ['error' => $e->getMessage()])
            ]);
            
            Log::error('RetryPayslipEmailJob: Swift Transport Exception', [
                'payslip_id' => $this->payslip_id,
                'error' => $e->getMessage()
            ]);
        } catch (\Swift_RfcComplianceException $e) {
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_rfc_error', ['error' => $e->getMessage()])
            ]);
            
            Log::error('RetryPayslipEmailJob: Swift RFC Exception', [
                'payslip_id' => $this->payslip_id,
                'error' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            $existingReason = $payslip->failure_reason ?? '';
            $payslip->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . ' | ' . __('payslips.retry_error', ['error' => $e->getMessage()])
            ]);
            
            Log::error('RetryPayslipEmailJob: General Exception', [
                'payslip_id' => $this->payslip_id,
                'error' => $e->getMessage()
            ]);
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
