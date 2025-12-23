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
            'created_overtime',
            $overtime,
            [],
            [],
            [
                'translation_key' => 'created_overtime',
                'translation_params' => ['date' => $overtime->start_time],
            ]
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
            $translationKey = $overtime->approval_status == 1 ? 'approved_overtime' : 'rejected_overtime';
            $translationParams = ['user' => $overtime->user->name, 'date' => $overtime->overtime_date];
            $changes = $overtime->approval_status == 1 ? 'approved_overtime' : 'rejected_overtime';
            $status = "overtime_" . ($overtime->approval_status == 1 ? "approved" : "rejected");
        } else {
            $status = "overtime_updated";
            $translationKey = 'updated_overtime';
            $translationParams = ['user' => $overtime->user->name, 'date' => $overtime->overtime_date];
            $changes = 'updated_overtime';
        }

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes,
            $overtime,
            [],
            [],
            [
                'translation_key' => $translationKey,
                'translation_params' => $translationParams,
            ]
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
            'deleted_overtime',
            $overtime,
            [],
            [],
            [
                'translation_key' => 'deleted_overtime',
                'translation_params' => ['user' => $overtime->user->name, 'date' => $overtime->start_time],
            ]
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
