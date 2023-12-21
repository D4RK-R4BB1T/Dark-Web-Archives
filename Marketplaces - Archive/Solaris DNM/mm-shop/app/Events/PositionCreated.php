<?php

namespace App\Events;

use App\GoodsPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PositionCreated
{
    use SerializesModels;

    public $position;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(GoodsPosition $position)
    {
        $this->position = $position;
    }

}
