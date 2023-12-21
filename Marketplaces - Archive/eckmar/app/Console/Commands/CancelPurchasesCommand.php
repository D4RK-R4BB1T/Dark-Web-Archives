<?php

namespace App\Console\Commands;

use App\Purchase;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelPurchasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:cancel {days?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel purchases that don\'t  have paid balance in purchased state!';

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
        $daysOldPurchases = $this->argument('days')!=null ? intval($this->argument('days')) : config('marketplace.days_old_purchases');

        $cancelingPurchases = Purchase::where('state', 'purchased')
                                    -> where('created_at', '<', Carbon::now()->subDays($daysOldPurchases))->get();

        if($cancelingPurchases->isNotEmpty()) {
            // Foreach purchase cancel
            foreach ($cancelingPurchases as $purchaseToCancel) {
                try{
                    // if there is no funds
                    if($purchaseToCancel->getBalance() == 0)
                        $purchaseToCancel->cancel();
                    else
                        throw new \Exception('There is funds on purchase #' . $purchaseToCancel->id . ' canceling failed!');
                    // print out success
                    $this->info("Purchase #$purchaseToCancel->id successfully canceled!");
                }
                catch (\Exception $e){
                    $this->warn('Cancelation failed beacuse: ' . $e->getMessage());
                }
            }
        }
        else{
            $this->warn('There are no purchases to cancel!');
        }

    }
}
