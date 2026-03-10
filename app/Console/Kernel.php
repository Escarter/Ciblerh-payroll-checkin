<?php

namespace App\Console;

use App\Console\Commands\ClearSuccessfulJobsFolders;
use App\Console\Commands\SplitPdfCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ClearSuccessfulJobsFolders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('wima:clean-processed')->dailyAt('01:30');
        $schedule->command('wima:leave-update-process')->lastDayOfMonth('23:50')->timezone('Africa/Douala');
        $schedule->command('advance-salary:send-pending-reminder')->dailyAt('08:00')->timezone('Africa/Douala');
        $schedule->command('wima:wish-happy-birthday')->dailyAt('08:00')->timezone('Africa/Douala');
        $schedule->command('reports:process-scheduled')->hourly();

        // Clean up expired sessions every 30 minutes
        $schedule->call(function () {
            $this->pruneExpiredSessions();
        })->everyThirtyMinutes();

        // Deactivate inactive users - scheduled based on setting time, defaults to 02:00
        $schedule->command('users:deactivate-inactive')
            ->dailyAt($this->getDeactivationTime())
            ->timezone(config('app.timezone', 'UTC'))
            ->when(fn() => $this->isDeactivationEnabled());

        // Fetch SFTP payslips - scheduled based on frequency setting
        $schedule->job(new \App\Jobs\FetchSftpPayslipsJob)
            ->{$this->getSftpSyncFrequency()}()
            ->when(fn() => $this->isSftpSyncEnabled());

        // Scan SFTP push folder - frequency configurable from settings
        $pushEvent = $schedule->command('sftp:scan-push-folder')
            ->when(fn() => $this->isSftpSyncEnabled())
            ->withoutOverlapping();
        $this->applySftpPushScanFrequency($pushEvent);
    }

    /**
     * Get deactivation check time from settings
     */
    private function getDeactivationTime(): string
    {
        try {
            $config = \App\Services\FeatureConfigurationService::getDeactivationConfig();
            return $config['check_time'] ?? '02:00';
        } catch (\Exception $e) {
            return '02:00';
        }
    }

    /**
     * Check if deactivation is enabled
     */
    private function isDeactivationEnabled(): bool
    {
        try {
            return \App\Services\FeatureConfigurationService::isDeactivationEnabled();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Apply the configured push-scan frequency to a schedule event.
     * Days are stored as comma-separated weekday numbers (0=Sun … 6=Sat).
     */
    private function applySftpPushScanFrequency(\Illuminate\Console\Scheduling\Event $event): void
    {
        try {
            $config   = \App\Services\FeatureConfigurationService::getSftpConfig();
            $freq     = $config['push_scan_frequency'] ?? 'everyFiveMinutes';
            $time     = $config['push_scan_time']      ?? '00:00';
            $daysStr  = $config['push_scan_days']      ?? '';

            $simpleMethods = [
                'everyMinute'         => 'everyMinute',
                'everyFiveMinutes'    => 'everyFiveMinutes',
                'everyTenMinutes'     => 'everyTenMinutes',
                'everyFifteenMinutes' => 'everyFifteenMinutes',
                'everyThirtyMinutes'  => 'everyThirtyMinutes',
                'hourly'              => 'hourly',
                'everyTwoHours'       => 'everyTwoHours',
                'everyThreeHours'     => 'everyThreeHours',
                'everyFourHours'      => 'everyFourHours',
                'everySixHours'       => 'everySixHours',
                'everyTwelveHours'    => 'everyTwelveHours',
            ];

            if (isset($simpleMethods[$freq])) {
                $method = $simpleMethods[$freq];
                $event->$method();
                return;
            }

            if ($freq === 'daily') {
                $event->dailyAt($time ?: '00:00');
                return;
            }

            if ($freq === 'custom_days' && !empty($daysStr)) {
                $days = array_filter(array_map('intval', explode(',', $daysStr)));
                if (!empty($days)) {
                    $event->days($days)->at($time ?: '00:00');
                    return;
                }
            }
        } catch (\Exception $e) {
            // fall through to safe default
        }

        $event->everyFiveMinutes();
    }

    /**
     * Get SFTP sync frequency
     */
    private function getSftpSyncFrequency(): string
    {
        try {
            $config = \App\Services\FeatureConfigurationService::getSftpConfig();
            return match($config['sync_frequency'] ?? 'daily') {
                'hourly' => 'hourly',
                'weekly' => 'weekly',
                default => 'daily',
            };
        } catch (\Exception $e) {
            return 'daily';
        }
    }

    /**
     * Check if SFTP sync is enabled
     */
    private function isSftpSyncEnabled(): bool
    {
        try {
            return \App\Services\FeatureConfigurationService::isSftpSyncEnabled();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Prune expired session files and database records
     */
    private function pruneExpiredSessions(): void
    {
        $sessionDriver = config('session.driver');
        $lifetime = config('session.lifetime') * 60; // Convert minutes to seconds
        $now = time();

        // Clean up database sessions
        if ($sessionDriver === 'database') {
            try {
                $pruned = \DB::table(config('session.table'))
                    ->where('last_activity', '<', $now - $lifetime)
                    ->delete();
                
                if ($pruned > 0) {
                    \Log::info("Pruned $pruned expired database sessions");
                }
            } catch (\Exception $e) {
                \Log::error("Failed to prune database sessions: " . $e->getMessage());
            }
        }

        // Clean up file-based sessions
        if ($sessionDriver === 'file') {
            $sessionPath = config('session.files');

            if (!is_dir($sessionPath)) {
                return;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sessionPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            $pruned = 0;

            foreach ($files as $file) {
                if ($file->isFile()) {
                    // Check if file is older than session lifetime
                    if (($now - $file->getMTime()) > $lifetime) {
                        @unlink($file->getPathname());
                        $pruned++;
                    }
                }
            }

            if ($pruned > 0) {
                \Log::info("Pruned $pruned expired session files");
            }
        }
    }
}
