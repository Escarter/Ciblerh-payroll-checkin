<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $guarded  = [];

    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getInitialsAttribute()
    {
        return strtoupper(Str::substr($this->first_name, 0, 1)) . "" . strtoupper(Str::substr($this->last_name, 0, 1));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('email_sent_status','successful')->where('sms_sent_status','successful');
    }
    public function scopeFailed($query)
    {
        return $query->where('email_sent_status','failed');
    }

    public function sendProcess()
    {
        return $this->belongsTo(SendPayslipProcess::class, 'send_payslip_process_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public static function search($query)
    {
        return empty($query) ?
            static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
            }) :
            static::query()
            ->when(auth()->user()->getRoleNames()->first() === "supervisor", function ($query) {
                return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
            })
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%');
                $q->orWhere('last_name', 'like', '%' . $query . '%');
                $q->orWhere('email', 'like', '%' . $query . '%');
                $q->orWhere('matricule', 'like', '%' . $query . '%');
                $q->orWhere('phone', 'like', '%' . $query . '%');
                $q->orWhere('month', 'like', '%' . $query . '%');
                $q->orWhere('email_sent_status', 'like', '%' . $query . '%');
                $q->orWhere('sms_sent_status', 'like', '%' . $query . '%');
            });
    }
}
