<?php

namespace App\Events;

use App\Good;
use App\GoodsPosition;
use Illuminate\Queue\SerializesModels;

class PositionDeleted
{
    use SerializesModels;

    public $position;

    /**
     * Create a new event instance.
     *
     * @param GoodsPosition $position
     */
    public function __construct(GoodsPosition $position)
    {
        $this->position = $position;
    }
}
