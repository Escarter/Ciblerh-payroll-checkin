<?php

namespace App\Livewire\Portal\Checklogs;

use App\Models\Ticking;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Exports\ChecklogExport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;


    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?string $checkin_comments = null;
    public ?int $manager_approval_status = 1;
    public ?string $manager_approval_reason = null;
    public ?int $supervisor_approval_status = 1;
    public ?string $supervisor_approval_reason = null;
    public ?int $checklog_id = null;
    public ?string $user = null;
    public ?string $role = null;
    public ?Ticking $checklog = null;

    //Multiple Selection props
    public array $selectedChecklogs = [];
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
        if($value){
            $this->selectedChecklogs = match($this->role){
                "supervisor" => Ticking::search($this->query)->supervisor()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "manager" => Ticking::search($this->query)->manager()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => Ticking::search($this->query)->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "default"=> [],
            };
            $this->updatedselectedChecklogs();
        }else{
            $this->selectedChecklogs = [];
            $this->updatedselectedChecklogs();

        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedChecklogs()
    {
        $this->bulkDisabled = count($this->selectedChecklogs) < 2;
        $this->checklog = null;
    }

    //Get & assign selected checklog props
    public function initData($checklog_id)
    {
        $checklog = Ticking::findOrFail($checklog_id);

        $this->checklog = $checklog;
        $this->start_time = $checklog->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($checklog->end_time) ? $checklog->end_time->format('Y-m-d\TH:i') : '';
        $this->checkin_comments = $checklog->checkin_comments;
        if($this->role === "supervisor"){
            $this->supervisor_approval_status = $checklog->supervisor_approval_status;
            $this->supervisor_approval_reason = $checklog->supervisor_approval_reason;
        }else{
            $this->manager_approval_status = $checklog->manager_approval_status;
            $this->manager_approval_reason = $checklog->manager_approval_reason;
        }
        $this->checklog_id = $checklog->id;
        $this->user = $checklog->user_full_name;
        $this->selectedChecklogs = [];
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
           $this->role === "supervisor" ? 
                $this->supervisor_approval_status = Ticking::SUPERVISOR_APPROVAL_APPROVED :
                $this->manager_approval_status = Ticking::MANAGER_APPROVAL_APPROVED;

            $this->bulk_approval_status = true;
        } else {
            $this->role === "supervisor" ?
                $this->supervisor_approval_status = Ticking::SUPERVISOR_APPROVAL_REJECTED :
                $this->manager_approval_status = Ticking::MANAGER_APPROVAL_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        if($this->role === "supervisor"){
            Ticking::whereIn('id', $this->selectedChecklogs)->update([
                'supervisor_approval_status' => $this->supervisor_approval_status,
                'supervisor_approval_reason' => $this->supervisor_approval_reason,
            ]);
        }else{

            Ticking::whereIn('id', $this->selectedChecklogs)->update([
                'manager_approval_status' => $this->manager_approval_status,
                'manager_approval_reason' => $this->manager_approval_reason,
            ]);
        }
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Checkin successfully updated!'), 'EditBulkChecklogModal');
    }

    public function update()
    {
        if (!Gate::allows('ticking-update')) {
            return abort(401);
        }
        $this->validate();

        if ($this->role === "supervisor") {
            DB::transaction(function(){
                $this->checklog->update([
                    'supervisor_approval_status' => $this->supervisor_approval_status,
                    'supervisor_approval_reason' => $this->supervisor_approval_reason,
                ]);
            });
        } else {
            DB::transaction(
                function () {
                $this->checklog->update([
                    'manager_approval_status' => $this->manager_approval_status,
                    'manager_approval_reason' => $this->manager_approval_reason,
                ]);
            });
        }
       
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Checkin successfully updated!'), 'EditChecklogModal');
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
        $this->closeModalAndFlashMessage(__('Checkin successfully deleted!'), 'DeleteModal');
    }

    public function export()
    {
        return (new ChecklogExport($this->query))->download('Checklogs-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
    
        $this->reset([
            'checklog',
            'checklog_id',
            'user',
            'start_time',
            'end_time',
            'manager_approval_status', 'manager_approval_reason',
            'supervisor_approval_status', 'supervisor_approval_reason',
            'checkin_comments',
            'selectedChecklogs',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function render()
    {
        if (!Gate::allows('ticking-read')) {
            return abort(401);
        }
        $checklogs = match($this->role) {
            "supervisor" => Ticking::search($this->query)->supervisor()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "manager" => Ticking::search($this->query)->manager()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "admin"=>  Ticking::search($this->query)->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
           default => [],
        };
        $checklogs_count = match($this->role) {
            "supervisor" => Ticking::supervisor()->count(),
            "manager" => Ticking::manager()->count(),
            "admin"=>  Ticking::count(),
           default => [],
        };
        $pending_checklogs_count = match($this->role) {
            "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
            "manager" => Ticking::manager()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
            "admin"=>  Ticking::where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
           default => [],
        };
        $approved_checklogs_count = match($this->role) {
            "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "manager" => Ticking::manager()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "admin"=>  Ticking::where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
           default => [],
        };
        $rejected_checklogs_count = match($this->role) {
            "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
            "manager" => Ticking::manager()->where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
            "admin"=>  Ticking::where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
           default => [],
        };

        return view('livewire.portal.checklogs.index', [
            'checklogs' => $checklogs,
            'checklogs_count' => $checklogs_count,
            'pending_checklogs_count' => $pending_checklogs_count,
            'approved_checklogs_count' => $approved_checklogs_count,
            'rejected_checklogs_count' => $rejected_checklogs_count,
        ])->layout('components.layouts.dashboard');
    }
}

