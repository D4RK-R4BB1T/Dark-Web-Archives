<?php

namespace App\Events;

use App\Shop;
use App\Transaction;
use App\User;
use App\Wallet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PendingBalanceChanged extends \Event
{
    use SerializesModels;

    /** @var Wallet */
    public $wallet;
    /** @var User */
    public $user;
    /** @var Shop */
    public $shop;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @param Wallet $wallet
     * @param Transaction $transaction
     */
    public function __construct(Wallet $wallet, Transaction $transaction)
    {
        $this->wallet = $wallet;
        if ($this->wallet->user) {
            $this->user = $this->wallet->user;
        } elseif ($this->wallet->shop) {
            $this->shop = $this->wallet->shop;
        }
        $this->transaction = $transaction;
    }
}
