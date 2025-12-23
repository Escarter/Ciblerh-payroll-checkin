<?php

namespace App\Livewire\Portal\AuditLogs;

use Livewire\Component;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    //DataTable props
    public ?string $query = null;
    public ?string $resultCount;
    public string $orderBy = 'created_at';
    public string $orderAsc = 'desc';
    public int $perPage = 15;
    protected $paginationTheme = "bootstrap";

    public $audit_log;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedAuditLogs = [];
    public $selectAll = false;

    // Enhanced filtering (similar to StratagemAI)
    public $userFilter = '';
    public $actionFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Detail modal
    public $showDetailModal = false;
    public $selectedLog = null;

    protected $queryString = [
        'query' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 15],
        'activeTab' => ['except' => 'active'],
    ];

    public function mount()
    {
        // Check if user has any audit log read permission or is admin
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');
        
        if (!$isAdmin && !Gate::allows('audit_log-read_all') && !Gate::allows('audit_log-read_own_only')) {
            abort(403, 'Unauthorized access to audit logs.');
        }
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function openDetailModal($logId)
    {
        // Check if user has any audit log read permission or is admin
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');
        
        if (!$isAdmin && !Gate::allows('audit_log-read_all') && !Gate::allows('audit_log-read_own_only')) {
            abort(403, 'Unauthorized access to audit logs.');
        }
        
        $query = AuditLog::with('user');
        if ($this->activeTab === 'deleted') {
            $query->onlyTrashed();
        }
        
        $this->selectedLog = $query->findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    //Get & assign selected audit log props
    public function initData($audit_log_id)
    {
        $this->audit_log = AuditLog::withTrashed()->findOrFail($audit_log_id);
    }

    public function delete($auditLogId = null)
    {
        try {
            $auditLog = $auditLogId ? AuditLog::findOrFail($auditLogId) : $this->audit_log;

            if (!$auditLog) {
                $this->dispatch("showToast", message: __('audit_logs.audit_log_not_found'), type: "danger");
                return;
            }

            $auditLog->delete(); // Soft delete
            $this->closeModalAndFlashMessage(__('audit_logs.audit_log_moved_to_trash'), 'DeleteModal');
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('audit_logs.danger_deleting_audit_log') . $e->getMessage(), type: "danger");
        }
        
        $this->clearFields();
    }

    public function forceDelete($auditLogId = null)
    {
        try {
            $auditLog = $auditLogId ? AuditLog::withTrashed()->findOrFail($auditLogId) : $this->audit_log;

            if (!$auditLog) {
                $this->dispatch("showToast", message: __('audit_logs.audit_log_not_found'), type: "danger");
                return;
            }

            $auditLog->forceDelete();
            $this->closeModalAndFlashMessage(__('audit_logs.audit_log_permanently_deleted'), 'ForceDeleteModal');
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('audit_logs.danger_deleting_audit_log') . $e->getMessage(), type: "danger");
        }
        
        $this->clearFields();
    }

    public function clearFields()
    {
        $this->audit_log = null;
    }

    public function closeModalAndFlashMessage($message, $modalId = null)
    {
        $this->dispatch("showToast", message: $message, type: "success");

        if ($modalId) {
            $this->dispatch('closeModal', modalId: $modalId);
        }
    }

    public function restore($auditLogId)
    {
        if (!Gate::allows('audit_log-restore')) {
            return abort(401);
        }

        $auditLog = AuditLog::withTrashed()->findOrFail($auditLogId);
        $auditLog->restore();

        $this->closeModalAndFlashMessage(__('audit_logs.audit_log_restored'), 'RestoreModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('audit_log-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedAuditLogs)) {
            AuditLog::whereIn('id', $this->selectedAuditLogs)->delete(); // Soft delete
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('audit_logs.selected_audit_logs_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('audit_log-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedAuditLogs)) {
            AuditLog::withTrashed()->whereIn('id', $this->selectedAuditLogs)->restore();
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('audit_logs.selected_audit_logs_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!empty($this->selectedAuditLogs)) {
            AuditLog::withTrashed()->whereIn('id', $this->selectedAuditLogs)->forceDelete();
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('audit_logs.selected_audit_logs_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedAuditLogs = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        $currentPageLogIds = $this->getAuditLogs()->pluck('id')->toArray();
        
        $allCurrentPageSelected = count(array_intersect($this->selectedAuditLogs, $currentPageLogIds)) === count($currentPageLogIds) && count($currentPageLogIds) > 0;
        
        if ($allCurrentPageSelected) {
            $this->selectedAuditLogs = array_values(array_diff($this->selectedAuditLogs, $currentPageLogIds));
        } else {
            $this->selectedAuditLogs = array_values(array_unique(array_merge($this->selectedAuditLogs, $currentPageLogIds)));
        }
        
        $this->selectAll = count(array_intersect($this->selectedAuditLogs, $currentPageLogIds)) === count($currentPageLogIds) && count($currentPageLogIds) > 0;
    }

    public function toggleAuditLogSelection($auditLogId)
    {
        if (in_array($auditLogId, $this->selectedAuditLogs)) {
            $this->selectedAuditLogs = array_values(array_diff($this->selectedAuditLogs, [$auditLogId]));
        } else {
            $this->selectedAuditLogs = array_values(array_unique(array_merge($this->selectedAuditLogs, [$auditLogId])));
        }
        
        $currentPageLogIds = $this->getAuditLogs()->pluck('id')->toArray();
        $this->selectAll = count(array_intersect($this->selectedAuditLogs, $currentPageLogIds)) === count($currentPageLogIds) && count($currentPageLogIds) > 0;
    }

    private function getAuditLogs()
    {
        $role = auth()->user()->getRoleNames()->first();
        
        // Base query with role-based scoping
        $query = match($role){
            "supervisor" => AuditLog::search($this->query)->whereUserId(auth()->user()->id),
            "manager" => AuditLog::search($this->query)->manager(),
            "admin" => AuditLog::search($this->query),
            default => AuditLog::query(),
        };

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Enhanced filtering (similar to StratagemAI)
        $query->when($this->userFilter, function ($q) {
            $q->where('user_id', $this->userFilter);
        })
        ->when($this->actionFilter, function ($q) {
            $q->where('action_type', 'like', '%' . $this->actionFilter . '%');
        })
        ->when($this->dateFrom, function ($q) {
            $q->whereDate('created_at', '>=', $this->dateFrom);
        })
        ->when($this->dateTo, function ($q) {
            $q->whereDate('created_at', '<=', $this->dateTo);
        });

        // Eager load user relationship for better performance
        return $query->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    /**
     * Get available actions for filter
     */
    public function getActionOptionsProperty()
    {
        return [
            'created' => __('audit_logs.action_created'),
            'updated' => __('audit_logs.action_updated'),
            'deleted' => __('audit_logs.action_deleted'),
            'login' => __('audit_logs.action_login'),
            'logout' => __('audit_logs.action_logout'),
            'exported' => __('audit_logs.action_exported'),
            'imported' => __('audit_logs.action_imported'),
        ];
    }

    public function render()
    {
        $role = auth()->user()->getRoleNames()->first();
        $logs = $this->getAuditLogs();

        // Get counts for active audit logs (non-deleted)
        $active_logs = match($role){
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)->whereNull('deleted_at')->count(),
            "manager" => AuditLog::manager()->whereNull('deleted_at')->count(),
            "admin" => AuditLog::whereNull('deleted_at')->count(),
            default => 0,
        };

        $deleted_logs = match($role){
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count(),
            "manager" => AuditLog::manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => AuditLog::withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };

        // Legacy counts for backward compatibility
        $logs_count = match($role){
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::manager()->count(),
            "admin" => AuditLog::count(),
            default => 0,
        };
        $creation_log_count = match($role){
            "supervisor" => AuditLog::creation()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::creation()->manager()->count(),
            "admin" => AuditLog::creation()->count(),
            default => 0,
        };
        $update_log_count = match($role){
            "supervisor" => AuditLog::updation()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::updation()->manager()->count(),
            "admin" => AuditLog::updation()->count(),
            default => 0,
        };
        $deletion_log_count = match($role){
            "supervisor" => AuditLog::deletion()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::deletion()->manager()->count(),
            "admin" => AuditLog::deletion()->count(),
            default => 0,
        };

        // Get users for filter (only if user has permission to view all)
        $users = [];
        if (Gate::allows('audit_log-read_all') || $role === 'admin') {
            $users = User::orderBy('first_name')->orderBy('last_name')->get();
        }
            
        return view('livewire.portal.audit-logs.index', [
            'logs' => $logs,
            'active_logs' => $active_logs,
            'deleted_logs' => $deleted_logs,
            'logs_count' => $logs_count,
            'creation_log_count' => $creation_log_count,
            'update_log_count' => $update_log_count,
            'deletion_log_count' => $deletion_log_count,
            'users' => $users,
            'actionOptions' => $this->actionOptions,
        ])->layout('components.layouts.dashboard');
    }
}
