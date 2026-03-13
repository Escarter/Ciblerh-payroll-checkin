<?php

namespace App\Console;

use App\Console\Commands\ClearSuccessfulJobsFolders;
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
     * All scheduled tasks are now defined in routes/console.php
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Scheduled tasks are defined in routes/console.php
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
