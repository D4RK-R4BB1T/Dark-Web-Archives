<?php

namespace App\Listeners;

use App\Events\PendingBalanceChanged;
use App\Events\TransactionConfirmed;
use App\Events\TransactionCreated;
use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Transaction;
use App\User;
use App\Wallet;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TransactionEventListener
{
    /** @var BitcoinLogger */
    public $log;

    /**
     * Create the event listener.
     *
     * @param BitcoinLogger $log
     */
    public function __construct(BitcoinLogger $log)
    {
        $this->log = $log;
    }

    /**
     * Performs when transaction is just created.
     *
     * @param TransactionCreated $event
     */
    public function created(TransactionCreated $event)
    {
    }

    /**
     * Performs when transaction was confirmed.
     *
     * @param  TransactionConfirmed  $event
     * @return void
     */
    public function confirmed(TransactionConfirmed $event)
    {
        $transaction = $event->transaction;
        $this->log->info('Transaction confirmed.', [
            'tx_id' => $transaction->tx_id,
            'vout' => $transaction->vout
        ]);
        $transaction->handled = TRUE;
        $transaction->save();
        
        $event->transaction->wallet->balanceOperation(
            $transaction->amount,
            BitcoinUtils::CURRENCY_BTC,
            'Пополнение баланса'
        );
    }

}
