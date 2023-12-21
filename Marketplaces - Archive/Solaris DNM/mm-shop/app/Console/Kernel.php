<?php

namespace App\Console;

use App\Console\Commands\AdvStatsDropCache;
use App\Console\Commands\CalculateStats;
use App\Console\Commands\CatalogSync;
use App\Console\Commands\CleanQiwiIncomes;
use App\Console\Commands\Cleanup;
use App\Console\Commands\CreateWallet;
use App\Console\Commands\HandleTransactions;
use App\Console\Commands\Init;
use App\Console\Commands\PerformActions;
use App\Console\Commands\QiwiApiSync;
use App\Console\Commands\UpdateBitcoinRates;
use App\Console\Commands\ChangePassword;
use App\Console\Commands\UpdateHasQuestsCache;
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
        HandleTransactions::class,
        CleanQiwiIncomes::class,
        CalculateStats::class,
        UpdateBitcoinRates::class,
        PerformActions::class,
        CatalogSync::class,
        QiwiApiSync::class,
        Cleanup::class,
        Init::class,
        ChangePassword::class,
        UpdateHasQuestsCache::class,
        CreateWallet::class,
        AdvStatsDropCache::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('mm2:handle_transactions')->cron('*/2 * * * * *');
        $schedule->command('mm2:update_rates')->everyFiveMinutes();
        $schedule->command('mm2:cleanup')->everyTenMinutes();
        $schedule->command('mm2:clean_qiwi_incomes')->daily();
        $schedule->command('mm2:calculate_stats')->daily();
        /*$schedule->command('mm2:catalog_sync')
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path() . '/logs/sync.log');*/
        $schedule->command('mm2:qiwi_sync')->everyFiveMinutes()
            ->appendOutputTo(storage_path() . '/logs/qiwi_api.log');
        $schedule->command('mm2:advstats_drop_cache')->everyTenMinutes();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
