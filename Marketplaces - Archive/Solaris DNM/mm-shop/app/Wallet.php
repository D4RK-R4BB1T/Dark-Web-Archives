<?php

namespace App;

use App\Events\ActualBalanceChanged;
use App\Exceptions\BitcoinException;
use App\Packages\Utils\BitcoinUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Wallet
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $user_id
 * @property string $title
 * @property float $balance
 * @property string $type
 * @property string $wallet
 * @property string $wallet_key
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Shop $shop
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereBalance($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereWallet($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereWalletKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Shop $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Transaction[] $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property string $deleted_at
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereDeletedAt($value)
 * @property string $segwit_wallet
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereSegwitWallet($value)
 * @property float $reserved_balance
 * @method static \Illuminate\Database\Query\Builder|\App\Wallet whereReservedBalance($value)
 */
class Wallet extends Model
{
    use SoftDeletes;

    const TYPE_PRIMARY = 'primary';
    const TYPE_ADDITIONAL = 'additional';

    protected $table = 'wallets';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id', 'shop_id', 'title', 'type', 'wallet', 'balance', 'wallet_key', 'segwit_wallet'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Shop
     */
    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|User
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Transaction[]
     */
    public function transactions()
    {
        return $this->hasMany('App\Transaction', 'wallet_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Operation[]
     */
    public function operations()
    {
        return $this->hasMany('App\Operation', 'wallet_id', 'id');
    }

    /**
     * Return user real balance.
     *
     * @param string $currency
     * @return float
     */
    public function getRealBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return BitcoinUtils::convert($this->balance - $this->reserved_balance, BitcoinUtils::CURRENCY_BTC, $currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanRealBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getRealBalance($currency), $currency);
    }

    /**
     * Returns balance from unconfirmed transactions.
     *
     * @param string $currency
     * @return float
     */
    public function getPendingBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        $amount = $this->transactions()
            ->where('confirmations', '<=', config('mm2.confirmations_amount'))
            ->where('handled', false)
            ->sum('amount');

        return BitcoinUtils::convert($amount, BitcoinUtils::CURRENCY_BTC, $currency);
    }

    /**
     * Returns balance from real and pending balance.
     *
     * @param string $currency
     * @return float
     */
    public function getExpectedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->getRealBalance($currency) + $this->getPendingBalance($currency);
    }

    /**
     * Returns balance which are reserved on wallet.
     *
     * @param string $currency
     * @return float
     */
    public function getReservedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return BitcoinUtils::convert($this->reserved_balance, BitcoinUtils::CURRENCY_BTC, $currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanReservedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getReservedBalance($currency), $currency);
    }

    /**
     * Changes balance.
     *
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $fields
     * @return Operation
     * @throws BitcoinException
     */
    public function balanceOperation($amount, $currency = BitcoinUtils::CURRENCY_BTC, $description = '', $fields = [])
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            throw new BitcoinException('Failed to perform balance operation: payments are marked as disabled.');
        }

        $amount = BitcoinUtils::convert($amount, $currency, BitcoinUtils::CURRENCY_BTC);

        $operation = \DB::transaction(function () use ($amount, $description, $fields) {
            $this->lockForUpdate();

            $operation = $this->operations()->create(array_merge($fields, [
                'amount' => $amount,
                'description' => $description
            ]));

            $this->increment('balance', $amount);

            return $operation;
        });

        event(new ActualBalanceChanged($this));
        return $operation;
    }

    public function reserveOperation($amount, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            throw new BitcoinException('Failed to perform balance operation: payments are marked as disabled.');
        }

        $amount = BitcoinUtils::convert($amount, $currency, BitcoinUtils::CURRENCY_BTC);

        $operation = \DB::transaction(function () use ($amount) {
            $this->lockForUpdate();

            $this->increment('reserved_balance', $amount);
            $this->save();
        });

        return $operation;
    }

    /**
     * Check if wallet has enough balance.
     * @param $amount
     * @param string $currency
     * @return bool
     */
    public function haveEnoughBalance($amount, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return false;
        }

        if ($this->getRealBalance($currency) < $amount) {
            return false;
        }

        return true;
    }
}