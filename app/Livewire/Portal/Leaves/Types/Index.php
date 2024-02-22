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
        $leave_type = LeaveType::findOrFail($leave_type_id);

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
            $this->leave_type->delete();
        }

        $this->clearFields();

        $this->closeModalAndFlashMessage(__('LeaveType successfully deleted!'), 'DeleteModal');
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
        $leave_types = LeaveType::search($this->query)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        return view('livewire.portal.leaves.types.index', [
            'leave_types' => $leave_types,
            'leave_types_count' => LeaveType::count(),
            'active_leave_types' => LeaveType::where('is_active', true)->count(),
            'inactive_leave_types' => LeaveType::where('is_active', false)->count(),
        ])->layout('components.layouts.dashboard');

    }


}
