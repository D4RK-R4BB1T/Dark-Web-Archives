<?php

namespace App\Console\Commands;

use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;
use Illuminate\Console\Command;


class UpdateBitcoinRates extends Command
{
    /**
     * @var BitcoinLogger
     */
    protected $log;

    /**
     * @var Client
     */
    protected $client;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:update_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Bitcoin rates.';

    /**
     * Create a new command instance.
     *
     * @param Client $client
     * @param BitcoinLogger $log
     */
    public function __construct(Client $client, BitcoinLogger $log)
    {
        $this->log = $log;
        $this->client = $client;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($attempt = 0)
    {
        $attempt++;
        $this->log->info('Updating Bitcoin rates (attempt #' . $attempt . ')');

        try {
            $request = $this->client->get('https://blockchain.info/ticker', [
                'proxy' => [
                    //'http' => sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port')),
                    //'https' => sprintf('socks5h://%s:%d', config('mm2.tord_host'), config('mm2.tord_port'))
                ],

                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_1,
                    CURLOPT_PROXYTYPE => 7 // http://blog.daviddemartini.com/archives/6273
                ]
            ]);

            $body = $request->getBody();
            $response = json_decode($body);
        } catch (\Exception $e) {
            $this->log->warn('Failed to make HTTP request: ' . $e);

            if ($attempt < 3)
            {
                sleep(10);
                return $this->handle($attempt);
            }

            $response = (object)[];
        }

        try {
            if (!property_exists($response, 'USD')) throw new \AssertionError('Property USD not exists.');
            if (!property_exists($response, 'RUB')) throw new \AssertionError('Property RUB not exists.');
            if ($response->USD->buy === 3299.98) {
                throw new \AssertionError('Blockchain gave us shitty value...');
            }
        } catch (\AssertionError $e) {
            $this->log->warn('Rates are not updated: ' . $e);

            if ($attempt < 3)
            {
                sleep(10);
                return $this->handle($attempt);
            }

            if (!BitcoinUtils::isPaymentsEnabled()) {
                $this->log->warn('!!!! Payments are marked as disabled.');
            }
            $this->log->info('Finished updating Bitcoin rates.');
            return null;
        }
        
        $expiresAt = Carbon::now()->addMinutes(config('mm2.rates_cache_expires_at'));
        \Cache::put('rates_usd', $response->USD->buy, $expiresAt);
        \Cache::put('rates_rub', $response->RUB->buy, $expiresAt);
        $this->log->info('Finished updating Bitcoin rates.', ['usd' => $response->USD->buy, 'rub' => $response->RUB->buy]);
    }
}
