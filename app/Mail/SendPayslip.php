<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        
        return $this->markdown('email.payslip.send',['employee'=> $this->user, 'month'=>$this->month])
                    ->subject(__('Your :month :year payslip',['month'=>$this->month,'year'=>now()->year]))
                    ->attach($file_path, [
                        'as' => $this->user->matricule.'_'.$this->month.''.now()->year.'.pdf',
                        'mime' => 'application/pdf',
                    ]);

    }
}
