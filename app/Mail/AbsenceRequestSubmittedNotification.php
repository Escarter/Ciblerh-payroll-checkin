<?php

namespace App\Mail;

use App\Models\Absence;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbsenceRequestSubmittedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Absence $absence;
    public User $employee;
    public User $supervisor;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function __construct(Absence $absence, User $employee, User $supervisor, ?string $startDate = null, ?string $endDate = null)
    {
        $this->absence = $absence;
        $this->employee = $employee;
        $this->supervisor = $supervisor;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function build()
    {
        $locale = $this->supervisor->preferred_language ?? config('app.locale', 'fr');
        $subject = $locale === 'en'
            ? 'New Leave of Absence Request from ' . $this->employee->name
            : 'Nouvelle demande d\'absence de ' . $this->employee->name;

        return $this->markdown('email.absence.request-submitted', [
            'absence' => $this->absence,
            'employee' => $this->employee,
            'supervisor' => $this->supervisor,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'locale' => $locale,
        ])->subject($subject);
    }
}
