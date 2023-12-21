<?php

namespace App\Providers;

use App\Packages\Referral\ReferralState;
use Illuminate\Support\ServiceProvider;

class ReferralServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('referral_state', function ($app) {
            return new ReferralState();
        });
    }
}
