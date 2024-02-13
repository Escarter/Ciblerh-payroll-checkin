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
