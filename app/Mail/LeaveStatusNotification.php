<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Leave $leave;
    public User $user;
    public bool $approved;

    public function __construct(Leave $leave, User $user, bool $approved)
    {
        $this->leave = $leave;
        $this->user = $user;
        $this->approved = $approved;
    }

    public function build()
    {
        $locale = $this->user->preferred_language ?? config('app.locale', 'fr');
        $subject = $this->approved
            ? ($locale === 'en' ? 'Your Leave Request Has Been Approved' : 'Votre demande de congé a été approuvée')
            : ($locale === 'en' ? 'Your Leave Request Has Been Rejected' : 'Votre demande de congé a été rejetée');

        return $this->markdown('email.leave.status-notification', [
            'leave' => $this->leave,
            'user' => $this->user,
            'approved' => $this->approved,
            'locale' => $locale,
        ])->subject($subject);
    }
}
