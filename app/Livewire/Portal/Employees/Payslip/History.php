<?php

namespace App\Livewire\Portal\Employees\Payslip;

use App\Models\User;
use App\Models\Payslip;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class History extends Component
{
    use WithDataTable;

    public $employee;

    public ?Payslip $payslip;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedPayslips = [];
    public $selectAll = false;

    public function mount($employee_uuid)  
    {
        $this->employee = User::whereUuid($employee_uuid)->first();

    }

    public function initData($payslip_id)
    {
        if(!empty($payslip_id))
        {
            $this->payslip = Payslip::withTrashed()->findOrFail($payslip_id);
        }
    }

    public function downloadPayslip($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);
        
        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->showToast(__('payslips.payslip_file_not_found'), 'danger');
            return;
        }
        
        try {
            return response()->download(
                Storage::disk('modified')->path($payslip->file), 
                $payslip->matricule. "_" . $payslip->year.'_'.$payslip->month.'.pdf', 
                ['Content-Type'=> 'application/pdf']
            );
        } catch (\Exception $e) {
            $this->showToast(__('payslips.unable_to_download_payslip'), 'danger');
        }
    }
    public function resendEmail()
    {
        if (!empty($this->payslip)) {
            $employee = User::findOrFail($this->payslip->employee->id);

        
                if (Storage::disk('modified')->exists($this->payslip->file)) {

                    $destination_file = $this->payslip->file;


                    if (!empty($employee->email)) {

                        try {
                            setSavedSmtpCredentials();

                            Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $this->payslip->month));

                            // Validate if email was actually sent before updating status
                            if (Mail::failures()) {
                                // Reset retry count for manual resend, then schedule automatic retry if enabled
                                $maxRetries = config('ciblerh.email_retry_attempts', 3);
                                $this->payslip->update([
                                    'email_sent_status' => Payslip::STATUS_FAILED,
                                    'email_retry_count' => 0, // Reset retry count for manual resend
                                    'last_email_retry_at' => null,
                                    'failure_reason' => __('payslips.failed_to_send_email_recipient', ['email' => $employee->email])
                                ]);
                                
                                // Schedule automatic retry if retries are enabled
                                if ($maxRetries > 0) {
                                    $retryDelay = config('ciblerh.email_retry_delay', 60);
                                    RetryPayslipEmailJob::dispatch($this->payslip->id)
                                        ->delay(now()->addSeconds($retryDelay));
                                    
                                    $this->payslip->update([
                                        'email_retry_count' => 1,
                                        'last_email_retry_at' => now(),
                                        'failure_reason' => __('payslips.failed_to_send_email_recipient_retry_scheduled', ['email' => $employee->email])
                                    ]);
                                }
                                
                                Log::info('mail-failed: ' . json_encode(Mail::failures()));
                                $message = $maxRetries > 0 
                                    ? __('payslips.email_automatic_retry_scheduled')
                                    : __('payslips.failed_to_send_email');
                                $this->closeModalAndFlashMessage($message, 'resendEmailModal');
                            } else {
                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                                    'email_retry_count' => 0, // Reset retry count on success
                                    'last_email_retry_at' => null,
                                    'failure_reason' => null, // Clear failure reason
                            ]);

                            // sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                            Log::info('mail-sent');
                            auditLog(
                                auth()->user(),
                                'send_sms',
                                'web',
                                'User <a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a> send email to  <a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                            );

                            $this->closeModalAndFlashMessage(__('payslips.email_resent_successfully'), 'resendEmailModal');
                            }

                        } catch (\Swift_TransportException $e) {

                            Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                            Log::info('' . PHP_EOL . '');
                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        } catch (\Swift_RfcComplianceException $e) {
                            Log::info('------> err Swift_Rfc:' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        } catch (Exception $e) {
                            Log::info('------> err' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        }
                    } else {
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => __('payslips.no_valid_email_address')
                        ]);
                    $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                    }
                }
            
        }
    }

    public function resendSMS()
    {
        if(!empty($this->payslip)){

            $setting = Setting::first();

            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                default => new Nexah($setting)
            };

            if($sms_client->getBalance()['credit'] !== 0){
                $employee = User::findOrFail($this->payslip->employee->id);

                if (auth()->user()->hasRole('user') && $employee->group->user_id !== auth()->user()->id) {
                    return abort(401);
                }

                sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                auditLog(
                    auth()->user(),
                    'send_sms',
                    'web',
                    'User <a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a> send sms to  <a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                );
                $this->closeModalAndFlashMessage(__('payslips.sms_sent_successfully', ['user' => $employee->name]), 'resendSMSModal');
    
            }else{

                
                $this->closeModalAndFlashMessage(__('payslips.insufficient_sms_balance'), 'resendSMSModal');
            }
         
        }

    }

    public function delete($payslipId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        try {
            $payslip = $payslipId ? Payslip::findOrFail($payslipId) : $this->payslip;

            if (!empty($payslip)) {
                $payslip->delete(); // Soft delete
                $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_moved_to_trash'), 'DeleteModal');
            } else {
                $this->showToast(__('payslips.payslip_not_found'), 'danger');
            }
        } catch (\Exception $e) {
            $this->showToast(__('payslips.error_deleting_payslip') . $e->getMessage(), 'danger');
        }
        
        $this->reset(['payslip']);
    }

    public function restore($payslipId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $payslip = Payslip::withTrashed()->findOrFail($payslipId);
        $payslip->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($payslipId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        try {
            $payslip = $payslipId ? Payslip::withTrashed()->findOrFail($payslipId) : $this->payslip;

            if (!empty($payslip)) {
                $payslip->forceDelete();
                $this->closeModalAndFlashMessage(__('payslips.payslip_permanently_deleted'), 'ForceDeleteModal');
            } else {
                $this->showToast(__('payslips.payslip_not_found'), 'danger');
            }
        } catch (\Exception $e) {
            $this->showToast(__('payslips.error_deleting_payslip') . $e->getMessage(), 'danger');
        }
        
        $this->reset(['payslip']);
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedPayslips)) {
            Payslip::whereIn('id', $this->selectedPayslips)->delete(); // Soft delete
            $this->selectedPayslips = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedPayslips)) {
            Payslip::withTrashed()->whereIn('id', $this->selectedPayslips)->restore();
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
            Payslip::withTrashed()->whereIn('id', $this->selectedPayslips)->forceDelete();
            $this->selectedPayslips = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPayslips = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPayslips = $this->getPayslips()->pluck('id')->toArray();
        } else {
            $this->selectedPayslips = [];
        }
    }

    public function togglePayslipSelection($payslipId)
    {
        if (in_array($payslipId, $this->selectedPayslips)) {
            $this->selectedPayslips = array_diff($this->selectedPayslips, [$payslipId]);
        } else {
            $this->selectedPayslips[] = $payslipId;
        }
        
        $this->selectAll = count($this->selectedPayslips) === $this->getPayslips()->count();
    }

    private function getPayslips()
    {
        $query = Payslip::search($this->query)->where('employee_id', $this->employee->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        // if (auth()->user()->hasRole('employee') && $this->employee->department->author_id !== auth()->user()->id) {
        //     return abort(401);
        // }
        
        $payslips = $this->getPayslips();

        // Get counts for active payslips (non-deleted)
        $active_payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->whereNull('deleted_at')->count();
        $deleted_payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->withTrashed()->whereNotNull('deleted_at')->count();

        return view('livewire.portal.employees.payslip.history', [
            'payslips' => $payslips,
            'payslips_count' => $active_payslips, // Legacy for backward compatibility
            'active_payslips' => $active_payslips,
            'deleted_payslips' => $deleted_payslips,
        ])->layout('components.layouts.dashboard');
    }
}
