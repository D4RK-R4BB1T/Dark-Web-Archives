<?php

namespace App\Providers;

use App\Packages\CatalogSync\CatalogSynchronization;
use App\SyncState;
use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\ServiceProvider;

class CatalogSynchronizationServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CatalogSynchronization::class, function ($app) {
            $guzzle_cfg = [
                'timeout' => 90,
                'http_errors' => false,
                'headers' => [
                    'X-Guard-Bypass' => true
                ],
            ];

            if(!config('mm2.local_sync')) {
                $guzzle_cfg['proxy'] = [
                    'http' => sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port')),
                    'https' => sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port'))
                ];
                $guzzle_cfg['curl'] = [
                    CURLOPT_PROXYTYPE => 7 // http://blog.daviddemartini.com/archives/6273
                ];
            }

            $guzzle = new Client($guzzle_cfg);
            $encrypter = new Encrypter(config('mm2.catalog_encryption_key'), 'AES-256-CBC');

            return new CatalogSynchronization($guzzle, $encrypter);
        });
    }

    public function provides()
    {
        return [CatalogSynchronization::class];
    }
}
