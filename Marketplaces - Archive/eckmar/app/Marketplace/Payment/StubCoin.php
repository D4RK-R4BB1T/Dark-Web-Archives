<?php


namespace App\Marketplace\Payment;


use App\Marketplace\Utility\RPCWrapper;
use App\Marketplace\Utility\BitcoinConverter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class that simulates interface of the coin
 */
class StubCoin implements Coin
{
    function generateAddress(array $parameters = []) :string
    {
        return 'addressStub#' . rand(0,999999);
    }

    function getBalance(array $parameters = []) : float
    {
        return 101;
    }

    function sendFrom(string $fromAccount, string $toAddress, float $amount){
        Log::info("From accout " . $fromAccount . " to address " . $toAddress . " to amount " . $amount);
    }

    function sendToMany(array $addressesAmounts){
        
        foreach($addressesAmounts as $adr){
            Log::info("STB Transaction to address $adr");
        }
    }

    function usdToCoin( $usd ) : float {
        return $usd;
    }

    function coinLabel() : string {
        return 'stb';
    }

    function sendToAddress(string $toAddress, float $amount) {
        Log::info("Sending to address " . $toAddress . " to amount " . $amount);
    }


}


