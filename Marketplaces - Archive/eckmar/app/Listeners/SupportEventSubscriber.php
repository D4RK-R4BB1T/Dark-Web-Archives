<?php


namespace App\Listeners;


use App\Events\Support\NewTicketReply;
use App\Events\Support\TicketClosed;

class SupportEventSubscriber {



    public function onNewTicketMessage(NewTicketReply $event)
    {
        $content = 'There is a new reply on your support ticket by ['.$event->ticketReply->user->username.']';
        $routeName = 'profile.tickets';
        $routeParams = serialize(['ticket'=>$event->ticketReply->ticket->id]);
        $event->ticketReply->ticket->user->notify($content,$routeName,$routeParams);
    }


    public function onTicketClosed(TicketClosed $event){
        $content = 'Your support ticket has been closed by administrator';
        $routeName = 'profile.tickets';
        $routeParams = serialize(['ticket'=>$event->ticket->id]);
        $event->ticket->user->notify($content,$routeName,$routeParams);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\Support\NewTicketReply',
            'App\Listeners\SupportEventSubscriber@onNewTicketMEssage'
        );

        $events->listen(
            'App\Events\Support\TicketClosed',
            'App\Listeners\SupportEventSubscriber@onTicketClosed'
        );




    }

}