<?php

namespace App\Listeners\Purchase;

use App\Events\Purchase\ProductDelivered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDeliveredNotification
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
     * @param  ProductDelivered  $event
     * @return void
     */
    public function handle(ProductDelivered $event)
    {
        $content = 'Your product has been marked delivered by buyer ['.$event->buyer->username.']';
        $routeName = 'profile.sales.single';
        $routeParams = serialize(['sales'=>$event->purchase->id]);
        $event->vendor->user->notify($content,$routeName,$routeParams);
    }
}
