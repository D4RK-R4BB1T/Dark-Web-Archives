<?php

namespace App\Console\Commands;

use App\QiwiWallet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanQiwiIncomes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:clean_qiwi_incomes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans Qiwi incomes. Should be ran once a day.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $wallets = QiwiWallet::all();
        $isStartOfMonth = Carbon::now()->isSameDay(Carbon::now()->startOfMonth());

        foreach ($wallets as $wallet) {
            $wallet->current_day_income = 0;
            if ($isStartOfMonth) {
                $wallet->current_month_income = 0;
            }
            $wallet->save();
        }
    }
}
