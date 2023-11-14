<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }
    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }
    public function scopeCreation($query)
    {
        return $query->where('action_type',  'LIKE', '%_created');
    }
    public function scopeDeletion($query)
    {
        return $query->where('action_type',  'LIKE', '%_deleted')->orWhere('action_type',  'LIKE', '%_rejected');
    }
    public function scopeUpdation($query)
    {
        return $query->where('action_type',  'LIKE', '%_updated');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getInitialsAttribute()
    {
        return strtoupper(Str::substr($this->user, 0, 1));
    }

    public function getStyleAttribute()
    {
        $action = explode('_', $this->action_type)[1];
        return [
            'login' => 'secondary',
            'logout' => 'info',
            'update' => 'warning',
            'reset' => 'success',
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'exported' => 'gray-600',
            'imported' => 'gray-400',
            'rejected' => 'danger',
            'approved' => 'success',
            'sending' => 'warning',
            'type' => 'warning',
            'report' => 'success'
        ][$action];
    }
    public static function search($query)
    {
        return empty($query) ?
            static::query()->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                 return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
            })->when(auth()->user()->getRoleNames()->first() === "manager", function ($query) {
                return $query->manager();
            }) :
            static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                 return $query
                 ->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'))
                 ->orWhere('user_id',auth()->user()->id);
            })
            ->when(auth()->user()->getRoleNames()->first() === "manager", function ($query) {
                 return $query->manager();
            })
            ->where(function ($q) use ($query) {
                $q->where('action_type', 'like', '%' . $query . '%');
                $q->orWhere('action_perform', 'like', '%' . $query . '%');
                $q->orWhere('user', 'like', '%' . $query . '%');
                $q->orWhereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', '%' . $query . '%');
                    $q->orWhere('last_name', 'like', '%' . $query . '%');
                    $q->orWhere('matricule', 'like', '%' . $query . '%');
                    $q->orWhere('email', 'like', '%' . $query . '%');
                });
            });
    }
}
