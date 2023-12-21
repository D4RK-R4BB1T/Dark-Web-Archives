<?php

namespace App\Events;

use App\GoodsPosition;
use App\User;
use Illuminate\Queue\SerializesModels;

class PositionPurchased
{
    public $position;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(GoodsPosition $position, User $user)
    {
        $this->position = $position;
        $this->user = $user;
    }
}
