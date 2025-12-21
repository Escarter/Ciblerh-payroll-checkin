<?php

namespace App\Jobs\TestJobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestProcessingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $testId;
    protected $queueName;

    public function __construct($testId = null)
    {
        $this->testId = $testId ?: uniqid();
        $this->queueName = 'processing';
        $this->queue = 'processing';
    }

    public function handle()
    {
        Log::info("TestProcessingQueueJob executed - Test ID: {$this->testId}, Queue: {$this->queueName}");

        // Simulate heavy processing work
        sleep(2);

        Log::info("TestProcessingQueueJob completed - Test ID: {$this->testId}");
    }

    public function failed(\Throwable $exception)
    {
        Log::error("TestProcessingQueueJob failed - Test ID: {$this->testId}, Error: " . $exception->getMessage());
    }
}


