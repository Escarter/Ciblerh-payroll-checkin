<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class MonitorQueuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                            {--queue= : Monitor specific queue}
                            {--failed : Show only failed jobs}
                            {--pending : Show only pending jobs}
                            {--recent=10 : Show last N jobs}
                            {--session= : Monitor specific test session}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue status and job execution';

    /**
     * Available queues
     */
    protected $queues = [
        'default',
        'high-priority',
        'emails',
        'processing',
        'pdf-processing'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificQueue = $this->option('queue');
        $showFailed = $this->option('failed');
        $showPending = $this->option('pending');
        $recentCount = (int) $this->option('recent');
        $testSession = $this->option('session');

        if ($testSession) {
            return $this->monitorTestSession($testSession);
        }

        $this->info('📊 Queue Monitoring Dashboard');
        $this->info('================================');

        if ($specificQueue) {
            if (!in_array($specificQueue, $this->queues)) {
                $this->error("Invalid queue: {$specificQueue}");
                $this->info('Available queues: ' . implode(', ', $this->queues));
                return 1;
            }
            $queuesToMonitor = [$specificQueue];
        } else {
            $queuesToMonitor = $this->queues;
        }

        // Check Redis connection and queue sizes
        $this->checkRedisConnection();
        $this->displayQueueSizes($queuesToMonitor);

        // Check database job tables
        $this->checkDatabaseJobs($queuesToMonitor, $recentCount, $showFailed, $showPending);

        // Check failed jobs
        if (!$showPending) {
            $this->checkFailedJobs($specificQueue);
        }

        return 0;
    }

    /**
     * Check Redis connection
     */
    protected function checkRedisConnection()
    {
        try {
            $redis = Redis::connection('default');
            $redis->ping();
            $this->info('✅ Redis connection: OK');
        } catch (\Exception $e) {
            $this->error('❌ Redis connection: FAILED - ' . $e->getMessage());
        }
    }

    /**
     * Display queue sizes
     */
    protected function displayQueueSizes(array $queues)
    {
        $this->info('📈 Queue Sizes (Redis):');

        $queueData = [];
        foreach ($queues as $queue) {
            try {
                $size = Redis::llen("queues:{$queue}");
                $queueData[] = [
                    $queue,
                    $size,
                    $size > 0 ? '🟡 Active' : '🟢 Empty'
                ];
            } catch (\Exception $e) {
                $queueData[] = [
                    $queue,
                    'N/A',
                    '❌ Error'
                ];
            }
        }

        $this->table(['Queue', 'Pending Jobs', 'Status'], $queueData);
    }

    /**
     * Check database jobs
     */
    protected function checkDatabaseJobs(array $queues, int $recentCount, bool $showFailed, bool $showPending)
    {
        $this->info('🗄️  Database Jobs Status:');

        try {
            $query = DB::table('jobs');

            if ($showFailed) {
                $this->info('Showing only failed jobs...');
                return $this->checkFailedJobs(null);
            }

            if ($showPending) {
                $this->info('Showing only pending jobs...');
                // Pending jobs are in the jobs table
            }

            $jobs = $query->orderBy('created_at', 'desc')
                         ->limit($recentCount)
                         ->get();

            if ($jobs->isEmpty()) {
                $this->info('   No jobs found in database');
                return;
            }

            $jobData = $jobs->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                $queue = $job->queue ?? 'default';

                return [
                    $job->id,
                    $queue,
                    $jobClass,
                    $job->created_at,
                    $job->available_at ? '🟡 Delayed' : '🟢 Ready',
                    $job->attempts . '/' . ($payload['maxTries'] ?? 'N/A')
                ];
            })->toArray();

            $this->table(
                ['ID', 'Queue', 'Job Class', 'Created', 'Status', 'Attempts'],
                $jobData
            );

        } catch (\Exception $e) {
            $this->error('❌ Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Check failed jobs
     */
    protected function checkFailedJobs($specificQueue = null)
    {
        $this->info('💥 Failed Jobs:');

        try {
            $query = DB::table('failed_jobs');

            if ($specificQueue) {
                $query->where('queue', $specificQueue);
            }

            $failedJobs = $query->orderBy('failed_at', 'desc')
                               ->limit(10)
                               ->get();

            if ($failedJobs->isEmpty()) {
                $this->info('   ✅ No failed jobs found');
                return;
            }

            $failedData = $failedJobs->map(function ($job) {
                return [
                    $job->id,
                    $job->queue ?? 'default',
                    substr($job->exception, 0, 50) . '...',
                    $job->failed_at
                ];
            })->toArray();

            $this->table(['ID', 'Queue', 'Error', 'Failed At'], $failedData);

        } catch (\Exception $e) {
            $this->error('❌ Failed to check failed jobs: ' . $e->getMessage());
        }
    }

    /**
     * Monitor specific test session
     */
    protected function monitorTestSession(string $sessionId)
    {
        $this->info("🔍 Monitoring Test Session: {$sessionId}");

        $testData = Cache::get("queue_test_{$sessionId}");

        if (!$testData) {
            $this->error('❌ Test session not found or expired');
            return 1;
        }

        $this->info('Started: ' . $testData['started_at']->format('Y-m-d H:i:s'));
        $this->info('Queues tested: ' . implode(', ', $testData['queues']));

        if (empty($testData['jobs'])) {
            $this->info('No jobs dispatched yet');
            return 0;
        }

        $this->info('📋 Dispatched Jobs:');

        $jobData = collect($testData['jobs'])->map(function ($job) {
            return [
                $job['queue'],
                $job['test_id'],
                $job['job_class'],
                $job['dispatched_at']->format('H:i:s')
            ];
        })->toArray();

        $this->table(['Queue', 'Test ID', 'Job Class', 'Dispatched'], $jobData);

        $this->info('💡 Check application logs for job execution results');
        $this->info('💡 Use --failed flag to check for failed jobs');

        return 0;
    }
}

