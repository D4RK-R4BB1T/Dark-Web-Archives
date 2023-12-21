<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\City
 *
 * @property integer $id
 * @property string $title
 * @property integer $priority
 * @method static \Illuminate\Database\Query\Builder|\App\City whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\City whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\City wherePriority($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Region[] $regions
 */
class City extends Model
{
    const CITY_ALL = 'all';
    
    protected $table = 'cities';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'title', 'priority'
    ];

    public static function allCached()
    {
        return \Cache::remember('cities', 60, function () {
            return City::orderBy('priority', 'desc')->get();
        });
    }

    public static function allReal()
    {
        return City::allCached()->filter(function($item, $key) {
            return !in_array($item->id, City::citiesNotReal());
        });
    }

    public static function citiesWithRegions()
    {
        return [
            1, // Moscow
            3  // SPb
        ];
    }

    public static function citiesNotReal()
    {
        return [
            4, // Отправка по России
            5, // Отправка по Украине
            6, // Отправка по России и СНГ
            7  // Без региона
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Region[]
     */
    public function regions()
    {
        return $this->hasMany('App\Region', 'city_id', 'id');
    }
}
