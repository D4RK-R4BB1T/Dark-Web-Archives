<?php

namespace App\Console\Commands;

use App\Exceptions\RequestException;
use App\Purchase;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleasePurchasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:release {days?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release all purchases in \'sent\' state, older than some number of days.';

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
        // load default from config if there is no option
        $daysOldPurchases = $this->argument('days')!=null ? intval($this->argument('days')) : config('marketplace.days_old_purchases');

        $releasingPurchases = Purchase::where('state', 'sent')
                                    -> where('updated_at', '<', Carbon::now()->subDays($daysOldPurchases))->get();


        if(count($releasingPurchases) > 0){
            foreach ($releasingPurchases as $purchase){
                try{
                    $purchase->release();
                    $this->info("Purchase #$purchase->id is successfully released!");
                }
                catch (RequestException $exception){
                    $this->warn("Error with purchase #$purchase->id, " . $exception ->getMessage() . " For more info please check the logs!");
                }
            }
        }
        else {
            $this -> warn("There are no purchases in 'sent' state older than $daysOldPurchases days!");
        }
    }
}
