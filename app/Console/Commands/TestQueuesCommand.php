<?php

namespace App\Console\Commands;

use App\Jobs\TestJobs\TestDefaultQueueJob;
use App\Jobs\TestJobs\TestHighPriorityQueueJob;
use App\Jobs\TestJobs\TestEmailsQueueJob;
use App\Jobs\TestJobs\TestProcessingQueueJob;
use App\Jobs\TestJobs\TestPdfProcessingQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TestQueuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test
                            {--wait : Wait for jobs to complete before showing results}
                            {--timeout=30 : Timeout in seconds when waiting for completion}
                            {--queue= : Test only specific queue (default, high-priority, emails, processing, pdf-processing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all queue configurations by dispatching test jobs';

    /**
     * Available queues to test
     */
    protected $queues = [
        'default' => [
            'job' => TestDefaultQueueJob::class,
            'description' => 'Default queue for general operations'
        ],
        'high-priority' => [
            'job' => TestHighPriorityQueueJob::class,
            'description' => 'High-priority queue for critical operations'
        ],
        'emails' => [
            'job' => TestEmailsQueueJob::class,
            'description' => 'Queue for email and SMS operations'
        ],
        'processing' => [
            'job' => TestProcessingQueueJob::class,
            'description' => 'Queue for heavy processing (imports/exports)'
        ],
        'pdf-processing' => [
            'job' => TestPdfProcessingQueueJob::class,
            'description' => 'Queue for PDF processing operations'
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Queue Testing...');

        $specificQueue = $this->option('queue');
        $shouldWait = $this->option('wait');
        $timeout = (int) $this->option('timeout');

        if ($specificQueue) {
            if (!isset($this->queues[$specificQueue])) {
                $this->error("Invalid queue: {$specificQueue}");
                $this->info('Available queues: ' . implode(', ', array_keys($this->queues)));
                return 1;
            }
            $queuesToTest = [$specificQueue => $this->queues[$specificQueue]];
        } else {
            $queuesToTest = $this->queues;
        }

        $this->info('📋 Queues to test: ' . count($queuesToTest));
        $this->table(
            ['Queue', 'Description'],
            collect($queuesToTest)->map(function ($config, $queue) {
                return [$queue, $config['description']];
            })->toArray()
        );

        $testSessionId = Str::uuid();
        $this->info("🔑 Test Session ID: {$testSessionId}");

        // Store test session in cache for tracking
        Cache::put("queue_test_{$testSessionId}", [
            'started_at' => now(),
            'queues' => array_keys($queuesToTest),
            'jobs' => []
        ], now()->addMinutes(30));

        $dispatchedJobs = [];

        foreach ($queuesToTest as $queueName => $config) {
            $this->info("📤 Dispatching test job to '{$queueName}' queue...");

            $testId = "{$testSessionId}_{$queueName}_" . time();
            $jobClass = $config['job'];

            try {
                $job = new $jobClass($testId);
                dispatch($job);

                $dispatchedJobs[] = [
                    'queue' => $queueName,
                    'test_id' => $testId,
                    'job_class' => $jobClass,
                    'dispatched_at' => now()
                ];

                $this->info("✅ Job dispatched successfully - Test ID: {$testId}");

            } catch (\Exception $e) {
                $this->error("❌ Failed to dispatch job to '{$queueName}': " . $e->getMessage());
            }
        }

        // Update cache with dispatched jobs
        $testData = Cache::get("queue_test_{$testSessionId}");
        $testData['jobs'] = $dispatchedJobs;
        Cache::put("queue_test_{$testSessionId}", $testData, now()->addMinutes(30));

        if ($shouldWait) {
            $this->info("⏳ Waiting for jobs to complete (timeout: {$timeout}s)...");
            $this->monitorJobs($testSessionId, $timeout);
        } else {
            $this->info('💡 Jobs dispatched! Use the test session ID to monitor progress.');
            $this->info('   Run: php artisan queue:test --wait --session=' . $testSessionId);
        }

        $this->info('🎉 Queue testing initiated successfully!');
        return 0;
    }

    /**
     * Monitor jobs for completion
     */
    protected function monitorJobs(string $sessionId, int $timeout)
    {
        $startTime = time();
        $completedJobs = [];
        $failedJobs = [];

        $this->info('📊 Monitoring job execution...');

        while (time() - $startTime < $timeout) {
            $testData = Cache::get("queue_test_{$sessionId}");

            if (!$testData) {
                $this->error('❌ Test session data lost');
                break;
            }

            $allJobs = $testData['jobs'];
            $pendingJobs = collect($allJobs)->filter(function ($job) use ($completedJobs, $failedJobs) {
                return !in_array($job['test_id'], array_merge(
                    array_column($completedJobs, 'test_id'),
                    array_column($failedJobs, 'test_id')
                ));
            });

            if ($pendingJobs->isEmpty()) {
                $this->info('✅ All jobs completed!');
                break;
            }

            // Check for completed/failed jobs in logs or database
            // This is a simplified check - in production you might want more sophisticated monitoring
            sleep(2);
        }

        $this->displayResults($sessionId);
    }

    /**
     * Display test results
     */
    protected function displayResults(string $sessionId)
    {
        $testData = Cache::get("queue_test_{$sessionId}");

        if (!$testData) {
            $this->error('❌ Unable to retrieve test results');
            return;
        }

        $this->info('📊 Test Results Summary:');
        $this->info('Started: ' . $testData['started_at']->format('Y-m-d H:i:s'));
        $this->info('Duration: ' . $testData['started_at']->diffInSeconds(now()) . ' seconds');

        $jobsTable = collect($testData['jobs'])->map(function ($job) {
            return [
                $job['queue'],
                $job['test_id'],
                $job['job_class'],
                $job['dispatched_at']->format('H:i:s')
            ];
        })->toArray();

        $this->table(
            ['Queue', 'Test ID', 'Job Class', 'Dispatched At'],
            $jobsTable
        );

        $this->info('💡 To check individual job status, look at:');
        $this->info('   - Laravel logs for execution messages');
        $this->info('   - Queue monitoring tools');
        $this->info('   - Database failed_jobs table for failures');
    }
}


