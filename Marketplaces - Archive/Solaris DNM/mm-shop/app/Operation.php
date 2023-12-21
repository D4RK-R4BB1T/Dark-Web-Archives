<?php
/**
 * File: Operation.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Operation
 *
 * @property integer $id
 * @property integer $user_id
 * @property float $amount
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property integer $wallet_id
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereWalletId($value)
 * @property-read \App\Wallet $wallet
 * @property integer $order_id
 * @property-read \App\Order $order
 * @method static \Illuminate\Database\Query\Builder|\App\Operation whereOrderId($value)
 */
class Operation extends Model
{
    protected $table = 'operations';
    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'order_id', 'amount', 'description'
    ];
    /**
     * @return BelongsTo|Builder|Wallet
     */
    public function wallet()
    {
        return $this->belongsTo('App\Wallet', 'wallet_id', 'id');
    }

    /**
     * @return BelongsTo|Builder|Wallet
     */
    public function trashedWallet()
    {
        return $this->wallet()->withTrashed();
    }

    /**
     * @return BelongsTo|Builder|Order
     */
    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id', 'id');
    }
}