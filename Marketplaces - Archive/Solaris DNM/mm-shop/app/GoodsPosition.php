<?php

namespace App;

use App\Events\PositionDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\GoodsPosition
 *
 * @property integer $id
 * @property integer $good_id
 * @property integer $package_id
 * @property integer $employee_id
 * @property integer $subregion_id
 * @property integer $custom_place_id
 * @property string $quest
 * @property boolean $available
 * @property boolean $moderated
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition wherePackageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereSubregionId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereCustomPlaceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereQuest($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereAvailable($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Good $good
 * @property-read \App\GoodsPackage $package
 * @property-read \App\Region $region
 * @property-read \App\Employee $employee
 * @property-read \App\CustomPlace $customPlace
 * @property int $distribution_id
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereDistributionId($value)
 * @property-read \App\AccountingDistribution $distribution
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition applySearchFilters(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition filterCity($cityId)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition filterRegion($regionId)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition filterUserName($username)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPosition whereModerated($value)
 */
class GoodsPosition extends Model
{
    protected $table = 'goods_positions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'good_id', 'package_id', 'distribution_id', 'employee_id', 'subregion_id', 'custom_place_id', 'quest', 'available',
        'moderated'
    ];

    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo('App\GoodsPackage', 'package_id', 'id');
    }

    public function region()
    {
        return $this->belongsTo('App\Region', 'subregion_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    public function customPlace()
    {
        return $this->belongsTo('App\CustomPlace', 'custom_place_id', 'id');
    }

    public function distribution()
    {
        return $this->belongsTo('App\AccountingDistribution', 'distribution_id', 'id');
    }

    public function delete()
    {
        event(new PositionDeleted($this));
        return parent::delete(); // TODO: Change the autogenerated stub
    }

    public function scopeApplySearchFilters(Builder $query, Request $request): Builder
    {
        if(!empty($username = $request->get('username'))) {
            $query = $query->filterUserName($username);
        }

        if (!empty($city_id = $request->get('city_id'))) {
            $query = $query->filterCity($city_id);
        }

        if (!empty($region_id = $request->get('region_id'))) {
            $query = $query->filterRegion($region_id);
        }

        return $query;
    }

    public function scopeFilterCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeFilterRegion($query, $regionId)
    {
        return $query->where('subregion_id', $regionId);
    }

    public function scopeFilterUserName($query, $username)
    {
        return $query->where('username', 'LIKE', '%' . $username . '%');
    }
}
