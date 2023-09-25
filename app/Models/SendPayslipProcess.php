<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SendPayslipProcess extends Model
{
    use HasFactory;

    protected $guarded  = [];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }

    public function owner()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'send_payslip_process_id');
    }
}
