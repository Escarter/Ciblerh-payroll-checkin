<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $changeType,
        public ?string $method = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $changedAt = null
    ) {
        try {
            setSavedSmtpCredentials();
        } catch (\Throwable) {
            // Fall back to default mailer configuration
        }
        $this->changedAt = $changedAt ?? now()->toDateTimeString();
    }

    public function build(): self
    {
        $appName = Branding::portalTitle();
        $setting = Setting::first();

        $subjectKey = match ($this->changeType) {
            'enabled' => 'two_factor.notify_subject_enabled',
            'method_changed' => 'two_factor.notify_subject_method_changed',
            'disabled' => 'two_factor.notify_subject_disabled',
            'reset' => 'two_factor.notify_subject_reset',
            'codes_regenerated' => 'two_factor.notify_subject_codes_regenerated',
            default => 'two_factor.notify_subject_changed',
        };

        $mail = $this
            ->subject(__($subjectKey, ['app' => $appName]))
            ->view('emails.two-factor-changed', [
                'user' => $this->user,
                'appName' => $appName,
                'changeType' => $this->changeType,
                'method' => $this->method,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'changedAt' => $this->changedAt,
            ]);

        if ($setting && !empty($setting->from_email) && !empty($setting->from_name)) {
            $mail->from($setting->from_email, $setting->from_name);
        }

        return $mail;
    }
}
