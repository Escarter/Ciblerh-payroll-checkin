<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
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
                Notification::sendNow($event->employee, new SendCredentialsNotification($event->password));
            } catch (\Exception $e) {
                Log::error('Failed to send credentials notification', [
                    'employee_id' => $event->employee->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
