<?php

namespace App;

use App\Marketplace\Utility\CurrencyConverter;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use Uuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    /**
     * Returns PhysicalProduct that belongs to this shipping
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function physicalProduct()
    {
        return $this -> hasOne(\App\PhysicalProduct::class, 'id', 'product_id');
    }
    
    
    /**
     * @return string amount of dollars with 2 decimals
     */
    public function getDollarsAttribute()
    {
        return number_format($this -> price, 2, '.', '');
    }

    public function getLongNameAttribute()
    {
        return $this -> name . ' - ' . $this -> duration . ' - ' .  CurrencyConverter::convertToLocal($this -> price). ' '. CurrencyConverter::getSymbol(CurrencyConverter::getLocalCurrency()).  ' (' . $this -> from_quantity  .  ' - ' .  $this -> to_quantity . ' ' . $this -> physicalProduct -> product -> mesure .  ' )';
    }

    /**
     * Set the product for new model
     *
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        if($product != null)
            $this -> product_id = $product -> id;
    }

    /**
     * Returns 2 decimals stringed number of local value of the shipping
     *
     * @return string
     */
    public function getLocalValueAttribute()
    {
        return number_format(CurrencyConverter::convertToLocal($this -> price), 2, '.', '');
    }

}
