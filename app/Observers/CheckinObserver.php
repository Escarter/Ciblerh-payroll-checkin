<?php

namespace App\Observers;

use App\Models\Ticking;

class CheckinObserver
{
    /**
     * Handle the Ticking "created" event.
     *
     * @param  \App\Models\Ticking  $ticking
     * @return void
     */
    public function created(Ticking $ticking)
    {
        // auditLog(
        //     auth()->user(),
        //     'checkin_created',
        //     'web',
        //     __('Created checkin record for') . $ticking->user->name . __(' for the date ') . $ticking->start_time
        // );
    }

    /**
     * Handle the Ticking "updated" event.
     *
     * @param  \App\Models\Ticking  $ticking
     * @return void
     */
    public function updated(Ticking $ticking)
    {
        if(auth()->user()->getRoleNames()->first() ===  "supervisor"){
            if ($ticking->supervisor_approval_status !== $ticking->getOriginal('supervisor_approval_status')) {
                $changes = ($ticking->supervisor_approval_status == 1
                    ? __('audit_logs.approved_checkin_supervisor', ['user' => $ticking->user->name, 'date' => $ticking->start_time])
                    : __('audit_logs.rejected_checkin_supervisor', ['user' => $ticking->user->name, 'date' => $ticking->start_time]));
                $status = "checkin_" . ($ticking->supervisor_approval_status == 1 ? "approved" : "rejected");
            } else {
                $status = "checkin_updated";
                $changes = __('audit_logs.updated_checkin', ['user' => $ticking->user->name, 'date' => $ticking->start_time]);
            }
        }else{
            if ($ticking->manager_approval_status !== $ticking->getOriginal('manager_approval_status')) {
                $changes = ($ticking->manager_approval_status == 1
                    ? __('audit_logs.approved_checkin_manager', ['user' => $ticking->user->name, 'date' => $ticking->start_time])
                    : __('audit_logs.rejected_checkin_manager', ['user' => $ticking->user->name, 'date' => $ticking->start_time]));
                $status = "checkin_" . ($ticking->manager_approval_status == 1 ? "approved" : "rejected");
            } else {
                $status = "checkin_updated";
                $changes = __('audit_logs.updated_checkin', ['user' => $ticking->user->name, 'date' => $ticking->start_time]);
            }
        }
        

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes
        );
    }

    /**
     * Handle the Ticking "deleted" event.
     *
     * @param  \App\Models\Ticking  $ticking
     * @return void
     */
    public function deleted(Ticking $ticking)
    {
        auditLog(
            auth()->user(),
            'checkin_deleted',
            'web',
            __('audit_logs.deleted_checkin', ['user' => $ticking->user->name, 'date' => $ticking->start_time])
        );
    }

    /**
     * Handle the Ticking "restored" event.
     *
     * @param  \App\Models\Ticking  $ticking
     * @return void
     */
    public function restored(Ticking $ticking)
    {
        //
    }

    /**
     * Handle the Ticking "force deleted" event.
     *
     * @param  \App\Models\Ticking  $ticking
     * @return void
     */
    public function forceDeleted(Ticking $ticking)
    {
        //
    }
}
