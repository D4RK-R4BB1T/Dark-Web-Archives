<?php

namespace App\Console\Commands;

use App\AdvStats;
use App\AdvStatsCache;
use App\Packages\CatalogSync\SynchronizationException;
use App\SyncState;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class AdvStatsDropCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:advstats_drop_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop AdvStats cache to catalog';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $guzzle_cfg = [
            'timeout' => 90,
            'http_errors' => false,
            'headers' => [
                'X-Guard-Bypass' => true,
                'Accept' => 'application/json',
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

        $client = new Client($guzzle_cfg);
        $encrypter = new Encrypter(config('mm2.catalog_encryption_key'), 'AES-256-CBC');
        $syncState = SyncState::getDefaultSyncState();

        $url = config('mm2.local_sync_url')
            ? 'http://' . config('mm2.local_sync_url') . '/api/advstats'
            : 'http://' . $syncState->sync_server . '/api/advstats';

        $cache = AdvStatsCache::get();
        $data = $encrypter->encrypt(json_encode($cache));

        try {
            $response = $client->post($url, ['form_params' => ['data' => $data]]);
            AdvStatsCache::flush();
        } catch (Exception $e) {
            $this->error('Advstats synchronization is unsuccessful (' . get_class($e) . '): ' . $e->getMessage());
            $this->error($e->getFile() . ' at line ' . $e->getLine());
        }
    }
}
