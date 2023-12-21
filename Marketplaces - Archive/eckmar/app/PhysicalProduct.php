<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class PhysicalProduct extends User
{
    use Uuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    public $generateManualy = true;
    /**
     * @var array of posible country options
     */
    public static $countriesOptions = ['all' => 'All countries', 'include' => 'Included countries', 'exclude' => 'All except excluded countries'];

    /**
     * Return instance of the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this -> hasOne(\App\Product::class, 'id', 'id');
    }

    /**
     * Array of shortcode's countries
     *
     * @return array
     */
    public function countriesArray()
    {
        if(!empty($this -> countries))
            return explode(',', $this -> countries);
        return [];
    }

    /**
     * Returns array of long named countries like United Kingdom,France,Germany...
     *
     * @return array
     */
    public function countriesLongArray()
    {
        $countries = [];
        foreach ($this -> countriesArray() as $country){
            $countries []= config('countries.' . $country);
        }
        return $countries;
    }

    /**
     * return string for describing shipment to countries
     *
     * @return string
     */
    public function shipsTo()
    {
        if($this -> countries_option == 'all')
            return 'all';
        if($this -> countries_option == 'include')
            return ' only to countries';
        return 'all except countries ';
    }

    /**
     * Returns list of long named countries separated with commas
     * Germany,England,France...
     *
     * @return string
     */
    public function countriesLong()
    {
        if(!empty($this -> countriesLongArray()))
            return implode(', ', $this -> countriesLongArray());
        return '';
    }

    /**
     * Returns the long name of the country from which the product are sent
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function shipsFrom()
    {
        return config('countries.' . $this -> country_from);
    }

    /**
     * Glue the countries
     *
     * @param array $countries
     *
     */
    public function setCountries(?array $countries )
    {
        if(!empty($countries) )
            $this -> countries = implode(',', $countries);
        else
            $this -> countries = '';
    }

    /**
     * Returns collection of shippings
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shippings()
    {
        return $this -> hasMany(\App\Shipping::class, 'product_id', 'id')
            ->where('deleted', '=', 0);
    }




}
