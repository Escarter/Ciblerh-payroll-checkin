<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name'),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Comprehensive health check including queue status
     */
    public function detailed(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name'),
            'environment' => app()->environment(),
            'checks' => []
        ];

        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queues' => $this->checkQueues(),
        ];

        $health['checks'] = $checks;

        // Determine overall status
        $failedChecks = array_filter($checks, fn($check) => $check['status'] !== 'healthy');
        if (!empty($failedChecks)) {
            $health['status'] = 'unhealthy';
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $migrations = DB::table('migrations')->count();

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'details' => [
                    'migrations_count' => $migrations,
                    'connection' => config('database.default'),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
                'details' => [
                    'connection' => config('database.redis.default.host') . ':' . config('database.redis.default.port'),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            if ($value === 'test_value') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working',
                    'details' => [
                        'driver' => config('cache.default'),
                    ]
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage permissions
     */
    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/health_check_' . time() . '.tmp');
            $written = file_put_contents($testFile, 'test');
            $read = file_get_contents($testFile);
            unlink($testFile);

            if ($written !== false && $read === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage is writable',
                    'details' => [
                        'path' => storage_path('app'),
                    ]
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage read/write failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check queue status and worker health
     */
    private function checkQueues(): array
    {
        $queues = ['high-priority', 'emails', 'processing', 'pdf-processing', 'default'];
        $queueStatus = [];

        try {
            foreach ($queues as $queue) {
                $count = \Illuminate\Support\Facades\Queue::size($queue);
                $queueStatus[$queue] = [
                    'jobs_count' => $count,
                    'status' => $count > 100 ? 'warning' : 'healthy'
                ];
            }

            // Check for critical queue backlog
            $highPriorityCount = $queueStatus['high-priority']['jobs_count'] ?? 0;
            $emailCount = $queueStatus['emails']['jobs_count'] ?? 0;

            $warnings = [];
            if ($highPriorityCount > 10) {
                $warnings[] = "High priority queue has $highPriorityCount jobs";
            }
            if ($emailCount > 100) {
                $warnings[] = "Email queue has $emailCount jobs";
            }

            return [
                'status' => empty($warnings) ? 'healthy' : 'warning',
                'message' => empty($warnings) ? 'All queues healthy' : 'Queue warnings detected',
                'details' => [
                    'queues' => $queueStatus,
                    'warnings' => $warnings,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed',
                'error' => $e->getMessage()
            ];
        }
    }
}