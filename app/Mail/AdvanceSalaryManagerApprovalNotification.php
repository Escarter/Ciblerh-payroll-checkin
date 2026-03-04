<?php

namespace App\Mail;

use App\Models\AdvanceSalary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdvanceSalaryManagerApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public AdvanceSalary $advanceSalary;
    public User $employee;
    public User $manager;

    public function __construct(AdvanceSalary $advanceSalary, User $employee, User $manager)
    {
        $this->advanceSalary = $advanceSalary;
        $this->employee = $employee;
        $this->manager = $manager;
    }

    public function build()
    {
        $locale = $this->manager->preferred_language ?? config('app.locale', 'fr');
        $subject = $locale === 'en'
            ? 'Advance Salary Request Pending Your Approval'
            : 'Demande d\'avance sur salaire en attente de votre approbation';

        return $this->markdown('email.advance-salary.manager-approval-notification', [
            'advanceSalary' => $this->advanceSalary,
            'employee' => $this->employee,
            'manager' => $this->manager,
            'locale' => $locale,
        ])->subject($subject);
    }
}
