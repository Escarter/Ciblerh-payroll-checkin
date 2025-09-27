<?php

namespace App\Livewire\Portal\Leaves\Types;

use Livewire\Component;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Livewire\Traits\WithDataTable;

class Index extends Component
{
    use WithDataTable;
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
        $this->closeModalAndFlashMessage(__('LeaveType created successfully!'), 'CreateLeaveTypeModal');
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
        $this->closeModalAndFlashMessage(__('LeaveType successfully updated!'), 'EditLeaveTypeModal');
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
        $this->closeModalAndFlashMessage(__('LeaveType successfully moved to trash!'), 'DeleteModal');
    }

    public function restore($leaveTypeId)
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        $leaveType = LeaveType::withTrashed()->findOrFail($leaveTypeId);
        $leaveType->restore();

        $this->closeModalAndFlashMessage(__('LeaveType successfully restored!'), 'RestoreModal');
    }

    public function forceDelete($leaveTypeId)
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        $leaveType = LeaveType::withTrashed()->findOrFail($leaveTypeId);
        $leaveType->forceDelete();

        $this->closeModalAndFlashMessage(__('LeaveType permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaveTypes)) {
            LeaveType::whereIn('id', $this->selectedLeaveTypes)->delete(); // Soft delete
            $this->selectedLeaveTypes = [];
        }

        $this->closeModalAndFlashMessage(__('Selected leave types moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('leave_type-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaveTypes)) {
            LeaveType::withTrashed()->whereIn('id', $this->selectedLeaveTypes)->restore();
            $this->selectedLeaveTypes = [];
        }

        $this->closeModalAndFlashMessage(__('Selected leave types restored!'), 'BulkRestoreModal');
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

        $this->closeModalAndFlashMessage(__('Selected leave types permanently deleted!'), 'BulkForceDeleteModal');
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
        $this->validate([
            'leave_type_file' => 'sometimes|nullable|mimes:xlsx,csv|max:500',
        ]);
        Excel::import(new LeaveTypeImport(), $this->leave_type_file);
        auditLog(
            auth()->user(),
            'leave_type_imported',
            'web',
            __('Imported excel file for LeaveType')
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('LeaveType successfully imported!'), 'importLeaveTypesModal');
    }
    public function export()
    {
        auditLog(
            auth()->user(),
            'leave_type_exported',
            'web',
            __('Exported excel file for LeaveType')
        );
        return (new LeaveTypeExport($this->query))->download('leave_types-' . Str::random(5) . '.xlsx');
    }


    public function clearFields()
    {
        $this->reset([
            'name',
            'default_number_of_days',
            'description',
        ]);
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


}
