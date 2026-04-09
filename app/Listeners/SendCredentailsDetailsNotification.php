<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Models\CredentialToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Notifications\SendCredentialsNotification;

class SendCredentailsDetailsNotification
{
   
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EmployeeCreated  $event
     * @return void
     */
    public function handle(EmployeeCreated $event)
    {

        $validator = Validator::make(['email' => $event->employee->email ], [
            'email' => 'required|email',
        ]);

        if($validator->passes()){
            try {
                // Create credential token instead of passing plain password
                $token = CredentialToken::createForUser($event->employee, $event->password, 24, $event->importJobId);
                
                // Queue notification with token ID (secure, not the actual password)
                // Use sendNow() only for immediate dispatch, or queue it properly via Notification::send()
                // Notification::send() will respect ShouldQueue if the notification implements it
                $event->employee->notify(new SendCredentialsNotification($token->id));
                
                Log::info('Credentials notification queued for employee', [
                    'employee_id' => $event->employee->id,
                    'email' => $event->employee->email,
                    'token_id' => $token->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send credentials notification', [
                    'employee_id' => $event->employee->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
