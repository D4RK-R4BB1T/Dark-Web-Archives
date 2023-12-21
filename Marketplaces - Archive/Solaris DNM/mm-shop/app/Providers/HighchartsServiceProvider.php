<?php

namespace App\Providers;

use App\Packages\Highcharts;
use Illuminate\Support\ServiceProvider;

class HighchartsServiceProvider extends ServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->app->singleton(Highcharts::class, function($app) {
            $highcharts = new Highcharts(config('mm2.highcharts_host'), config('mm2.highcharts_port'));
            return $highcharts;
        });
    }

    /**
     * @inheritdoc
     */
    public function provides()
    {
        return [Highcharts::class];
    }
}
