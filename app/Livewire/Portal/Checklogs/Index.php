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
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedChecklogsForDelete = [];
    public $selectAllForDelete = false;


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
        $this->closeModalAndFlashMessage(__('employees.checkin_successfully_updated'), 'EditBulkChecklogModal');
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
        $this->closeModalAndFlashMessage(__('employees.checkin_successfully_updated'), 'EditChecklogModal');
    }
    public function delete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->checklog)) {
            $this->checklog->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.checkin_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore($checklogId)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        $checklog = Ticking::withTrashed()->findOrFail($checklogId);
        $checklog->restore();

        $this->closeModalAndFlashMessage(__('employees.checkin_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($checklogId)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        $checklog = Ticking::withTrashed()->findOrFail($checklogId);
        $checklog->forceDelete();

        $this->closeModalAndFlashMessage(__('employees.checkin_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        // Handle both active tab (selectedChecklogs) and deleted tab (selectedChecklogsForDelete)
        if (!empty($this->selectedChecklogs)) {
            // Active tab - soft delete selected items
            Ticking::whereIn('id', $this->selectedChecklogs)->delete(); // Soft delete
            $this->selectedChecklogs = [];
            $this->selectAll = false;
        } elseif (!empty($this->selectedChecklogsForDelete)) {
            // Deleted tab - already handled by existing logic
            Ticking::whereIn('id', $this->selectedChecklogsForDelete)->delete(); // Soft delete
            $this->selectedChecklogsForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_checkin_records_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedChecklogsForDelete)) {
            Ticking::withTrashed()->whereIn('id', $this->selectedChecklogsForDelete)->restore();
            $this->selectedChecklogsForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_checkin_records_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedChecklogsForDelete)) {
            Ticking::withTrashed()->whereIn('id', $this->selectedChecklogsForDelete)->forceDelete();
            $this->selectedChecklogsForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_checkin_records_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedChecklogsForDelete = [];
        $this->selectAllForDelete = false;
    }

    public function toggleSelectAllForDelete()
    {
        if ($this->selectAllForDelete) {
            $this->selectedChecklogsForDelete = $this->getChecklogs()->pluck('id')->toArray();
        } else {
            $this->selectedChecklogsForDelete = [];
        }
    }

    public function toggleChecklogSelectionForDelete($checklogId)
    {
        if (in_array($checklogId, $this->selectedChecklogsForDelete)) {
            $this->selectedChecklogsForDelete = array_diff($this->selectedChecklogsForDelete, [$checklogId]);
        } else {
            $this->selectedChecklogsForDelete[] = $checklogId;
        }

        $this->selectAllForDelete = count($this->selectedChecklogsForDelete) === $this->getChecklogs()->count();
    }

    public function selectAllVisible()
    {
        $this->selectedChecklogs = $this->getChecklogs()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedChecklogsForDelete = $this->getChecklogs()->pluck('id')->toArray();
    }

    public function selectAllChecklogs()
    {
        $this->selectedChecklogs = match ($this->role) {
            'supervisor' => Ticking::search($this->query)->supervisor()->with(['user'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Ticking::search($this->query)->manager()->with(['user'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Ticking::search($this->query)->with(['user'])->whereNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
        $this->updatedselectedChecklogs();
    }

    public function selectAllDeletedChecklogs()
    {
        $this->selectedChecklogsForDelete = match ($this->role) {
            'supervisor' => Ticking::search($this->query)->supervisor()->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Ticking::search($this->query)->manager()->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Ticking::search($this->query)->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
    }

    private function getChecklogs()
    {
        $query = Ticking::search($this->query)->with(['user']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match($this->role){
            "supervisor" => $query->supervisor(),
            "manager" => $query->manager(),
            "admin" => null, // No additional filtering for admin
            default => [],
        };

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
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

        $checklogs = $this->getChecklogs();

        // Get counts for active checklog records (non-deleted)
        $active_checklogs = match($this->role){
            "supervisor" => Ticking::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
            "manager" => Ticking::search($this->query)->manager()->whereNull('deleted_at')->count(),
            "admin" => Ticking::search($this->query)->whereNull('deleted_at')->count(),
           default => 0,
        };

        // Get counts for deleted checklog records
        $deleted_checklogs = match($this->role){
            "supervisor" => Ticking::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            "manager" => Ticking::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => Ticking::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
           default => 0,
        };

        // Get approval status counts for active records only
        $pending_checklogs_count = match($this->role){
            "supervisor" => Ticking::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
            "manager" => Ticking::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
            "admin" => Ticking::whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->count(),
           default => 0,
        };
        $approved_checklogs_count = match($this->role){
            "supervisor" => Ticking::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "manager" => Ticking::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "admin" => Ticking::whereNull('deleted_at')->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->count(),
           default => 0,
        };
        $rejected_checklogs_count = match($this->role){
            "supervisor" => Ticking::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
            "manager" => Ticking::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
            "admin" => Ticking::whereNull('deleted_at')->where('supervisor_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count(),
           default => 0,
        };

        return view('livewire.portal.checklogs.index', [
            'checklogs' => $checklogs,
            'checklogs_count' => $active_checklogs, // Legacy for backward compatibility
            'active_checklogs' => $active_checklogs,
            'deleted_checklogs' => $deleted_checklogs,
            'pending_checklogs_count' => $pending_checklogs_count,
            'approved_checklogs_count' => $approved_checklogs_count,
            'rejected_checklogs_count' => $rejected_checklogs_count,
        ])->layout('components.layouts.dashboard');
    }
}

