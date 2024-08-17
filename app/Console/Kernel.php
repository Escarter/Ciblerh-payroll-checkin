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
        $schedule->command('wima:wish-happy-birthday')->dailyAt('08:00')->timezone('Africa/Douala');
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
