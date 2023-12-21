<?php

namespace App\Listeners;


use App\Events\Admin\ProductDeleted;
use App\Events\Admin\UserEdited;
use App\Events\Admin\UserGroupChanged;
use App\Events\Admin\UserPermissionsUpdated;
use App\Log;

class AdminActionSubscriber
{
    /**
     * Admin changed user
     */
    public function onUserChange(UserEdited $event) {
        Log::enter([
            'user_id' => $event->editedData['admin']->id,
            'type' => 'edit',
            'description' => 'Changed ['. $event->editedData['field'] .'] field from ['.$event->editedData['old'].'] to ['.$event->editedData['new'].']',
            'performed_on' => $event->editedData['user'],
        ]);
    }

    /**
     * Admin deleted product
     */
    public function onProductDelete(ProductDeleted $event){

        Log::enter([
            'user_id' => $event->admin->id,
            'type' => 'remove',
            'description' => 'Deleted product ['.$event->product->name.'] owned by ['.$event->vendor->username.']',
            'performed_on' => $event->vendor,
        ]);
    }

    public function onUserGroupChange(UserGroupChanged $event){
        $statusChangeDesc = [
          true => 'given to',
          false => 'taken from'
        ];
        Log::enter([
            'user_id' => $event->admin->id,
            'type' => 'change',
            'description' =>  ucfirst($event->userGroup).' status '.$statusChangeDesc[$event->status].' user',
            'performed_on' => $event->user,
        ]);
    }
    public function onUserPermissionUpdate(UserPermissionsUpdated $event){
        Log::enter([
            'user_id' => $event->admin->id,
            'type' => 'update',
            'description' => 'Permissions updated',
            'performed_on' => $event->user,
        ]);
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\Admin\UserEdited',
            'App\Listeners\AdminActionSubscriber@onUserChange'
        );

        $events->listen(
            'App\Events\Admin\ProductDeleted',
            'App\Listeners\AdminActionSubscriber@onProductDelete'
        );

        $events->listen(
            'App\Events\Admin\UserGroupChanged',
            'App\Listeners\AdminActionSubscriber@onUserGroupChange'
        );
        $events->listen(
                'App\Events\Admin\UserPermissionsUpdated',
                'App\Listeners\AdminActionSubscriber@onUserPermissionUpdate'
        );


    }
}