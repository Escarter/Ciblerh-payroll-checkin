<?php

namespace App\Livewire\Portal\Leaves;

use App\Models\Leave;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;

class Index extends Component
{
    use WithDataTable;


    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?string $leave_reason = null;
    public ?string $leave_type = null;
    public ?int $manager_approval_status = 1;
    public ?string $manager_approval_reason = null;
    public ?int $supervisor_approval_status = 1;
    public ?string $supervisor_approval_reason = null;
    public ?string $user = null;
    public ?string $role = null;
    public ?Leave $leave = null;

    //Multiple Selection props
    public array $selectedLeaves = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;
    public $bulk_approval_status = true;


    //Update & Store Rules
    protected array $rules = [
        'supervisor_approval_status' => 'sometimes',
        'supervisor_approval_reason' => 'sometimes',
        'manager_approval_status' => 'sometimes',
        'manager_approval_reason' => 'sometimes',
    ];

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }


    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedLeaves = match ($this->role) {
                "supervisor" => Leave::search($this->query)->supervisor()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "manager" => Leave::search($this->query)->manager()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => Leave::search($this->query)->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "default" => [],
            };
            $this->updatedselectedLeaves();
        } else {
            $this->selectedLeaves = [];
            $this->updatedselectedLeaves();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedLeaves()
    {
        $this->bulkDisabled = count($this->selectedLeaves) < 2;
        $this->leave = null;
    }

    //Get & assign selected leave props
    public function initData($leave_id)
    {
        $leave = Leave::findOrFail($leave_id);

        $this->leave = $leave;
        $this->start_date = $leave->start_date->format('Y-m-d');
        $this->end_date = !empty($leave->end_date) ? $leave->end_date->format('Y-m-d') : '';
        $this->leave_reason = $leave->leave_reason;
        if ($this->role === "supervisor") {
            $this->supervisor_approval_status = $leave->supervisor_approval_status;
            $this->supervisor_approval_reason = $leave->supervisor_approval_reason;
        } else {
            $this->manager_approval_status = $leave->manager_approval_status;
            $this->manager_approval_reason = $leave->manager_approval_reason;
        }
        $this->leave_type = $leave->leaveType->name;
        $this->user = $leave->user->name;
        $this->selectedLeaves = [];
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->role === "supervisor" ?
            $this->supervisor_approval_status = Leave::SUPERVISOR_APPROVAL_APPROVED :
                $this->manager_approval_status = Leave::MANAGER_APPROVAL_APPROVED;

            $this->bulk_approval_status = true;
        } else {
            $this->role === "supervisor" ?
            $this->supervisor_approval_status = Leave::SUPERVISOR_APPROVAL_REJECTED :
                $this->manager_approval_status = Leave::MANAGER_APPROVAL_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        if ($this->role === "supervisor") {
            Leave::whereIn('id', $this->selectedLeaves)->update([
                'supervisor_approval_status' => $this->supervisor_approval_status,
                'supervisor_approval_reason' => $this->supervisor_approval_reason,
            ]);
        } else {

            Leave::whereIn('id', $this->selectedLeaves)->update([
                'manager_approval_status' => $this->manager_approval_status,
                'manager_approval_reason' => $this->manager_approval_reason,
            ]);
        }
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave successfully updated!'), 'EditBulkLeaveModal');
    }

    public function update()
    {
        if (!Gate::allows('leave-update')) {
            return abort(401);
        }
        $this->validate();

        if ($this->role === "supervisor") {
            DB::transaction(function () {
                $this->leave->update([
                    'supervisor_approval_status' => $this->supervisor_approval_status,
                    'supervisor_approval_reason' => $this->supervisor_approval_reason,
                ]);
            });
        } else {
            DB::transaction(
                function () {
                    $this->leave->update([
                        'manager_approval_status' => $this->manager_approval_status,
                        'manager_approval_reason' => $this->manager_approval_reason,
                    ]);

                    if($this->manager_approval_status === Leave::MANAGER_APPROVAL_APPROVED){

                        $this->leave->user->decrement('remaining_leave_days', Carbon::parse($this->leave->start_date)->diffInDays(Carbon::parse( $this->leave->end_date)));
                    }
                }
            );
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave successfully updated!'), 'EditLeaveModal');
    }
    public function delete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        if (!empty($this->leave)) {

            $this->leave->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Leave successfully deleted!'), 'DeleteModal');
    }

    public function export()
    {
        return (new LeaveExport($this->query))->download('leaves-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {

        $this->reset([
            'leave',
            'leave_type',
            'user',
            'start_time',
            'end_time',
            'manager_approval_status', 'manager_approval_reason',
            'supervisor_approval_status', 'supervisor_approval_reason',
            'leave_reason',
            'selectedLeaves',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function render()
    {
        if (!Gate::allows('leave-read')) {
            return abort(401);
        }
        $leaves = match ($this->role) {
            "supervisor" => Leave::search($this->query)->supervisor()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "manager" => Leave::search($this->query)->manager()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "admin" =>  Leave::search($this->query)->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default => [],
        };
        $leaves_count = match ($this->role) {
            "supervisor" => Leave::supervisor()->count(),
            "manager" => Leave::manager()->count(),
            "admin" =>  Leave::count(),
            default => [],
        };
        $pending_leaves_count = match ($this->role) {
            "supervisor" => Leave::supervisor()->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
            "manager" => Leave::manager()->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
            "admin" =>  Leave::where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
            default => [],
        };
        $approved_leaves_count = match ($this->role) {
            "supervisor" => Leave::supervisor()->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "manager" => Leave::manager()->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "admin" =>  Leave::where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
            default => [],
        };
        $rejected_leaves_count = match ($this->role) {
            "supervisor" => Leave::supervisor()->where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
            "manager" => Leave::manager()->where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
            "admin" =>  Leave::where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
            default => [],
        };

        return view('livewire.portal.leaves.index', [
            'leaves' => $leaves,
            'leaves_count' => $leaves_count,
            'pending_leaves_count' => $pending_leaves_count,
            'approved_leaves_count' => $approved_leaves_count,
            'rejected_leaves_count' => $rejected_leaves_count,
        ])->layout('components.layouts.dashboard');
    }
  
}
