<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdvanceSalaryPendingReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $pendingAdvanceSalaries;
    public $recipient;
    public $recipientRole;

    public function __construct($pendingAdvanceSalaries, $recipient, string $recipientRole)
    {
        $this->pendingAdvanceSalaries = $pendingAdvanceSalaries;
        $this->recipient = $recipient;
        $this->recipientRole = $recipientRole;
    }

    public function build()
    {
        $locale = $this->recipient->preferred_language ?? config('app.locale', 'fr');
        $subject = $locale === 'en'
            ? 'Reminder: ' . $this->pendingAdvanceSalaries->count() . ' Advance Salary Request(s) Pending Approval'
            : 'Rappel: ' . $this->pendingAdvanceSalaries->count() . ' demande(s) d\'avance sur salaire en attente d\'approbation';

        return $this->markdown('email.advance-salary.pending-reminder', [
            'pendingAdvanceSalaries' => $this->pendingAdvanceSalaries,
            'recipient' => $this->recipient,
            'recipientRole' => $this->recipientRole,
            'locale' => $locale,
        ])->subject($subject);
    }
}
