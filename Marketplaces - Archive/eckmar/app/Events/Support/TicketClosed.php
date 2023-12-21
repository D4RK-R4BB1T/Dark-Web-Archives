<?php

namespace App\Events\Support;


use App\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

class TicketClosed
{
    use Dispatchable;


    public $ticket;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }


}
