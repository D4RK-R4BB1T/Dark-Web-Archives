<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GoodsCity
 *
 * @property int $id
 * @property int $good_id
 * @property int $city_id
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsCity whereCityId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsCity whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsCity whereId($value)
 * @mixin \Eloquent
 * @property-read \App\City $city
 */
class GoodsCity extends Model
{
    protected $table = 'goods_cities';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'good_id', 'city_id'
    ];

    public function city() {
        return $this->hasOne('App\City', 'id', 'city_id');
    }
}
