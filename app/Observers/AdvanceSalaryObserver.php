<?php

namespace App\Observers;

use App\Models\AdvanceSalary;

class AdvanceSalaryObserver
{
    /**
     * Handle the AdvanceSalary "created" event.
     *
     * @param  \App\Models\AdvanceSalary  $advanceSalary
     * @return void
     */
    public function created(AdvanceSalary $advanceSalary)
    {
        auditLog(
            auth()->user(),
            'advanceSalary_created',
            'web',
            'created_advance_salary',
            $advanceSalary,
            [],
            [],
            [
                'translation_key' => 'created_advance_salary',
                'translation_params' => ['amount' => $advanceSalary->amount],
                'link' => '<a href="/portal/advance-salaries?advance_salary_id="' . $advanceSalary->id . '>' . $advanceSalary->amount . '</a>',
            ]
        );
    }

    /**
     * Handle the AdvanceSalary "updated" event.
     *
     * @param  \App\Models\AdvanceSalary  $advanceSalary
     * @return void
     */
    public function updated(AdvanceSalary $advanceSalary)
    {
        if ($advanceSalary->approval_status !== $advanceSalary->getOriginal('approval_status')) {
            $translationKey = $advanceSalary->approval_status == 1 ? 'approved_advance_salary' : 'rejected_advance_salary';
            $translationParams = ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) . 'XAF'];
            $changes = $advanceSalary->approval_status == 1 ? 'approved_advance_salary' : 'rejected_advance_salary';
            $status = "advanceSalary_" . ($advanceSalary->approval_status == 1 ? "approved" : "rejected");
        } else {
            $status = "advanceSalary_updated";
            $translationKey = 'updated_advance_salary';
            $translationParams = ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) .'XAF'];
            $changes = 'updated_advance_salary';
        }

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes,
            $advanceSalary,
            [],
            [],
            [
                'translation_key' => $translationKey,
                'translation_params' => $translationParams,
            ]
        );
    }

    /**
     * Handle the AdvanceSalary "deleted" event.
     *
     * @param  \App\Models\AdvanceSalary  $advanceSalary
     * @return void
     */
    public function deleted(AdvanceSalary $advanceSalary)
    {
        auditLog(
            auth()->user(),
            'advanceSalary_deleted',
            'web',
            'deleted_advance_salary',
            $advanceSalary,
            [],
            [],
            [
                'translation_key' => 'deleted_advance_salary',
                'translation_params' => ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) . 'XAF'],
            ]
        );
    }

    /**
     * Handle the AdvanceSalary "restored" event.
     *
     * @param  \App\Models\AdvanceSalary  $advanceSalary
     * @return void
     */
    public function restored(AdvanceSalary $advanceSalary)
    {
        //
    }

    /**
     * Handle the AdvanceSalary "force deleted" event.
     *
     * @param  \App\Models\AdvanceSalary  $advanceSalary
     * @return void
     */
    public function forceDeleted(AdvanceSalary $advanceSalary)
    {
        //
    }
}
