<?php

namespace App\Models;

use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Overtime extends Model
{
    use HasFactory, HasUUID, SoftDeletes;

    const APPROVAL_STATUS_PENDING = 0;
    const APPROVAL_STATUS_APPROVED = 1;
    const APPROVAL_STATUS_REJECTED = 2;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'immutable_datetime',
        'end_time' => 'immutable_datetime',
    ];

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }
    public function scopeSupervisor($query)
    {
         return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
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
    public function getTimeWorkedAttribute()
    {
        return $this->end_time->diff($this->start_time)->format('%H:%I');
    }

    public function isApproved()
    {
        return match ($this->approval_status) {
            self::APPROVAL_STATUS_PENDING => false,
            self::APPROVAL_STATUS_APPROVED => true,
            self::APPROVAL_STATUS_REJECTED => false,
            default => false
        };
    }

    public static function search($query)
    {
        return empty($query) ? 
        static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                 return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
            }) :
        static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                 return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
            })
            ->where(function ($q) use ($query) {
                $q->where('start_time', 'like', '%' . $query . '%');
                $q->orWhere('end_time', 'like', '%' . $query . '%');
                $q->orWhereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', '%' . $query . '%');
                    $q->orWhere('last_name', 'like', '%' . $query . '%');
                    $q->orWhere('matricule', 'like', '%' . $query . '%');
                    $q->orWhere('email', 'like', '%' . $query . '%');
                    $q->orWhereHas('department', function ($q) use ($query) {
                        $q->where('name', 'like', '%' . $query . '%');
                    });
                    $q->orWhereHas('service', function ($q) use ($query) {
                        $q->where('name', 'like', '%' . $query . '%');
                    });
                });
                $q->orWhereHas('company', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                });
                
            });
    }
}
