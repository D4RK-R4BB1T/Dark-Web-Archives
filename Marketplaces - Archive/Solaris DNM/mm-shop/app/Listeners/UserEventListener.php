<?php

namespace App\Listeners;

use App\Events\ActualBalanceChanged;
use App\Events\BitcoinWalletCreated;
use App\Events\PendingBalanceChanged;
use App\Jobs\CreateBitcoinWallet;
use App\Packages\Loggers\ShopLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserEventListener
{
    /** @var ShopLogger */
    public $shopLogger;

    /**
     * Create the event listener.
     *
     * @param ShopLogger $shopLogger
     */
    public function __construct(ShopLogger $shopLogger)
    {
        $this->shopLogger = $shopLogger;
    }

    /**
     * Occurs when new user registered at the system.
     *
     * @param  Registered  $event
     * @return void
     */
    public function registered(Registered $event)
    {
        $job = new CreateBitcoinWallet($event->user, Wallet::TYPE_PRIMARY, ['title' => 'Основной кошелек пользователя']);
        dispatch($job);
    }

    /**
     * Occurs when Bitcoin wallet for user is ready.
     *
     * @param BitcoinWalletCreated $event
     * @return void
     */
    public function walletCreated(BitcoinWalletCreated $event)
    {
        if (!$event->user) {
            return;
        }

        $user = $event->user;
        \Log::info('Registration is finished, marking user ' . $user->username . ' as active.');
        $user->active = true;
        $user->save();
    }

    /**
     * Occurs when pending balance has changed.
     *
     * @param PendingBalanceChanged $event
     */
    public function pendingBalanceChanged(PendingBalanceChanged $event)
    {
    }

    /**
     * Occurs when actual balance has changed.
     *
     * @param ActualBalanceChanged $event
     */
    public function actualBalanceChanged(ActualBalanceChanged $event)
    {
        if (!$event->user) {
            return;
        }

        $user = $event->user;
        
        // Shop activation.
        if ($user->role === User::ROLE_SHOP_PENDING) {
            $balance = $user->getRealBalance(BitcoinUtils::CURRENCY_USD);
            $shopPrice = config('mm2.shop_usd_price');

            if ($balance >= $shopPrice * config('mm2.shop_usd_price_approx')) {
                $user->balanceOperation(-$shopPrice, BitcoinUtils::CURRENCY_USD, 'Оплата моментального магазина');
                Shop::init($user);
                $this->shopLogger->alert('New shop created.', ['user_id' => $user->id, 'shop_id' => $user->shop()->id]);
            }
        }
    }
}
