<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPayslip extends Mailable //implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;
    protected $destination;
    protected $month;
    protected $year;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $destination, string $month, ?int $year = null)
    {
        $this->user = $user;
        $this->destination = $destination;
        $this->month = $month;
        $this->year = $year ?? now()->year;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $file_path = Storage::disk('modified')->path($this->destination);
        $setting = Setting::first();

        $frMonths = [
            'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
            'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
            'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
            'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre',
        ];
        $monthForEmail = $this->user->preferred_language === 'en'
            ? $this->month
            : ($frMonths[$this->month] ?? $this->month);

        $email_subject = str_replace(
            [':month:', ':year:'],
            [$monthForEmail, $this->year],
            $this->user->preferred_language === 'en' ? $setting->email_subject_en : $setting->email_subject_fr
        );

        $mail_content = str_replace(
            [':name:', ':month:'],
            [$this->user->name, $monthForEmail],
            $this->user->preferred_language === 'en' ? $setting->email_content_en : $setting->email_content_fr
        );

        return $this->markdown('email.payslip.send',['message'=> $mail_content])
                    ->subject($email_subject)
                    ->attach($file_path, [
                        'as' => $this->user->matricule.'_'.$this->month.'-'.$this->year.'.pdf',
                        'mime' => 'application/pdf',
                    ]);

    }
}
