<?php

namespace App\Mail;

use App\Models\AdvanceSalary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdvanceSalaryRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public AdvanceSalary $advanceSalary;
    public User $employee;
    public User $supervisor;

    /**
     * Create a new message instance.
     */
    public function __construct(AdvanceSalary $advanceSalary, User $employee, User $supervisor)
    {
        $this->advanceSalary = $advanceSalary;
        $this->employee = $employee;
        $this->supervisor = $supervisor;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $locale = $this->supervisor->preferred_language ?? config('app.locale', 'fr');

        $subject = $locale === 'en'
            ? 'New Advance Salary Request from ' . $this->employee->name
            : 'Nouvelle demande d\'avance sur salaire de ' . $this->employee->name;

        return $this->markdown('email.advance-salary.request-notification', [
            'advanceSalary' => $this->advanceSalary,
            'employee' => $this->employee,
            'supervisor' => $this->supervisor,
            'locale' => $locale,
        ])->subject($subject);
    }
}
