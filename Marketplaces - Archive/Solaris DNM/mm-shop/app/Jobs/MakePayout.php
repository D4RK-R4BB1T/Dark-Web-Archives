<?php

namespace App\Jobs;

use App\Exceptions\BitcoinException;
use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Nbobtc\Command\Command;

class MakePayout implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    /** @var BitcoinLogger */
    protected $log;

    /** @var BitcoinUtils */
    protected $utils;

    /** @var string */
    protected $wallet;

    /** @var float */
    protected $amount;

    /** @var string */
    protected $method;

    /** @var string */
    protected $route;

    /** @var integer **/
    protected $walletId;

    /** @var integer */
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param string $wallet
     * @param float $amount
     * @param string $method
     * @param string $route
     * @param integer $walletId
     * @param integer $userId
     */
    public function __construct($wallet, $amount, $method, $route, $walletId, $userId)
    {
        $this->wallet = $wallet;
        $this->amount = floatval($amount);
        $this->method = $method;
        $this->route = $route;
        $this->walletId = $walletId;
        $this->userId = $userId;


        $this->utils = resolve('App\Packages\Utils\BitcoinUtils');
        $this->log = resolve('App\Packages\Loggers\BitcoinLogger');
    }

    /**
     * Execute the job.
     * @return void
     * @throws BitcoinException
     * @throws \Exception
     */
    public function handle()
    {
        $this->log->info('Handling Bitcoin payout', [
            'wallet' => $this->wallet,
            'amount' => $this->amount,
            'method' => $this->method,
            'route' => $this->route,
            'wallet_id' => $this->walletId,
            'user_id' => $this->userId
        ]);

        if (!BitcoinUtils::isPaymentsEnabled()) {
            $this->log->error('Payments are marked as disabled, can not process payout.');
            throw new BitcoinException('Payments are marked as disabled, can not process payout.');
        }

        $amount = $this->amount - config('mm2.bitcoin_fee');
        $result = $this->utils->sendCommand(new Command('sendfrom', [
            config('mm2.application_id'),
            $this->wallet,
            BitcoinUtils::prepareAmountToJSON($amount)
        ]));
        if (empty($result->result)) {
            $this->log->critical('Can not process payout: ' . (string) $result->error);
            throw new BitcoinException('Can not process payout: ' . (string) $result->error);
        }

        $this->log->info('Payout created', [
            'wallet' => $this->wallet,
            'amount' => $this->amount,
            'result' => $result->result
        ]);

        Payout::create([
            'wallet_id' => $this->walletId,
            'amount' => $amount,
            'user_id' => $this->userId,
            'method' => $this->method,
            'route' => $this->route,
            'result' => json_encode($result)
        ]);
    }
}
