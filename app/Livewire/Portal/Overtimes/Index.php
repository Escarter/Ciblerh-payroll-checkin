<?php

namespace App\Livewire\Portal\Overtimes;

use Livewire\Component;
use App\Models\Overtime;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Exports\OvertimeExport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?string $reason = null;
    public ?int $approval_status = 1;
    public ?string $approval_reason = null;
    public ?int $overtime_id = null;
    public ?string $user = null;
    public ?Overtime $overtime = null;
    public ?string $role = null;

    //Multiple Selection props
    public array $selectedOvertimes = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;
    public $bulk_approval_status = true;


    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }

    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedOvertimes = match($this->role){
                "supervisor" => Overtime::search($this->query)->supervisor()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "manager" => Overtime::search($this->query)->manager()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => Overtime::search($this->query)->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "default"=> [],
            };
            $this->updatedselectedOvertimes();
        } else {
            $this->selectedOvertimes = [];
            $this->updatedselectedOvertimes();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedOvertimes()
    {
        $this->bulkDisabled = count($this->selectedOvertimes) < 2;
        $this->overtime = null;
    }

    //Get & assign selected overtime props
    public function initData($overtime_id)
    {
        $overtime = Overtime::findOrFail($overtime_id);

        $this->overtime = $overtime;
        $this->start_time = $overtime->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($overtime->end_time) ? $overtime->end_time->format('Y-m-d\TH:i') : '';
        $this->reason = $overtime->reason;
        $this->approval_status = $overtime->approval_status;
        $this->approval_reason = $overtime->approval_reason;
        $this->overtime_id = $overtime->id;
        $this->user = $overtime->user->name;
        $this->selectedOvertimes = [];
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->approval_status = Overtime::APPROVAL_STATUS_APPROVED;
            $this->bulk_approval_status = true;
        } else {
            $this->approval_status = Overtime::APPROVAL_STATUS_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        $this->validate([
            'approval_status' => 'required',
            'approval_reason' => 'required',
        ]);

        Overtime::whereIn('id', $this->selectedOvertimes)->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Overtimes successfully updated!'), 'EditBulkOvertimeModal');
    }

    public function update()
    {
        if (!Gate::allows('overtime-update')) {
            return abort(401);
        }

        $this->validate([
            'approval_status' => 'required',
            'approval_reason' => 'required',
        ]);

        $this->overtime->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Overtime successfully updated!'), 'EditOvertimeModal');
    }
    public function delete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        $this->overtime->delete();

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Overtime successfully deleted!'), 'DeleteModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'overtime_exported',
            'web',
            ucfirst(auth()->user()->name) . __(' exported excel file for overtime')
        );
        return (new OvertimeExport($this->query))->download('Overtime-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->reset([
            'overtime',
            'overtime_id',
            'user',
            'start_time',
            'end_time',
            'reason',
            'approval_status',
            'approval_reason',
            'selectedOvertimes',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function render()
    {
        if (!Gate::allows('overtime-read')) {
            return abort(401);
        }
        $overtimes = match($this->role){ 
            "supervisor" => Overtime::search($this->query)->supervisor()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "manager" => Overtime::search($this->query)->manager()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "admin" => Overtime::search($this->query)->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
           default => [],
        };
        $overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->count(),
            "manager" => Overtime::manager()->count(),
            "admin" => Overtime::count(),
           default => [],
        };
        $pending_overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
            "manager" => Overtime::manager()->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
            "admin" => Overtime::where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
           default => [],
        };
        $approved_overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
            "manager" => Overtime::manager()->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
            "admin" => Overtime::where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
           default => [],
        };
        $rejected_overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
            "manager" => Overtime::manager()->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
            "admin" => Overtime::where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
           default => [],
        };

        return view('livewire.portal.overtimes.index', [
            'overtimes' => $overtimes,
            'overtimes_count' => $overtimes_count,         
            'pending_overtimes_count' => $pending_overtimes_count,
            'approved_overtimes_count' => $approved_overtimes_count,
            'rejected_overtimes_count' => $rejected_overtimes_count,
        ])->layout('components.layouts.dashboard');
    }
}
