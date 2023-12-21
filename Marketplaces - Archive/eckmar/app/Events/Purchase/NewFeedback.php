<?php

namespace App\Events\Purchase;

use App\Feedback;
use Illuminate\Foundation\Events\Dispatchable;


class NewFeedback
{
    use Dispatchable;

    /**
     * Feedback that triggered the event
     *
     * @var Feedback
     */
    public $feedback;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }


}
