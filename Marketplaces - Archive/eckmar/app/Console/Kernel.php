<?php

namespace App\Console;

use App\Console\Commands\CompletePurchaseCommand;
use App\Console\Commands\DeleteOldMessages;
use App\Console\Commands\ReleasePurchasesCommand;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule to delete old messages every old days
        $schedule -> command(DeleteOldMessages::class, ['days' => config('marketplace.days_old_messages')])
                    ->days(config('marketplace.days_old_messages'));

        // Make the command for releasing purchases runs each X days
        $schedule -> command(ReleasePurchasesCommand::class, ['days' => config('marketplace.days_old_purchases')])
                    ->days(config('marketplace.days_old_purchases'));

        // Run completing command for purchases every defined number of days
        $schedule -> command(CompletePurchaseCommand::class) -> days(config('marketplace.days_complete'));

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
