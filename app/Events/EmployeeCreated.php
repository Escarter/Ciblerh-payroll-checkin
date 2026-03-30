<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class EmployeeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employee;
    public $password;
    public $importJobId;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(User $employee, $password, ?int $importJobId = null)
    {
        $this->employee = $employee;
        $this->password = $password;
        $this->importJobId = $importJobId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
