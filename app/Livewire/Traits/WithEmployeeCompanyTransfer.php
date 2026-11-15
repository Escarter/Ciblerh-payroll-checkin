<?php

namespace App\Livewire\Traits;

use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use App\Services\FeatureConfigurationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

trait WithEmployeeCompanyTransfer
{
    public $editCompanyId = null;

    public $transferEmployeeIds = [];

    public $transferEmployeeSummary = '';

    public $transferCompanyId = null;

    public $transferDepartmentId = null;

    public $transferServiceId = null;

    public $transferDepartments = [];

    public $transferServices = [];

    protected function resolveTransferUserRole(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->hasRole('manager')) {
            return 'manager';
        }

        return $this->auth_role ?? $user->getRoleNames()->first();
    }

    public function isEmployeeCompanyTransferEnabled(): bool
    {
        if (! FeatureConfigurationService::isEmployeeCompanyTransferEnabled()) {
            return false;
        }

        return in_array($this->resolveTransferUserRole(), ['admin', 'manager'], true);
    }

    public function getTransferableCompanies(): Collection
    {
        if (! $this->isEmployeeCompanyTransferEnabled()) {
            return collect();
        }

        $role = $this->resolveTransferUserRole();

        if ($role === 'admin') {
            return Company::orderBy('name')->get(['id', 'name']);
        }

        if ($role === 'manager') {
            return auth()->user()
                ? auth()->user()->managerCompanies()->orderBy('companies.name')->get(['companies.id', 'companies.name'])
                : collect();
        }

        return collect();
    }

    public function canTransferSingleEmployee(User $employee): bool
    {
        if (! $this->isEmployeeCompanyTransferEnabled()) {
            return false;
        }

        $role = $this->resolveTransferUserRole();

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            if ($employee->hasRole('admin')) {
                return false;
            }

            $managedCompanyIds = $this->getTransferableCompanies()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return in_array((int) $employee->company_id, $managedCompanyIds, true);
        }

        return false;
    }

    public function updatedEditCompanyId($companyId): void
    {
        if (! $this->isEmployeeCompanyTransferEnabled()) {
            return;
        }

        $companyId = $companyId ? (int) $companyId : null;

        $allowedIds = $this->getTransferableCompanies()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $companyId || ! in_array($companyId, $allowedIds, true)) {
            $this->departments = collect();
        } else {
            $this->departments = Department::where('company_id', $companyId)->orderBy('name')->get();
        }

        $this->selectedDepartmentId = null;
        if (property_exists($this, 'department_id')) {
            $this->department_id = null;
        }
        $this->service_id = null;
        $this->services = collect();
    }

    public function updatedTransferCompanyId($companyId): void
    {
        $companyId = $companyId ? (int) $companyId : null;

        $allowedIds = $this->getTransferableCompanies()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($companyId && in_array($companyId, $allowedIds, true)) {
            $this->transferDepartments = Department::where('company_id', $companyId)->orderBy('name')->get();
        } else {
            $this->transferDepartments = collect();
        }

        $this->transferDepartmentId = null;
        $this->transferServiceId = null;
        $this->transferServices = collect();
    }

    public function updatedTransferDepartmentId($departmentId): void
    {
        $departmentId = $departmentId ? (int) $departmentId : null;

        if ($departmentId && $this->transferCompanyId) {
            $department = Department::where('id', $departmentId)
                ->where('company_id', (int) $this->transferCompanyId)
                ->first();

            $this->transferServices = $department
                ? Service::where('department_id', $department->id)->orderBy('name')->get()
                : collect();
        } else {
            $this->transferServices = collect();
        }

        $this->transferServiceId = null;
    }

    public function openTransferModal(int $employeeId): void
    {
        if (! Gate::allows('employee-update')) {
            abort(401);
        }

        if (! $this->isEmployeeCompanyTransferEnabled()) {
            $this->showToast(__('employees.company_transfer_disabled'), 'danger');

            return;
        }

        $employee = User::findOrFail($employeeId);

        if (! $this->canTransferSingleEmployee($employee)) {
            abort(403, __('employees.cannot_transfer_employee'));
        }

        $this->transferEmployeeIds = [$employee->id];
        $this->transferEmployeeSummary = $employee->name.' ('.($employee->company?->name ?? __('common.none')).')';
        $this->transferCompanyId = $employee->company_id;
        $this->transferDepartments = $employee->company_id
            ? Department::where('company_id', $employee->company_id)->orderBy('name')->get()
            : collect();
        $this->transferDepartmentId = $employee->department_id;
        $this->transferServices = $employee->department_id
            ? Service::where('department_id', $employee->department_id)->orderBy('name')->get()
            : collect();
        $this->transferServiceId = $employee->service_id;

        $this->resetErrorBag();
    }

    public function openBulkTransferModal(): void
    {
        if (! Gate::allows('employee-update')) {
            abort(401);
        }

        if (! $this->isEmployeeCompanyTransferEnabled()) {
            $this->showToast(__('employees.company_transfer_disabled'), 'danger');

            return;
        }

        $ids = collect($this->selectedEmployees ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->transferEmployeeIds = $ids;
        $this->transferEmployeeSummary = '';
        $this->transferCompanyId = null;
        $this->transferDepartmentId = null;
        $this->transferServiceId = null;
        $this->transferDepartments = collect();
        $this->transferServices = collect();

        $this->resetErrorBag();
    }

    public function transferEmployeesToCompany(): void
    {
        if (! Gate::allows('employee-update')) {
            abort(401);
        }

        if (! $this->isEmployeeCompanyTransferEnabled()) {
            $this->showToast(__('employees.company_transfer_disabled'), 'danger');

            return;
        }

        $allowedCompanyIds = $this->getTransferableCompanies()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->validate([
            'transferEmployeeIds' => ['required', 'array', 'min:1'],
            'transferCompanyId' => ['required', 'integer', function ($attribute, $value, $fail) use ($allowedCompanyIds) {
                if (! in_array((int) $value, $allowedCompanyIds, true)) {
                    $fail(__('employees.invalid_target_company'));
                }
            }],
            'transferDepartmentId' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value && ! Department::where('id', (int) $value)->where('company_id', (int) $this->transferCompanyId)->exists()) {
                    $fail(__('employees.department_must_belong_to_company'));
                }
            }],
            'transferServiceId' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value) {
                    if (! $this->transferDepartmentId || ! Service::where('id', (int) $value)->where('department_id', (int) $this->transferDepartmentId)->exists()) {
                        $fail(__('employees.service_must_belong_to_department'));
                    }
                }
            }],
        ]);

        $targetCompany = Company::findOrFail((int) $this->transferCompanyId);
        $targetDepartmentId = $this->transferDepartmentId ? (int) $this->transferDepartmentId : null;
        $targetServiceId = ($targetDepartmentId && $this->transferServiceId) ? (int) $this->transferServiceId : null;

        $employees = User::whereIn('id', $this->transferEmployeeIds)->get();

        if ($employees->isEmpty()) {
            $this->showToast(__('employees.no_employees_selected_for_transfer'), 'danger');

            return;
        }

        foreach ($employees as $employee) {
            if (! $this->canTransferSingleEmployee($employee)) {
                abort(403, __('employees.cannot_transfer_employee'));
            }
        }

        $transferredCount = 0;

        foreach ($employees as $employee) {
            $oldCompanyId = $employee->company_id ? (int) $employee->company_id : null;
            $companyChanged = $oldCompanyId !== (int) $targetCompany->id;

            $employee->update([
                'company_id' => $targetCompany->id,
                'department_id' => $targetDepartmentId,
                'service_id' => $targetServiceId,
            ]);

            if ($companyChanged) {
                $this->finalizeEmployeeCompanyTransfer($employee, $oldCompanyId, (int) $targetCompany->id);
            }

            $transferredCount++;
        }

        if (property_exists($this, 'selectedEmployees')) {
            $this->selectedEmployees = [];
        }
        if (property_exists($this, 'selectAll')) {
            $this->selectAll = false;
        }

        $this->resetTransferModalFields();

        $this->closeModalAndFlashMessage(
            __('employees.employees_transferred_successfully', [
                'count' => $transferredCount,
                'company' => $targetCompany->name,
            ]),
            'TransferCompanyModal'
        );
    }

    public function resetTransferModalFields(): void
    {
        $this->transferEmployeeIds = [];
        $this->transferEmployeeSummary = '';
        $this->transferCompanyId = null;
        $this->transferDepartmentId = null;
        $this->transferServiceId = null;
        $this->transferDepartments = collect();
        $this->transferServices = collect();
    }

    protected function resolveUpdatedEmployeeCompanyAndStructure(
        User $employee,
        ?int $defaultCompanyId,
        $departmentId,
        $serviceId
    ): array {
        $oldCompanyId = $employee->company_id ? (int) $employee->company_id : $defaultCompanyId;
        $targetCompanyId = $oldCompanyId;

        if ($this->isEmployeeCompanyTransferEnabled() && ! empty($this->editCompanyId) && $this->canTransferSingleEmployee($employee)) {
            $requestedCompanyId = (int) $this->editCompanyId;
            $allowedCompanyIds = $this->getTransferableCompanies()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! in_array($requestedCompanyId, $allowedCompanyIds, true)) {
                throw ValidationException::withMessages([
                    'editCompanyId' => [__('employees.invalid_target_company')],
                ]);
            }

            $targetCompanyId = $requestedCompanyId;
        }

        $resolvedDepartmentId = ! empty($departmentId) ? (int) $departmentId : null;
        if ($resolvedDepartmentId && $targetCompanyId) {
            $belongsToCompany = Department::where('id', $resolvedDepartmentId)
                ->where('company_id', $targetCompanyId)
                ->exists();

            if (! $belongsToCompany) {
                $resolvedDepartmentId = null;
            }
        }

        $resolvedServiceId = (! empty($serviceId) && $resolvedDepartmentId) ? (int) $serviceId : null;
        if ($resolvedServiceId && $resolvedDepartmentId) {
            $belongsToDepartment = Service::where('id', $resolvedServiceId)
                ->where('department_id', $resolvedDepartmentId)
                ->exists();

            if (! $belongsToDepartment) {
                $resolvedServiceId = null;
            }
        }

        return [
            'company_id' => $targetCompanyId,
            'department_id' => $resolvedDepartmentId,
            'service_id' => $resolvedServiceId,
            'old_company_id' => $oldCompanyId,
            'company_changed' => $oldCompanyId !== $targetCompanyId,
        ];
    }

    protected function finalizeEmployeeCompanyTransfer(User $employee, ?int $oldCompanyId, ?int $newCompanyId): void
    {
        if (! $newCompanyId || $oldCompanyId === $newCompanyId) {
            return;
        }

        // Remove supervisor department assignments belonging to previous companies
        $employee->supDepartments()
            ->whereHas('department', function ($query) use ($newCompanyId) {
                $query->where('company_id', '!=', $newCompanyId);
            })
            ->delete();

        $oldCompanyName = $oldCompanyId ? (Company::find($oldCompanyId)?->name ?? (string) $oldCompanyId) : __('common.none');
        $newCompanyName = Company::find($newCompanyId)?->name ?? (string) $newCompanyId;

        auditLog(
            auth()->user(),
            'employee_company_transferred',
            'web',
            __('employees.employee_company_transferred_audit', [
                'employee' => $employee->name,
                'from' => $oldCompanyName,
                'to' => $newCompanyName,
            ])
        );
    }
}
