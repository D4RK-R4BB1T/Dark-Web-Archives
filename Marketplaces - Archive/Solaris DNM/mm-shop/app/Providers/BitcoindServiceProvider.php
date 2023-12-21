<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use \Nbobtc\Http\Client;

class BitcoindServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\Nbobtc\Http\Client::class, function($app) {
            $driver = new \Nbobtc\Http\Driver\CurlDriver();
            $driver->addCurlOption(CURLOPT_TIMEOUT, 40);

            $client = new \Nbobtc\Http\Client(
                sprintf('http://%s:%s@%s:%s',
                    config('mm2.bitcoind_rpc_user'),
                    config('mm2.bitcoind_rpc_password'),
                    config('mm2.bitcoind_rpc_host'),
                    config('mm2.bitcoind_rpc_port')
                )
            );
            $client->withDriver($driver);
            $client->getRequest()->withHeader('Connection', 'keep-alive');
            return $client;
        });
    }

    /**
     * @inheritdoc
     */
    public function provides()
    {
        return [Client::class];
    }
}
