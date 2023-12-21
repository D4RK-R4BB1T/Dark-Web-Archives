<?php

namespace App\Events\Message;

use App\Message;

use Illuminate\Foundation\Events\Dispatchable;

class MessageSent
{
    use Dispatchable;

    /**
     * Message that is being sent
     *
     * @var Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message)
    {

        $this->message = $message;
    }


}
