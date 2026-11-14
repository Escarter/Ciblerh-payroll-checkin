<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('hideSuperadminRole', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (!app()->runningInConsole()) {
                $authUser = auth()->user();
                if (!$authUser || !$authUser->hasRole('superadmin')) {
                    $builder->where('name', '!=', 'superadmin');
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}