<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\QiwiTransaction
 *
 * @property integer $id
 * @property integer $qiwi_wallet_id
 * @property integer $order_id
 * @property float $amount
 * @property string $sender
 * @property string $comment
 * @property string $status
 * @property string $paid_at
 * @property string $last_checked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\QiwiWallet $qiwiWallet
 * @property-read \App\Order $order
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereQiwiWalletId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereOrderId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereSender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereComment($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction wherePaidAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereLastCheckedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction applySearchFilters(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiTransaction filterWallet($walletId)
 */
class QiwiTransaction extends Model
{
    const STATUS_RESERVED = 'reserved';
    const STATUS_PAID = 'paid';

    protected $table = 'qiwi_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'qiwi_wallet_id', 'order_id', 'amount', 'sender', 'comment', 'status', 'paid_at', 'last_checked_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'last_checked_at' => 'datetime'
    ];

    public function qiwiWallet()
    {
        return $this->belongsTo('App\QiwiWallet', 'qiwi_wallet_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id', 'id');
    }

    public function scopeApplySearchFilters(Builder $transactions, Request $request)
    {
        if (!empty($walletId = $request->get('wallet'))) {
            $transactions = $transactions->filterWallet($walletId);
        }

        return $transactions;
    }

    public function scopeFilterWallet($query, $walletId)
    {
        return $query->where('qiwi_wallet_id', $walletId);
    }

    public function getHumanStatus()
    {
        switch ($this->status) {
            case QiwiTransaction::STATUS_PAID:
                return 'Оплачен';

            case QiwiTransaction::STATUS_RESERVED:
                return 'Резерв';

            default:
                return 'Неизвестно';
        }
    }
}
