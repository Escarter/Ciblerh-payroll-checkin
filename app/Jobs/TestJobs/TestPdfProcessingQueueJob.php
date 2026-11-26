<?php

namespace App\Jobs\TestJobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestPdfProcessingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $testId;
    protected $queueName;

    public function __construct($testId = null)
    {
        $this->testId = $testId ?: uniqid();
        $this->queueName = 'pdf-processing';
        $this->queue = 'pdf-processing';
    }

    public function handle()
    {
        Log::info("TestPdfProcessingQueueJob executed - Test ID: {$this->testId}, Queue: {$this->queueName}");

        // Simulate PDF processing work
        sleep(1);

        Log::info("TestPdfProcessingQueueJob completed - Test ID: {$this->testId}");
    }

    public function failed(\Throwable $exception)
    {
        Log::error("TestPdfProcessingQueueJob failed - Test ID: {$this->testId}, Error: " . $exception->getMessage());
    }
}


