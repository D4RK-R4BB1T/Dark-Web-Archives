<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\ProductSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductSentNotification
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
     * @param  ProductSent  $event
     * @return void
     */
    public function handle(ProductSent $event)
    {

        $content = 'Your product has been sent by vendor ['.$event->vendor->user->username.']';
        $routeName = 'profile.purchases.single';
        $routeParams = serialize(['purchase'=>$event->purchase->id]);
        $event->buyer->notify($content,$routeName,$routeParams);
    }
}
