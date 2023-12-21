<?php

namespace App\Console\Commands;

use App\Exceptions\BitcoinException;
use App\Jobs\CreateBitcoinWallet;
use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\User;
use App\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class PerformActions extends Command
{
    /** @var BitcoinUtils */
    protected $utils;

    /** @var BitcoinLogger */
    protected $log;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:perform_actions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform actions in application context';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->utils = resolve('App\Packages\Utils\BitcoinUtils');
        $this->log = resolve('App\Packages\Loggers\BitcoinLogger');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accountName = config('mm2.application_id');

        $this->log->info('Creating Bitcoin wallet.', [
            'account' => $accountName
        ]);

        $wallets = Wallet::all();

        foreach ($wallets as $dbWallet) {
            $wallet = $this->utils->sendCommand(new \Nbobtc\Command\Command('getnewaddress', $accountName));
            if (empty($wallet->result)) {
                $this->log->addCritical('An error occured while creating Bitcoin wallet: getaccountaddress failed!');
                throw new BitcoinException('An error occured while creating Bitcoin wallet: getaccountaddress failed!');
            }

            $privateKey = $this->utils->sendCommand(new \Nbobtc\Command\Command('dumpprivkey', $wallet->result));
            if (empty($privateKey->result)) {
                $this->log->addCritical('An error occured while creating Bitcoin wallet: dumpprivkey failed!.');
                throw new BitcoinException('An error occured while creating Bitcoin wallet. dumpprivkey failed!');
            }

            $segwitWallet = $this->utils->sendCommand(new \Nbobtc\Command\Command('addwitnessaddress', $wallet->result));
            if (empty($segwitWallet->result)) {
                $this->log->addCritical('An error occured while creating Bitcoin wallet: dumpprivkey failed!.');
                throw new BitcoinException('An error occured while creating Bitcoin wallet. dumpprivkey failed!');
            }

            $this->utils->sendCommand(new \Nbobtc\Command\Command('setaccount', [$segwitWallet->result, $accountName]));


            $dbWallet->wallet = $wallet->result;
            $dbWallet->wallet_key = encrypt($privateKey->result);
            $dbWallet->segwit_wallet = $segwitWallet->result;
            $dbWallet->save();

            $this->info('New wallet generated: ' . $dbWallet->wallet . ' ' . $dbWallet->segwit_wallet);
        }

        $this->info('That\'s all!');
    }
}
