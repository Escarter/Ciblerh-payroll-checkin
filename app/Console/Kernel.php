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

        // Deactivate inactive users - scheduled based on setting time, defaults to 02:00
        $schedule->command('users:deactivate-inactive')
            ->dailyAt($this->getDeactivationTime())
            ->timezone(config('app.timezone', 'UTC'))
            ->when(fn() => $this->isDeactivationEnabled());

        // Fetch SFTP payslips - scheduled based on frequency setting
        $schedule->job(new \App\Jobs\FetchSftpPayslipsJob)
            ->{$this->getSftpSyncFrequency()}()
            ->when(fn() => $this->isSftpSyncEnabled());
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
}
