<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\User;
use App\Models\Payslip;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Details extends Component
{
    use WithDataTable;

    public $job;
    public ?Payslip $payslip;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedPayslips = [];
    public $selectAll = false;

    public function mount($id)
    {
        $this->job = SendPayslipProcess::findOrFail($id);
    }

    public function initData($payslip_id)
    {
        if (!empty($payslip_id)) {
            $this->payslip = Payslip::findOrFail($payslip_id);
        }
    }

    public function resendPayslip()
    {
        if (!empty($this->payslip)) {
            $employee = User::findOrFail($this->payslip->employee->id);


            if (Storage::disk('modified')->exists($this->payslip->file)) {

                $destination_file = $this->payslip->file;


                if (!empty($employee->email)) {

                    try {
                        setSavedSmtpCredentials();

                        Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $this->payslip->month));

                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                        ]);

                        sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                        Log::info('mail-sent');

                        $this->closeModalAndFlashMessage(__('Employee Payslip resent successfully'), 'resendPayslipModal');
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
                        'failure_reason' => __('No valid email address for User')
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
            $this->payslip->delete(); // Soft delete
            $this->closeModalAndFlashMessage(__('Payslip successfully moved to trash!'), 'DeleteModal');
        }
    }

    public function restore($payslipId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $payslip = Payslip::withTrashed()->findOrFail($payslipId);
        $payslip->restore();

        $this->closeModalAndFlashMessage(__('Payslip successfully restored!'), 'RestoreModal');
    }

    public function forceDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->payslip)) {
            $this->payslip->forceDelete();
            $this->closeModalAndFlashMessage(__('Payslip permanently deleted!'), 'ForceDeleteModal');
        }
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

        $this->closeModalAndFlashMessage(__('Selected payslips moved to trash!'), 'BulkDeleteModal');
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

        $this->closeModalAndFlashMessage(__('Selected payslips restored!'), 'BulkRestoreModal');
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

        $this->closeModalAndFlashMessage(__('Selected payslips permanently deleted!'), 'BulkForceDeleteModal');
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
            $this->selectedPayslips = $this->getAllPayslips()->pluck('id')->toArray();
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
        
        $this->selectAll = count($this->selectedPayslips) === $this->getAllPayslips()->count();
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

        return view('livewire.portal.payslips.details', [
            'payslips' => $payslips,
            'payslips_count' => count($this->job->payslips), // Legacy for backward compatibility
            'active_payslips' => $active_payslips,
            'deleted_payslips' => $deleted_payslips,
            'job' => $this->job
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
}
