<?php

namespace App;

use App\Packages\Utils\BitcoinUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\Good
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $city_id
 * @property integer $category_id
 * @property string $title
 * @property string $description
 * @property string $image_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereCityId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereCategoryId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereHasQuests($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereHasReadyQuests($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Shop $shop
 * @property-read \App\City $city
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsPackage[] $packages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsPosition[] $positions
 * @method static \Illuminate\Database\Query\Builder|\App\Good filterTitle($title)
 * @method static \Illuminate\Database\Query\Builder|\App\Good filterCity($cityId)
 * @method static \Illuminate\Database\Query\Builder|\App\Good filterReadyOnly()
 * @method static \Illuminate\Database\Query\Builder|\App\Good filterCategory($categoryId)
 * @method static \Illuminate\Database\Query\Builder|\App\Good applySearchFilters($request)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsPhoto[] $photos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsReview[] $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CustomPlace[] $customPlaces
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsService[] $services
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AccountingLot[] $lots
 * @property int $priority
 * @method static \Illuminate\Database\Query\Builder|\App\Good wherePriority($value)
 * @property int $buy_count
 * @method static \Illuminate\Database\Query\Builder|\App\Good whereBuyCount($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\City[] $cities
 */
class Good extends Model
{
    protected $table = 'goods';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'city_id', 'category_id', 'title', 'description', 'image_url', 'priority'
    ];

    public function delete()
    {
        $this->packages()->delete();
        $this->reviews()->delete();
        $this->customPlaces()->delete();
        $this->lots()->delete();

        return parent::delete();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Shop
     */
    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|City[]
     */
    public function cities()
    {
        return $this->belongsToMany('App\City', 'goods_cities');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|GoodsPhoto[]
     */
    public function photos()
    {
        return $this->hasMany('App\GoodsPhoto', 'good_id', 'id');
    }

    public function category()
    {
        return Category::getById($this->category_id)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|GoodsPackage[]
     */
    public function packages()
    {
        return $this->hasMany('App\GoodsPackage', 'good_id', 'id');
    }

    /**
     * @return Builder|GoodsPackage[]
     */
    public function availablePackages()
    {
        return $this->packages()->where(function ($query) {
            $query->whereHas('availablePositions')->orWhere('preorder', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|GoodsPosition[]
     */
    public function positions()
    {
        return $this->hasMany('App\GoodsPosition', 'good_id', 'id');
    }

    /**
     * @return Builder|GoodsPosition[]
     */
    public function availablePositions()
    {
        return $this->positions()->where(function ($query) {
            $query->where('available', 1);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|GoodsReview[]
     */
    public function reviews()
    {
        return $this->hasMany('App\GoodsReview', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|CustomPlace[]
     */
    public function customPlaces()
    {
        return $this->hasMany('App\CustomPlace', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Order[]
     */
    public function orders()
    {
        return $this->hasMany('App\Order', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|AccountingLot[]
     */
    public function lots()
    {
        return $this->hasMany('App\AccountingLot', 'good_id', 'id');
    }

    public function scopeApplySearchFilters(Builder $goods, Request $request)
    {
        if ($request->has('region') ||
            $request->get('availability') === 'ready' ||
            $request->has('city'))
        {
            /** @var Builder $packages */
            $packages = GoodsPackage::select('good_id');

            $city = $request->get('city');

            if (in_array($city, City::citiesWithRegions()) && is_numeric($region = $request->get('region'))) {
                $packages = $packages->filterRegion($region);
            }

            if ($request->get('availability', 'all') === 'ready') {
                $packages = $packages->where('has_ready_quests', true);
            }

            if (is_numeric($city)) {
                $packages = $packages->where('city_id', $city);
            }

            $goods->whereIn('id', $packages);
        }

        if (is_numeric($category = $request->get('category'))) {
            $goods = $goods->filterCategory($category);
        }

        if (!empty($query = $request->get('query'))) {
            $goods = $goods->filterTitle($query);
        }

        return $goods;
    }

    public function scopeFilterTitle($query, $title)
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }

    public function scopeFilterCategory($query, $categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return $query;
        }
        if ($category->isMain()) {
            return $query->whereIn('category_id', $category->children()->pluck('id')->toArray());
        } else {
            return $query->where('category_id', $categoryId);
        }
    }

    private $_cheapestAvailablePackage = null;
    private $_mostExpensiveAvailablePackage = null;
    private function _findCheapestAndMostExpensiveAvailablePackages()
    {
        if (!$this->_cheapestAvailablePackage || !$this->_mostExpensiveAvailablePackage) {
            $availablePackages = $this->availablePackages;
            $availablePackages = $availablePackages->each(function (&$item, $key) {
                /** @var GoodsPackage $item */
                $item->price_btc = $item->getPrice(BitcoinUtils::CURRENCY_BTC);
            })->sortBy('price_btc');

            $this->_cheapestAvailablePackage = $availablePackages->first();
            $this->_mostExpensiveAvailablePackage = $availablePackages->last();
        }
    }

    private $_cheapestPackage = null;
    private $_mostExpensivePackage = null;
    private function _findCheapestAndMostExpensivePackages()
    {
        if (!$this->_cheapestPackage || !$this->_mostExpensivePackage)
        {
            $packages = $this->packages;
            $packages = $packages->each(function (&$item, $key) {
                /** @var GoodsPackage $item */
                $item->price_btc = $item->getPrice(BitcoinUtils::CURRENCY_BTC);
            })->sortBy('price_btc');

            $this->_cheapestPackage = $packages->first();
            $this->_mostExpensivePackage = $packages->last();
        }
    }

    public function getCheapestAvailablePackage()
    {
        $this->_findCheapestAndMostExpensiveAvailablePackages();
        return $this->_cheapestAvailablePackage;
    }

    public function getMostExpensiveAvailablePackage()
    {
        $this->_findCheapestAndMostExpensiveAvailablePackages();
        return $this->_mostExpensiveAvailablePackage;
    }

    public function getCheapestPackage()
    {
        $this->_findCheapestAndMostExpensivePackages();
        return $this->_cheapestPackage;
    }

    public function getMostExpensivePackage()
    {
        $this->_findCheapestAndMostExpensivePackages();
        return $this->_mostExpensivePackage;
    }

    public function getRating()
    {
        $ratings = $this->reviews()
            ->select(\DB::raw('count(*) as c, sum(shop_rating) as sr, sum(dropman_rating) as dr, sum(item_rating) as ir'))
            ->first()
            ->toArray();

        if ($ratings['c'] == 0) {
            return '0.00';
        }

        return sprintf('%.2f',
            ((int) $ratings['sr'] + (int) $ratings['dr'] + (int) $ratings['ir']) / (3 * (int) $ratings['c']));
    }
}
