<?php

namespace App\Livewire\Portal\AnnualDeclarations;

use App\Jobs\Plan\AnnualDeclarationSendingPlan;
use App\Livewire\Traits\WithDataTable;
use App\Models\Company;
use App\Models\Department;
use App\Models\SendAnnualDeclarationProcess;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    use WithDataTable;

    public $company_id;
    public $department_id;
    public $year;
    public $declaration_file;
    public $companies = [];
    public $departments = [];
    public string $role = '';

    public function mount()
    {
        if (!Gate::allows('payslip-read')) {
            abort(401);
        }

        $user = auth()->user();
        $this->role = (string) $user->getRoleNames()->first();
        $this->year = now()->year - 1;

        $this->companies = match ($this->role) {
            'manager' => Company::manager()->orderBy('name')->get(),
            'admin' => Company::orderBy('name')->get(),
            default => [],
        };

        $this->departments = $this->role === 'supervisor'
            ? Department::whereIn('id', $user->supDepartments->pluck('department_id'))->get()
            : [];
    }

    public function updatedCompanyId($companyId)
    {
        $this->departments = $companyId ? Department::where('company_id', $companyId)->get() : [];
        $this->department_id = null;
    }

    public function send()
    {
        if (!Gate::allows('payslip-create')) {
            abort(401);
        }

        $this->validate([
            'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
            'declaration_file' => 'required|mimes:pdf',
            'department_id' => $this->role === 'supervisor' ? 'required' : 'nullable',
        ]);

        $user = auth()->user();
        $companyId = $this->company_id ?: $user->company_id;

        if ($this->role === 'supervisor' && !in_array((int) $this->department_id, $user->supDepartments->pluck('department_id')->map(fn ($id) => (int) $id)->all(), true)) {
            return $this->showToast(__('common.unauthorized_department_access'), 'danger');
        }

        if ($this->role === 'manager' && !$user->managerCompanies->pluck('id')->contains((int) $companyId)) {
            return $this->showToast(__('common.unauthorized_access'), 'danger');
        }

        $setting = Setting::first();
        if (empty($setting) || (empty($setting->smtp_host) && empty($setting->smtp_port))) {
            return $this->showToast(__('payslips.smtp_setting_required'), 'danger');
        }

        $rawPath = $this->declaration_file->store($user->id, 'raw');
        $rawFile = \Storage::disk('raw')->path($rawPath);

        if (countPages($rawFile) > config('ciblerh.max_payslip_pages')) {
            return $this->showToast(__('payslips.file_upload_max_pages', ['max' => config('ciblerh.max_payslip_pages')]), 'danger');
        }

        if ($blocking = SendAnnualDeclarationProcess::blockingProcessFor((int) $companyId, $this->department_id ? (int) $this->department_id : null, (int) $this->year)) {
            $this->showToast(__('annual_declarations.process_already_running_or_completed'), 'warning');
            return $this->redirect(route('portal.annual-declarations.details', $blocking->id), navigate: true);
        }

        $process = SendAnnualDeclarationProcess::create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'company_id' => $companyId,
            'department_id' => $this->department_id ?: null,
            'raw_file' => $rawFile,
            'destination_directory' => 'annual_' . Str::random(20),
            'year' => $this->year,
            'batch_id' => '',
        ]);

        AnnualDeclarationSendingPlan::start($process);

        auditLog(
            $user,
            'annual_declaration_sending',
            'web',
            'annual_declaration_sending_initiated',
            null,
            [],
            [],
            ['translation_key' => 'annual_declaration_sending_initiated', 'translation_params' => ['user' => $user->name, 'year' => $this->year]]
        );

        $this->showToast(__('annual_declarations.job_processing_status'), 'success');
        return $this->redirect(route('portal.annual-declarations.index'), navigate: true);
    }

    public function render()
    {
        $processes = match ($this->role) {
            'manager' => SendAnnualDeclarationProcess::manager(),
            'supervisor' => SendAnnualDeclarationProcess::supervisor(),
            'admin' => SendAnnualDeclarationProcess::query(),
            default => SendAnnualDeclarationProcess::query()->whereRaw('1 = 0'),
        };

        $stats = [
            'processes' => (clone $processes)->count(),
            'processing' => (clone $processes)->where('status', SendAnnualDeclarationProcess::STATUS_PROCESSING)->count(),
        ];
        $ids = (clone $processes)->pluck('id');
        $stats['sent'] = \App\Models\AnnualDeclaration::whereIn('send_annual_declaration_process_id', $ids)
            ->where('email_sent_status', \App\Models\AnnualDeclaration::STATUS_SUCCESSFUL)->count();
        $stats['review'] = \App\Models\AnnualDeclaration::whereIn('send_annual_declaration_process_id', $ids)->needsReview()->count();

        $processes = $processes->with(['company', 'department', 'owner'])
            ->withCount([
                'declarations as sendable_count' => fn ($q) => $q->sendable(),
                'declarations as review_count' => fn ($q) => $q->needsReview(),
                'declarations as sent_count' => fn ($q) => $q->where('email_sent_status', \App\Models\AnnualDeclaration::STATUS_SUCCESSFUL),
            ])
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.portal.annual-declarations.index', compact('processes', 'stats'))->layout('components.layouts.dashboard');
    }
}
