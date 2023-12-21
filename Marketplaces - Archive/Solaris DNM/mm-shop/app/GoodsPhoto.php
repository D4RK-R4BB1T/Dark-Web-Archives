<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GoodsPhoto
 *
 * @property integer $id
 * @property integer $good_id
 * @property string $image_url
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPhoto whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPhoto whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPhoto whereImageUrl($value)
 * @mixin \Eloquent
 */
class GoodsPhoto extends Model
{
    protected $table = 'goods_photos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'good_id', 'image_url'
    ];

    public $timestamps = false;
}
