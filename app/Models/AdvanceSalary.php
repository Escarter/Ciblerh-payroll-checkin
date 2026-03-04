<?php

namespace App\Models;

use NumberFormatter;
use Illuminate\Support\Str;
use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdvanceSalary extends Model
{
    use HasFactory, HasUUID, SoftDeletes;

    const TYPE_ADVANCE = 'advance';
    const TYPE_LOAN = 'loan';

    const APPROVAL_STATUS_PENDING = 0;
    const APPROVAL_STATUS_APPROVED = 1;
    const APPROVAL_STATUS_REJECTED = 2;

    protected $guarded = [];

    protected $casts = [
        'repayment_from_month' => 'datetime',
        'repayment_to_month' => 'datetime',
        'advance_for_month' => 'date',
        'is_fully_repaid' => 'boolean',
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
        $user = auth()->user();
        if ($user && $user->hasRole('supervisor')) {
            return $query->whereIn('department_id', $user->supDepartments->pluck('department_id'));
        }
        return $query->where('id', 0); // Return no results if no supervisor context
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getAmountInWordsAttribute()
    {
        $formater = new NumberFormatter(config("app.locale"), NumberFormatter::SPELLOUT);
        $formater->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-numbering-verbose");

        return $formater->format($this->amount);
    }
    public function getInitialsAttribute()
    {
        $name = explode(" ",$this->beneficiary_name);
        if(count($name) >= 2){
            return strtoupper(Str::substr($name[0], 0, 1)) . "" . strtoupper(Str::substr($name[1], 0, 1));
        }else{
            return strtoupper(Str::substr($name[0], 0, 1)) ;
        }
    }

    public function isApproved()
    {
        $status = $this->getEffectiveApprovalStatus();
        return $status === self::APPROVAL_STATUS_APPROVED;
    }

    /** Get effective approval status: manager overrides supervisor, falls back to legacy approval_status */
    public function getEffectiveApprovalStatus(): int
    {
        if ($this->manager_approval_status !== null) {
            return (int) $this->manager_approval_status;
        }
        if ($this->supervisor_approval_status !== null) {
            return (int) $this->supervisor_approval_status;
        }
        return (int) ($this->approval_status ?? self::APPROVAL_STATUS_PENDING);
    }

    public function isPendingSupervisorApproval(): bool
    {
        return ($this->supervisor_approval_status ?? self::APPROVAL_STATUS_PENDING) === self::APPROVAL_STATUS_PENDING;
    }

    public function isPendingManagerApproval(): bool
    {
        $supervisorApproved = ($this->supervisor_approval_status ?? self::APPROVAL_STATUS_PENDING) === self::APPROVAL_STATUS_APPROVED;
        $managerPending = ($this->manager_approval_status ?? self::APPROVAL_STATUS_PENDING) === self::APPROVAL_STATUS_PENDING;
        return $supervisorApproved && $managerPending;
    }

    public function canSupervisorEditAmount(): bool
    {
        return $this->isPendingSupervisorApproval();
    }

    public static function search($query)
    {
        return empty($query) ? 
        static::query()->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
             return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
        }) :
        static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                 return $query->whereIn('department_id',auth()->user()->supDepartments->pluck('department_id'));
            })
            ->where(function ($q) use ($query) {
                $q->where('amount', 'like', '%' . $query . '%');
                $q->orWhere('reason', 'like', '%' . $query . '%');
                $q->orWhere('beneficiary_name', 'like', '%' . $query . '%');
                $q->orWhere('beneficiary_mobile_money_number', 'like', '%' . $query . '%');
                $q->orWhereHas('user', function ($q) use ($query) {
                    $q->where('first_name', 'like', '%' . $query . '%');
                    $q->orWhere('last_name', 'like', '%' . $query . '%');
                    $q->orWhere('matricule', 'like', '%' . $query . '%');
                    $q->orWhere('email', 'like', '%' . $query . '%');
                });
            });
    }
}
