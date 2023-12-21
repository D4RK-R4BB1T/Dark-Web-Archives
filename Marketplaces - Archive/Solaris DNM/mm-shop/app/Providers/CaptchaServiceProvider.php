<?php

namespace App\Providers;

use App\Packages\Captcha;
use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends \Latrell\Captcha\CaptchaServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/latrell-captcha.php', 'latrell-captcha');

        $this->app->singleton('captcha', function ($app) {
            return Captcha::instance();
        });
    }
}
