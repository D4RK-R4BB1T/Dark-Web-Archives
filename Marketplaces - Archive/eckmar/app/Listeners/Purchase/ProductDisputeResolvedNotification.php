<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\ProductDisputeResolved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDisputeResolvedNotification
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
     * @param  ProductDisputeResolved  $event
     * @return void
     */
    public function handle(ProductDisputeResolved $event)
    {
        /**
         * Notify Buyer
         */
        $content = 'Dispute for your purchase is now resolved';
        $routeName = 'profile.purchases.single';
        $routeParams = serialize(['purchase'=>$event->purchase->id]);
        $event->buyer->notify($content,$routeName,$routeParams);

        /**
         * Notify vendor
         */
        $content = 'Dispute for your sale is now resolved';
        $routeName = 'profile.sales.single';
        $routeParams = serialize(['sale'=>$event->purchase->id]);
        $event->vendor->user->notify($content,$routeName,$routeParams);
    }
}
