<?php

namespace App\Listeners\Experience;

use App\Events\Purchase\ProductDelivered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDeliveredXPUpdate
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
        $multiplier = config('experience.multipliers.product_delivered');
        $amount = round($event->purchase->getSumDollars()*$multiplier,0);
        $event->vendor->grantExperience($amount);
    }
}
