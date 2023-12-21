<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\PaidService
 *
 * @property integer $id
 * @property integer $shop_id
 * @property string $title
 * @property float $price
 * @property string $currency
 * @property-read \App\Shop $shop
 * @method static \Illuminate\Database\Query\Builder|\App\ShopsService whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopsService whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopsService whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopsService wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopsService whereCurrency($value)
 * @mixin \Eloquent
 */
class PaidService extends Model
{
    protected $table = 'paid_services';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'title', 'price', 'currency'
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Shop
     */
    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    public function delete()
    {
        GoodsPackagesService::whereServiceId($this->id)->delete();
        return parent::delete();
    }

    /**
     * @param string|null $currency
     * @return float
     */
    public function getPrice($currency = null)
    {
        return BitcoinUtils::convert($this->price, $this->currency, $currency ?: $this->currency);
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public function getHumanPrice($currency = null)
    {
        $currency = $currency ?: $this->currency;
        return human_price($this->getPrice($currency), $currency);
    }
}
