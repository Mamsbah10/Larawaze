<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Delete search history older than 14 days (daily check)
        $schedule->command('search-history:cleanup')->daily();
        
        // Delete notifications older than 7 days (daily check at 1 AM)
        $schedule->command('notifications:cleanup --days=7')->daily()->at('01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
