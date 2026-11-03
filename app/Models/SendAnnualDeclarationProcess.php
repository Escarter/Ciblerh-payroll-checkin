<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendAnnualDeclarationProcess extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUCCESSFUL = 'successful';

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function declarations()
    {
        return $this->hasMany(AnnualDeclaration::class, 'send_annual_declaration_process_id');
    }

    public function sftpProposal()
    {
        return $this->belongsTo(PayslipMatchingProposal::class, 'sftp_proposal_id');
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
     * A process for the same scope that is running or already completed blocks a new one.
     */
    public static function blockingProcessFor(int $companyId, ?int $departmentId, int $year): ?self
    {
        return static::query()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->when($departmentId, fn ($q) => $q->where(fn ($inner) => $inner->where('department_id', $departmentId)->orWhereNull('department_id')))
            ->whereIn('status', [self::STATUS_PROCESSING, self::STATUS_SUCCESSFUL])
            ->latest('id')
            ->first();
    }
}
