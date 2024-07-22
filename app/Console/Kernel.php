<?php

namespace App\Console;

use App\Jobs\ClearUnusedFiles;
use App\Jobs\DeleteOldSubmissionsErrorFiles;
use App\Jobs\FindBrokenSubmissionsAndFixJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ClearUnusedFiles(), 'low')->weekly()->days([1])->dailyAt('05:00');

        $schedule->job(new DeleteOldSubmissionsErrorFiles(), 'low')->weekly()->days([6])->dailyAt('05:00');
        $schedule->job(new FindBrokenSubmissionsAndFixJob(), 'low')->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
