<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\QiwiExchangeTransaction
 *
 * @property int $id
 * @property int $qiwi_exchange_request_id
 * @property float $pay_amount
 * @property string $pay_address
 * @property string $pay_comment
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction wherePayAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction wherePayAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction wherePayComment($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction whereQiwiExchangeRequestId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property bool $pay_need_input
 * @property string $pay_input_description
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction wherePayInputDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\QiwiExchangeTransaction wherePayNeedInput($value)
 */
class QiwiExchangeTransaction extends Model
{
    protected $table = 'qiwi_exchanges_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'qiwi_exchange_request_id', 'pay_amount', 'pay_address', 'pay_comment', 'pay_need_input', 'pay_input_description'
    ];
}
