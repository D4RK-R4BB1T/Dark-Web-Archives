<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Payout
 *
 * @property integer $id
 * @property integer $wallet_id
 * @property integer $user_id
 * @property float $amount
 * @property string $method
 * @property string $route
 * @property string $result
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereWalletId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereMethod($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereRoute($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereResult($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Payout whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payout extends Model
{
    protected $table = 'payouts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'wallet_id', 'user_id', 'amount', 'method', 'route', 'result'
    ];
}
