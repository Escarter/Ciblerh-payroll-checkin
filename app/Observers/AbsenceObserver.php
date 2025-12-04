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
            __('Created an absence with date '). $absence->absence_date
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
            $changes = ($absence->approval_status == 1 ? __('common.approved') : __('common.rejected') ). " ". __(' the absence from '). $absence->user->name . __(' with date ') . $absence->absence_date; 
            $status = "absence_". ($absence->approval_status == 1 ? "approved" : "rejected");
        }else{
            $status = "absence_updated";
            $changes = __('Updated the absence from '). $absence->user->name . __(' with date ') . $absence->absence_date; 
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
            __('Deleted absence from ') . $absence->user->name . __(' with date ') . $absence->absence_date 
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
