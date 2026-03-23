<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation while applying saved SMTP settings.
     * 
     * @throws \RuntimeException If mail configuration is required but incomplete
     */
    public function toMail($notifiable): MailMessage
    {
        try {
            $settingsApplied = setSavedSmtpCredentials();
            
            if (!$settingsApplied) {
                // No saved settings were applied — log this for debugging
                Log::warning('Password reset: No saved SMTP settings found. Falling back to environment configuration.', [
                    'user_id' => $notifiable->id,
                    'user_email' => $notifiable->email,
                ]);
                
                // Check if environment-level mail config is available
                if (empty(config('mail.mailers.smtp.host'))) {
                    throw new \RuntimeException(
                        'Email configuration is not set up. Please configure SMTP settings in the application settings or set MAIL_HOST environment variable.'
                    );
                }
            }
        } catch (\RuntimeException $e) {
            Log::error('Password reset failed due to mail configuration error', [
                'user_id' => $notifiable->id,
                'user_email' => $notifiable->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $mailMessage = parent::toMail($notifiable);
        $setting = Setting::where('company_id', 1)->first();

        if (!empty($setting?->from_email)) {
            $mailMessage->from(
                $setting->from_email,
                $setting->from_name ?: config('mail.from.name')
            );
        }

        return $mailMessage;
    }
}