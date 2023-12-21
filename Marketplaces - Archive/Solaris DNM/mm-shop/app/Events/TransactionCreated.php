<?php

namespace App\Events;

use App\Transaction;
use Illuminate\Queue\SerializesModels;

class TransactionCreated extends \Event
{
    use SerializesModels;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
