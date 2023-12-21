<?php

namespace App;

use App\Packages\EncryptableTrait;
use App\Packages\Utils\BitcoinUtils;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Builder;

/**
 * App\QiwiWallet
 *
 * @property integer $id
 * @property integer $shop_id
 * @property string $login
 * @property string $password
 * @property float $balance
 * @property float $reserved_balance
 * @property float $limit
 * @property string $status
 * @property \Carbon\Carbon $last_checked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Shop $shop
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereBalance($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereReservedBalance($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereLimit($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereLastCheckedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\QiwiTransaction[] $qiwiTransactions
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet availableForPackage($package)
 * @property float $daily_limit
 * @property float $current_day_income
 * @property float $current_month_income
 * @property float $monthly_limit
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereDailyLimit($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereCurrentDayIncome($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereCurrentMonthIncome($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiWallet whereMonthlyLimit($value)
 */
class QiwiWallet extends Model
{
    use EncryptableTrait;

    const STATUS_ACTIVE = 'active';
    const STATUS_DEAD = 'dead';

    protected $table = 'qiwi_wallets';
    protected $primaryKey = 'id';

    protected $casts = [
        'last_checked_at' => 'datetime'
    ];

    protected $fillable = [
        'shop_id', 'login', 'password',
        'daily_limit', 'monthly_limit', 'current_day_income', 'current_month_income',
        'status', 'last_checked_at'
    ];

    protected $hidden = [
        'password'
    ];

    protected $encryptable = [
        'password'
    ];

    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    public function qiwiTransactions()
    {
        return $this->hasMany('App\QiwiTransaction', 'qiwi_wallet_id', 'id');
    }

    public function getHumanStatus()
    {
        switch ($this->status) {
            case QiwiWallet::STATUS_ACTIVE:
                $notChecked = $this->last_checked_at < Carbon::now()->addMinutes(-config('mm2.qiwi_balance_expires_at'));
                $dailyLimitExceeded = $this->daily_limit > 0 && ($this->current_day_income + $this->reserved_balance) > $this->daily_limit;
                $monthlyLimitExceeded = $this->monthly_limit > 0 && ($this->current_month_income + $this->reserved_balance) > $this->monthly_limit;
                if ($notChecked) {
                    return '<span class="text-muted hint--top" aria-label="Кошелек давно не проверялся">Пауза <i class="glyphicon glyphicon-question-sign"></i></span>';
                }

                if ($dailyLimitExceeded) {
                    return '<span class="text-muted hint--top" aria-label="Дневной лимит кошелька превышен">Пауза <i class="glyphicon glyphicon-question-sign"></i></span>';
                }

                if ($monthlyLimitExceeded) {
                    return '<span class="text-muted hint--top" aria-label="Месячный лимит кошелька превышен">Пауза <i class="glyphicon glyphicon-question-sign"></i></span>';
                }

                return 'Работает';

            case QiwiWallet::STATUS_DEAD:
                return 'Невалидный';

            default:
                return 'Неизвестно';
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param GoodsPackage $package
     * @return mixed
     */
    public function scopeAvailableForPackage($query, $package)
    {
        return $query->where('status', QiwiWallet::STATUS_ACTIVE)
            ->where('last_checked_at', '>=', Carbon::now()->addMinutes(-config('mm2.qiwi_balance_expires_at')))
            ->where(function($q) use ($package) {
                $q->where('daily_limit', 0)->orWhereRaw(
                    "`current_day_income` + `reserved_balance` + '{$package->getQiwiPrice(BitcoinUtils::CURRENCY_RUB)}' <= `daily_limit`"
                );
            })
            ->where(function($q) use ($package) {
                $q->where('monthly_limit', 0)->orWhereRaw(
                    "`current_month_income` + `reserved_balance` + '{$package->getQiwiPrice(BitcoinUtils::CURRENCY_RUB)}' <= `monthly_limit`"
                );
            });
    }
}