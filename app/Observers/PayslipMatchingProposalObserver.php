<?php

namespace App\Observers;

use App\Models\PayslipMatchingProposal;
use App\Notifications\SftpAutoMatchNotification;
use App\Notifications\SftpProposalCreatedNotification;
use App\Services\FeatureConfigurationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PayslipMatchingProposalObserver
{
    /**
     * Handle the PayslipMatchingProposal "created" event.
     *
     * @param  \App\Models\PayslipMatchingProposal  $proposal
     * @return void
     */
    public function created(PayslipMatchingProposal $proposal)
    {
        Log::info('PayslipMatchingProposalObserver: Proposal created', [
            'proposal_id' => $proposal->id,
            'file_name' => $proposal->file_name,
            'status' => $proposal->status,
        ]);

        // Send proposal creation notification if enabled
        $this->sendProposalCreatedNotification($proposal);

        // Send threshold-based notifications
        $this->sendThresholdBasedNotifications($proposal);
    }

    /**
     * Send proposal creation notification if enabled in settings
     */
    private function sendProposalCreatedNotification(PayslipMatchingProposal $proposal): void
    {
        $setting = \App\Models\Setting::first();

        if (!$setting || !$setting->sftp_match_created_notification_enabled) {
            return;
        }

        $emailList = $setting->sftp_match_created_notification_email ?? '';

        if (empty(trim($emailList))) {
            Log::warning('PayslipMatchingProposalObserver: Proposal creation notification enabled but no email configured', [
                'proposal_id' => $proposal->id,
            ]);
            return;
        }

        try {
            $this->notifyEmails($emailList, new SftpProposalCreatedNotification($proposal));
            Log::info('PayslipMatchingProposalObserver: Proposal creation notification sent', [
                'proposal_id' => $proposal->id,
                'emails' => $emailList,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayslipMatchingProposalObserver: Failed to send proposal creation notification', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send threshold-based notifications based on match confidence and rules
     */
    private function sendThresholdBasedNotifications(PayslipMatchingProposal $proposal): void
    {
        $autoMatchConfig = FeatureConfigurationService::getSftpAutoMatchConfig();
        $notificationEmails = $autoMatchConfig['notification_email'] ?? '';
        
        if (empty(trim($notificationEmails))) {
            Log::warning('PayslipMatchingProposalObserver: Auto-match notifications enabled but no email configured', [
                'proposal_id' => $proposal->id,
            ]);
            return;
        }

        $proposedMatch = $proposal->proposed_match ?? [];
        $candidates = $proposedMatch['candidates'] ?? [];
        $best = !empty($candidates) ? $candidates[0] : null;
        $bestConfidence = (float) ($best['confidence'] ?? 0);
        $configuredThreshold = (int) ($autoMatchConfig['threshold'] ?? 80);
        $autoMatchEnabled = (bool) ($autoMatchConfig['enabled'] ?? false);

        // Determine notification type based on match criteria
        if ($best === null) {
            $this->sendNotification($notificationEmails, $proposal, 'no_match');
        } elseif (!$this->meetsThresholdCriteria($best, $autoMatchConfig)) {
            $this->sendNotification($notificationEmails, $proposal, 'manual_review');
        } elseif (!$proposal->matched_to_company_id || !$proposal->matched_to_department_id) {
            if ($proposal->matched_to_company_id && !$proposal->matched_to_department_id) {
                $this->sendNotification($notificationEmails, $proposal, 'dept_required');
            } else {
                $this->sendNotification($notificationEmails, $proposal, 'manual_review');
            }
        } elseif ($autoMatchEnabled && $this->meetsThresholdCriteria($best, $autoMatchConfig)) {
            // This would be handled by the auto-processing logic
            Log::info('PayslipMatchingProposalObserver: Match meets auto-criteria, will be processed automatically', [
                'proposal_id' => $proposal->id,
                'confidence' => $bestConfidence,
                'threshold' => $configuredThreshold,
            ]);
        } else {
            $this->sendNotification($notificationEmails, $proposal, 'manual_review');
        }
    }

    /**
     * Check if candidate meets threshold criteria
     */
    private function meetsThresholdCriteria(array $candidate, array $config): bool
    {
        return FeatureConfigurationService::canAutoMatch($candidate, $config);
    }

    /**
     * Send notification to configured email addresses
     */
    private function sendNotification(string $emailList, PayslipMatchingProposal $proposal, string $type): void
    {
        try {
            $this->notifyEmails($emailList, new SftpAutoMatchNotification($proposal, $type));
            
            Log::info('PayslipMatchingProposalObserver: Threshold notification sent', [
                'proposal_id' => $proposal->id,
                'type' => $type,
                'emails' => $emailList,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayslipMatchingProposalObserver: Failed to send threshold notification', [
                'proposal_id' => $proposal->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to multiple email addresses
     */
    private function notifyEmails(string $emailList, \Illuminate\Notifications\Notification $notification): void
    {
        // Apply SMTP settings from database before sending notifications
        try {
            setSavedSmtpCredentials();
        } catch (\Throwable $e) {
            Log::error('PayslipMatchingProposalObserver: Failed to apply SMTP settings', [
                'error' => $e->getMessage(),
            ]);
            return; // Don't attempt to send emails if SMTP settings failed
        }

        $emails = array_filter(array_map('trim', preg_split('/[\s,]+/', $emailList, -1, PREG_SPLIT_NO_EMPTY)));

        foreach ($emails as $email) {
            try {
                Notification::route('mail', $email)->notify(clone $notification);
            } catch (\Throwable $e) {
                Log::error('PayslipMatchingProposalObserver: Failed to queue notification email', [
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PayslipMatchingProposal "updated" event.
     *
     * @param  \App\Models\PayslipMatchingProposal  $proposal
     * @return void
     */
    public function updated(PayslipMatchingProposal $proposal)
    {
        // Get the dirty attributes to see what changed
        $changes = $proposal->getDirty();
        
        // Only proceed if status actually changed
        if (!isset($changes['status'])) {
            return;
        }

        $oldStatus = $proposal->getOriginal('status');
        $newStatus = $proposal->status;

        Log::info('PayslipMatchingProposalObserver: Proposal status updated', [
            'proposal_id' => $proposal->id,
            'file_name' => $proposal->file_name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Send notifications for specific status changes
        $this->sendStatusChangeNotifications($proposal, $oldStatus, $newStatus);
    }

    /**
     * Send notifications for status changes during retry/reprocessing
     */
    private function sendStatusChangeNotifications(PayslipMatchingProposal $proposal, string $oldStatus, string $newStatus): void
    {
        $setting = \App\Models\Setting::first();

        if (!$setting || !$setting->sftp_match_created_notification_enabled) {
            Log::info('PayslipMatchingProposalObserver: Status change notifications disabled in settings', [
                'proposal_id' => $proposal->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            return;
        }

        $notificationEmails = $setting->sftp_match_created_notification_email ?? '';
        
        if (empty(trim($notificationEmails))) {
            Log::warning('PayslipMatchingProposalObserver: Status change notification enabled but no email configured', [
                'proposal_id' => $proposal->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            return;
        }

        // Determine notification type based on status change
        $notificationType = $this->getNotificationTypeForStatusChange($oldStatus, $newStatus);
        
        if ($notificationType === null) {
            return; // No notification needed for this status change
        }

        try {
            $this->sendNotification($notificationEmails, $proposal, $notificationType);
            
            Log::info('PayslipMatchingProposalObserver: Status change notification sent', [
                'proposal_id' => $proposal->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notification_type' => $notificationType,
                'emails' => $notificationEmails,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayslipMatchingProposalObserver: Failed to send status change notification', [
                'proposal_id' => $proposal->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine notification type for status change
     */
    private function getNotificationTypeForStatusChange(string $oldStatus, string $newStatus): ?string
    {
        // Successful retry: pending -> processed
        if ($oldStatus === PayslipMatchingProposal::STATUS_PENDING && $newStatus === PayslipMatchingProposal::STATUS_PROCESSED) {
            return 'retry_success';
        }

        // Failed retry: pending -> failed
        if ($oldStatus === PayslipMatchingProposal::STATUS_PENDING && $newStatus === PayslipMatchingProposal::STATUS_FAILED) {
            return 'retry_failed';
        }

        // Rejected after processing: processed/validated -> rejected
        if (in_array($oldStatus, [PayslipMatchingProposal::STATUS_PROCESSED, PayslipMatchingProposal::STATUS_VALIDATED]) && 
            $newStatus === PayslipMatchingProposal::STATUS_REJECTED) {
            return 'rejected_after_processing';
        }

        // Validated after pending: pending -> validated
        if ($oldStatus === PayslipMatchingProposal::STATUS_PENDING && $newStatus === PayslipMatchingProposal::STATUS_VALIDATED) {
            return 'validated';
        }

        return null; // No notification for other status changes
    }
}
