<?php

namespace App;

use App\Marketplace\Utility\CurrencyConverter;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use Uuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public static function averagePrice() : float
    {
        $averagePrice = self::avg('price');
        if($averagePrice != null)
        {
            return $averagePrice;
        }
        return 0;
    }
    
    /**
     * Relationship with the product
     *
     * @return \App\Product
     */
    public function product()
    {
        return $this -> belongsTo(Product::class);
    }

    /**
     * @return string amount of dollars with 2 decimals
     */
    public function getDollarsAttribute()
    {
        return number_format($this -> price, 2, '.', '');
    }
    
    

    /**
     * Relationship of purchases
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchases()
    {
        return $this -> hasMany(\App\Purchase::class, 'offer_id', 'id');
    }

    /**
     * Converts price of the form to local price
     *
     * @return string
     */
    public function getLocalPriceAttribute()
    {
        return CurrencyConverter::convertToLocal($this->price);
    }


}
