<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

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
        $action = explode('_', $this->action_type)[1];
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
