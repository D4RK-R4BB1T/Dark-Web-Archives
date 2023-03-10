<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * App\QiwiExchangeRequest
 *
 * @property int $id
 * @property int $qiwi_exchange_id
 * @property int $user_id
 * @property float $btc_amount
 * @property float $btc_rub_rate
 * @property string $status
 * @property string $finished_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereBtcAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereBtcRubRate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereFinishedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereQiwiExchangeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereUserId($value)
 * @mixin \Eloquent
 * @property-read \App\User $user
 * @property-read \App\QiwiExchange $qiwiExchange
 * @property bool $test_mode
 * @property-read \App\QiwiExchangeTransaction $qiwiExchangeTransaction
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereTestMode($value)
 * @property string $input
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereInput($value)
 * @property string $error_reason
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeRequest whereErrorReason($value)
 */
class QiwiExchangeRequest extends Model
{
    protected $table = 'qiwi_exchanges_requests';
    protected $primaryKey = 'id';

    protected $fillable = [
        'qiwi_exchange_id', 'user_id', 'btc_amount', 'btc_rub_rate', 'status', 'test_mode'
    ];

    protected $casts = [
        'test_mode' => 'boolean'
    ];

    const STATUS_CREATING = 'creating';
    const STATUS_RESERVED = 'reserved';
    const STATUS_PAID_REQUEST = 'paid_request';
    const STATUS_PAID = 'paid';
    const STATUS_PAID_PROBLEM = 'paid_problem';
    const STATUS_FINISHED  = 'finished';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|User
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|QiwiExchange
     */
    public function qiwiExchange()
    {
        return $this->belongsTo('App\QiwiExchange', 'qiwi_exchange_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|QiwiExchangeTransaction
     */
    public function qiwiExchangeTransaction()
    {
        return $this->hasOne('App\QiwiExchangeTransaction', 'qiwi_exchange_request_id', 'id');

    }

    public function getHumanStatus()
    {
        switch ($this->status) {
            case self::STATUS_CREATING:
                return '???????????????? ????????????';

            case self::STATUS_RESERVED:
                return '???????????????? ????????????';

            case self::STATUS_PAID:
                return '???????????????? ????????????????????';

            case self::STATUS_PAID_REQUEST:
                return '?????????????????????? ???? ????????????';

            case self::STATUS_PAID_PROBLEM:
                return '????????????????';

            case self::STATUS_FINISHED:
                return '??????????????????';

            case self::STATUS_CANCELLED:
                return '????????????????';

            default:
                return '????????????????????';
        }
    }

    public function finish()
    {
        /** @var QiwiExchange $exchange */
        $exchange = $this->qiwiExchange;
        /** @var Wallet $exchangeWallet */
        $exchangeWallet = $exchange->exchangeWallet();

        if (!$this->test_mode) {
            $exchange->exchangeWallet()->reserveOperation(-$this->btc_amount);
            $exchange->exchangeWallet()->balanceOperation(-$this->btc_amount, BitcoinUtils::CURRENCY_BTC, '????????????????: ???????????????? ???????????? #' . $this->id);
            //sleep(1);
            $this->user->balanceOperation($this->btc_amount, BitcoinUtils::CURRENCY_BTC, '????????????????: ???????????????? ???????????? #' . $this->id);

            $fee = $this->btc_amount * config('mm2.exchange_api_fee');
            $exchange->exchangeWallet()->balanceOperation(-$fee, BitcoinUtils::CURRENCY_BTC, '????????????????: ???????????????? ???? ??????????');
            Income::create([
                'wallet_id' => $exchangeWallet->id,
                'amount_usd' => btc2usd($fee),
                'amount_btc' => $fee,
                'description' => '???????????????? ???? ?????????? ' . $this->id
            ]);
        }

        $this->status = self::STATUS_FINISHED;
        $this->save();
    }

    public function forceCancel()
    {
        /** @var QiwiExchange $exchange */
        $exchange = $this->qiwiExchange;

        if (!$this->test_mode) {
            $exchange->exchangeWallet()->reserveOperation(-$this->btc_amount);
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }
}
