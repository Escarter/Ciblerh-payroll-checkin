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
    public bool $selectAll = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedChecklogsForDelete = [];
    public $selectAllForDelete = false;

    // Reactive count properties
    public $activeChecklogsCount = 0;
    public $deletedChecklogsCount = 0;

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

        // Initialize counts
        $this->updateCounts();
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
        $createdCheckins = [];
        $createdOvertimes = [];

        foreach ($period as $date) {

            $existing_checkin =  auth()->user()->tickings()->whereDate('start_time', Carbon::parse($date->format('Y-m-d')))->first();

            if (empty($existing_checkin)) {

                $checkin = auth()->user()->tickings()->create(
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

                $createdCheckins[] = [
                    'id' => $checkin->id,
                    'date' => $date->format('Y-m-d'),
                    'start_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->start_time)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->end_time)->format('Y-m-d H:i:s'),
                ];

                if (Carbon::parse($this->end_time)->format('H:i:s') > auth()->user()->work_end_time) {
                   $overtime = $this->recordOvertime(Carbon::parse($date->format('Y-m-d') . " " . $this->end_time));
                   if ($overtime) {
                       $createdOvertimes[] = [
                           'id' => $overtime->id,
                           'date' => $overtime->start_time->format('Y-m-d'),
                           'start_time' => $overtime->start_time->format('Y-m-d H:i:s'),
                           'end_time' => $overtime->end_time->format('Y-m-d H:i:s'),
                       ];
                   }
                }

            } else {

                $existing_checkin->update([
                    'start_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->start_time),
                    'end_time' => Carbon::parse($date->format('Y-m-d') . " " . $this->end_time),
                ]);

            }
        }

        // Create a single audit log entry for bulk checkin creation
        if (count($createdCheckins) > 0) {
            auditLog(
                auth()->user(),
                'checkin_bulk_created',
                'web',
                'bulk_created_checklogs',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_created_checklogs',
                    'translation_params' => ['count' => count($createdCheckins)],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_create',
                    'affected_count' => count($createdCheckins),
                    'affected_ids' => array_column($createdCheckins, 'id'),
                    'affected_records' => $createdCheckins,
                    'date_range' => [
                        'start' => $this->start_day,
                        'end' => $this->end_day,
                    ],
                ]
            );
        }

        // Create a single audit log entry for bulk overtime creation (if any)
        if (count($createdOvertimes) > 0) {
            auditLog(
                auth()->user(),
                'overtime_bulk_created',
                'web',
                'bulk_created_overtimes',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_created_overtimes',
                    'translation_params' => ['count' => count($createdOvertimes)],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_create',
                    'affected_count' => count($createdOvertimes),
                    'affected_ids' => array_column($createdOvertimes, 'id'),
                    'affected_records' => $createdOvertimes,
                    'date_range' => [
                        'start' => $this->start_day,
                        'end' => $this->end_day,
                    ],
                ]
            );
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

        // Update counts
        $this->updateCounts();
    }

    public function bulkDelete()
    {
        if (!Gate::allows('ticking-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selected)) {
            $checklogs = Ticking::whereIn('id', $this->selected)
                ->where('user_id', auth()->user()->id)
                ->get();

            $affectedRecords = $checklogs->map(function ($checklog) {
                return [
                    'id' => $checklog->id,
                    'start_time' => $checklog->start_time,
                    'end_time' => $checklog->end_time,
                ];
            })->toArray();

            Ticking::whereIn('id', $this->selected)
                ->where('user_id', auth()->user()->id)
                ->delete();

            if ($checklogs->count() > 0) {
                auditLog(
                    auth()->user(),
                    'checkin_bulk_deleted',
                    'web',
                    'bulk_deleted_checklogs',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_checklogs',
                        'translation_params' => ['count' => $checklogs->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $checklogs->count(),
                        'affected_ids' => $checklogs->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selected = [];
            $this->selectAll = false;

            $this->closeModalAndFlashMessage(__('employees.selected_checkins_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function restore($checklogId)
    {
        if (!Gate::allows('ticking-restore')) {
            return abort(401);
        }

        $checklog = Ticking::withTrashed()->findOrFail($checklogId);

        // Check if this checklog belongs to the current user
        if ($checklog->user_id !== auth()->id()) {
            return abort(403);
        }

        $checklog->restore();

        $this->closeModalAndFlashMessage(__('employees.checkin_restored'), 'RestoreModal');

        // Update counts
        $this->updateCounts();
    }

    public function forceDelete($checklogId = null)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        // If no checklogId provided, try to get it from selectedChecklogsForDelete
        if (!$checklogId) {
            if (!empty($this->selectedChecklogsForDelete) && is_array($this->selectedChecklogsForDelete)) {
                $checklogId = $this->selectedChecklogsForDelete[0] ?? null;
            } else {
                $this->showToast(__('employees.no_checklog_selected'), 'danger');
                return;
            }
        }

        $checklog = Ticking::withTrashed()->findOrFail($checklogId);

        // Check if this checklog belongs to the current user
        if ($checklog->user_id !== auth()->id()) {
            return abort(403);
        }

        $checklog->forceDelete();

        // Clear selection after deletion
        if (in_array($checklogId, $this->selectedChecklogsForDelete ?? [])) {
            $this->selectedChecklogsForDelete = array_diff($this->selectedChecklogsForDelete, [$checklogId]);
        }

        $this->closeModalAndFlashMessage(__('employees.checkin_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkRestore()
    {
        if (!Gate::allows('ticking-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedChecklogsForDelete)) {
            $checklogs = Ticking::withTrashed()
                ->whereIn('id', $this->selectedChecklogsForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $checklogs->map(function ($checklog) {
                return [
                    'id' => $checklog->id,
                    'start_time' => $checklog->start_time,
                    'end_time' => $checklog->end_time,
                ];
            })->toArray();

            Ticking::withTrashed()
                ->whereIn('id', $this->selectedChecklogsForDelete)
                ->where('user_id', auth()->id())
                ->restore();

            if ($checklogs->count() > 0) {
                auditLog(
                    auth()->user(),
                    'checkin_bulk_restored',
                    'web',
                    'bulk_restored_checklogs',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_checklogs',
                        'translation_params' => ['count' => $checklogs->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $checklogs->count(),
                        'affected_ids' => $checklogs->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedChecklogsForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_checkins_restored'), 'BulkRestoreModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedChecklogsForDelete)) {
            $checklogs = Ticking::withTrashed()
                ->whereIn('id', $this->selectedChecklogsForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $checklogs->map(function ($checklog) {
                return [
                    'id' => $checklog->id,
                    'start_time' => $checklog->start_time,
                    'end_time' => $checklog->end_time,
                ];
            })->toArray();

            Ticking::withTrashed()
                ->whereIn('id', $this->selectedChecklogsForDelete)
                ->where('user_id', auth()->id())
                ->forceDelete();

            if ($checklogs->count() > 0) {
                auditLog(
                    auth()->user(),
                    'checkin_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_checklogs',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_checklogs',
                        'translation_params' => ['count' => $checklogs->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => $checklogs->count(),
                        'affected_ids' => $checklogs->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedChecklogsForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_checkins_permanently_deleted'), 'BulkForceDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    //Toggle the $selectAll on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selected = $this->getChecklogs()->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
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
        $this->selected = $this->getChecklogs()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedChecklogsForDelete = $this->getChecklogs()->pluck('id')->toArray();
    }

    public function selectAllChecklogs()
    {
        $this->selected = Ticking::where('user_id', auth()->user()->id)->whereNull('deleted_at')->pluck('id')->toArray();
    }

    public function selectAllDeletedChecklogs()
    {
        $this->selectedChecklogsForDelete = Ticking::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray();
    }

    private function updateCounts()
    {
        $this->activeChecklogsCount = Ticking::where('user_id', auth()->user()->id)->whereNull('deleted_at')->count();
        $this->deletedChecklogsCount = Ticking::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count();
    }

    private function getChecklogs()
    {
        $query = Ticking::where('user_id', auth()->user()->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function recordOvertime($end_time)
    {
        $existing_overtime =  auth()->user()->overtimes()->whereDate('start_time', Carbon::parse($end_time)->format('Y-m-d'))->first();

        if (empty($existing_overtime)) {
            return auth()->user()->overtimes()->create([
                'start_time' => Carbon::parse(Carbon::parse($end_time)->format('Y-m-d') . " " . auth()->user()->work_end_time),
                'end_time' => $end_time,
                'minutes_worked' => Carbon::parse(Carbon::parse($end_time)->format('Y-m-d') . " " . auth()->user()->work_end_time)->diffInMinutes($end_time),
                'reason' => __('System generated for checkin done on the :day',['day'=> Carbon::parse($end_time)->format('Y-m-d')]),
                'company_id' => !empty($this->company) ? $this->company->id : null,
                'department_id' => !empty($this->department) ? $this->department->id : null,
            ]);
        }
        
        return null;
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
            'selected',
            'selectAll',
        ]);
    }


    public function render()
    {
        if (!Gate::allows('ticking-read')) {
            return abort(401);
        }

        $checklogs = $this->getChecklogs();

        // Get counts from all checklogs, not just current page
        $allChecklogs = Ticking::where('user_id', auth()->user()->id);
        $pending_checklogs_count = $allChecklogs->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)->whereNull('deleted_at')->count();
        $approved_checklogs_count = $allChecklogs->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->whereNull('deleted_at')->count();
        $rejected_checklogs_count = $allChecklogs->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)->where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.checklog.index', compact('checklogs', 'pending_checklogs_count', 'approved_checklogs_count', 'rejected_checklogs_count'))->layout('components.layouts.employee.master');
    }
}
