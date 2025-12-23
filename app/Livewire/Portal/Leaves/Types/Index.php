<?php

namespace App\Livewire\Portal\Leaves\Types;

use App\Livewire\BaseImportComponent;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Livewire\Traits\WithDataTable;
use App\Imports\LeaveTypeImport;
use App\Exports\LeaveTypeExport;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class Index extends BaseImportComponent
{
    use WithDataTable, WithFileUploads;

    protected $importType = 'leave_types';
    protected $importPermission = 'leave_type-create';

    // Cache for existing leave type names to avoid N+1 queries
    protected $existingLeaveTypeNames;

    //
    public ?LeaveType $leave_type = null;
    public ?int $leave_type_id = null;
    public ?string $description = null;
    public ?string $name = null;
    public ?int $default_number_of_days = 0;
    public ?bool $is_active = null;
    public $leave_type_file = null;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedLeaveTypes = [];
    public $selectAll = false;

    //Update & Store Rules
    protected array $rules = [
        'name' => 'required',
    ];

    /**
     * Handle leave type file upload
     */
    public function updatedLeaveTypeFile()
    {
        // Validate the uploaded file
        $this->validate([
            'leave_type_file' => 'sometimes|nullable|mimes:xlsx,xls,csv,txt|max:' . ($this->maxFileSize * 1024)
        ]);

        // Clear previous preview when new file is uploaded
        $this->clearPreview();
    }

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }

    public function initData($leave_type_id)
    {
        $leave_type = LeaveType::withTrashed()->findOrFail($leave_type_id);

        $this->leave_type = $leave_type;
        $this->name = $leave_type->name;
        $this->description = $leave_type->description;
        $this->is_active = $leave_type->is_active;
        $this->default_number_of_days = $leave_type->default_number_of_days;
    }

    public function store()
    {
        if (!Gate::allows('leave_type-create')) {
            return abort(401);
        }

        $this->validate();

        $leave_type = LeaveType::create([
            'name' => $this->name,
            'default_number_of_days' => $this->default_number_of_days,
            'description' => $this->description,
            'author_id' => auth()->user()->id,
        ]);
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leavetype_created_successfully'), 'CreateLeaveTypeModal');
    }

    public function update()
    {
        if (!Gate::allows('leave_type-update')) {
            return abort(401);
        }
        $this->validate();

        DB::transaction(function () {
            $this->leave_type->update([
                'name' => $this->name,
                'default_number_of_days' => $this->default_number_of_days,
                'description' => $this->description,
                'is_active' => $this->is_active == "true" ? 1 : 0,
            ]);
        });
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leavetype_successfully_updated'), 'EditLeaveTypeModal');
    }

    public function delete()
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        if (!empty($this->leave_type)) {
            $this->leave_type->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leavetype_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('leave_type-restore')) {
            return abort(401);
        }

        $leaveType = LeaveType::withTrashed()->findOrFail($this->leave_type_id);
        $leaveType->restore();

        $this->closeModalAndFlashMessage(__('leaves.leavetype_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($leaveTypeId = null)
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        // If no leaveTypeId provided, try to get it from selectedLeaveTypes
        if (!$leaveTypeId) {
            if (!empty($this->selectedLeaveTypes) && is_array($this->selectedLeaveTypes)) {
                $leaveTypeId = $this->selectedLeaveTypes[0] ?? null;
            } elseif ($this->leave_type_id) {
                $leaveTypeId = $this->leave_type_id;
            } else {
                $this->showToast(__('leaves.no_leave_type_selected'), 'danger');
                return;
            }
        }

        $leaveType = LeaveType::withTrashed()->findOrFail($leaveTypeId);
        $leaveType->forceDelete();

        // Clear selection after deletion
        if (in_array($leaveTypeId, $this->selectedLeaveTypes ?? [])) {
            $this->selectedLeaveTypes = array_diff($this->selectedLeaveTypes, [$leaveTypeId]);
        }
        $this->leave_type_id = null;

        $this->closeModalAndFlashMessage(__('leaves.leavetype_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('leave_type-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaveTypes)) {
            LeaveType::whereIn('id', $this->selectedLeaveTypes)->delete(); // Soft delete
            $this->selectedLeaveTypes = [];
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leave_types_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('leave_type-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaveTypes)) {
            LeaveType::withTrashed()->whereIn('id', $this->selectedLeaveTypes)->restore();
            $this->selectedLeaveTypes = [];
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leave_types_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaveTypes)) {
            LeaveType::withTrashed()->whereIn('id', $this->selectedLeaveTypes)->forceDelete();
            $this->selectedLeaveTypes = [];
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leave_types_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedLeaveTypes = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedLeaveTypes = $this->getLeaveTypes()->pluck('id')->toArray();
        } else {
            $this->selectedLeaveTypes = [];
        }
    }

    public function toggleLeaveTypeSelection($leaveTypeId)
    {
        if (in_array($leaveTypeId, $this->selectedLeaveTypes)) {
            $this->selectedLeaveTypes = array_diff($this->selectedLeaveTypes, [$leaveTypeId]);
        } else {
            $this->selectedLeaveTypes[] = $leaveTypeId;
        }
        
        $this->selectAll = count($this->selectedLeaveTypes) === $this->getLeaveTypes()->count();
    }

    private function getLeaveTypes()
    {
        $query = LeaveType::search($this->query);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function import()
    {
        // Use the parent import method from BaseImportComponent
        parent::import();
    }
    public function export()
    {
        if (!Gate::allows('leave_type-read')) {
            return abort(401);
        }

        auditLog(
            auth()->user(),
            'leave_type_exported',
            'web',
            __('leaves.exported_excel_file_for_leavetype')
        );
        return (new LeaveTypeExport($this->query))->download('leave_types-' . Str::random(5) . '.xlsx');
    }



    public function render()
    {
        if (!Gate::allows('leave_type-read')) {
            return abort(401);
        }

        $leave_types = $this->getLeaveTypes();

        // Get counts for active leave types (non-deleted)
        $active_leave_types = LeaveType::search($this->query)->whereNull('deleted_at')->where('is_active', true)->count();
        $inactive_leave_types = LeaveType::search($this->query)->whereNull('deleted_at')->where('is_active', false)->count();
        $deleted_leave_types = LeaveType::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count();

        return view('livewire.portal.leaves.types.index', [
            'leave_types' => $leave_types,
            'leave_types_count' => $active_leave_types + $inactive_leave_types, // Legacy for backward compatibility
            'active_leave_types' => $active_leave_types,
            'inactive_leave_types' => $inactive_leave_types,
            'deleted_leave_types' => $deleted_leave_types,
        ])->layout('components.layouts.dashboard');
    }

    /**
     * Get import columns for leave type preview
     */
    protected function getImportColumns(): array
    {
        return [
            0 => __('leaves.leave_type'),
            1 => __('common.description'),
            2 => __('leaves.default_number_of_days'),
            3 => __('common.status'),
        ];
    }

    /**
     * Get column definitions for preview
     */
    public function getPreviewColumns(): array
    {
        return $this->getImportColumns();
    }

    /**
     * Override to return correct file property for this component
     */
    protected function getFileProperty()
    {
        return $this->leave_type_file ?? null;
    }

    /**
     * Get company ID (not needed for leave types)
     */
    protected function getCompanyId(): ?int
    {
        return null;
    }

    /**
     * Get department ID (not needed for leave types)
     */
    protected function getDepartmentId(): ?int
    {
        return null;
    }

    /**
     * Validate a single leave type preview row
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Validate leave type name
            if (empty($rowData[0] ?? '')) {
                $errors[] = __('leaves.leave_type_name_required');
            } elseif ($this->isLeaveTypeNameExists($rowData[0])) {
                $warnings[] = __('leaves.leave_type_already_exists');
            }

            // Validate default number of days
            if (!empty($rowData[2] ?? '')) {
                $days = $rowData[2];
                if (!is_numeric($days) || $days < 0) {
                    $errors[] = __('leaves.default_number_of_days_must_be_positive_number');
                }
            }

            // Validate status
            if (!empty($rowData[3] ?? '')) {
                $status = strtolower($rowData[3]);
                if (!in_array($status, ['true', 'false', '1', '0', 'yes', 'no'])) {
                    $errors[] = __('common.status_must_be_boolean');
                }
            }

        } catch (\Exception $e) {
            $errors[] = __('common.row_validation_error', ['error' => $e->getMessage()]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Preload validation data to optimize performance
     */
    protected function preloadValidationData(): void
    {
        // Cache existing leave type names to avoid N+1 queries during validation
        // Limit results to prevent timeouts
        if (!isset($this->existingLeaveTypeNames)) {
            $this->existingLeaveTypeNames = LeaveType::limit(1000) // Reasonable limit for leave types
                ->pluck('name')
                ->map(function($name) {
                    return strtolower(trim($name));
                })
                ->toArray();
        }
    }

    /**
     * Check if leave type name exists (optimized to avoid N+1 queries)
     */
    protected function isLeaveTypeNameExists(string $name): bool
    {
        return in_array(strtolower(trim($name)), $this->existingLeaveTypeNames ?? []);
    }

    /**
     * Perform the actual leave type import
     */
    protected function performImport()
    {
        Excel::import(new LeaveTypeImport(), $this->leave_type_file);

        return [
            'imported_count' => 'unknown', // Excel import doesn't return count easily
        ];
    }

    /**
     * Clear leave type-specific fields
     */
    public function clearFields()
    {
        parent::clearFields();
        $this->leave_type_file = null;
        $this->reset([
            'name',
            'default_number_of_days',
            'description',
        ]);
    }


}
