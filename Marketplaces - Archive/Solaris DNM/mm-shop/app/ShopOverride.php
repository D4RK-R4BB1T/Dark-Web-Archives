<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ShopOverride
 *
 * @property int $id
 * @property int $shop_id
 * @property string $param
 * @property string $value
 * @method static \Illuminate\Database\Query\Builder|\App\ShopOverride whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopOverride whereParam($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopOverride whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ShopOverride whereValue($value)
 * @mixin \Eloquent
 */
class ShopOverride extends Model
{

    public $timestamps = false;
    protected $table = 'shop_overrides';
    protected $fillable = ['shop_id', 'param', 'value'];
}
