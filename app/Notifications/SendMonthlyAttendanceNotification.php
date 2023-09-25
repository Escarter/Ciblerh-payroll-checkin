<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendMonthlyAttendanceNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $start_of_month = now()->startOfMonth()->format('d/m/Y');
        $end_of_month = now()->endOfMonth()->format('d/m/Y');
        
        return (new MailMessage)
            ->subject(Lang::get("CibleRh Checkin - Attendance Validation"))
            ->greeting(Lang::get("Hello ").$notifiable->first_name)
            ->line(Lang::get('Your attendance of the period of :start to :end has been validated!',['start'=>$start_of_month,'end'=>$end_of_month]))
            ->action(Lang::get('Login to view'), route('login'))
            ->line(Lang::get('Thank you for using our application'));
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
