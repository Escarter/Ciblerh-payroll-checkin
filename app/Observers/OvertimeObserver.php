<?php

namespace App\Observers;

use App\Models\Overtime;

class OvertimeObserver
{
    /**
     * Handle the Overtime "created" event.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return void
     */
    public function created(Overtime $overtime)
    {
        auditLog(
            auth()->user(),
            'overtime_created',
            'web',
            __('Created overtime record for the date ') . $overtime->start_time
        );
    }

    /**
     * Handle the Overtime "updated" event.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return void
     */
    public function updated(Overtime $overtime)
    {
        if ($overtime->approval_status !== $overtime->getOriginal('approval_status')) {
            $changes = ($overtime->approval_status == 1 ? __('Approved') : __('Rejected')) . " " . __(' the overtime from ') . $overtime->user->name . __(' with date') . $overtime->overtime_date ;
            $status = "overtime_" . ($overtime->approval_status == 1 ? "approved" : "rejected");
        } else {
            $status = "overtime_updated";
            $changes =  __('Updated the overtime from') . $overtime->user->name . __(' with date ') . $overtime->overtime_date ; ;
        }

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes
        );
    }

    /**
     * Handle the Overtime "deleted" event.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return void
     */
    public function deleted(Overtime $overtime)
    {
        auditLog(
            auth()->user(),
            'overtime_deleted',
            'web',
            __('Deleted overtime record for ') . $overtime->user->name . __(' for the date ') . $overtime->start_time
        );
    }

    /**
     * Handle the Overtime "restored" event.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return void
     */
    public function restored(Overtime $overtime)
    {
        //
    }

    /**
     * Handle the Overtime "force deleted" event.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return void
     */
    public function forceDeleted(Overtime $overtime)
    {
        //
    }
}
