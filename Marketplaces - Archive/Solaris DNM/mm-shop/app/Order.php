<?php

namespace App;

use App\MessengerModels\Thread;
use App\Packages\PriceModifier\GroupPriceModifier;
use App\Packages\PriceModifier\ReferralPriceModifier;
use App\Packages\Stub;
use App\Packages\Utils\BitcoinUtils;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * App\Order
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $shop_id
 * @property integer $position_id
 * @property integer $review_id
 * @property integer $good_id
 * @property string $good_title
 * @property float $package_amount
 * @property string $package_measure
 * @property float $package_price
 * @property string $package_currency
 * @property float $package_price_btc
 * @property boolean $package_preorder
 * @property integer $package_preorder_time
 * @property string $status
 * @property boolean $status_was_problem
 * @property boolean $courier_fined
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePositionId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereReviewId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGoodTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackageAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackageMeasure($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackagePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackageCurrency($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackagePriceBtc($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackagePreorder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackagePreorderTime($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereStatusWasProblem($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property boolean $guarantee
 * @property-read \App\User $user
 * @property-read \App\Shop $shop
 * @property-read \App\GoodsPosition $position
 * @property-read \App\Good $good
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGuarantee($value)
 * @property string $comment
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereComment($value)
 * @property integer $city_id
 * @property string $good_image_url
 * @property-read \App\City $city
 * @property-read \App\GoodsReview $review
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereCityId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGoodImageUrl($value)
 * @property integer $package_id
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePackageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order applySearchFilters($request)
 * @method static \Illuminate\Database\Query\Builder|\App\Order filterStatus($status)
 * @property-read \App\MessengerModels\Thread $thread
 * @method static \Illuminate\Database\Query\Builder|\App\Order filterGoodId($goodId)
 * @method static \Illuminate\Database\Query\Builder|\App\Order filterCity($cityId)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrdersService[] $services
 * @method static \Illuminate\Database\Query\Builder|\App\Order filterUser($userId)
 * @property-read \App\QiwiTransaction $qiwiTransaction
 * @property-read \App\GoodsPackage $package
 * @property float $referrer_fee
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereReferrerFee($value)
 * @property int $promocode_id
 * @property float $user_price_btc
 * @method static \Illuminate\Database\Query\Builder|\App\Order wherePromocodeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereUserPriceBtc($value)
 * @property-read \App\Promocode $promocode
 * @property float $group_percent_amount
 * @property string $group_title
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGroupPercentAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereGroupTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Order whereCourierFined($value)
 */
class Order extends Model
{
    const STATUS_PREORDER_PAID = 'preorder_paid';
    const STATUS_QIWI_RESERVED = 'qiwi_reserved';
    const STATUS_QIWI_PAID = 'qiwi_paid';
    const STATUS_PAID = 'paid';
    const STATUS_PROBLEM = 'problem';
    const STATUS_FINISHED = 'finished';

    protected $table = 'orders';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'user_id', 'city_id', 'shop_id', 'position_id', 'review_id', 'promocode_id',
        'good_id', 'good_title', 'good_image_url',
        'package_id', 'package_amount', 'package_measure', 'package_price', 'package_currency', 'package_price_btc',
        'package_preorder', 'package_preorder_time',
        'status', 'status_was_problem', 'guarantee', 'comment',
        'referrer_fee',
        'user_price_btc',
        'group_percent_amount', 'group_title'
    ];

    protected $casts = [
        'package_amount' => 'float',
        'package_price' => 'float',
        'package_price_btc' => 'float'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|User
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|City
     */
    public function city()
    {
        return $this->belongsTo('App\City', 'city_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Shop
     */
    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|GoodsPackage
     */
    public function package()
    {
        return $this->belongsTo('App\GoodsPackage', 'package_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|GoodsPosition
     */
    public function position()
    {
        return $this->belongsTo('App\GoodsPosition', 'position_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|GoodsReview
     */
    public function review()
    {
        return $this->hasOne('App\GoodsReview', 'order_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|Thread
     */
    public function thread()
    {
        return $this->hasOne('App\MessengerModels\Thread', 'order_id', 'id');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|\App\Good
     */
    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|OrdersService[]
     */
    public function services()
    {
        return $this->hasMany('App\OrdersService', 'order_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|QiwiTransaction
     */
    public function qiwiTransaction()
    {
        return $this->hasOne('App\QiwiTransaction', 'order_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|Promocode
     */
    public function promocode()
    {
        return $this->hasOne('App\Promocode', 'id', 'promocode_id');
    }

    public function scopeApplySearchFilters(\Illuminate\Database\Eloquent\Builder $orders, Request $request)
    {
        if (!empty($status = $request->get('status'))) {
            $orders = $orders->filterStatus($status);
        }

        if (!empty($goodId = $request->get('good'))) {
            $orders = $orders->filterGoodId($goodId);
        }

        if (!empty($cityId = $request->get('city'))) {
            $orders = $orders->filterCity($cityId);
        }

        if (!empty($userId = $request->get('user'))) {
            $orders = $orders->filterUser($userId);
        } elseif(!empty($userName = $request->get('username'))) {
            $orders = $orders->filterUserName($userName);
        }

        return $orders;
    }

    public function scopeFilterStatus($query, $status)
    {
        if ($status === 'active') {
            return $query->where('status', '!=', Order::STATUS_FINISHED);
        }
        return $query->where('status', $status);
    }

    public function scopeFilterGoodId($query, $goodId)
    {
        return $query->where('good_id', $goodId);
    }

    public function scopeFilterCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeFilterUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFilterUserName($query, $username)
    {
        $userIds = User::select(['id'])
            ->where('username', 'like', '%'.$username.'%')
            ->limit(250)
            ->pluck('id')
            ->filter()
            ->toArray();

        return $query->whereIn('user_id', $userIds);
    }

    /**
     * @return Stub|Good
     */
    public function _stub_good()
    {
        return stub('Good', [
            'id' => $this->good_id,
            'shop_id' => $this->shop_id,
            'title' => $this->good_title,
            'image_url' => $this->good_image_url
        ]);
    }

    /**
     * @param bool $includeModifiers Show price with referrer fee, or real price otherwise
     * @return Stub|GoodsPackage
     */
    public function _stub_package($includeModifiers = false)
    {
        if ($includeModifiers) {
            $price = $this->package_price;
            $price = GroupPriceModifier::apply($price, $this->package_currency, $this->group_percent_amount);
            if (!empty($this->referrer_fee)) {
                $price = ReferralPriceModifier::applyFee($price, $this->package_currency, $this->referrer_fee);
            }
        } else {
            $price = $this->package_price;
        }
        return stub('GoodsPackage', [
            'id' => $this->package_id,
            'city_id' => $this->city_id,
            'amount' => $this->package_amount,
            'measure' => $this->package_measure,
            'price' => $price,
            'currency' => $this->package_currency,
            'preorder' => $this->package_preorder,
            'preorder_time' => $this->package_preorder_time
        ]);
    }

    /**
     * @return Stub|UserGroup
     */
    public function _stub_group()
    {
        return stub('UserGroup', [
            'id' => 0,
            'title' => $this->group_title,
            'percent_amount' => $this->group_percent_amount
        ]);
    }


    /**
     * @return PaidService[]
     */
    public function _stub_services()
    {
        $result = [];
        foreach ($this->services as $service) {
            $result[] = stub('PaidService', [
                'title' => $service->title,
                'price' => $service->price,
                'currency' => $service->currency
            ]);
        }

        return $result;
    }

    /**
     * @param string $currency
     * @return float|string
     */
    public function getOverallPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        $result = $this->user_price_btc;
        return BitcoinUtils::convert($result, BitcoinUtils::CURRENCY_BTC, $currency);
    }

    /**
     * Returns time in seconds before address should be hidden.
     *
     * @return int
     */
    public function getQuestRemainingTime()
    {
        return config('mm2.order_quest_time') * 3600 - Carbon::now()->diffInSeconds($this->position->updated_at, TRUE);
    }

    /**
     * Returns time before address should be hidden in human-readable format.
     *
     * @return string
     */
    public function getHumanQuestRemainingTime()
    {
        $diff = $this->getQuestRemainingTime() / 60;
        $hours = floor($diff / 60);
        $minutes = $diff % 60;
        return sprintf('%d %s %d %s',
            $hours, plural($hours, ['час', 'часа', 'часов']),
            $minutes, plural($minutes, ['минуту', 'минуты', 'минут'])
        );
    }

    /**
     * @return int
     */
    public function getPreorderRemainingTime()
    {
        $preorderTime = $this->package_preorder_time * 3600;
        return $preorderTime - Carbon::now()->diffInSeconds($this->created_at, TRUE);
    }

    /**
     * @return string
     */
    public function getHumanPreorderRemainingTime()
    {
        $secInMin = 60;
        $secInHour = $secInMin * 60;
        $secInDay = $secInHour * 24;

        $seconds = $this->getPreorderRemainingTime();

        $days = floor($seconds / $secInDay);
        $hourSeconds = $seconds % $secInDay;
        $hours = floor($hourSeconds / $secInHour);
        $minutes = floor($hourSeconds % $secInHour / $secInMin);

        $sections = [
            ['value' => $days, 'plural' => ['день', 'дня', 'дней']],
            ['value' => $hours, 'plural' => ['час', 'часа', 'часов']],
            ['value' => $minutes, 'plural' => ['минуту', 'минуты', 'минут']]
        ];

        $parts = [];
        foreach ($sections as $section) {
            if ($section['value'] > 0) {
                $parts[] = $section['value'] . ' ' . plural($hours, $section['plural']);
            }
        }

        return implode(', ', $parts);
    }

    /**
     * @return Carbon
     */
    public function getReservationEndTime()
    {
        return $this->created_at->addMinutes(config('mm2.order_reserve_time'));
    }

    /**
     * @return int
     */
    public function getReservationRemainingTime()
    {
        return config('mm2.order_reserve_time') * 60 - Carbon::now()->diffInSeconds($this->created_at, TRUE);
    }

    /**
     * @return string
     */
    public function getHumanReservationRemainingTime()
    {
        $diff = $this->getReservationRemainingTime() / 60;
        $hours = floor($diff / 60);
        $minutes = $diff % 60;
        return sprintf('%d %s %d %s',
            $hours, plural($hours, ['час', 'часа', 'часов']),
            $minutes, plural($minutes, ['минуту', 'минуты', 'минут'])
        );
    }

    public function hasPriceModifiers()
    {
        return !empty($this->referrer_fee) || !empty($this->group_percent_amount);
    }
    /**
     * @return string
     */
    public function getHumanStatus()
    {
        switch ($this->status)
        {
            case Order::STATUS_PAID:
                return 'Ожидает отзыва';

            case Order::STATUS_PREORDER_PAID:
                return 'Ожидает доставки';

            case Order::STATUS_FINISHED:
                return 'Завершен';

            case Order::STATUS_PROBLEM:
                return 'Проблема';

            case Order::STATUS_QIWI_RESERVED:
                return 'Зарезервирован (QIWI)';

            case Order::STATUS_QIWI_PAID:
                return 'Проверяется';

            default:
                return 'Неизвестно';
        }
    }
}