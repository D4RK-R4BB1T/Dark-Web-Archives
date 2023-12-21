<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Marketplace\Payment\{Payment, Escrow, Coin, BitcoinPayment};


class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Return payment class
        $this -> app -> singleton(Payment::class, function($app, $parameters){
            return new Escrow($parameters['purchase']);
        });
        // Return coin class
        $this -> app -> singleton(Coin::class, function($app){
            return new BitcoinPayment();
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
