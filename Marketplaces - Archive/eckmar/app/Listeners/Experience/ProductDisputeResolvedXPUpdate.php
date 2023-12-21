<?php

namespace App\Listeners\Experience;

use App\Events\Purchase\ProductDisputeResolved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductDisputeResolvedXPUpdate
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

        $resolvedBuyer = $event->purchase->dispute->winner->id == $event->buyer->id;

        // if its resolved in favor of buyer
        if ($resolvedBuyer){
            $multiplier = config('experience.multipliers.product_dispute_lost');
            $amount = round($event->purchase->getSumDollars()*$multiplier,0);
            $event->vendor->takeExperience($amount);
        }

        // if its resolved in favor of vendor
        if (!$resolvedBuyer){
            $multiplier = config('experience.multipliers.product_delivered');
            $amount = round($event->purchase->getSumDollars()*$multiplier,0);
            $event->vendor->grantExperience($amount);
        }
    }
}
