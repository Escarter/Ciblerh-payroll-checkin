<?php

namespace App\Livewire\Employee\Leaves;

use App\Models\Leave;
use Livewire\Component;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Carbon\Carbon;

class Index extends Component
{
    use WithDataTable;

    //Create, Edit, Delete, View Post props
    public  $start_date;
    public  $end_date;
    public  $leave_type_id;
    public  $types;
    public  $leave_reason;
    public  $interval;
    public ?Leave $leave = null;


    public function updatedEndDate($value)
    {
        if(!empty($value))
        {
            $this->interval = Carbon::parse($this->start_date)->lt(Carbon::parse($this->end_date)) ? __('Selected Leave days are '). '<strong>' . Carbon::parse($value)->diffInDays(Carbon::parse($this->start_date)) . '</strong>'.__(' days'): __('Start date must be less than end_date');
        }
    }
    public function updatedStartDate($value)
    {
        if(!empty($value))
        {
            $this->interval = Carbon::parse($value)->lt(Carbon::parse($this->end_date)) ? __('Selected Leave days are '). '<strong>' . Carbon::parse($value)->diffInDays(Carbon::parse($this->end_date)) .'</strong>'.__(' days'): __('Start date must be less than end_date');
        }
    }

    public function mount()
    {
        $this->types = LeaveType::select('name','id')->get();
    }
    
    public function store()
    {
        if (!Gate::allows('leave-create')) {
            return abort(401);
        }

        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'leave_type_id' => 'required',
            'leave_reason' => 'required',
        ]);

        $leave =  auth()->user()->leaves()->create(
            [
                'company_id' => auth()->user()->company_id,
                'department_id' => auth()->user()->department_id,
                'author_id' => auth()->user()->author_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'leave_type_id' => $this->leave_type_id,
                'leave_reason' => $this->leave_reason,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave request successfully submitted - nice 😍!'), 'CreateLeaveModal');
    }
    //Get & assign selected absence props
    public function initData($leave_id)
    {
        $leave = Leave::findOrFail($leave_id);

        $this->leave = $leave;
        $this->start_date = $leave->start_date->format('Y-m-d');
        $this->end_date = $leave->end_date->format('Y-m-d');
        $this->leave_type_id = $leave->leave_type_id;
        $this->leave_reason = $leave->leave_reason;
    }

    public function update()
    {
        if (!Gate::allows('leave-update')) {
            return abort(401);
        }

        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'leave_type_id' => 'required',
            'leave_reason' => 'required',
        ]);

        $this->leave->update([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'leave_type_id' => $this->leave_type_id,
            'leave_reason' => $this->leave_reason,
            'supervisor_approval_status' => $this->end_date != $this->leave->end_date ? Leave::SUPERVISOR_APPROVAL_PENDING :  $this->leave->supervisor_approval_status,
            'manager_approval_status' => $this->end_date != $this->leave->end_date ? Leave::MANAGER_APPROVAL_PENDING :  $this->leave->manager_approval_status,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave request updated successfully - nice 😍!!'), 'EditLeaveModal');
    }
    public function delete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        if (!empty($this->leave)) {

            auth()->user()->leaves()->findOrFail($this->leave->id)->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave successfully deleted!'), 'DeleteModal');
    }

    public function clearFields()
    {
        $this->reset([
            'leave',
            'start_date',
            'end_date',
            'leave_type_id',
            'leave_reason',
            'interval',
        ]);
    }

    public function render()
    {
        if (!Gate::allows('leave-read')) {
            return abort(401);
        }

        $leaves = Leave::search($this->query)->where('user_id', auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $pending_leave =  Leave::where('user_id', auth()->user()->id)->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->where('manager_approval_status', Leave::MANAGER_APPROVAL_PENDING)->count();
        $approved_leave =  Leave::where('user_id', auth()->user()->id)->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->where('manager_approval_status', Leave::MANAGER_APPROVAL_APPROVED)->count();
        $rejected_leave =  Leave::where('user_id', auth()->user()->id)->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_REJECTED)->where('manager_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count();

        return view('livewire.employee.leaves.index', compact('leaves', 'pending_leave', 'approved_leave', 'rejected_leave'))->layout('components.layouts.employee.master');
    }
}
