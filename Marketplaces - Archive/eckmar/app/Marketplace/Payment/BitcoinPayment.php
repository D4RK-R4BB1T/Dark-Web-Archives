<?php

namespace App\Marketplace\Payment;


use App\Marketplace\Utility\RPCWrapper;
use App\Marketplace\Utility\BitcoinConverter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BitcoinPayment implements Coin
{

    /**
     * RPCWrapper Server instance
     *
     * @var
     */
    protected $bitcoind;

    /**
     * RPCWrapper constructor.
     */
    public function __construct()
    {
        $this -> bitcoind = new RPCWrapper(config('coins.bitcoin.username'),
            config('coins.bitcoin.password'),
            config('coins.bitcoin.host'),
            config('coins.bitcoin.port'));
    }

    /**
     * Generate new address with optional btc user parameter
     *
     * @param array $params
     * @return string
     * @throws \Exception
     */
    function generateAddress(array $params = []): string
    {
        // only if the btc user is set then call with parameter
        if(array_key_exists('btc_user', $params))
            $address = $this -> bitcoind -> getnewaddress($params['btc_user']);
        else
            $address = $this -> bitcoind -> getnewaddress();

        // Error in bitcoin
        if($this -> bitcoind -> error)

            throw new \Exception($this -> bitcoind -> error);

        return $address;
    }


    /**
     * Returns the total received balance of the account
     *
     * @param array $params
     * @return float
     * @throws \Exception
     */
    function getBalance(array $params = []): float
    {
        // first check by address
        if(array_key_exists('address', $params))
            $accountBalance = $this -> bitcoind -> getreceivedbyaddress($params['address'], (int)config('marketplace.bitcoin.minconfirmations'));
//        else if(array_key_exists('account', $params))
//            // fetch the balance of the account if this parameter is set
//            $accountBalance = $this -> bitcoind -> getbalance($params['account'], (int)config('marketplace.bitcoin.minconfirmations'));
        else
            throw new \Exception('You havent specified any parameter');

        if($this -> bitcoind -> error)
            throw new \Exception($this -> bitcoind -> error);

        return $accountBalance;
    }

    /**
     * Calls a procedure to send from address to address some amount
     *
     * @param string $fromAddress
     * @param string $toAddress
     * @param float $amount
     * @throws \Exception
     */
    function sendToAddress(string $toAddress, float $amount)
    {
        // call bitcoind procedure
        $this -> bitcoind -> sendtoaddress($toAddress, $amount);

        if($this -> bitcoind -> error)
            throw new \Exception("Sending to $toAddress amount $amount \n" . $this -> bitcoind -> error);

    }

    /**
     * Send to array of addresses
     *
     * @param string $fromAccount
     * @param array $addressesAmounts
     * @throws \Exception
     */
    function sendToMany(array $addressesAmounts)
    {
        // send to many addresses
//        foreach ($addressesAmounts as $address => $amount){
//            $this -> bitcoind -> sendtoaddress($address, $amount);
//        }

        $this->bitcoind->sendmany("", $addressesAmounts, (int)config('marketplace.bitcoin.minconfirmations'));


        if ($this->bitcoind->error) {
            $errorString = "";
            foreach ($addressesAmounts as $address => $amount){
                $errorString .= "To $address : $amount \n";
            }
            throw new \Exception( $this->bitcoind->error . "\nSending to: $errorString" );
        }
    }
    /**
     * Convert USD to equivalent BTC amount, rounded on 8 decimals
     *
     * @param $usd
     * @return float
     */
    function usdToCoin($usd): float
    {
        return round( BitcoinConverter::usdToBtc($usd), 8, PHP_ROUND_HALF_DOWN );
    }


    /**
     * Returns the string label of the coin
     *
     * @return string
     */
    function coinLabel(): string
    {
        return 'btc';
    }


}