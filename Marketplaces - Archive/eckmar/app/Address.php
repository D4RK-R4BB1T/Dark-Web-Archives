<?php

namespace App;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


/**
 * Represents the instance of the coin address for any user
 * Can be any Coin that is supported in the config
 *
 * Class Address
 * @package App
 */
class Address extends Model
{
    use Uuids;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;


    public static function label($coinName)
    {
        if($coinName=='btcm')
            return 'btc pubkey';
        return $coinName;
    }


    /**
     * Relationship with the user
     */
    public function user()
    {
        return $this -> belongsTo(\App\User::class, 'user_id', 'id');
    }

    /**
     * Fix for Bitcoin multisig
     *
     * @param $coin
     * @return string
     */
    public function getCoinAttribute($coin)
    {
        if($coin=='btcm')
            return 'btc pubkey';
        return $coin;
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

}
