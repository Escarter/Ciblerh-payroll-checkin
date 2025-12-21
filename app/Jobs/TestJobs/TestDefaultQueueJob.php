<?php

namespace App\Jobs\TestJobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestDefaultQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $testId;
    protected $queueName;

    public function __construct($testId = null)
    {
        $this->testId = $testId ?: uniqid();
        $this->queueName = 'default';
        // Note: This job uses the default queue (not explicitly set)
    }

    public function handle()
    {
        Log::info("TestDefaultQueueJob executed - Test ID: {$this->testId}, Queue: {$this->queueName}");

        // Simulate some work
        sleep(1);

        Log::info("TestDefaultQueueJob completed - Test ID: {$this->testId}");
    }

    public function failed(\Throwable $exception)
    {
        Log::error("TestDefaultQueueJob failed - Test ID: {$this->testId}, Error: " . $exception->getMessage());
    }
}


