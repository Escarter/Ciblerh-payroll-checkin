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
            'created_absence',
            $absence,
            [],
            [],
            [
                'translation_key' => 'created_absence',
                'translation_params' => ['date' => $absence->absence_date],
            ]
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
            $translationKey = $absence->approval_status == 1 ? 'approved_absence' : 'rejected_absence';
            $translationParams = ['user' => $absence->user->name, 'date' => $absence->absence_date];
            $changes = $absence->approval_status == 1 ? 'approved_absence' : 'rejected_absence';
            $status = "absence_". ($absence->approval_status == 1 ? "approved" : "rejected");
        }else{
            $status = "absence_updated";
            $translationKey = 'updated_absence';
            $translationParams = ['user' => $absence->user->name, 'date' => $absence->absence_date];
            $changes = 'updated_absence'; 
        }
    
        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes,
            $absence,
            [],
            [],
            [
                'translation_key' => $translationKey,
                'translation_params' => $translationParams,
            ]
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
            'deleted_absence',
            $absence,
            [],
            [],
            [
                'translation_key' => 'deleted_absence',
                'translation_params' => ['user' => $absence->user->name, 'date' => $absence->absence_date],
            ] 
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
