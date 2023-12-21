<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use Illuminate\Database\Eloquent\Model;

/**
 * App\GoodsPackagesService
 *
 * @property integer $id
 * @property integer $package_id
 * @property integer $service_id
 * @property-read \App\GoodsPackage $package
 * @property-read \App\PaidService $service
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPackagesService whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPackagesService wherePackageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\GoodsPackagesService whereServiceId($value)
 * @mixin \Eloquent
 */
class GoodsPackagesService extends Model
{
    protected $table = 'goods_packages_services';
    protected $primaryKey = 'id';

    public $fillable = [
        'package_id', 'service_id'
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Query\Builder|GoodsPackage
     */
    public function package()
    {
        return $this->belongsTo('App\GoodsPackage', 'package_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo('App\PaidService', 'service_id', 'id');
    }
}