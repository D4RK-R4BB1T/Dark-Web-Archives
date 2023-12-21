<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * App\Promocode
 *
 * @property int $id
 * @property int $employee_id
 * @property string $code
 * @property string $discount_mode
 * @property float $percent_amount
 * @property float $price_amount
 * @property string $price_currency
 * @property string $mode
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereDiscountMode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereExpiresAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereMode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode wherePercentAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode wherePriceAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode wherePriceCurrency($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property bool $is_active
 * @method static \Illuminate\Database\Query\Builder|\App\Promocode whereIsActive($value)
 * @property-read \App\Employee $employee
 */
class Promocode extends Model
{
    const DISCOUNT_MODE_PERCENT = 'percent';
    const DISCOUNT_MODE_PRICE = 'price';
    const MODE_SINGLE_USE = 'single_use';
    const MODE_UNTIL_DATE = 'until_date';

    protected $table = 'promocodes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_id',
        'code', 'discount_mode',
        'percent_amount', 'price_amount', 'price_currency',
        'is_active', 'mode', 'expires_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime'
    ];


    // - Dependencies

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|\App\Employee
     */
    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    // - Public

    public function getHumanDiscount($currency = null)
    {
        switch ($this->discount_mode) {
            case Promocode::DISCOUNT_MODE_PRICE:
                $currency = $currency ?: $this->price_currency;
                return human_price($this->price_amount, $currency);
            case Promocode::DISCOUNT_MODE_PERCENT:
                return trim_zeros(number_format($this->percent_amount, 2)). ' %';
            default:
                return null;
        }
    }

    public function isActive()
    {
        $isActive = $this->is_active;
        if ($this->expires_at) {
            return $isActive && $this->expires_at->startOfDay()->greaterThanOrEqualTo(Carbon::now());
        } else {
            return $isActive;
        }
    }

    public function markUsedIfNeeded()
    {
        if (!$this->isActive()) {
            return;
        }

        if ($this->mode == self::MODE_SINGLE_USE) {
            $this->is_active = false;
            $this->save();
        }
    }

    public static function generate()
    {
        $appId = mb_strtoupper(@mb_substr(config('mm2.application_id'), 0, 3));
        $payload = Str::random(16);
        return $appId . '-' . $payload;
    }
}
