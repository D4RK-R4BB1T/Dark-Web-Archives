<?php

namespace App\Events;

use App\Shop;
use App\User;
use App\Wallet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BitcoinWalletCreated extends \Event
{
    use SerializesModels;

    /** @var Wallet */
    public $wallet;
    /** @var User */
    public $user;
    /** @var Shop */
    public $shop;
    /**
     * Create a new event instance.
     *
     * @param \App\Wallet $wallet
     */
    public function __construct(Wallet $wallet)
    {
        $this->wallet = $wallet;
        if ($this->wallet->user) {
            $this->user = $this->wallet->user;
        } elseif ($this->wallet->shop) {
            $this->shop = $this->wallet->shop;
        }
    }
}
