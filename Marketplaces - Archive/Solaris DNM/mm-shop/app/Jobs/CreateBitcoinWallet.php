<?php

namespace App\Jobs;

use App\Events\BitcoinWalletCreated;
use App\Exceptions\BitcoinException;
use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Nbobtc\Command\Command;

use App\User;

class CreateBitcoinWallet implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var BitcoinUtils */
    protected $utils;
    /** @var User */
    protected $user;
    /** @var Shop */
    protected $shop;
    /** @var BitcoinLogger */
    protected $log;
    /** @var string */
    protected $type;
    /** @var array */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param \App\User|\App\Shop $model User or shop who need wallet.
     * @param string $type Type of wallet (Wallet::TYPE_PRIMARY or Wallet::TYPE_ADDITIONAL)
     * @param array $data (Additional data to merge when creating wallet)
     */
    public function __construct($model, $type, array $data = [])
    {
        if ($model instanceof User) {
            $this->user = $model;
        } elseif ($model instanceof Shop) {
            $this->shop = $model;
        } else {
            throw new \InvalidArgumentException('Model type is invalid.');
        }

        if (!in_array($type, [Wallet::TYPE_PRIMARY, Wallet::TYPE_ADDITIONAL])) {
            throw new \InvalidArgumentException('Wallet type is invalid');
        }

        $this->type = $type;
        $this->data = $data;

        $this->utils = resolve('App\Packages\Utils\BitcoinUtils');
        $this->log = resolve('App\Packages\Loggers\BitcoinLogger');
    }

    /**
     * Execute the job.
     *
     * @throws BitcoinException
     */
    public function handle()
    {
        $accountName = config('mm2.application_id');

        $this->log->info('Creating Bitcoin wallet.', [
            'account' => $accountName
        ]);

        $wallet = $this->utils->sendCommand(new Command('getnewaddress', $accountName));
        if (empty($wallet->result)) {
            $this->log->addCritical('An error occured while creating Bitcoin wallet: getnewaddress failed!');
            throw new BitcoinException('An error occured while creating Bitcoin wallet: getnewaddress failed!');
        }

        $privateKey = $this->utils->sendCommand(new Command('dumpprivkey', $wallet->result));
        if (empty($privateKey->result)) {
            $this->log->addCritical('An error occured while creating Bitcoin wallet: dumpprivkey failed!.');
            throw new BitcoinException('An error occured while creating Bitcoin wallet. dumpprivkey failed!');
        }

        $segwitWallet = $this->utils->sendCommand(new Command('addwitnessaddress', $wallet->result));
        if (empty($segwitWallet->result)) {
            $this->log->addCritical('An error occured while creating Bitcoin wallet: addwitnessaddress failed!.');
            throw new BitcoinException('An error occured while creating Bitcoin wallet. addwitnessaddress failed!');
        }

        $this->utils->sendCommand(new Command('setaccount', [$segwitWallet->result, $accountName]));

        $walletProperties = array_merge($this->data, [
            'type' => $this->type,
            'wallet' => $wallet->result,
            'wallet_key' => encrypt($privateKey->result),
            'segwit_wallet' => $segwitWallet->result
        ]);

        if ($this->user) {
            $walletProperties['user_id'] = $this->user->id;
        } elseif ($this->shop) {
            $walletProperties['shop_id'] = $this->shop->id;
        }

        /** @var Wallet $wallet **/
        $wallet = Wallet::create($walletProperties);

        $this->log->info('Wallet created. ', [
            'account' => $accountName,
            'wallet' => $wallet->wallet,
            'segwit_wallet' => $wallet->segwit_wallet
        ]);

        event(new BitcoinWalletCreated($wallet));
    }
}
