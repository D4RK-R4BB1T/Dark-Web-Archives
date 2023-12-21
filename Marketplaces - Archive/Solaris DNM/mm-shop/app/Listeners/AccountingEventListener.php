<?php

namespace App\Listeners;

use App\AccountingDistribution;
use App\AccountingLot;
use App\Events\AppEventsPositionCreated;
use App\Events\OrderFinished;
use App\Events\PositionCreated;
use App\Events\PositionDeleted;
use App\GoodsPosition;
use App\Order;
use App\Shop;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountingEventListener
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
     * @param  PositionCreated  $event
     * @return void
     */
    public function positionCreated(PositionCreated $event)
    {
        /** @var GoodsPosition $position */
        $position = $event->position;
        $shop = Shop::getDefaultShop();

        if (!$position->package) {
            return;
        }
        /** @var \App\AccountingLot $lot */
        $lot = $shop->lots()->lockForUpdate()
            ->where('good_id', $position->good_id)
            ->where('measure', $position->package->measure)
            ->where('available_amount', '>=', $position->package->amount)
            ->first();

        if (!$lot) {
            return;
        }
        /** @var \App\AccountingDistribution $distribution */
        $distribution = $lot->distributions()->lockForUpdate()
            ->where('employee_id', $position->employee_id)
            ->where('available_amount', '>=', $position->package->amount)
            ->first();

        if (!$distribution) {
            return;
        }

        $position->distribution_id = $distribution->id;
        $position->save();

        $distribution->available_amount -= $position->package->amount;
        $distribution->save();

        $lot->available_amount -= $position->package->amount;
        $lot->save();
    }

    public function positionDeleted(PositionDeleted $event)
    {
        /** @var GoodsPosition $position */
        $position = $event->position;
        if (!$position->available) {
            return;
        }

        $package = $position->package;
        if (!$package) {
            return;
        }

        /** @var AccountingDistribution $distribution */
        $distribution = $position->distribution()->lockForUpdate()->first();
        if (!$distribution) {
            return;
        }

        /** @var AccountingLot $lot */
        $lot = $distribution->lot()->lockForUpdate()->first();
        if (!$lot) {
            return;
        }

        $distribution->available_amount += $package->amount;
        $distribution->save();

        $lot->available_amount += $package->amount;
        $lot->save();
    }

    /**
     * @param OrderFinished $event
     */
    public function orderFinished(OrderFinished $event)
    {
        /** @var Order $order */
        $order = $event->order;

        /** @var \App\AccountingDistribution $distribution **/
        $distribution = $order->position->distribution()->lockForUpdate()->first();
        if (!$distribution) {
            return;
        }

        $distribution->proceed_btc += $order->package_price_btc;
        $distribution->save();
    }
}
