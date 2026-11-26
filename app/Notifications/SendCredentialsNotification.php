<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\CredentialToken;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    public $tokenId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($tokenId)
    {
        $this->tokenId = $tokenId;
        $this->onQueue('emails');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Fetch token and get password (supports both numeric token IDs and legacy token strings)
        $token = is_numeric($this->tokenId)
            ? CredentialToken::find((int) $this->tokenId)
            : CredentialToken::where('token', (string) $this->tokenId)->first();
        
        if (!$token || $token->user_id !== $notifiable->id) {
            \Log::error('SendCredentialsNotification: Invalid token or user mismatch', [
                'token_id' => $this->tokenId,
                'user_id' => $notifiable->id,
            ]);
            return (new MailMessage())->markdown('email.credentials', ['content' => 'Error: Unable to retrieve credentials.']);
        }

        // Get password and mark token as used (ensure it's always a string)
        $password = (string) $token->getPasswordAndMarkUsed();
        
        $setting = Setting::first();
        setSavedSmtpCredentials();

        // Prepare replacement values - all cast to strings
        $replacements = [
            (string) $notifiable->name,
            (string) url("/login"),
            (string) $notifiable->email,
            $password
        ];

        // Handle case where settings don't exist yet (during import)
        if (!$setting) {
            $welcome_email_subject = $notifiable->preferred_language === 'en' ? "CibleRH - Login Credentials" : "CibleRH - Identifiants de connexion";

            $template = $notifiable->preferred_language === 'en' ?
                "<h3>Dear :name:,</h3> <p>Your account has been created and you can now login into the employee portal at :site_url:. Your credentials are:</p> <strong>Username: :username:</strong> <br><strong>Password: :password:</strong><p></p> <p><strong>Important:</strong> We recommend you change your password after your first login by going to your profile settings.</p> <p>In case of any difficulties, Contact your support via </p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>" :
                "<h2>Cher(e) :name:,</h2> <p>Votre compte a été créé et vous pouvez désormais vous connecter au portail des employés sur :site_url:. Vos identifiants sont :</p> <strong>Nom d'utilisateur : :username:</strong> <br><strong>Mot de passe : :password:</strong><p></p> <p><strong>Important :</strong> Nous vous recommandons de changer votre mot de passe après votre première connexion en allant dans les paramètres de votre profil.</p> <p>En cas de difficultés, contactez votre support via </p> <p>Appel et SMS : :support_number:</p> <p>Mail : :mail_address:</p>";

            $welcome_mail_content = (string) str_replace([':name:', ':site_url:', ':username:', ':password:'], $replacements, $template);

            // Use default from email and name if settings don't exist
            return (new MailMessage)
                ->from('noreply@example.com', 'CibleRH')
                ->subject($welcome_email_subject)
                ->markdown('email.credentials', ['content' => $welcome_mail_content]);
        }

        $welcome_email_subject = (string) ($notifiable->preferred_language === 'en' ? $setting->welcome_email_subject_en : $setting->welcome_email_subject_fr);

        $template = (string) ($notifiable->preferred_language === 'en' ? $setting->welcome_email_content_en : $setting->welcome_email_content_fr);
        
        $welcome_mail_content = (string) str_replace([':name:', ':site_url:', ':username:', ':password:'], $replacements, $template);

        return (new MailMessage)
            ->from((string) $setting->from_email, (string) $setting->from_name)
            ->subject($welcome_email_subject)
            ->markdown('email.credentials', ['content' => $welcome_mail_content]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
