<?php

namespace App\Listeners;

use App\Events\Message\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMessageNotifications
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        $content = 'You have received new message from ['.$event->message->getSender()->username.']';
        $routeName = 'profile.messages';
        $routeParams = serialize(['conversation'=>$event->message->conversation()->first()->id]);
        $event->message->getReceiver()->notify($content,$routeName,$routeParams);
    }
}
