<?php
/**
 * File: BitcoinUtils.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Packages\Utils;


use App\Exceptions\BitcoinException;
use App\Packages\Loggers\BitcoinLogger;
use Carbon\Carbon;
use Nbobtc\Http\Client;

class BitcoinUtils
{
    /**
     * @var Client
     */
    public $client;
    /**
     * @var BitcoinLogger
     */
    public $log;

    #region Work with rates & currencies
    const CURRENCY_USD = 'usd';
    const CURRENCY_RUB = 'rub';
    const CURRENCY_BTC = 'btc';

    /**
     * Should return false if something is wrong with payments.
     * You can add future payments checks here.
     *
     * @return bool
     */
    public static function isPaymentsEnabled()
    {
        try {
            if (self::getRate(self::CURRENCY_RUB) === 0) throw new \AssertionError('RUB rate is 0.');
            if (self::getRate(self::CURRENCY_USD) === 0) throw new \AssertionError('USD rate is 0.');
        } catch (\AssertionError $e) {
            return FALSE;
        }

        return TRUE;
    }

    public static function getRate($currency)
    {
        if ($currency === self::CURRENCY_BTC) {
            return 1;
        }
        return \Cache::get('rates_' . $currency, 0);
    }
    
    public static function convert($amount, $from, $to, $fromRate = null, $toRate = null)
    {
        if (!self::isPaymentsEnabled()) {
            return '-';
        }

        if ($from === $to) {
            return $amount;
        }

        if ($fromRate == null) {
            $fromRate = self::getRate($from);
        }

        if ($toRate == null) {
            $toRate = self::getRate($to);
        }

        return ($toRate / $fromRate) * $amount;
    }

    /**
     * Convers $amount USD to BTC.
     * @param float $amount
     * @return float
     */
    public static function usdToBtc($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_USD, self::CURRENCY_BTC);
    }
    /**
     * Converts $amount RUB to BTC
     *
     * @param float $amount
     * @return float
     */
    public static function rubToBtc($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_RUB, self::CURRENCY_BTC);
    }

    /**
     * Converts $amount BTC to USD
     *
     * @param float $amount
     * @return float
     */
    public static function btcToUsd($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_BTC, self::CURRENCY_USD);
    }

    /**
     * Converts $amount BTC to RUB
     *
     * @param float $amount
     * @return float
     */
    public static function btcToRub($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_BTC, self::CURRENCY_RUB);
    }
    #endregion

    #region Work with Bitcoind
    public function __construct(Client $client, BitcoinLogger $log)
    {
        $this->client = $client;
        $this->log = $log;
    }

    /**
     * @param $amount
     * @return float
     */
    public static function prepareAmountToJSON($amount)
    {
        return (float)(round($amount, 8));
    }

    /**
     * Sends command to Bitcoind.
     *
     * @param \Nbobtc\Command\Command $command
     * @return mixed|\Nbobtc\Http\Message\Response|\Psr\Http\Message\ResponseInterface
     */
    public function sendCommand(\Nbobtc\Command\Command $command)
    {
        try {
            //$this->log->debug('Sending command: ' . $command->getMethod());
            $response = $this->client->sendCommand($command);
            //$this->log->debug('Response status code: ' . $response->getStatusCode());

            $body = $response->getBody()->getContents();

            if ($response->getStatusCode() !== 200) {
                throw new BitcoinException('Bitcoind ' . $command->getMethod() . ' failed: ' .
                    'invalid status code: ' . $response->getStatusCode() . ', ' .
                    'response was: ' . $body
                );
            }

            $response = json_decode($body);
            if (isset($response->error) && !is_null($response->error)) {
                throw new BitcoinException('Bitcoind ' . $command->getMethod() . ' failed: ' . $response->error);
            }

            return $response;
        } catch(\Exception $e) {
            $this->log->critical($e);
            return (object)['result' => [], 'error' => $e];
        }
    }

    #endregion
}