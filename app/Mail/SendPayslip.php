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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $destination, string $month)
    {
        $this->user = $user;
        $this->destination = $destination;
        $this->month = $month;
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

        $email_subject = $this->user->preferred_language === 'en' ? 
            str_replace([':month:',':year:'],[$this->month, now()->year], $setting->email_subject_en) :
             str_replace([':month:', ':year:'], [$this->month, now()->year],$setting->email_subject_fr);
        
        $mail_content = $this->user->preferred_language === 'en' ?
            str_replace([':name:', ':month:'], [$this->user->name, now()->month], $setting->email_content_en) :
            str_replace([':name:', ':month:'], [$this->user->name, now()->month], $setting->email_content_fr);

        return $this->markdown('email.payslip.send',['message'=> $mail_content])
                    ->subject($email_subject)
                    ->attach($file_path, [
                        'as' => $this->user->matricule.'_'.$this->month.'-'.now()->year.'.pdf',
                        'mime' => 'application/pdf',
                    ]);

    }
}
