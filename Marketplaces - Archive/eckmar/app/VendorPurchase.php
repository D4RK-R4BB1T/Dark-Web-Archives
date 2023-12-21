<?php

namespace App;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


/**
 * Address of the coin
 *
 * Class DepositAddress
 * @package App
 */
class VendorPurchase extends Model
{
    use Uuids;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    /**
     * Relationship with the user
     */
    public function user()
    {
        return $this -> belongsTo(\App\User::class);
    }


    /**
     * Returns the balance of the deposit address
     *
     * @return float
     * @throws \Exception
     */
    public function getBalance()
    {
        $coinServiceClass = config('coins.coin_list.'. $this -> coin);
        $coinService = new $coinServiceClass();

        // Send request for balance of the address
        return  $coinService->getBalance(['address' => $this -> address]);
    }

    /**
     * Return formatted number of balance of the address
     *
     * @return string
     */
    public function getBalanceAttribute()
    {
        try {
            $balanceAddress = $this -> getBalance();
        }
        catch (\Exception $e){
            // inform admin
            Log::warning("Request for balance of the '$this->address', coin '$this->coin' is failed beacuse:");
            Log::warning($e -> getMessage());
            return 'Unavailable';
        }
        return number_format( $balanceAddress,8);
    }


    /**
     * Returns how much need to be paid to the address
     *
     * @return float
     */
    public function getTargetBalance()
    {
        $coinServiceClass = config('coins.coin_list.'. $this -> coin);
        $coinService = new $coinServiceClass();

        $vendorFeeUsd = config('marketplace.vendor_fee');
        return  $coinService -> usdToCoin($vendorFeeUsd);
    }

    /**
     * Returns formated number how much needs to be paid
     *
     * @return string
     */
    public function getTargetAttribute()
    {
        return number_format($this -> getTargetBalance(), 8);
    }

    /**
     *  Returns true if there is enough funds on this coin address
     *
     *  @return bool
     */
    public function isEnough()
    {
        try{
            $coinServiceClass = config('coins.coin_list.'. $this -> coin);
            $coinService = new $coinServiceClass();

            $vendorFeeUsd = config('marketplace.vendor_fee');
            $vendorFeeCoin = $coinService -> usdToCoin($vendorFeeUsd);

            // returns true if the balance is bigger than it should be paid
            return $this -> getBalance() >= $vendorFeeCoin;
        }
        catch (\Exception $e){
            Log::warning($e -> getMessage());
            return false;
        }

    }


    /**
     * String how long was passed since adding address
     *
     * @return string
     */
    public function getAddedAgoAttribute()
    {
        return Carbon::parse($this -> created_at) -> diffForHumans();
    }


    /**
     * Unload all funds to market address
     *
     * @return bool
     */
    public function unloadFunds()
    {
        try{
            $coinServiceClass = config('coins.coin_list.'. $this -> coin);
            $coinService = new $coinServiceClass();

            $marketCoinAddresses = config('coins.market_addresses.' . $this->coin);
            // pick one from the array
            $marketCoinAddress = $marketCoinAddresses[array_rand($marketCoinAddresses)];

            // send to market address
            $coinService->sendToAddress($marketCoinAddress, $this->getBalance());

        }
        catch (\Exception $e){
            Log::warning($e);
            return false;
        }
    }
}
