<?php

namespace App\Notifications;

use App\Models\PayslipMatchingProposal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SftpProposalCreatedNotification extends Notification
{
    public PayslipMatchingProposal $proposal;

    public function __construct(PayslipMatchingProposal $proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Mail only — sent via Notification::route('mail', $email)->notify(...)
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $proposedMatch = $this->proposal->proposed_match ?? [];
        $best = $proposedMatch['best_match'] ?? null;
        $companyName = $best['company_name'] ?? ($proposedMatch['company_raw'] ?? 'Unknown');
        $confidence = $best ? round(($best['confidence'] ?? 0) * 100, 1) . '%' : '—';
        $period = $this->proposal->matched_month && $this->proposal->matched_year
            ? sprintf('%02d/%d', $this->proposal->matched_month, $this->proposal->matched_year)
            : '—';
        $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';

        return (new MailMessage)
            ->subject('[CibleRH] New payslip proposal created — ' . $this->proposal->file_name)
            ->greeting('New payslip proposal')
            ->line('A new payslip proposal has been created and is awaiting review.')
            ->line('**File:** ' . $this->proposal->file_name)
            ->line('**Status:** ' . ucfirst($this->proposal->status))
            ->line('**Suggested Company:** ' . $companyName)
            ->line('**Confidence:** ' . $confidence)
            ->line('**Period:** ' . $period)
            ->action('Review proposal', $link)
            ->line('Click the button above to open the proposal in the validator. You will be asked to log in if not already authenticated.');
    }
}
