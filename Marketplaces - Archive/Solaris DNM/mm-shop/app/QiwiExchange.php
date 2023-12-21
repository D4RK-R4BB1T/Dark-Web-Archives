<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * App\QiwiExchange
 *
 * @property int $id
 * @property int $shop_id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string $api_url
 * @property string $api_key
 * @property float $btc_rub_rate
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereApiKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereApiUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereBtcRubRate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereUserId($value)
 * @mixin \Eloquent
 * @property bool $active
 * @property string $last_response
 * @property string $last_response_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\QiwiExchangeRequest[] $exchangeRequests
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereLastResponse($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereLastResponseAt($value)
 * @property int $reserve_time
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereReserveTime($value)
 * @property float $min_amount
 * @property float $max_amount
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereMaxAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereMinAmount($value)
 * @property bool $trusted
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchange whereTrusted($value)
 */
class QiwiExchange extends Model
{
    protected $table = 'qiwi_exchanges';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'user_id', 'title', 'description', 'api_url', 'api_key', 'btc_rub_rate', 'reserve_time',
        'min_amount', 'max_amount',
        'active', 'trusted'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_response_at' => 'datetime'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|QiwiExchangeRequest[]
     */
    public function exchangeRequests()
    {
        return $this->hasMany('App\QiwiExchangeRequest', 'qiwi_exchange_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function convertRubles($amount, $from, $to)
    {
        if (!in_array($from, [BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_RUB]))
        {
            throw new \InvalidArgumentException('From can be only BTC or RUB');
        }

        if (!in_array($to, [BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_RUB]))
        {
            throw new \InvalidArgumentException('From can be only BTC or RUB');
        }

        $fromRate = $from == BitcoinUtils::CURRENCY_BTC ? 1 : $this->btc_rub_rate;
        $toRate = $to === BitcoinUtils::CURRENCY_BTC ? 1 : $this->btc_rub_rate;

        return BitcoinUtils::convert($amount, $from, $to, $fromRate, $toRate);
    }

    /**
     * @return Wallet
     */
    public function exchangeWallet()
    {
        return $this->user->primaryWallet();
    }
}
