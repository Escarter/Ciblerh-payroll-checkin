<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    const STATUS_PENDING = 0;
    const STATUS_SUCCESSFUL = 1;
    const STATUS_FAILED = 2;
    //used only for reporting view
    const SMS_STATUS_PENDING = 3;
    const SMS_STATUS_SUCCESSFUL = 4;
    const SMS_STATUS_FAILED = 5;

    protected $guarded  = [];

    public function scopeSupervisor($query)
    {
        return $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
    }

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }

    public function getEncryptionStatusTextAttribute()
    {
        return match($this->encryption_status){
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_SUCCESSFUL => __('Successful'),
            default => __('Not recorded')
        };
    }
    public function getEmailStatusTextAttribute()
    {
        return match($this->email_sent_status){
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_SUCCESSFUL => __('Successful'),
            default => ''
        };
    }
    public function getSmsStatusTextAttribute()
    {
        return match($this->sms_sent_status){
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_SUCCESSFUL => __('Successful'),
            default => ''
        };
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
        return $query->where('email_sent_status',self::STATUS_SUCCESSFUL)->where('sms_sent_status',self::STATUS_SUCCESSFUL);
    }
    public function scopeFailed($query)
    {
        return $query->where('email_sent_status',self::STATUS_FAILED);
    }

    public function sendProcess()
    {
        return $this->belongsTo(SendPayslipProcess::class, 'send_payslip_process_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class,'employee_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class,'department_id');
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
