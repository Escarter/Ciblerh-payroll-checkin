<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Queueable;

    public string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message = 'Test job executed successfully')
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Test enum_value function
        if (!function_exists('enum_value')) {
            throw new \Exception('enum_value function not available');
        }

        // Test database operations that might use enum_value
        $user = User::find(1); // This might trigger enum_value in relationships

        // Log success
        Log::info('TestQueueJob: ' . $this->message);
        Log::info('TestQueueJob: enum_value function available: ' . (function_exists('enum_value') ? 'YES' : 'NO'));
        Log::info('TestQueueJob: User found: ' . ($user ? $user->email : 'NO'));
    }
}
