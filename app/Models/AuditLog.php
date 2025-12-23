<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
    ];

    public function scopeManager($query)
    {
        $manager = auth()->user();
        if ($manager && $manager->hasRole('manager')) {
            return $query->whereIn('company_id', $manager->managerCompanies->pluck('id'));
        }
        return $query;
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
        $action = explode('_', $this->action_type)[1] ?? $this->action_type;
        $styles = [
            'login' => 'secondary',
            'logout' => 'info',
            'update' => 'warning',
            'reset' => 'success',
            'created' => 'success',
            'updated' => 'warning',
            'delete' => 'danger',
            'deleted' => 'danger',
            'exported' => 'gray-600',
            'imported' => 'gray-400',
            'rejected' => 'danger',
            'approved' => 'success',
            'sending' => 'warning',
            'type' => 'warning',
            'report' => 'success',
            'sms' => 'info',
            'email' => 'tertiary',
            'payslip' => 'danger'
        ];
        
        return $styles[$action] ?? 'secondary'; // Default fallback
    }

    /**
     * Get formatted action badge color (similar to StratagemAI).
     */
    public function getActionColorAttribute(): string
    {
        $action = explode('_', $this->action_type)[1] ?? $this->action_type;
        
        return match(strtolower($action)) {
            'created', 'approved' => 'success',
            'updated', 'sending', 'type' => 'warning',
            'deleted', 'delete', 'rejected' => 'danger',
            'login' => 'secondary',
            'logout', 'sms' => 'info',
            'exported', 'imported' => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Get formatted action icon (similar to StratagemAI).
     */
    public function getActionIconAttribute(): string
    {
        $action = explode('_', $this->action_type)[1] ?? $this->action_type;
        
        return match(strtolower($action)) {
            'created' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            'updated', 'update' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'deleted', 'delete' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            'restore' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            default => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        };
    }
    public function getTranslatedActionTypeAttribute()
    {
        $translated = __('audit_logs.' . $this->action_type);
        return $translated !== 'audit_logs.' . $this->action_type ? $translated : $this->action_type;
    }

    public function getTranslatedActionPerformAttribute()
    {
        // For new entries that use translation keys
        if (str_starts_with($this->action_perform, 'audit_logs.')) {
            return __('audit_logs.' . str_replace('audit_logs.', '', $this->action_perform));
        }

        // Try to match and translate common patterns in existing entries
        $actionPerform = $this->action_perform;

        // Login patterns
        if (preg_match('/Successfully logged in from ip ([0-9.]+)/i', $actionPerform, $matches)) {
            return __('audit_logs.login_successful', ['ip' => $matches[1]]);
        }
        if (preg_match('/Successfully logged out from ip ([0-9.]+)/i', $actionPerform, $matches)) {
            return __('audit_logs.logout_successful', ['ip' => $matches[1]]);
        }
        if (preg_match('/Tried to log in from ip ([0-9.]+) but contract has expired!/i', $actionPerform, $matches)) {
            return __('audit_logs.login_contract_expired', ['ip' => $matches[1]]);
        }
        if (preg_match('/Tried to log in from ip ([0-9.]+)but account is banned!/i', $actionPerform, $matches)) {
            return __('audit_logs.login_account_banned', ['ip' => $matches[1]]);
        }

        // CRUD patterns
        if (preg_match('/Created ([a-zA-Z_]+) with name (.+)/i', $actionPerform, $matches)) {
            return __('audit_logs.created_entity', ['entity' => $matches[1], 'name' => $matches[2]]);
        }
        if (preg_match('/Updated ([a-zA-Z_]+) with name (.+)/i', $actionPerform, $matches)) {
            return __('audit_logs.updated_entity', ['entity' => $matches[1], 'name' => $matches[2]]);
        }
        if (preg_match('/Deleted ([a-zA-Z_]+) with name (.+)/i', $actionPerform, $matches)) {
            return __('audit_logs.deleted_entity', ['entity' => $matches[1], 'name' => $matches[2]]);
        }

        // Import patterns
        if (preg_match('/Imported excel file for ([a-zA-Z_]+) for ([a-zA-Z_]+) (.+)/i', $actionPerform, $matches)) {
            $entity = $matches[1];
            $parentType = $matches[2];
            $parentName = $matches[3];
            if ($parentType === 'company') {
                return __('audit_logs.imported_entities_for_company', ['entities' => $entity, 'company' => $parentName]);
            } elseif ($parentType === 'department') {
                return __('audit_logs.imported_entities_for_department', ['entities' => $entity, 'department' => $parentName]);
            }
        }
        if (preg_match('/Exported excel file for ([a-zA-Z_]+) for ([a-zA-Z_]+) (.+)/i', $actionPerform, $matches)) {
            $entity = $matches[1];
            $parentType = $matches[2];
            $parentName = $matches[3];
            if ($parentType === 'company') {
                return __('audit_logs.exported_entities_for_company', ['entities' => $entity, 'company' => $parentName]);
            } elseif ($parentType === 'department') {
                return __('audit_logs.exported_entities_for_department', ['entities' => $entity, 'department' => $parentName]);
            }
        }

        // Return original text if no pattern matches
        return $actionPerform;
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
