<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticking extends Model
{
    use HasFactory, HasUUID, SoftDeletes;

    const SUPERVISOR_APPROVAL_PENDING = 0;
    const SUPERVISOR_APPROVAL_APPROVED = 1;
    const SUPERVISOR_APPROVAL_REJECTED = 2;

    const MANAGER_APPROVAL_PENDING = 0;
    const MANAGER_APPROVAL_APPROVED = 1;
    const MANAGER_APPROVAL_REJECTED = 2;

    protected $guarded = [];

    protected $hidden = ['id'];
    protected $casts = [
        'start_time' => 'immutable_datetime',
        'end_time' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    public function scopeSupervisor($query)
    {
         return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
    }
    public function scopeManager($query)
    {
        $manager = auth()->user();
        if ($manager && $manager->hasRole('manager')) {
            return $query->whereIn('company_id', $manager->managerCompanies->pluck('id'));
        }
        return $query;
    }

    public function getTimeWorkedAttribute()
    {
        if(!is_null($this->end_time)){
            return Carbon::parse($this->user->work_start_time)->diff(Carbon::parse($this->user->work_end_time)->subMinutes(90))->format('%H:%I');
        }
        return __('common.still_working');
    }
    public function isApproved($status_owner = '')
    {
        return match ($status_owner) {
            'supervisor' => match ($this->supervisor_approval_status) {
                $this::SUPERVISOR_APPROVAL_PENDING => false,
                $this::SUPERVISOR_APPROVAL_APPROVED => true,
                $this::SUPERVISOR_APPROVAL_REJECTED => false,
                default => false,
            },
            'manager' => match ($this->manager_approval_status) {
                $this::MANAGER_APPROVAL_PENDING => false,
                $this::MANAGER_APPROVAL_APPROVED => true,
                $this::MANAGER_APPROVAL_REJECTED => false,
                default => false,
            },
        };
    }
    public static function search($query)
    {
        $user = auth()->user();
        $isSupervisor = $user && $user->getRoleNames()->first() === "supervisor";
        
        return empty($query) ? static::query()->when($isSupervisor, function ($query) use ($user) {
             return $query->whereIn('department_id', $user->supDepartments->pluck('department_id'));
        }) :
        static::query()
        ->when($isSupervisor, function($query) use ($user) {
             return $query->whereIn('department_id', $user->supDepartments->pluck('department_id'));
        })
        ->where(function ($q) use ($query) { 
            $q->where('start_time', 'like', '%' . $query . '%');
            $q->orWhere('end_time', 'like', '%' . $query . '%');
            $q->orWhere('company_name', 'like', '%' . $query . '%');
            $q->orWhere('department_name', 'like', '%' . $query . '%');
            $q->orWhere('service_name', 'like', '%' . $query . '%');
            $q->orWhereHas('user', function ($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%');
                $q->orWhere('last_name', 'like', '%' . $query . '%');
                $q->orWhere('matricule', 'like', '%' . $query . '%');
                $q->orWhere('email', 'like', '%' . $query . '%');
            });
        });
    }
}
