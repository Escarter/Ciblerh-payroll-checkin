<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PayslipMatchingProposal;

class SendPayslipProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded  = [];


    public function getMonthAttribute($value)
    {
        return normalizeMonthToEnglishName($value) ?? $value;
    }

    public function setMonthAttribute($value): void
    {
        $this->attributes['month'] = normalizeMonthToEnglishName($value) ?? $value;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentWithTrashed()
    {
        return $this->belongsTo(Department::class)->withTrashed();
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

    public function owner()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'send_payslip_process_id');
    }

    /**
     * The SFTP matching proposal that triggered this delivery process (nullable for non-SFTP uploads).
     */
    public function sftpProposal()
    {
        return $this->belongsTo(PayslipMatchingProposal::class, 'sftp_proposal_id');
    }
}
