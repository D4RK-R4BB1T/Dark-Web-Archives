<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\ProductDisputeNewMessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDisputeNewMessageNotification
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
     * @param  ProductDisputeNewMessageSent  $event
     * @return void
     */
    public function handle(ProductDisputeNewMessageSent $event)
    {
        if ($event->initiator == $event->vendor->user){
            /**
             * Notify Buyer
             */
            $content = 'There is new message on your disputed purchase';
            $routeName = 'profile.purchases.single';
            $routeParams = serialize(['purchase'=>$event->purchase->id]);
            $event->buyer->notify($content,$routeName,$routeParams);
        }

        if ($event->initiator == $event->buyer){
            /**
             * Notify vendor
             */
            $content = 'There is new message on your disputed sale';
            $routeName = 'profile.sales.single';
            $routeParams = serialize(['sale'=>$event->purchase->id]);
            $event->vendor->user->notify($content,$routeName,$routeParams);
        }
    }
}
