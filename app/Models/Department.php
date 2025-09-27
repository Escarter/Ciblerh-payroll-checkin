<?php

namespace App\Models;

use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, HasUUID,SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeManager($query)
    {
        $manager = auth()->user();
        if ($manager && $manager->hasRole('manager')) {
            return $query->whereIn('company_id', $manager->managerCompanies->pluck('id'));
        }
        return $query;
    }

    public function depSupervisor(): HasOne
    {
        return $this->hasOne(SupervisorDepartment::class, 'department_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopeSupervisor($query)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('supervisor')) {
            return $query->whereIn('id', $user->supDepartments->pluck('department_id'));
        }
        return $query->where('id', 0); // Return no results if no supervisor context
    }

    public function employees() : HasMany
    {
        return $this->hasMany(User::class);
    }
    public function services() : HasMany
    {
        return $this->hasMany(Service::class);
    }
    public static function search($query)
    {
        return empty($query) ? static::query() :
            static::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%');
                $q->orWhereHas('company', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                });
            });
    }
}
