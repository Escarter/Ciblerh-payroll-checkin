<?php

namespace App\Livewire\Portal\AuditLogs;

use Livewire\Component;
use App\Models\AuditLog;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

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

    //Get & assign selected overtime props
    public function initData($audit_log_id)
    {
        $this->audit_log = AuditLog::findOrFail($audit_log_id);
    }

    public function delete($auditLogId = null)
    {
        $auditLog = $auditLogId ? AuditLog::findOrFail($auditLogId) : $this->audit_log;
        $auditLog->delete(); // Soft delete

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Audit Log successfully moved to trash!'), 'DeleteModal');
    }

    public function restore($auditLogId)
    {
        $auditLog = AuditLog::withTrashed()->findOrFail($auditLogId);
        $auditLog->restore();

        $this->closeModalAndFlashMessage(__('Audit Log successfully restored!'), 'RestoreModal');
    }

    public function forceDelete($auditLogId)
    {
        $auditLog = AuditLog::withTrashed()->findOrFail($auditLogId);
        $auditLog->forceDelete();

        $this->closeModalAndFlashMessage(__('Audit Log permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!empty($this->selectedAuditLogs)) {
            AuditLog::whereIn('id', $this->selectedAuditLogs)->delete(); // Soft delete
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('Selected audit logs moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!empty($this->selectedAuditLogs)) {
            AuditLog::withTrashed()->whereIn('id', $this->selectedAuditLogs)->restore();
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('Selected audit logs restored!'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!empty($this->selectedAuditLogs)) {
            AuditLog::withTrashed()->whereIn('id', $this->selectedAuditLogs)->forceDelete();
            $this->selectedAuditLogs = [];
        }

        $this->closeModalAndFlashMessage(__('Selected audit logs permanently deleted!'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedAuditLogs = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedAuditLogs = $this->getAuditLogs()->pluck('id')->toArray();
        } else {
            $this->selectedAuditLogs = [];
        }
    }

    public function toggleAuditLogSelection($auditLogId)
    {
        if (in_array($auditLogId, $this->selectedAuditLogs)) {
            $this->selectedAuditLogs = array_diff($this->selectedAuditLogs, [$auditLogId]);
        } else {
            $this->selectedAuditLogs[] = $auditLogId;
        }
        
        $this->selectAll = count($this->selectedAuditLogs) === $this->getAuditLogs()->count();
    }

    private function getAuditLogs()
    {
        $role = auth()->user()->getRoleNames()->first();
        
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

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
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
            
        return view('livewire.portal.audit-logs.index', [
            'logs' => $logs,
            'active_logs' => $active_logs,
            'deleted_logs' => $deleted_logs,
            'logs_count' => $logs_count,
            'creation_log_count' => $creation_log_count,
            'update_log_count' => $update_log_count,
            'deletion_log_count' => $deletion_log_count,
            ])->layout('components.layouts.dashboard');
    }
}
