<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\User;
use App\Models\Payslip;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Jobs\RetryPayslipEmailJob;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use App\Services\SMS\TwilioSMS;
use App\Services\SMS\Nexah;
use App\Services\SMS\AwsSnsSMS;

class Details extends Component
{
    use WithDataTable;

    public $job;
    public ?Payslip $payslip;
    public ?int $payslip_id = null;
    public ?Payslip $selectedPayslip = null;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedPayslips = [];
    public $selectAll = false;

    // Unmatched employees tab
    public $showUnmatched = false;

    public function mount($id)
    {
        $this->job = SendPayslipProcess::findOrFail($id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($this->job->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
    }

    public function initData($payslip_id)
    {
        if (!empty($payslip_id)) {
            $this->payslip = Payslip::findOrFail($payslip_id);
        }
    }

    public function showPayslipDetails($payslip_id)
    {
        $payslip = Payslip::with('sendProcess.department')->findOrFail($payslip_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $this->selectedPayslip = $payslip;
        $this->dispatch('open-modal', 'payslipDetailsModal');
    }

    public function closePayslipDetailsModal()
    {
        $this->selectedPayslip = null;
    }

    public function refreshTaskData()
    {
        // Refresh the selected payslip data
        if ($this->selectedPayslip) {
            $this->selectedPayslip->refresh();
        }
    }

    public function downloadPayslip($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }

        if (!Storage::disk("modified")->exists($payslip->file)) {
            $this->dispatch("flash-message-error", message: __('payslips.payslip_file_not_found'));
            return;
        }

        return Storage::disk("modified")->download(
            $payslip->file,
            $payslip->matricule . "_" . $payslip->year . "_" . $payslip->month . ".pdf",
            ["Content-Type" => "application/pdf"]
        );
    }

    public function resendPayslip()
    {
        if (!empty($this->payslip)) {
            // Validate supervisor access
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                if (!in_array($this->payslip->department_id, $validDepartmentIds)) {
                    abort(403, __('common.unauthorized_department_access'));
                }
            }
            
            // Check if already successfully sent
            if ($this->payslip->email_sent_status === Payslip::STATUS_SUCCESSFUL && $this->payslip->sms_sent_status === Payslip::STATUS_SUCCESSFUL) {
                $this->closeModalAndFlashMessage(__('payslips.payslip_already_sent_successfully'), 'resendPayslipModal');
                return;
            }

            $employee = User::findOrFail($this->payslip->employee->id);


            if (Storage::disk('modified')->exists($this->payslip->file)) {

                $destination_file = $this->payslip->file;


                if (!empty($employee->email)) {

                    try {
                        setSavedSmtpCredentials();

                        Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $this->payslip->month));

                        // Email accepted by mail server - delivery will be confirmed via webhooks
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                            'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                            'email_sent_at' => now(),
                            'email_retry_count' => 0, // Reset retry count on success
                            'last_email_retry_at' => null,
                            'failure_reason' => null, // Clear failure reason
                        ]);

                        // Check SMS balance for optimization
                        $sms_balance = null;
                        $setting = Setting::first();
                        if (!empty($setting->sms_provider)) {
                            $sms_client = match ($setting->sms_provider) {
                                'twilio' => new TwilioSMS($setting),
                                'nexah' => new Nexah($setting),
                                'aws_sns' => new AwsSnsSMS($setting),
                                default => new Nexah($setting)
                            };

                            try {
                                $sms_balance = $sms_client->getBalance();
                            } catch (\Exception $e) {
                                Log::warning('Failed to check SMS balance in resend payslip: ' . $e->getMessage());
                            }
                        }

                        sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip, $sms_balance);

                        Log::info('mail-sent');

                        $this->closeModalAndFlashMessage(__('payslips.employee_payslip_resent_successfully'), 'resendPayslipModal');
                    } catch (\Swift_TransportException $e) {

                        Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                        Log::info('' . PHP_EOL . '');
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);

                    } catch (\Swift_RfcComplianceException $e) {
                        Log::info('------> err Swift_Rfc:' . $e->getMessage());
                        Log::info('' . PHP_EOL . '');

                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);
                    } catch (\Exception $e) {
                        Log::info('------> err' . $e->getMessage());
                        Log::info('' . PHP_EOL . '');

                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);
                    }
                } else {
                    $this->payslip->update([
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => __('payslips.no_valid_email_address')
                    ]);
                }
            }
        }
    }

    public function delete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->payslip)) {
            // Validate supervisor access
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                if (!in_array($this->payslip->department_id, $validDepartmentIds)) {
                    abort(403, __('common.unauthorized_department_access'));
                }
            }
            
            $this->payslip->delete(); // Soft delete
            $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_moved_to_trash'), 'DeleteModal');
        }
    }

    public function restore($payslip_id = null)
    {
        if (!Gate::allows('payslip-restore')) {
            return abort(401);
        }

        $payslip_id = $payslip_id ?? $this->payslip_id;
        $payslip = Payslip::withTrashed()->findOrFail($payslip_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $payslip->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($payslip_id = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $payslip_id = $payslip_id ?? $this->payslip_id;
        $payslip = Payslip::withTrashed()->findOrFail($payslip_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $payslip->forceDelete();

        $this->closeModalAndFlashMessage(__('payslips.payslip_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedPayslips)) {
            $query = Payslip::whereIn('id', $this->selectedPayslips);
            
            // Validate supervisor access - only allow deleting payslips from their departments
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $query->delete(); // Soft delete
            $this->selectedPayslips = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedPayslips)) {
            $query = Payslip::withTrashed()->whereIn('id', $this->selectedPayslips);
            
            // Validate supervisor access - only allow restoring payslips from their departments
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $query->restore();
            $this->selectedPayslips = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedPayslips)) {
            $query = Payslip::withTrashed()->whereIn('id', $this->selectedPayslips);
            
            // Validate supervisor access - only allow force deleting payslips from their departments
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $query->forceDelete();
            $this->selectedPayslips = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function bulkResendFailed()
    {
        if (!Gate::allows('payslip-bulkresend-email') || !Gate::allows('payslip-bulkresend-sms')) {
            return abort(401);
        }

        // Get all failed payslips for this process
        $failedPayslips = Payslip::where('send_payslip_process_id', $this->job->id)
            ->where('email_sent_status', Payslip::STATUS_FAILED)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL) // Only resend if encryption succeeded
            ->get();

        if ($failedPayslips->isEmpty()) {
            $this->closeModalAndFlashMessage(__('payslips.no_failed_payslips_found_to_resend'), 'BulkResendFailedModal');
            return;
        }

        $resendCount = 0;
        $skippedCount = 0;

        foreach ($failedPayslips as $payslip) {
            // Skip if already successfully sent (double check in case status changed)
            if ($payslip->email_sent_status === Payslip::STATUS_SUCCESSFUL && $payslip->sms_sent_status === Payslip::STATUS_SUCCESSFUL) {
                $skippedCount++;
                continue;
            }

            $employee = User::find($payslip->employee_id);

            if (empty($employee) || empty($employee->email)) {
                $skippedCount++;
                continue;
            }

            if (!Storage::disk('modified')->exists($payslip->file)) {
                $skippedCount++;
                continue;
            }

            try {
                setSavedSmtpCredentials();

                Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $payslip->file, $payslip->month));

                // Email accepted by mail server - delivery will be confirmed via webhooks
                $payslip->update([
                    'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                    'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                    'email_sent_at' => now(),
                    'email_retry_count' => 0,
                    'last_email_retry_at' => null,
                    'failure_reason' => null,
                ]);

                    // Check SMS balance for optimization
                    $sms_balance = null;
                    $setting = Setting::first();
                    if (!empty($setting->sms_provider)) {
                        $sms_client = match ($setting->sms_provider) {
                            'twilio' => new TwilioSMS($setting),
                            'nexah' => new Nexah($setting),
                            'aws_sns' => new AwsSnsSMS($setting),
                            default => new Nexah($setting)
                        };

                        try {
                            $sms_balance = $sms_client->getBalance();
                        } catch (\Exception $e) {
                            Log::warning('Failed to check SMS balance in bulk resend: ' . $e->getMessage());
                        }
                    }

                    sendSmsAndUpdateRecord($employee, $payslip->month, $payslip, $sms_balance);
                    $resendCount++;
                }catch (\Exception $e) {
                Log::error('Bulk resend failed for payslip', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
                $skippedCount++;
            }
        }

        $message = __('payslips.bulk_resend_completed', [
            'resend' => $resendCount,
            'skipped' => $skippedCount
        ]);

        $this->closeModalAndFlashMessage($message, 'BulkResendFailedModal');
    }

    public function bulkResendFailedEmails()
    {
        if (!Gate::allows('payslip-bulkresend-email')) {
            return abort(401);
        }

        if (empty($this->selectedPayslips)) {
            $this->closeModalAndFlashMessage(__('payslips.no_payslips_selected'), 'BulkResendFailedEmailsModal');
            return;
        }

        // Get selected payslips that have successful encryption
        $eligiblePayslips = Payslip::whereIn('id', $this->selectedPayslips)
            ->where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->get();

        if ($eligiblePayslips->isEmpty()) {
            $this->closeModalAndFlashMessage(__('payslips.no_eligible_selected_payslips_for_email_resend'), 'BulkResendFailedEmailsModal');
            return;
        }

        $resendCount = 0;
        $skippedCount = 0;

        foreach ($eligiblePayslips as $payslip) {
            $employee = User::find($payslip->employee_id);

            if (empty($employee) || empty($employee->email)) {
                $skippedCount++;
                continue;
            }

            if (!Storage::disk('modified')->exists($payslip->file)) {
                $skippedCount++;
                continue;
            }

            try {
                setSavedSmtpCredentials();

                Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $payslip->file, $payslip->month));

                // Email accepted by mail server - delivery will be confirmed via webhooks
                $payslip->update([
                    'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                    'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                    'email_sent_at' => now(),
                    'email_retry_count' => 0,
                    'last_email_retry_at' => null,
                    'failure_reason' => null,
                ]);

                $resendCount++;
            } catch (\Exception $e) {
                Log::error('Bulk email resend failed for payslip', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
                $skippedCount++;
            }
        }

        $message = __('payslips.bulk_email_resend_completed', [
            'resend' => $resendCount,
            'skipped' => $skippedCount
        ]);

        $this->closeModalAndFlashMessage($message, 'BulkResendFailedEmailsModal');
    }

    public function bulkResendFailedSms()
    {
        if (!Gate::allows('payslip-bulkresend-sms')) {
            return abort(401);
        }

        if (empty($this->selectedPayslips)) {
            $this->closeModalAndFlashMessage(__('payslips.no_payslips_selected'), 'BulkResendFailedSmsModal');
            return;
        }

        // Get selected payslips that have successful encryption
        $eligiblePayslips = Payslip::whereIn('id', $this->selectedPayslips)
            ->where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->get();

        if ($eligiblePayslips->isEmpty()) {
            $this->closeModalAndFlashMessage(__('payslips.no_eligible_selected_payslips_for_sms_resend'), 'BulkResendFailedSmsModal');
            return;
        }

        $resendCount = 0;
        $skippedCount = 0;

        foreach ($eligiblePayslips as $payslip) {
            $employee = User::find($payslip->employee_id);

            if (empty($employee) || empty($employee->phone)) {
                $skippedCount++;
                continue;
            }

            if (!Storage::disk('modified')->exists($payslip->file)) {
                $skippedCount++;
                continue;
            }

            try {
                // Check SMS balance for optimization
                $sms_balance = null;
                $setting = Setting::first();
                if (!empty($setting->sms_provider)) {
                    $sms_client = match ($setting->sms_provider) {
                        'twilio' => new TwilioSMS($setting),
                        'nexah' => new Nexah($setting),
                        'aws_sns' => new AwsSnsSMS($setting),
                        default => new Nexah($setting)
                    };

                    try {
                        $sms_balance = $sms_client->getBalance();
                    } catch (\Exception $e) {
                        Log::warning('Failed to check SMS balance in bulk SMS resend: ' . $e->getMessage());
                    }
                }

                sendSmsAndUpdateRecord($employee, $payslip->month, $payslip, $sms_balance);
                $resendCount++;
            } catch (\Exception $e) {
                Log::error('Bulk SMS resend failed for payslip', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
                $skippedCount++;
            }
        }

        $message = __('payslips.bulk_sms_resend_completed', [
            'resend' => $resendCount,
            'skipped' => $skippedCount
        ]);

        $this->closeModalAndFlashMessage($message, 'BulkResendFailedSmsModal');
    }

    public function getFailedEmailsCount()
    {
        return Payslip::where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->count();
    }

    public function getFailedSmsCount()
    {
        return Payslip::where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->count();
    }

    public function getSelectedEligibleEmailsCount()
    {
        if (empty($this->selectedPayslips)) {
            return 0;
        }

        return Payslip::whereIn('id', $this->selectedPayslips)
            ->where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->count();
    }

    public function getSelectedEligibleSmsCount()
    {
        if (empty($this->selectedPayslips)) {
            return 0;
        }

        return Payslip::whereIn('id', $this->selectedPayslips)
            ->where('send_payslip_process_id', $this->job->id)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->count();
    }

    public function getFailedPayslipsCount()
    {
        return Payslip::where('send_payslip_process_id', $this->job->id)
            ->where('email_sent_status', Payslip::STATUS_FAILED)
            ->whereNotNull('file')
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            ->count();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPayslips = [];
        $this->selectAll = false;
        $this->showUnmatched = false;
    }
    
    public function toggleUnmatched()
    {
        $this->showUnmatched = !$this->showUnmatched;
        if ($this->showUnmatched) {
            $this->activeTab = 'active'; // Reset to active tab when showing unmatched
        }
    }
    
    public function getUnmatchedEmployees()
    {
        // Get all payslips for this process that have encryption_status = FAILED
        // and failure_reason contains "not found"
        return Payslip::where('send_payslip_process_id', $this->job->id)
            ->where('encryption_status', Payslip::STATUS_FAILED)
            ->where(function($query) {
                $query->where('failure_reason', 'like', '%not found%')
                      ->orWhere('failure_reason', 'like', '%Matricule%not found%');
            })
            ->when(!empty($this->query), function ($q) {
                $q->where(function ($query) {
                    $query->where('first_name', 'like', '%' . $this->query . '%')
                          ->orWhere('last_name', 'like', '%' . $this->query . '%')
                          ->orWhere('email', 'like', '%' . $this->query . '%')
                          ->orWhere('matricule', 'like', '%' . $this->query . '%');
                });
            })
            ->orderBy($this->orderBy, $this->orderAsc)
            ->paginate($this->perPage);
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Select only the payslips visible on the current page
            $this->selectedPayslips = $this->getPayslips()->pluck('id')->toArray();
        } else {
            $this->selectedPayslips = [];
        }

        // Update selectAll state based on whether all visible (paginated) payslips are selected
        $visiblePayslipIds = $this->getPayslips()->pluck('id')->toArray();
        $this->selectAll = !empty($visiblePayslipIds) && count(array_intersect($this->selectedPayslips, $visiblePayslipIds)) === count($visiblePayslipIds);
    }

    public function selectAllVisible()
    {
        $this->selectedPayslips = $this->getAllPayslips()->pluck('id')->toArray();
    }

    public function selectAllPayslips()
    {
        $query = Payslip::where('send_payslip_process_id', $this->job->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        $this->selectedPayslips = $query->pluck('id')->toArray();
    }

    public function togglePayslipSelection($payslipId)
    {
        if (in_array($payslipId, $this->selectedPayslips)) {
            $this->selectedPayslips = array_diff($this->selectedPayslips, [$payslipId]);
        } else {
            $this->selectedPayslips[] = $payslipId;
        }

        // Update selectAll state based on whether all visible (paginated) payslips are selected
        $visiblePayslipIds = $this->getPayslips()->pluck('id')->toArray();
        $this->selectAll = !empty($visiblePayslipIds) && count(array_intersect($this->selectedPayslips, $visiblePayslipIds)) === count($visiblePayslipIds);
    }

    private function getPayslips()
    {
        // Start with base query and apply soft delete filtering first
        $query = Payslip::query()->where('send_payslip_process_id', $this->job->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Apply search filtering after soft delete logic
        if (!empty($this->query)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->query . '%');
                $q->orWhere('last_name', 'like', '%' . $this->query . '%');
                $q->orWhere('email', 'like', '%' . $this->query . '%');
                $q->orWhere('matricule', 'like', '%' . $this->query . '%');
                $q->orWhere('phone', 'like', '%' . $this->query . '%');
                $q->orWhere('month', 'like', '%' . $this->query . '%');
                $q->orWhere('email_sent_status', 'like', '%' . $this->query . '%');
                $q->orWhere('sms_sent_status', 'like', '%' . $this->query . '%');
            });
        }

        // Apply role-based filtering
        if (auth()->user()->getRoleNames()->first() === "supervisor") {
            $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    private function getAllPayslips()
    {
        // Start with base query and apply soft delete filtering first
        $query = Payslip::query()->where('send_payslip_process_id', $this->job->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Apply search filtering after soft delete logic
        if (!empty($this->query)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->query . '%');
                $q->orWhere('last_name', 'like', '%' . $this->query . '%');
                $q->orWhere('email', 'like', '%' . $this->query . '%');
                $q->orWhere('matricule', 'like', '%' . $this->query . '%');
                $q->orWhere('phone', 'like', '%' . $this->query . '%');
                $q->orWhere('month', 'like', '%' . $this->query . '%');
                $q->orWhere('email_sent_status', 'like', '%' . $this->query . '%');
                $q->orWhere('sms_sent_status', 'like', '%' . $this->query . '%');
            });
        }

        // Apply role-based filtering
        if (auth()->user()->getRoleNames()->first() === "supervisor") {
            $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->get();
    }

    public function render()
    {
        $payslips = $this->getPayslips();

        // Get counts using the same logic as getPayslips but without pagination
        $active_payslips = $this->getPayslipsCount('active');
        $deleted_payslips = $this->getPayslipsCount('deleted');

        $unmatchedEmployees = $this->showUnmatched ? $this->getUnmatchedEmployees() : null;
        $unmatchedCount = Payslip::where('send_payslip_process_id', $this->job->id)
            ->where('encryption_status', Payslip::STATUS_FAILED)
            ->where(function($query) {
                $query->where('failure_reason', 'like', '%not found%')
                      ->orWhere('failure_reason', 'like', '%Matricule%not found%');
            })
            ->count();

        $totalEmployees = $this->job->payslips()->count();

        return view('livewire.portal.payslips.details', [
            'payslips' => $payslips,
            'payslips_count' => count($this->job->payslips), // Legacy for backward compatibility
            'active_payslips' => $active_payslips,
            'deleted_payslips' => $deleted_payslips,
            'job' => $this->job,
            'unmatchedEmployees' => $unmatchedEmployees,
            'unmatchedCount' => $unmatchedCount,
            'totalEmployees' => $totalEmployees
        ])->layout('components.layouts.dashboard');
    }

    private function getPayslipsCount($tab)
    {
        // Start with base query
        $query = Payslip::query()->where('send_payslip_process_id', $this->job->id);

        // Add soft delete filtering based on tab
        if ($tab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Apply search filtering if query exists
        if (!empty($this->query)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->query . '%');
                $q->orWhere('last_name', 'like', '%' . $this->query . '%');
                $q->orWhere('email', 'like', '%' . $this->query . '%');
                $q->orWhere('matricule', 'like', '%' . $this->query . '%');
                $q->orWhere('phone', 'like', '%' . $this->query . '%');
                $q->orWhere('month', 'like', '%' . $this->query . '%');
                $q->orWhere('email_sent_status', 'like', '%' . $this->query . '%');
                $q->orWhere('sms_sent_status', 'like', '%' . $this->query . '%');
            });
        }

        // Apply role-based filtering
        if (auth()->user()->getRoleNames()->first() === "supervisor") {
            $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->count();
    }

    public function getTranslatedFailureReason($failureReason)
    {
        if (empty($failureReason)) {
            return '';
        }

        // Translate the "Email/SMS skipped: Encryption failed." prefix
        $encryptionSkippedPrefix = __('payslips.encryption_failed_email_sms_skipped');
        
        // Check if failure reason starts with the encryption skipped prefix (in any language)
        // We need to check both English and French versions
        $englishPrefix = 'Email/SMS skipped: Encryption failed. ';
        $frenchPrefix = 'Email/SMS ignoré: Échec du cryptage. ';
        
        $remainingReason = $failureReason;
        $hasPrefix = false;
        
        if (strpos($failureReason, $englishPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($englishPrefix));
            $hasPrefix = true;
        } elseif (strpos($failureReason, $frenchPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($frenchPrefix));
            $hasPrefix = true;
        } elseif (strpos($failureReason, $encryptionSkippedPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($encryptionSkippedPrefix));
            $hasPrefix = true;
        }
        
        // Translate the remaining reason if it matches known patterns
        $translatedReason = $remainingReason;
        
        // Helper function to normalize month name to English for translation
        $normalizeMonthName = function($monthName) {
            $monthName = trim($monthName);
            // French to English month mapping
            $frenchToEnglish = [
                'janvier' => 'January',
                'février' => 'February',
                'mars' => 'March',
                'avril' => 'April',
                'mai' => 'May',
                'juin' => 'June',
                'juillet' => 'July',
                'août' => 'August',
                'septembre' => 'September',
                'octobre' => 'October',
                'novembre' => 'November',
                'décembre' => 'December',
            ];
            
            $lowerMonth = mb_strtolower($monthName);
            if (isset($frenchToEnglish[$lowerMonth])) {
                return $frenchToEnglish[$lowerMonth];
            }
            // If it's already in English or not found, return as-is
            return $monthName;
        };
        
        // Pattern: "Matricule :matricule not found in any PDF file for month :month"
        // English pattern
        if (preg_match('/Matricule\s+([A-Z0-9]+)\s+not found in any PDF file for month\s+(.+)/i', $remainingReason, $matches)) {
            $matricule = $matches[1];
            $monthName = trim($matches[2]);
            // Normalize month name to English, then translate to current locale
            $normalizedMonth = $normalizeMonthName($monthName);
            $translatedMonth = translateMonthName($normalizedMonth);
            $translatedReason = __('payslips.matricule_not_found_in_pdf', [
                'matricule' => $matricule,
                'month' => $translatedMonth
            ]);
        }
        // French pattern: "Matricule :matricule introuvable dans tout fichier PDF pour le mois :month"
        elseif (preg_match('/Matricule\s+([A-Z0-9]+)\s+introuvable dans tout fichier PDF pour le mois\s+(.+)/i', $remainingReason, $matches)) {
            $matricule = $matches[1];
            $monthName = trim($matches[2]);
            // Normalize month name to English, then translate to current locale
            $normalizedMonth = $normalizeMonthName($monthName);
            $translatedMonth = translateMonthName($normalizedMonth);
            $translatedReason = __('payslips.matricule_not_found_in_pdf', [
                'matricule' => $matricule,
                'month' => $translatedMonth
            ]);
        }
        // Also handle "User matricule is empty" pattern
        elseif (preg_match('/User matricule is empty/i', $remainingReason) || 
                preg_match('/Le matricule de l\'utilisateur est vide/i', $remainingReason)) {
            $translatedReason = __('payslips.user_matricule_empty');
        }
        // Handle SMS provider error messages
        elseif (preg_match('/SMS provider credentials are not configured/i', $remainingReason) ||
                preg_match('/Les identifiants du fournisseur SMS ne sont pas configurés/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_credentials_not_configured');
        }
        elseif (preg_match('/SMS provider not configured/i', $remainingReason) ||
                preg_match('/Fournisseur SMS non configuré/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_not_configured');
        }
        elseif (preg_match('/SMS provider initialization failed/i', $remainingReason) ||
                preg_match('/Échec d\'initialisation du fournisseur SMS/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_initialization_failed');
        }
        elseif (preg_match('/SMS provider balance check failed for (.+): (.+)/i', $remainingReason, $matches) ||
                preg_match('/Échec de vérification du solde SMS pour (.+): (.+)/i', $remainingReason, $matches)) {
            $translatedReason = __('payslips.sms_provider_balance_check_failed', [
                'provider' => trim($matches[1]),
                'error' => trim($matches[2])
            ]);
        }
        elseif (preg_match('/SMS provider unhealthy during balance check/i', $remainingReason) ||
                preg_match('/Fournisseur SMS défaillant lors de la vérification du solde/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_unhealthy_during_balance_check');
        }
        elseif (preg_match('/SMS provider is unhealthy/i', $remainingReason) ||
                preg_match('/Le fournisseur SMS est défaillant/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_unhealthy');
        }
        elseif (preg_match('/SMS sending exception/i', $remainingReason) ||
                preg_match('/Exception d\'envoi SMS/i', $remainingReason)) {
            // Extract the error message after the colon if present
            if (preg_match('/SMS sending exception:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Exception d\'envoi SMS:\s*(.+)/i', $remainingReason, $matches)) {
                $translatedReason = __('payslips.sms_sending_exception') . ': ' . trim($matches[1]);
            } else {
                $translatedReason = __('payslips.sms_sending_exception');
            }
        }
        elseif (preg_match('/SMS sending failed with unexpected error/i', $remainingReason) ||
                preg_match('/Échec d\'envoi SMS avec erreur inattendue/i', $remainingReason)) {
            // Extract the error message after the colon if present
            if (preg_match('/SMS sending failed with unexpected error:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Échec d\'envoi SMS avec erreur inattendue:\s*(.+)/i', $remainingReason, $matches)) {
                $translatedReason = __('payslips.sms_unexpected_error') . ': ' . trim($matches[1]);
            } else {
                $translatedReason = __('payslips.sms_unexpected_error');
            }
        }
        elseif (preg_match('/No valid phone number for user/i', $remainingReason) ||
                preg_match('/Aucun numéro de téléphone valide pour l\'utilisateur/i', $remainingReason)) {
            $translatedReason = __('payslips.no_valid_phone_number_for_user');
        }
        elseif (preg_match('/Failed sending SMS/i', $remainingReason) ||
                preg_match('/Échec d\'envoi de SMS/i', $remainingReason)) {
            $translatedReason = __('payslips.failed_sending_sms');
        }
        elseif (preg_match('/Insufficient SMS Balance/i', $remainingReason) ||
                preg_match('/Solde SMS insuffisant/i', $remainingReason)) {
            $translatedReason = __('payslips.insufficient_sms_balance');
        }
        elseif (preg_match('/SMS provider configuration error/i', $remainingReason) ||
                preg_match('/Erreur de configuration du fournisseur SMS/i', $remainingReason)) {
            // Check if there's a specific error message after
            if (preg_match('/SMS provider configuration error:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Erreur de configuration du fournisseur SMS:\s*(.+)/i', $remainingReason, $matches)) {
                $errorDetail = trim($matches[1]);
                // Check if it's the null value error
                if (preg_match('/Null value in provider configuration/i', $errorDetail) ||
                    preg_match('/Valeur nulle dans la configuration du fournisseur/i', $errorDetail)) {
                    $translatedReason = __('payslips.sms_provider_configuration_error') . ': ' . __('payslips.null_value_in_provider_config');
                } else {
                    $translatedReason = __('payslips.sms_provider_configuration_error') . ': ' . $errorDetail;
                }
            } else {
                $translatedReason = __('payslips.sms_provider_configuration_error');
            }
        }
        
        // Return the translated prefix + translated reason if prefix exists, otherwise just the translated reason
        return $hasPrefix ? $encryptionSkippedPrefix . $translatedReason : $translatedReason;
    }

    public function getPayslipOverallStatus($payslip)
    {
        // If encryption failed, overall status is failed
        if ($payslip->encryption_status == Payslip::STATUS_FAILED) {
            return 'failed';
        }

        // If encryption is still processing, overall status is processing
        if ($payslip->encryption_status == 0) {
            return 'processing';
        }

        // If email failed and SMS failed/disabled, overall status is failed
        if ($payslip->email_sent_status == Payslip::STATUS_FAILED &&
            ($payslip->sms_sent_status == Payslip::STATUS_FAILED || $payslip->sms_sent_status == Payslip::STATUS_DISABLED)) {
            return 'failed';
        }

        // If both email and SMS succeeded, or if email succeeded and SMS is disabled/failed but email is priority
        if ($payslip->email_sent_status == Payslip::STATUS_SUCCESSFUL) {
            return 'success';
        }

        // If SMS succeeded but email failed, still consider it successful (SMS fallback)
        if ($payslip->sms_sent_status == Payslip::STATUS_SUCCESSFUL && $payslip->email_sent_status == Payslip::STATUS_FAILED) {
            return 'success';
        }

        // If both are still pending/processing
        if (($payslip->email_sent_status == 0 || $payslip->email_sent_status == null) &&
            ($payslip->sms_sent_status == 0 || $payslip->sms_sent_status == null || $payslip->sms_sent_status == Payslip::STATUS_DISABLED)) {
            return 'processing';
        }

        // Default to processing for any other cases
        return 'processing';
    }
}
