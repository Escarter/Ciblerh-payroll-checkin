<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmittedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Leave $leave;

    public User $employee;

    public User $supervisor;

    public function __construct(Leave $leave, User $employee, User $supervisor)
    {
        $this->leave = $leave;
        $this->employee = $employee;
        $this->supervisor = $supervisor;
    }

    public function build()
    {
        $locale = $this->supervisor->preferred_language ?? config('app.locale', 'fr');
        $subject = $locale === 'en'
            ? 'New Leave Request from ' . $this->employee->name
            : 'Nouvelle demande de congé de ' . $this->employee->name;

        return $this->markdown('email.leave.request-submitted', [
            'leave' => $this->leave,
            'employee' => $this->employee,
            'supervisor' => $this->supervisor,
            'locale' => $locale,
        ])->subject($subject);
    }
}
