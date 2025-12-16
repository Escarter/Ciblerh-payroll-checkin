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
            __('audit_logs.created_advance_salary', ['amount' => $advanceSalary->amount]) . ' <a href="/portal/advance-salaries?advance_salary_id="' . $advanceSalary->id . '>' . $advanceSalary->amount . '</a>'
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
            $changes = ($advanceSalary->approval_status == 1
                ? __('audit_logs.approved_advance_salary', ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) . 'XAF'])
                : __('audit_logs.rejected_advance_salary', ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) . 'XAF']));
            $status = "advanceSalary_" . ($advanceSalary->approval_status == 1 ? "approved" : "rejected");
        } else {
            $status = "advanceSalary_updated";
            $changes = __('audit_logs.updated_advance_salary', ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) .'XAF']);
        }

        auditLog(
            auth()->user(),
            $status,
            'web',
            $changes
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
            __('audit_logs.deleted_advance_salary', ['user' => $advanceSalary->user->name, 'amount' => number_format($advanceSalary->amount) . 'XAF'])
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
