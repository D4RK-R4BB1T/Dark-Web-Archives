<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * App\AccountingLot
 *
 * @property int $id
 * @property int $shop_id
 * @property int $good_id
 * @property float $amount
 * @property float $unused_amount
 * @property float $available_amount
 * @property float $price
 * @property string $currency
 * @property string $note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereAvailableAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereCurrency($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereNote($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereUnusedAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AccountingDistribution[] $distributions
 * @property-read \App\Good $good
 * @property string $measure
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot whereMeasure($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingLot applySearchFilters(\Illuminate\Http\Request $request)
 */
class AccountingLot extends Model
{
    protected $table = 'accounting_lots';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'good_id', 'amount', 'unused_amount', 'available_amount',
        'measure', 'price', 'currency', 'note'
    ];

    public function delete()
    {
        $this->distributions()->delete();
        return parent::delete();
    }

    public function scopeApplySearchFilters(\Illuminate\Database\Eloquent\Builder $lots, Request $request)
    {
        if (!empty($goodId = $request->get('good'))) {
            $lots = $lots->where('good_id', $goodId);
        }

        if ($request->get('show', 'available') === 'available') {
            $lots = $lots->where('available_amount', '>', 0);
        }

        return $lots;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Good
     */
    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|AccountingDistribution[]
     */
    public function distributions()
    {
        return $this->hasMany('App\AccountingDistribution', 'lot_id', 'id');
    }

    /**
     * @param null $currency
     * @return float|int|string
     */
    public function getTotalPrice($currency = null)
    {
        $currency = $currency ?: $this->currency;
        return BitcoinUtils::convert($this->price, $this->currency, $currency);
    }
    /**
     * @param null $currency
     * @return string
     */
    public function getHumanTotalPrice($currency = null)
    {
        $currency = $currency ?: $this->currency;
        return human_price($this->getTotalPrice($currency), $currency);
    }

    /**
     * @return float
     */
    public function getTotalWeight()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getHumanTotalWeight()
    {
        return Formatters::getHumanWeight($this->getTotalWeight(), $this->measure);
    }

    /**
     * @return float
     */
    public function getAvailableWeight()
    {
        return $this->available_amount;
    }

    /**
     * @return string
     */
    public function getHumanAvailableWeight()
    {
        return Formatters::getHumanWeight($this->getAvailableWeight(), $this->measure);
    }

    /**
     * @return float
     */
    public function getUnusedWeight()
    {
        return $this->unused_amount;
    }
    /**
     * @return string
     */
    public function getHumanUnusedWeight()
    {
        return Formatters::getHumanWeight($this->getUnusedWeight(), $this->measure);
    }
}
