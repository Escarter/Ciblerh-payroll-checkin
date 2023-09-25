<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Notifications\SendCredentialsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $event->employee->notify(new SendCredentialsNotification($event->password));

        return false;
    }
}
