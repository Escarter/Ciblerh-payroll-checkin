<?php

namespace App\Livewire\Employee\AuditLogs;

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

    public function render()
    {

        $logs = AuditLog::search($this->query)->where('user_id',auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
            
        return view('livewire.employee.audit-logs.index', [
            'logs' => $logs,
            'logs_count' => AuditLog::where('user_id',auth()->user()->id)->count(),
            'creation_log_count' => AuditLog::where('user_id',auth()->user()->id)->creation()->count() ,
            'update_log_count' => AuditLog::where('user_id',auth()->user()->id)->updation()->count() ,
            'deletion_log_count' => AuditLog::where('user_id',auth()->user()->id)->deletion()->count() ,
            ])->layout('components.layouts.employee.master');
    }
}