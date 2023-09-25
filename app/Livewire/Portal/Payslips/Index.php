<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\Company;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Gate;
use App\Jobs\Plan\PayslipSendingPlan;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    public $companies = [];
    public $departments = [];
    public $company_id, $department_id, $month, $payslip_file;

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
        $this->companies = match (auth()->user()->getRoleNames()->first()) {
            'manager' => Company::manager()->orderBy('created_at', 'desc')->get(),
            'admin' => Company::orderBy('created_at', 'desc')->get(),
            'supervisor' => [],
            default => [],
        };

        $this->departments =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => [],
            'supervisor' => Department::whereIn('id', auth()->user()->supDepartments->pluck('department_id'))->get(),
            'admin' => [],
            default => [],
        };
        
    }

    public function updatedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = Department::where('company_id', $company_id)->get();
        }
    }

    public function send()
    {
        if (!Gate::allows('payslip-create')) {
            return abort(401);
        }

        $this->validate([
            'department_id' => 'required',
            'month' => 'required',
            'payslip_file' => 'required|mimes:pdf'
        ]);

        $raw_file_path = $this->payslip_file->store(auth()->user()->id, 'raw');

        $choosen_department = Department::findOrFail($this->department_id);

        $raw_file = Storage::disk('raw')->path($raw_file_path);

        $splitted_disk = Storage::disk('splitted');
        $modified_disk = Storage::disk('modified');

        $destination_directory = Str::random(20);


        if (countPages(Storage::disk('raw')->path($raw_file_path)) > config('ciblerh.max_payslip_pages')) {
            session()->flash('error',__('File uploaded needs to have ' . config('ciblerh.max_payslip_pages') . ' pages maximum'));
            return $this->redirect(route('portal.payslips.index'), navigate: true);
        }

        $existing = SendPayslipProcess::where('department_id', $this->department_id)->where('month', $this->month)->where('year', now()->year)->first();

        if (empty($existing)) {
            $payslip_process =
                SendPayslipProcess::create([
                    'user_id' => auth()->user()->id,
                    'company_id' => auth()->user()->company_id,
                    'department_id' => $this->department_id,
                    'author_id' => auth()->user()->id,
                    'raw_file' => $raw_file,
                    'destination_directory' => $destination_directory,
                    'month' => $this->month,
                    'year' => now()->year,
                    'batch_id' => ''
                ]);
        } else {
            $existing->update(['status' => 'processing', 'batch_id' => '']);
            $payslip_process = $existing;
        }

        PayslipSendingPlan::start($payslip_process);

        auditLog(
            auth()->user(),
            'payslip_sending',
            'web',
            'User <a href="/portal/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a> initiated the sending of payslip to department <strong>' . $choosen_department->name . '</strong> for the month of ' . $this->month . '-' . now()->year . '<a href="/admin/payslips">Go to Playslips details</a>'
        );

        session()->flash('message', __('Job started to process list and file uploaded check the status on the table!'));
        return $this->redirect(route('portal.payslips.index'), navigate: true);
    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

       $jobs =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->orderBy('created_at', 'desc')->get()->take(20),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->get()->take(20),
            'admin' => SendPayslipProcess::orderBy('created_at', 'desc')->get()->take(20),
            default => [],
        };

        return view('livewire.portal.payslips.index', compact('jobs'))->layout('components.layouts.dashboard');
    }
}
