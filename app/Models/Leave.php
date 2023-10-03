<?php

namespace App\Models;

use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
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
        'start_date' => 'immutable_date',
        'end_date' => 'immutable_date',
    ];

    public function getPeriodAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) .__(' days');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

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

    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }
    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
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
            default  => []
        };
    }

    public static function search($query)
    {
        return empty($query) ? static::query()->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
            return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }) :
            static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
            })
            ->where(function ($q) use ($query) {
                $q->where('start_time', 'like', '%' . $query . '%');
                $q->orWhere('end_time', 'like', '%' . $query . '%');
                $q->orWhere('leave_reason', 'like', '%' . $query . '%');
                $q->orWhereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', '%' . $query . '%');
                    $q->orWhere('last_name', 'like', '%' . $query . '%');
                    $q->orWhere('matricule', 'like', '%' . $query . '%');
                    $q->orWhere('email', 'like', '%' . $query . '%');
                });
                $q->orWhereHas('leaveType', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                });
            });
    }
   
}
