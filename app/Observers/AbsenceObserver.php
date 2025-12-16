<?php

namespace App\Observers;

use App\Models\Absence;
use Illuminate\Support\Str;

class AbsenceObserver
{
    /**
     * Handle the Absence "created" event.
     *
     * @param  \App\Models\Absence  $absence
     * @return void
     */
    public function created(Absence $absence)
    {
        auditLog(
            auth()->user(),
            'absence_created',
            'web',
            __('audit_logs.created_absence', ['date' => $absence->absence_date])
        );
    }

    /**
     * Handle the Absence "updated" event.
     *
     * @param  \App\Models\Absence  $absence
     * @return void
     */
    public function updated(Absence $absence)
    {
        
        if ($absence->approval_status !== $absence->getOriginal('approval_status')) {
            $changes = ($absence->approval_status == 1
                ? __('audit_logs.approved_absence', ['user' => $absence->user->name, 'date' => $absence->absence_date])
                : __('audit_logs.rejected_absence', ['user' => $absence->user->name, 'date' => $absence->absence_date])); 
            $status = "absence_". ($absence->approval_status == 1 ? "approved" : "rejected");
        }else{
            $status = "absence_updated";
            $changes = __('audit_logs.updated_absence', ['user' => $absence->user->name, 'date' => $absence->absence_date]); 
        }
    
        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes
        );
    }

    /**
     * Handle the Absence "deleted" event.
     *
     * @param  \App\Models\Absence  $absence
     * @return void
     */
    public function deleted(Absence $absence)
    {
        auditLog(
            auth()->user(),
            'absence_deleted',
            'web',
            __('audit_logs.deleted_absence', ['user' => $absence->user->name, 'date' => $absence->absence_date]) 
        );
    }

    /**
     * Handle the Absence "restored" event.
     *
     * @param  \App\Models\Absence  $absence
     * @return void
     */
    public function restored(Absence $absence)
    {
        //
    }

    /**
     * Handle the Absence "force deleted" event.
     *
     * @param  \App\Models\Absence  $absence
     * @return void
     */
    public function forceDeleted(Absence $absence)
    {
        //
    }
}
