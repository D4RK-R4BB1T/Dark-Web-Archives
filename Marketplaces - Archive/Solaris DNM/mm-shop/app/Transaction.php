<?php
/**
 * File: Transaction.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Transaction
 *
 * @property string $tx_id
 * @property integer $wallet_id
 * @property string $address
 * @property float $amount
 * @property boolean $handled
 * @property integer $confirmations
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Wallet $wallet
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereTxId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereWalletId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereHandled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereConfirmations($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $id
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereId($value)
 * @property bool $vout
 * @method static \Illuminate\Database\Query\Builder|\App\Transaction whereVout($value)
 */
class Transaction extends Model
{
    const CATEGORY_RECEIVE = 'receive';
    const CATEGORY_SEND = 'send';

    protected $table = 'transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'tx_id', 'vout', 'wallet_id', 'address', 'amount', 'confirmations', 'handled'
    ];
    
    public $timestamps = true;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Wallet
     */
    public function wallet()
    {
        return $this->belongsTo('App\Wallet', 'wallet_id', 'id');
    }
}
