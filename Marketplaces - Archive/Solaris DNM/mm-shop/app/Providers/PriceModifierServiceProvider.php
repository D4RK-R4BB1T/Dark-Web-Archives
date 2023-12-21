<?php

namespace App\Providers;

use App\Packages\PriceModifier\PriceModifierService;
use Illuminate\Support\ServiceProvider;

class PriceModifierServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('price_modifier', function() {
            return new PriceModifierService();
        });
    }
}
