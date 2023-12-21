<?php

namespace App\Providers;

use App\Packages\ExchangeAPI\ExchangeAPI;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ExchangeAPIServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ExchangeAPI::class, function ($app) {
            $guzzle = new Client([
                'proxy' => [
                    'http' => config('app.debug') ? null : sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port')),
                    'https' => config('app.debug') ? null : sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port'))
                ],

                'curl' => [
                    CURLOPT_PROXYTYPE => 7 // http://blog.daviddemartini.com/archives/6273
                ],

                'timeout' => 90,
                'http_errors' => false
            ]);

            return new ExchangeAPI($guzzle);
        });

    }
}
