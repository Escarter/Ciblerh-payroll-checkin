<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $minutes = 10,
        public readonly string $purpose = 'sign_in',
        public readonly ?string $recipientName = null
    ) {
        try {
            setSavedSmtpCredentials();
        } catch (\Throwable) {
            // Fall back to default mailer configuration
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = Branding::portalTitle();
        $greeting = $this->recipientName
            ? __('two_factor.mail_greeting_named', ['name' => $this->recipientName])
            : __('two_factor.mail_greeting');

        $purposeLine = match ($this->purpose) {
            'setup' => __('two_factor.mail_purpose_setup', ['app' => $brand]),
            'test' => __('two_factor.mail_purpose_test', ['app' => $brand]),
            default => __('two_factor.mail_purpose_sign_in', ['app' => $brand]),
        };

        $setting = Setting::first();

        $mail = (new MailMessage())
            ->subject(__('two_factor.mail_subject', ['app' => $brand, 'code' => $this->code]))
            ->greeting($greeting)
            ->line($purposeLine)
            ->line(__('two_factor.mail_code_line', ['code' => $this->code]))
            ->line(__('two_factor.mail_expires_line', ['minutes' => $this->minutes]))
            ->line(__('two_factor.mail_ignore_line'));

        if ($setting && !empty($setting->from_email) && !empty($setting->from_name)) {
            $mail->from($setting->from_email, $setting->from_name);
        }

        return $mail;
    }
}
