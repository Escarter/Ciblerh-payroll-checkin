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
            if (app()->runningInConsole()) {
                return;
            }

            static $isResolving = false;
            if ($isResolving) {
                return;
            }

            $isResolving = true;
            try {
                if (\App\Models\User::isCurrentSuperAdmin()) {
                    return;
                }

                if (!app()->bound('session') || !session()->isStarted()) {
                    return;
                }

                $guard = auth()->guard();
                $hasAuthId = $guard->hasUser() || session()->has($guard->getName());
                if (!$hasAuthId) {
                    return;
                }

                $builder->where($builder->getModel()->getTable() . '.name', '!=', 'superadmin');
            } finally {
                $isResolving = false;
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