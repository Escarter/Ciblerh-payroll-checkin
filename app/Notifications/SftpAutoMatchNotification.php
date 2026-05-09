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
     * @param string $type  'auto_validated' | 'dept_required' | 'manual_review' | 'no_match' | 'processing_failed'
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

        if ($this->type === 'manual_review') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=edit';

            return (new MailMessage)
                ->subject('[CibleRH] Manual review required — ' . $this->proposal->file_name)
                ->greeting('Manual match required')
                ->line('A payslip was matched with low confidence (below the perfect-match threshold). Please review and validate manually.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Detected company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->action('Open & validate manually', $link)
                ->line('Click the button above to open the proposal directly. You will be asked to log in if not already authenticated.');
        }

        if ($this->type === 'no_match') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=edit';

            return (new MailMessage)
                ->subject('[CibleRH] No company match found — manual assignment required')
                ->greeting('Manual assignment required')
                ->line('A payslip could not be matched to any company automatically.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Extracted text:** ' . ($proposedMatch['company_raw'] ?? '—'))
                ->line('**Period:** ' . $period)
                ->action('Open & assign manually', $link)
                ->line('Click the button above to assign company/department manually and continue processing.');
        }

        if ($this->type === 'processing_failed') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';
            $failureReason = $this->proposal->rejection_reason ?? 'Unknown error.';

            return (new MailMessage)
                ->subject('[CibleRH] Auto-processing failed — ' . $this->proposal->file_name)
                ->greeting('Auto-processing failure')
                ->line('A payslip was matched with high confidence and auto-validated, but the processing pipeline encountered an error.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->line('**Error:** ' . $failureReason)
                ->action('View failed proposal', $link)
                ->line('The proposal has been marked as failed. Click above to inspect it. You may need to re-trigger processing manually once the underlying issue is resolved.');
        }

        if ($this->type === 'retry_success') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';

            return (new MailMessage)
                ->subject('[CibleRH] Retry successful — ' . $this->proposal->file_name)
                ->greeting('Retry successful')
                ->line('A payslip proposal that previously failed or was pending has been successfully reprocessed.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->action('View processed proposal', $link)
                ->line('The proposal has been successfully processed and is ready for the next steps. Click above to view the details.');
        }

        if ($this->type === 'retry_failed') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';
            $failureReason = $this->proposal->rejection_reason ?? 'Unknown error during retry.';

            return (new MailMessage)
                ->subject('[CibleRH] Retry failed — ' . $this->proposal->file_name)
                ->greeting('Retry failed')
                ->line('A payslip proposal retry has failed.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->line('**Failure Reason:** ' . $failureReason)
                ->action('View failed proposal', $link)
                ->line('The proposal has been marked as failed. Click above to inspect the issue and consider manual intervention.');
        }

        if ($this->type === 'rejected_after_processing') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';
            $failureReason = $this->proposal->rejection_reason ?? 'Unknown rejection reason.';

            return (new MailMessage)
                ->subject('[CibleRH] Rejected after processing — ' . $this->proposal->file_name)
                ->greeting('Proposal rejected')
                ->line('A payslip proposal that was previously processed or validated has been rejected.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->line('**Rejection Reason:** ' . $failureReason)
                ->action('View rejected proposal', $link)
                ->line('The proposal has been rejected. Click above to review the reasons and take appropriate action.');
        }

        if ($this->type === 'validated') {
            $link = route('portal.payslips.sftp-validator') . '?proposal=' . $this->proposal->id . '&mode=view';

            return (new MailMessage)
                ->subject('[CibleRH] Proposal validated — ' . $this->proposal->file_name)
                ->greeting('Proposal validated')
                ->line('A payslip proposal has been validated and is ready for processing.')
                ->line('**File:** ' . $this->proposal->file_name)
                ->line('**Company:** ' . $companyName)
                ->line('**Confidence:** ' . $confidence . ' (' . $strategy . ')')
                ->line('**Period:** ' . $period)
                ->action('View validated proposal', $link)
                ->line('The proposal has been validated and is in the queue for processing. Click above to view the details.');
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
