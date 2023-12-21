<?php

namespace App\Console\Commands;

use App\Exceptions\RequestException;
use App\Marketplace\ModuleManager;
use App\Marketplace\Payment\FinalizeEarlyPayment;
use App\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompletePurchaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark purchases as delivered and complete the if there is enough balance on theirs addresses!';

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
        if (!FinalizeEarlyPayment::isEnabled()){
            $this->warn('Finalize Early module not available');
            return;
        }
        $procedure = FinalizeEarlyPayment::getProcedure();
        $procedure->commandHandle($this);


    }
}
