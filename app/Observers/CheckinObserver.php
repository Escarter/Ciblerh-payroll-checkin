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
                $translationKey = $ticking->supervisor_approval_status == 1 ? 'approved_checkin_supervisor' : 'rejected_checkin_supervisor';
                $translationParams = ['user' => $ticking->user->name, 'date' => $ticking->start_time];
                $changes = $ticking->supervisor_approval_status == 1 ? 'approved_checkin_supervisor' : 'rejected_checkin_supervisor';
                $status = "checkin_" . ($ticking->supervisor_approval_status == 1 ? "approved" : "rejected");
            } else {
                $status = "checkin_updated";
                $translationKey = 'updated_checkin';
                $translationParams = ['user' => $ticking->user->name, 'date' => $ticking->start_time];
                $changes = 'updated_checkin';
            }
        }else{
            if ($ticking->manager_approval_status !== $ticking->getOriginal('manager_approval_status')) {
                $translationKey = $ticking->manager_approval_status == 1 ? 'approved_checkin_manager' : 'rejected_checkin_manager';
                $translationParams = ['user' => $ticking->user->name, 'date' => $ticking->start_time];
                $changes = $ticking->manager_approval_status == 1 ? 'approved_checkin_manager' : 'rejected_checkin_manager';
                $status = "checkin_" . ($ticking->manager_approval_status == 1 ? "approved" : "rejected");
            } else {
                $status = "checkin_updated";
                $translationKey = 'updated_checkin';
                $translationParams = ['user' => $ticking->user->name, 'date' => $ticking->start_time];
                $changes = 'updated_checkin';
            }
        }
        

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes,
            $ticking,
            [],
            [],
            [
                'translation_key' => $translationKey,
                'translation_params' => $translationParams,
            ]
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
            'deleted_checkin',
            $ticking,
            [],
            [],
            [
                'translation_key' => 'deleted_checkin',
                'translation_params' => ['user' => $ticking->user->name, 'date' => $ticking->start_time],
            ]
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
