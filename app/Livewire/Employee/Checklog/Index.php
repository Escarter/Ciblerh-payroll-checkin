<?php

namespace App\Livewire\Employee\Checklog;

use App\Livewire\Traits\WithDataTable;
use Carbon\Carbon;
use App\Models\Ticking;
use App\Rules\CheckEndTimeRule;
use App\Rules\CheckStartAndEndTimeAreSameDayRule;
use Livewire\Component;
use Carbon\CarbonPeriod;
use Livewire\WithPagination;
use App\Rules\CheckStartTimeRule;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public  $start_day;
    public  $end_day;
    public ?string $comments = null;
    public ?int $checklog_id = null;
    public ?Ticking $checklog = null;
    public $company;
    public $department;
    public $service;
    

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;
    }
    //Get & assign selected checklog props
    public function initData($checklog_id)
    {
        $checklog = Ticking::findOrFail($checklog_id);
        $this->checklog = $checklog;
        $this->start_time = $checklog->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($checklog->end_time) ? $checklog->end_time->format('Y-m-d\TH:i') : '';
        $this->comments = $checklog->checkin_comments;
        $this->checklog_id = $checklog->id;
    }

    public function store()
    {
        if (!Gate::allows('ticking-create')) {
            return abort(401);
        }
       
        $this->validate([
            'start_time' => ['required','date',new CheckStartTimeRule],
            'end_time' => ['required', 'date', 'after:start_time', new CheckEndTimeRule, new CheckStartAndEndTimeAreSameDayRule($this->start_time)],
            'comments' => 'sometimes'
        ]);
        
        $existing_checkin =  auth()->user()->tickings()->whereDate('start_time', Carbon::parse($this->start_time)->format('Y-m-d'))->first();
        if (empty($existing_checkin)) {
            auth()->user()->tickings()->create(
                [
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                    'user_full_name' => auth()->user()->name,
                    'matricule' =>  auth()->user()->matricule,
                    'email' =>  auth()->user()->email,
                    'phone_number' =>   !empty(auth()->user()->professional_phone_number) ? auth()->user()->professional_phone_number : auth()->user()->personal_phone_number,
                    'company_id' =>   !empty($this->company) ? $this->company->id : NULL,
                    'company_name' =>   !empty($this->company) ? $this->company->name : NULL,
                    'department_id' =>  !empty($this->department) ? $this->department->id : NULL,
                    'department_name' =>   !empty($this->department) ? $this->department->name : NULL,
                    'service_id' =>   !empty($this->service) ? $this->service->id : NULL,
                    'service_name' => !empty($this->service) ? $this->service->name : NULL,
                    'checkin_comments' => $this->comments,
                    'author_id' => auth()->user()->author_id,
                ]
            );
            if(Carbon::parse($this->end_time)->format('H:i:s') > auth()->user()->work_end_time){
                $this->recordOvertime($this->end_time);
            }
        } else {
            $existing_checkin->update([
                'start_time' =>  $this->start_time,
                'end_time' =>  $this->end_time,
            ]);

        }
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.checkin_recorded_success'), 'CheckInModal');
    }

    public function bulkStore()
    {
        if (!Gate::allows('ticking-create')) {
            return abort(401);
        }

        $this->validate([
            'start_day' => 'required|date|before:end_day',
            'end_day' => 'required|date|after:start_day',
            'start_time' => ['required', 'date_format:H:i', 'before:end_time', new CheckStartTimeRule],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', new CheckEndTimeRule, new CheckStartAndEndTimeAreSameDayRule($this->start_time)],
            'comments' => 'sometimes'
        ]);

        $period = CarbonPeriod::create($this->start_day, $this->end_day);

        foreach ($period as $date) {

            $existing_checkin =  auth()->user()->tickings()->whereDate('start_time', Carbon::parse($date->format('Y-m-d')))->first();

            if (empty($existing_checkin)) {

                auth()->user()->tickings()->create(
                    [
                        'start_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->start_time),
                        'end_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->end_time),
                        'user_full_name' => auth()->user()->name,
                        'matricule' =>  auth()->user()->matricule,
                        'email' =>  auth()->user()->email,
                        'phone_number' =>  auth()->user()->professional_phone_number,
                        'company_id' =>   !empty($this->company) ? $this->company->id : NULL,
                        'company_name' =>   !empty($this->company) ? $this->company->name : NULL,
                        'department_id' =>  !empty($this->department) ? $this->department->id : NULL,
                        'department_name' =>   !empty($this->department) ? $this->department->name : NULL,
                        'service_id' =>   !empty($this->service) ? $this->service->id : NULL,
                        'service_name' => !empty($this->service) ? $this->service->name : NULL,
                        'checkin_comments' => $this->comments,
                        'author_id' => auth()->user()->author_id,
                    ]
                );

                if (Carbon::parse($this->end_time)->format('H:i:s') > auth()->user()->work_end_time) {
                   $this->recordOvertime(Carbon::parse($date->format('Y-m-d') . " " . $this->end_time));
                }

            } else {

                $existing_checkin->update([
                    'start_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->start_time),
                    'end_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->end_time),
                ]);

            }
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.checkins_recorded_success'), 'BulkCheckInModal');
    }
    public function update()
    {
        if (!Gate::allows('ticking-update')) {
            return abort(401);
        }
        $this->validate([
            'start_time' => 'required|date|before:end_time',
            'end_time' => 'required|date|after:start_time',
            'comments' => 'sometimes'
        ]);

        $existing_checkin =  auth()->user()->tickings()->whereDate('start_time', Carbon::parse($this->start_time)->format('Y-m-d'))->first();
        if (empty($existing_checkin)) {
            $this->checklog->update(
                [
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                    'checkin_comments' => $this->comments
                ]
            );
        } else {
            $existing_checkin->update([
                'start_time' =>  $this->start_time,
                'end_time' =>  $this->end_time,
                'checkin_comments' => $this->comments
            ]);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.checkin_updated_success'), 'EditChecklogModal');
    }
    public function delete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->checklog)) {

            $this->checklog->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.checkin_deleted_success'), 'DeleteModal');
    }

    public function recordOvertime($end_time)
    {
        $existing_overtime =  auth()->user()->overtimes()->whereDate('start_time', Carbon::parse($end_time)->format('Y-m-d'))->first();

        if (empty($existing_overtime)) {
            auth()->user()->overtimes()->create([
                'start_time' => Carbon::parse(Carbon::parse($end_time)->format('Y-m-d') . " " . auth()->user()->work_end_time),
                'end_time' => $end_time,
                'minutes_worked' => Carbon::parse(Carbon::parse($end_time)->format('Y-m-d') . " " . auth()->user()->work_end_time)->diffInMinutes($end_time),
                'reason' => __('System generated for checkin done on the :day',['day'=> Carbon::parse($end_time)->format('Y-m-d')]),
                'company_id' => !empty($this->company) ? $this->company->id : null,
                'department_id' => !empty($this->department) ? $this->department->id : null,
            ]);
        }else{

        }
    }
 
    public function clearFields()
    {
        $this->reset([
            'checklog',
            'checklog_id',
            'start_time',
            'start_day',
            'end_day',
            'end_time',
            'comments',
        ]);
    }


    public function render()
    {
        if (!Gate::allows('ticking-read')) {
            return abort(401);
        }

        $checklogs_count = Ticking::where('user_id',auth()->user()->id)->count();
        $checklogs = Ticking::where('user_id',auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $pending_checklogs_count =  Ticking::where('user_id',auth()->user()->id)->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)->count();
        $approved_checklogs_count =  Ticking::where('user_id',auth()->user()->id)->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->count();
        $rejected_checklogs_count =  Ticking::where('user_id',auth()->user()->id)->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count();

        return view('livewire.employee.checklog.index', compact('checklogs','checklogs_count','pending_checklogs_count','approved_checklogs_count','rejected_checklogs_count'))->layout('components.layouts.employee.master');
    }
}
