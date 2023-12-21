<?php

namespace App\Events\Support;


use App\TicketReply;
use Illuminate\Foundation\Events\Dispatchable;


class NewTicketReply
{
    use Dispatchable;


    /**
     * @var TicketReply
     */
    public $ticketReply;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TicketReply $ticketReply)
    {
        $this->ticketReply = $ticketReply;
    }


}
