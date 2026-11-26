<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualDeclaration extends Model
{
    use HasFactory, SoftDeletes;

    // Same numeric values as Payslip so delivery statuses stay comparable across documents.
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESSFUL = 1;
    public const STATUS_FAILED = 2;
    public const STATUS_DISABLED = 3;
    public const STATUS_SKIPPED = 4;

    public const PAGE_MATCHED = 'matched';
    public const PAGE_UNMATCHED = 'unmatched';
    public const PAGE_EMPTY = 'empty';

    protected $guarded = [];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'email_bounced' => 'boolean',
        'email_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'sms_delivered_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(SendAnnualDeclarationProcess::class, 'send_annual_declaration_process_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function scopeSendable($query)
    {
        return $query->where('page_status', self::PAGE_MATCHED);
    }

    public function scopeNeedsReview($query)
    {
        return $query->whereIn('page_status', [self::PAGE_UNMATCHED, self::PAGE_EMPTY]);
    }

    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }

    public function scopeManager($query)
    {
        $manager = auth()->user();
        if ($manager && $manager->hasRole('manager')) {
            return $query->whereIn('company_id', $manager->managerCompanies->pluck('id'));
        }

        return $query;
    }

    /**
     * Declarations an employee may see: sendable pages linked to them by id or by matricule in the same company.
     */
    public function scopeVisibleToEmployee($query, User $user)
    {
        $matricule = User::normalizeMatricule($user->matricule);

        return $query->where('page_status', self::PAGE_MATCHED)
            ->where(function ($q) use ($user, $matricule) {
                $q->where('employee_id', $user->id);
                if (!empty($matricule)) {
                    $q->orWhere(function ($inner) use ($user, $matricule) {
                        $inner->where('company_id', $user->company_id)
                            ->whereRaw('UPPER(TRIM(matricule)) = ?', [$matricule]);
                    });
                }
            });
    }

    public function isVisibleToEmployee(User $user): bool
    {
        return static::query()->whereKey($this->getKey())->visibleToEmployee($user)->exists();
    }
}
