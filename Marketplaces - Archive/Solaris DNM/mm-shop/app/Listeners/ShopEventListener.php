<?php

namespace App\Listeners;

use App\Events\OrderFinished;
use App\Events\PositionCreated;
use App\Events\PositionDeleted;
use App\Events\PositionPurchased;
use App\Good;
use App\GoodsCity;
use App\GoodsPackage;
use App\GoodsPosition;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShopEventListener
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
        $position = $event->position;
        $package = $position->package;
        if (!$package) { // package was deleted while preorder was not finished
            return;
        }

        if (!$position->available || $package->preorder) { // it was unmoderated or preorder position, so there is nothing to do
            return;
        }

        $package->has_ready_quests = $package->availablePositions()->count() > 0 ? 1 : 0;
        $package->has_quests = $package->has_ready_quests || $package->preorder;
        $package->save();
    }

    public function positionPurchased(PositionPurchased $event)
    {
        $package = $event->position->package;
        if (!$package) { // package was deleted while preorder was not finished
            return;
        }

        $package->has_ready_quests = $package->availablePositions()->count() > 0 ? 1 : 0;
        $package->has_quests = $package->has_ready_quests || $package->preorder;

        $package->save();
    }

    public function positionDeleted(PositionDeleted $event)
    {
        $position = $event->position;
        $package = $position->package;
        if (!$package) {
            return;
        }

        // method executes before position actually deletes, so we need to perform different check
        $package->has_ready_quests = $package->availablePositions()
                ->where('id', '!=', $position->id)
                ->count() > 0 ? 1 : 0;
        $package->has_quests = $package->has_ready_quests || $package->preorder;
        $package->save();
    }

    public function orderFinished(OrderFinished $event)
    {
        $order = $event->order;

        // добавлять / отнимать $employeeReward можно только если курьер не был оштрафован за ненаход
        if ($order->package && $order->position->employee && !$order->courier_fined) {
            $description = 'Выплата за заказ';
            $employee = $order->position->employee;
            $employeeReward = $order->package->employee_reward;

            if ($employeeReward !== 0.0) {
                $employee->earnings()->create([
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'amount' => $employeeReward,
                    'description' => $description
                ]);

                $employee->balance += $employeeReward;
                $employee->save();
            }
        }

        $order->shop->buy_count += 1;
        $order->shop->save();

        $order->user->buy_count += 1;
        $order->user->save();

        if ($order->good) {
            $order->good->buy_count += 1;
            $order->good->save();
        }
    }
}
