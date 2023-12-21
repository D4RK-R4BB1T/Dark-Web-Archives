<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use Auth;
use Illuminate\Database\Eloquent\Model;

/**
 * App\ExternalExchange
 *
 * @property int $id
 * @property string $payment_id
 * @property int $user_id
 * @property float $amount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange wherePaymentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ExternalExchange whereUserId($value)
 * @mixin \Eloquent
 */
class ExternalExchange extends Model
{
    protected $table = 'external_exchanges';
    protected $primaryKey = 'id';

    protected $fillable = [
        'payment_id', 'user_id', 'amount',
    ];

    public function getPrice($currency = null)
    {
        $to = $currency ?: BitcoinUtils::CURRENCY_BTC;
        return BitcoinUtils::convert($this->amount, BitcoinUtils::CURRENCY_BTC, $to);
    }

    public function getHumanPrice($currency = null): string
    {
        $currency = $currency ?: BitcoinUtils::CURRENCY_BTC;
        return human_price($this->getPrice($currency), $currency);
    }
}
