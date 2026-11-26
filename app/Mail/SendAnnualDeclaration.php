<?php

namespace App\Mail;

use App\Models\AnnualDeclaration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendAnnualDeclaration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected User $user, protected AnnualDeclaration $declaration)
    {
    }

    public function build()
    {
        $locale = $this->user->preferred_language === 'en' ? 'en' : 'fr';
        $params = ['name' => $this->user->name, 'year' => $this->declaration->year];

        return $this->markdown('email.payslip.send', ['message' => __('annual_declarations.email_content', $params, $locale)])
            ->subject(__('annual_declarations.email_subject', $params, $locale))
            ->attach(Storage::disk('modified')->path($this->declaration->file), [
                'as' => $this->declaration->matricule . '_declaration_' . $this->declaration->year . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
