<?php

namespace App\Mail;

use App\Models\Absence;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbsenceStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Absence $absence;
    public User $user;
    public bool $approved;

    public function __construct(Absence $absence, User $user, bool $approved)
    {
        $this->absence = $absence;
        $this->user = $user;
        $this->approved = $approved;
    }

    public function build()
    {
        $locale = $this->user->preferred_language ?? config('app.locale', 'fr');
        $subject = $this->approved
            ? ($locale === 'en' ? 'Your Absence Request Has Been Approved' : 'Votre demande d\'absence a été approuvée')
            : ($locale === 'en' ? 'Your Absence Request Has Been Rejected' : 'Votre demande d\'absence a été rejetée');

        return $this->markdown('email.absence.status-notification', [
            'absence' => $this->absence,
            'user' => $this->user,
            'approved' => $this->approved,
            'locale' => $locale,
        ])->subject($subject);
    }
}
