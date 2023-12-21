<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\CustomPlace
 *
 * @property integer $id
 * @property integer $good_id
 * @property integer $region_id
 * @property string $title
 * @property-read \App\Good $good
 * @property-read \App\Region $region
 * @method static \Illuminate\Database\Query\Builder|\App\CustomPlace whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CustomPlace whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CustomPlace whereRegionId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CustomPlace whereTitle($value)
 * @mixin \Eloquent
 * @property integer $shop_id
 * @property-read \App\Shop $shop
 * @method static \Illuminate\Database\Query\Builder|\App\CustomPlace whereShopId($value)
 */
class CustomPlace extends Model
{
    protected $table = 'custom_places';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'good_id', 'region_id', 'title'
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Shop
     */
    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Good
     */
    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Region
     */
    public function region()
    {
        return $this->belongsTo('App\Region', 'region_id', 'id');
    }

    public function delete()
    {
        GoodsPosition::where('custom_place_id', $this->id)->update([
            'subregion_id' => $this->region_id,
            'custom_place_id' => null
        ]);

        return parent::delete();
    }
}