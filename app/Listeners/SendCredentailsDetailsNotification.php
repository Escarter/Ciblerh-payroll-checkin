<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Notifications\SendCredentialsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\InteractsWithQueue;

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

        Notification::sendNow($event->employee, new SendCredentialsNotification($event->password));

        return false;
    }
}
