<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Income
 *
 * @property integer $id
 * @property integer $wallet_id
 * @property float $amount_usd
 * @property float $amount_btc
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Wallet $wallet
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereWalletId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereAmountUsd($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereAmountBtc($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Income whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Income extends Model
{
    protected $table = 'incomes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'wallet_id', 'amount_usd', 'amount_btc', 'description'
    ];

    public function wallet()
    {
        return $this->belongsTo('App\Wallet', 'wallet_id', 'id');
    }
}
