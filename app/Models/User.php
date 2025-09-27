<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Traits\HasUUID;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements HasLocalePreference
{
    use HasApiTokens, HasFactory, Notifiable, HasUUID, HasRoles, SoftDeletes;

    const STATUS_ACTIVE = 1;
    const STATUS_BANNED = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'net_salary' => 'double',
        'monthly_leave_allocation'=>'double',
        'remaining_leave_days'=>'double',
        'status'=>'boolean',
        'date_of_birth' => 'date'
    ];

    /**
     * Get the user's preferred locale.
     *
     * @return string
     */
    public function preferredLocale()
    {
        return $this->preferred_language;
    }

    protected function Matricule(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => strtoupper($value),
        );
    }

    public function scopeManager($query) : Builder
    {
        $manager = auth()->user();
        if ($manager && $manager->hasRole('manager')) {
            return $query->whereIn('company_id', $manager->managerCompanies->pluck('id'));
        }
        return $query;
    }
    
    public function scopeWithAndWhereHas($query, $relation, $constraint)
    {
        return $query->whereHas($relation, $constraint)
            ->with([$relation => $constraint]);
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }
    
    public function getInitialsAttribute()
    {
        return strtoupper(Str::substr($this->first_name, 0, 1)) . "" . strtoupper(Str::substr($this->last_name, 0, 1));
    }

    public function scopeSupervisor($query)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('supervisor')) {
            return $query->whereIn('department_id', $user->supDepartments->pluck('department_id'))
                         ->whereHas('roles', function($query) {
                             $query->where('name', 'employee');
                         })
                         ->whereDoesntHave('roles', function($query) {
                             $query->whereIn('name', ['admin', 'manager', 'supervisor']);
                         });
        }
        return $query->where('id', 0); // Return no results if no supervisor context
    }

    public function getStatusStyleAttribute()
    {
        return match($this->status){
            true => 'success',
            false => 'danger',
            default => 'info'
        };
    }
    public function getStatusTextAttribute()
    {
        return match($this->status){
            true => __('Active'),
            false => __('Banned'),
            default => __('Active'),
        };
    }

    public function getRoleStyleAttribute()
    {
        return match ($this->getRoleNames()->first()) {
            'supervisor' => 'info',
            'employee' => 'primary',
            'manager' => 'success',
            'admin' => 'warning',
            default=>'danger'
        };
    }
    
    public function supDepartments()
    {
        return $this->hasMany(SupervisorDepartment::class,'supervisor_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class,'user_id');
    }
    public function auditlogs()
    {
        return $this->hasMany(AuditLog::class,'user_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function tickings(): HasMany
    {
        return $this->hasMany(Ticking::class,'user_id');
    }
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class,'user_id');
    }

    public function managerCompanies()
    {
        return $this->belongsToMany(Company::class, 'manager_companies', 'manager_id', 'company_id');
    }

    public function advanceSalaries(): HasMany
    {
        return $this->hasMany(AdvanceSalary::class,'user_id');
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class,'user_id');
    }

    public function isContractValid()
    {
       if(!empty($this->contract_end)) {
           return Carbon::parse($this->contract_end)->lt(today()) ? true : false;
        }
    }

    public function checkInForToday()
    {
        return auth()->user()->tickings()->whereBetween('start_time', [now()->startOfDay(), now()->endOfDay()])->first();
    }

    public function checkedOutForToday(): bool
    {
        return auth()->user()->checkInForToday()->end_time !== null ? true : false;
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public static function search($query, $user = null)
    {
        $authUser = $user ?? auth()->user();
        $userRole = $authUser ? $authUser->getRoleNames()->first() : 'admin';
        
        return empty($query) ? 
        static::query()->when($userRole === "supervisor", function ($query) use ($authUser) {
            return $query->whereIn('department_id', $authUser->supDepartments->pluck('department_id'));
        })->when($userRole === "manager", function ($query) use ($authUser) {
            return $query->whereIn('company_id', $authUser->managerCompanies->pluck('id'));
        }):
        static::query()->when($userRole === "supervisor", function ($query) use ($authUser) {
            return $query->whereIn('department_id', $authUser->supDepartments->pluck('department_id'));
        })->when($userRole === "manager", function ($query) use ($authUser) {
            return $query->whereIn('company_id', $authUser->managerCompanies->pluck('id'));
        })
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%');
                $q->orWhere('last_name', 'like', '%' . $query . '%');
                $q->orWhere('email', 'like', '%' . $query . '%');
                $q->orWhere('matricule', 'like', '%' . $query . '%');
                $q->orWhere('professional_phone_number', 'like', '%' . $query . '%');
                $q->orWhere('personal_phone_number', 'like', '%' . $query . '%');
                $q->orWhereHas('service', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                });
                $q->orWhereHas('roles', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                });
            });
    }

    public function getHoursWorked($day, $month)
    {
        $ticking = $this->tickings()->whereMonth('start_time',$month)->whereDay('start_time',$day)->first();

        if(!empty($ticking)){
            if(!empty( $ticking->end_time)){
                return Carbon::parse($this->work_start_time)->diff(Carbon::parse($this->work_end_time)->subMinutes(90))->format('%Hh ');
                // return $ticking->end_time->diff($ticking->start_time)->format('%H:%I');
            }
        }
    }

    /**
     * Check if user can switch between employee and admin portals
     * User must have both admin/supervisor/manager AND employee roles
     */
    public function canSwitchPortals()
    {
        return $this->hasAnyRole(['admin', 'manager', 'supervisor']) && $this->hasRole('employee');
    }

    /**
     * Check if user should see admin portal primarily
     */
    public function isAdminUser()
    {
        return $this->hasAnyRole(['admin', 'manager', 'supervisor']);
    }
}
