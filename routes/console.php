<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Clean processed records
Schedule::command('wima:clean-processed')->dailyAt('01:30');

// Update leave process on last day of month
Schedule::command('wima:leave-update-process')
    ->lastDayOfMonth('23:50')
    ->timezone('Africa/Douala');

// Send pending advance salary reminders
Schedule::command('advance-salary:send-pending-reminder')
    ->dailyAt('08:00')
    ->timezone('Africa/Douala');

// Send birthday wishes
Schedule::command('wima:wish-happy-birthday')
    ->dailyAt('08:00')
    ->timezone('Africa/Douala');

// Process scheduled reports
Schedule::command('reports:process-scheduled')->hourly();

// Clean up expired sessions every 30 minutes
Schedule::call(function () {
    pruneExpiredSessions();
})->everyThirtyMinutes();

// Deactivate inactive users - scheduled based on setting time, defaults to 02:00
Schedule::command('users:deactivate-inactive')
    ->dailyAt(getDeactivationTime())
    ->timezone(config('app.timezone', 'UTC'))
    ->when(fn() => isDeactivationEnabled());

// Fetch SFTP payslips - scheduled based on frequency setting
Schedule::job(new \App\Jobs\FetchSftpPayslipsJob)
    ->{getSftpSyncFrequency()}()
    ->when(fn() => isSftpSyncEnabled());

// Scan SFTP push folder - frequency configurable from settings
$pushEvent = Schedule::command('sftp:scan-push-folder')
    ->when(fn() => isSftpSyncEnabled())
    ->withoutOverlapping();
applySftpPushScanFrequency($pushEvent);

/*
|--------------------------------------------------------------------------
| Schedule Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Get deactivation check time from settings
 */
function getDeactivationTime(): string
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
function isDeactivationEnabled(): bool
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
function applySftpPushScanFrequency(\Illuminate\Console\Scheduling\Event $event): void
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
function getSftpSyncFrequency(): string
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
function isSftpSyncEnabled(): bool
{
    try {
        return \App\Services\FeatureConfigurationService::isSftpSyncEnabled();
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Prune expired session files and database records
 */
function pruneExpiredSessions(): void
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
