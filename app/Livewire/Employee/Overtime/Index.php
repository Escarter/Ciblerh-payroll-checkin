<?php

namespace App\Livewire\Employee\Overtime;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use App\Models\Overtime;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Rules\CheckEndTimeRule;
use App\Rules\CheckStartTimeRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Rules\CheckStartAndEndTimeAreSameDayRule;
use App\Rules\Overtime\CheckOverlapWorkingHoursRule;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public $reason ;
    public ?int $overtime_id = null;
    public ?Overtime $overtime = null;
    public $company;
    public $department;
    public $service;

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;
    }

    //Get & assign selected overtime props
    public function initData($overtime_id)
    {
        $overtime = Overtime::findOrFail($overtime_id);

        $this->overtime = $overtime;
        $this->start_time = $overtime->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($overtime->end_time) ? $overtime->end_time->format('Y-m-d\TH:i') : '';
        $this->reason = $overtime->reason;
        $this->overtime_id = $overtime->id;
        
    }
    public function store()
    {
        if (!Gate::allows('overtime-create')) {
            return abort(401);
        }
        $this->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'reason' => 'required'
        ]);

        // Validate that user has required relationships
        if (empty($this->company)) {
            $this->addError('company', __('employees.not_associated_with_company'));
            return;
        }

        if (empty($this->department)) {
            $this->addError('department', __('employees.not_associated_with_department'));
            return;
        }

        auth()->user()->overtimes()->create([
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'author_id' => auth()->user()->author_id,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_created'), 'CreateOvertimeModal');
    }
    public function update()
    {
        if (!Gate::allows('overtime-update')) {
            return abort(401);
        }
        $this->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time', new CheckEndTimeRule, new CheckStartAndEndTimeAreSameDayRule($this->start_time)],
            'reason' => 'required'
        ]);

        $this->overtime->update([
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
            
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_updated'), 'EditOvertimeModal');
    }

    public function delete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        if (!empty($this->overtime)) {

            $this->overtime->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_deleted'), 'DeleteModal');
    }

    public function clearFields()
    {
        $this->reset([
            'overtime',
            'overtime_id',
            'start_time',
            'end_time',
            'reason',
        ]);
    }

    public function render()
    {
        $overtimes = Overtime::search($this->query)->where('user_id',auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        
        return view('livewire.employee.overtime.index',[
            'overtimes' => $overtimes,
            'overtimes_count'=> Overtime::where('user_id', auth()->user()->id)->count(),
            'pending_overtime' => Overtime::where('user_id', auth()->user()->id)->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
            'approved_overtime' => Overtime::where('user_id', auth()->user()->id)->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
            'rejected_overtime' => Overtime::where('user_id', auth()->user()->id)->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
        ])->layout('components.layouts.employee.master');
    }
}
