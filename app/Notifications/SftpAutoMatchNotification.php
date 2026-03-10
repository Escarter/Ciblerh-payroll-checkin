<?php

namespace App\Notifications;

use App\Models\PayslipMatchingProposal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SftpAutoMatchNotification extends Notification
{
    public PayslipMatchingProposal $proposal;
    public string $type;

    /**
     * @param PayslipMatchingProposal $proposal
     * @param string $type  'auto_validated' | 'dept_required'
     */
    public function __construct(PayslipMatchingProposal $proposal, string $type)
    {
        $this->proposal = $proposal;
        $this->type     = $type;
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
        $best          = $proposedMatch['best_match'] ?? null;
        $companyName   = $best['company_name'] ?? ($proposedMatch['company_raw'] ?? $this->proposal->file_name);
        $confidence    = $best ? round(($best['confidence'] ?? 0) * 100, 1) . '%' : '—';
        $strategy      = $best['strategy'] ?? '—';
        $period        = $this->proposal->matched_month && $this->proposal->matched_year
            ? sprintf('%02d/%d', $this->proposal->matched_month, $this->proposal->matched_year)
            : '—';

        if ($this->type === 'auto_validated') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';

            return (new MailMessage)
                ->subject('[CibleRH] Payslip auto-validated — ' . $this->proposal->file_name)
                ->greeting('Auto-match notification')
                ->line('A payslip has been automatically validated with high confidence.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->action('Open proposal', $link)
                ->line('The proposal is in the Validated tab and is ready for bulk processing. Click the button above to review it directly — you will be asked to log in if not already authenticated.');
        }

        // type === 'dept_required'
        $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=edit';

        return (new MailMessage)
            ->subject('[CibleRH] Auto-match: department review required — ' . $this->proposal->file_name)
            ->greeting('Auto-match: action required')
            ->line('A payslip matched a company with high confidence but the department could not be inferred automatically (company has multiple active departments). Manual review is required.')
            ->line('**File:** ' . $this->proposal->file_name)
            ->line('**Company:** ' . $companyName)
            ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
            ->line('**Period:** ' . $period)
            ->action('Open & assign department', $link)
            ->line('Click the button above to open the proposal directly. You will be asked to log in if not already authenticated, then the assignment form will open automatically.');
    }
}
