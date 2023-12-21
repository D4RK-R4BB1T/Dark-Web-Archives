<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\NewPurchase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductBoughtNotification
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
     * @param  NewPurchase  $event
     * @return void
     */
    public function handle(NewPurchase $event)
    {
        $content = 'Your product has been purchased by ['.$event->buyer->username.']';
        $routeName = 'profile.sales.single';
        $routeParams = serialize(['sale'=>$event->purchase->id]);
        $event->vendor->user->notify($content,$routeName,$routeParams);
    }
}
