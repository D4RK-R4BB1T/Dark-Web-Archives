<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\ProductDisputed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDisputedNotification
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
     * @param  ProductDisputed  $event
     * @return void
     */
    public function handle(ProductDisputed $event)
    {
        if ($event->initiator == $event->vendor->user){
            /**
             * Notify Buyer
             */
            $content = 'Purchase for a product you bought is being disputed';
            $routeName = 'profile.purchases.single';
            $routeParams = serialize(['purchase'=>$event->purchase->id]);
            $event->buyer->notify($content,$routeName,$routeParams);
        }

        if ($event->initiator == $event->buyer){
            /**
             * Notify vendor
             */
            $content = 'Purchase for a product you sold is being disputed';
            $routeName = 'profile.sales.single';
            $routeParams = serialize(['sale'=>$event->purchase->id]);
            $event->vendor->user->notify($content,$routeName,$routeParams);
        }



    }
}
