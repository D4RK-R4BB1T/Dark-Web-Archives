<?php

namespace App\Listeners;

use App\Events\OrderFinished;
use App\UserGroup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserGroupListener
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
     * @param  OrderFinished  $event
     * @return void
     */
    public function orderFinished(OrderFinished $event)
    {
        $user = $event->order->user;
        $newGroup = $user->suggestDiscountGroup();
        if ($newGroup) {
            $user->group_id = $newGroup->id;
            $user->save();
        }
    }
}
