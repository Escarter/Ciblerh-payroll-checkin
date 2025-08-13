<?php

namespace App\Models;

use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absence extends Model
{
    use HasFactory, HasUUID, SoftDeletes;

    const APPROVAL_STATUS_PENDING = 0;
    const APPROVAL_STATUS_APPROVED = 1;
    const APPROVAL_STATUS_REJECTED = 2;

    protected $guarded = [];

    protected $casts = [
        'absence_date' => 'immutable_date:Y-m-d'
    ];

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

    public function user(): BelongsTo 
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function company(): BelongsTo 
    {
        return $this->belongsTo(Company::class,'company_id');
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
        static::query()->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
            return $query->where('company_id', auth()->user()->company_id);
        }) :
        static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                return $query->where('company_id', auth()->user()->company_id);
            })
            ->where(function ($q) use ($query) {
                $q->where('absence_date', 'like', '%' . $query . '%');
                $q->orWhere('absence_reason', 'like', '%' . $query . '%');
                $q->orWhere('approval_status', 'like', '%' . $query . '%');
                $q->orWhere('approval_reason', 'like', '%' . $query . '%');
                $q->orWhereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', '%' . $query . '%');
                    $q->orWhere('last_name', 'like', '%' . $query . '%');
                    $q->orWhere('matricule', 'like', '%' . $query . '%');
                    $q->orWhere('email', 'like', '%' . $query . '%');
                });
            });
    }
}
