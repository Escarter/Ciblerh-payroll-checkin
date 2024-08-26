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

    //Get & assign selected overtime props
    public function initData($audit_log_id)
    {
        $this->audit_log = AuditLog::findOrFail($audit_log_id);
    }

    public function delete()
    {
        $this->audit_log->delete();

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Audit Log successfully deleted!'), 'DeleteModal');
    }

    public function render()
    {
        $role = auth()->user()->getRoleNames()->first();

        $logs = match($role){
            "supervisor" =>  AuditLog::search($this->query)->whereUserId(auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "manager" => AuditLog::search($this->query)->manager()->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage) ,
            "admin" => AuditLog::search($this->query)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage) ,
           default => [],
        };
        $logs_count = match($role){
            "supervisor" =>  AuditLog::whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::manager()->count() ,
            "admin" => AuditLog::count() ,
           default => [],
        };
        $creation_log_count = match($role){
            "supervisor" =>  AuditLog::creation()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::creation()->manager()->count() ,
            "admin" => AuditLog::creation()->count() ,
           default => [],
        };
        $update_log_count = match($role){
            "supervisor" =>  AuditLog::updation()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::updation()->manager()->count() ,
            "admin" => AuditLog::updation()->count() ,
           default => [],
        };
        $deletion_log_count = match($role){
            "supervisor" =>  AuditLog::deletion()->whereUserId(auth()->user()->id)->count(),
            "manager" => AuditLog::deletion()->manager()->count() ,
            "admin" => AuditLog::deletion()->count() ,
           default => [],
        }; 
            
        return view('livewire.portal.audit-logs.index', [
            'logs' => $logs,
            'logs_count' => $logs_count,
            'creation_log_count' => $creation_log_count,
            'update_log_count' => $update_log_count,
            'deletion_log_count' => $deletion_log_count,
            ])->layout('components.layouts.dashboard');
    }
}
