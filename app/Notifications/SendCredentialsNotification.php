<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendCredentialsNotification extends Notification  implements ShouldQueue
{
    use Queueable;
    public $password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($password)
    {
        $this->password = $password;
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
        $setting = Setting::first();

        setSavedSmtpCredentials();

        // Handle case where settings don't exist yet (during import)
        if (!$setting) {
            $welcome_email_subject = $notifiable->preferred_language === 'en' ? "EmploiServ - Login Credentials" : "EmploiServ - Identifiants de connexion";

            $welcome_mail_content = $notifiable->preferred_language === 'en' ?
                str_replace([':name:', ':site_url:',':username:',':password:'], [$notifiable->name, url("/login"), $notifiable->email, $this->password],
                    "<h3>Dear :name:,</h3> <p>Your account has been created and you can now login into the employee portal at, :site_url: your credentials are </p> <strong>Username :username:</strong> <br><strong>Password :password:</strong><p></p>  <p>In case of any difficulties, Contact your support via </p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>") :
                str_replace([':name:', ':site_url:',':username:',':password:'], [$notifiable->name, url("/login"), $notifiable->email, $this->password],
                    "<h2>Cher :name:,</h2> <p>Votre compte a été créé et vous pouvez désormais vous connecter au portail des employés sur,:site_url: vos identifiants sont </p> <strong>Nom d'utilisateur :username:</strong> <br><strong>Mot de passe :password:</strong><p></p> <p>En cas de difficultés, contactez votre support via </p> <p>Appel et SMS : :support_number:</p> <p>Mail : :mail_address:</p>");

            // Use default from email and name if settings don't exist
            return (new MailMessage)
                ->from('noreply@example.com', 'EmploiServ')
                ->subject($welcome_email_subject)
                ->markdown('email.credentials',['message' => $welcome_mail_content]);
        }

        $welcome_email_subject = $notifiable->preferred_language === 'en' ? $setting->welcome_email_subject_en : $setting->welcome_email_subject_fr;

        $welcome_mail_content = $notifiable->preferred_language === 'en' ?
            str_replace([':name:', ':site_url:',':username:',':password:'], [$notifiable->name, url("/login"), $notifiable->email, $this->password], $setting->welcome_email_content_en) :
            str_replace([':name:', ':site_url:',':username:',':password:'], [$notifiable->name, url("/login"), $notifiable->email, $this->password], $setting->welcome_email_content_fr);

        return (new MailMessage)
            ->from($setting->from_email, $setting->from_name)
            ->subject($welcome_email_subject)
            ->markdown('email.credentials',['message' => $welcome_mail_content]);
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
