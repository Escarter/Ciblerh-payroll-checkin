<?php

namespace App\Console\Commands;

use App\Mail\AdvanceSalaryPendingReminderNotification;
use App\Models\AdvanceSalary;
use App\Models\Company;
use App\Models\SupervisorDepartment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPendingAdvanceSalaryReminder extends Command
{
    protected $signature = 'advance-salary:send-pending-reminder';

    protected $description = 'Send daily email reminders to supervisors and managers with pending advance salary requests';

    public function handle(): int
    {
        $count = 0;

        // Notify supervisors with pending their approval
        $pendingSupervisor = AdvanceSalary::whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('supervisor_approval_status')
                    ->orWhere('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING);
            })
            ->with('user')
            ->get()
            ->groupBy('department_id');

        foreach ($pendingSupervisor as $departmentId => $advanceSalaries) {
            $supervisors = SupervisorDepartment::where('department_id', $departmentId)
                ->with('supervisor')
                ->get();
            foreach ($supervisors as $supDept) {
                $supervisor = $supDept->supervisor;
                if ($supervisor && $supervisor->email) {
                    Mail::to($supervisor->email)->send(
                        new AdvanceSalaryPendingReminderNotification($advanceSalaries, $supervisor, 'supervisor')
                    );
                    $count++;
                }
            }
        }

        // Notify managers with pending their approval (supervisor already approved)
        $pendingManager = AdvanceSalary::whereNull('deleted_at')
            ->where('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('manager_approval_status')
                    ->orWhere('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING);
            })
            ->with('user')
            ->get()
            ->groupBy('company_id');

        foreach ($pendingManager as $companyId => $advanceSalaries) {
            $company = Company::find($companyId);
            if (!$company) {
                continue;
            }
            foreach ($company->managers as $manager) {
                if ($manager->email) {
                    Mail::to($manager->email)->send(
                        new AdvanceSalaryPendingReminderNotification($advanceSalaries, $manager, 'manager')
                    );
                    $count++;
                }
            }
        }

        $this->info("Sent {$count} reminder email(s).");

        return Command::SUCCESS;
    }
}
