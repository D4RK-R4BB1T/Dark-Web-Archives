<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Registered' => [
            'App\Listeners\UserEventListener@registered',
        ],
        'App\Events\BitcoinWalletCreated' => [
            'App\Listeners\UserEventListener@walletCreated'
        ],
        'App\Events\PendingBalanceChanged' => [
            'App\Listeners\UserEventListener@pendingBalanceChanged'
        ],
        'App\Events\ActualBalanceChanged' => [
            'App\Listeners\UserEventListener@actualBalanceChanged',
        ],
        'App\Events\TransactionCreated' => [
            'App\Listeners\TransactionEventListener@created'
        ],
        'App\Events\TransactionConfirmed' => [
            'App\Listeners\TransactionEventListener@confirmed'
        ],
        'App\Events\PositionCreated' => [
            'App\Listeners\ShopEventListener@positionCreated',
            'App\Listeners\AccountingEventListener@positionCreated'
        ],
        'App\Events\PositionPurchased' => [
            'App\Listeners\ShopEventListener@positionPurchased'
        ],
        'App\Events\PositionDeleted' => [
            'App\Listeners\ShopEventListener@positionDeleted',
            'App\Listeners\AccountingEventListener@positionDeleted'
        ],
        'App\Events\OrderFinished' => [
            'App\Listeners\ShopEventListener@orderFinished',
            'App\Listeners\AccountingEventListener@orderFinished',
            'App\Listeners\UserGroupListener@orderFinished'
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
