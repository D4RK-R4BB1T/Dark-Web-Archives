<?php

namespace App;

use App\Exceptions\RequestException;
use App\Marketplace\Payment\FinalizeEarlyPayment;
use App\Marketplace\Utility\CurrencyConverter;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use Uuids;
    use Searchable;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    public static $orderingMap = [
        'newer' => 'created_at',
        'name' => 'name',
    ];

    /**
     * Return collection for front page
     *
     * @return mixed
     */
    public static function frontPage()
    {
        return self::where('active', 1) ->  paginate(config('marketplace.products_per_page'));
    }


    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray() {

        $product = $this->with('category')->with('user')->where('id', $this->id)->first();
        $array = [];
        $array['name'] = $product->name;
        $array['description'] = $product->description;
        $array['rules'] = $product->rules;
        $array['quantity'] = $product->quantity;
        $array['mesure'] = $product->mesure;
        $array['created_at'] = $product->created_at;
        if ($this->isPhysical()){
            $physicalProduct =  PhysicalProduct::where('id',$this->id)->first();
            $array['from_country_full'] = $physicalProduct->shipsFrom();
            $array['from_country_code'] = $physicalProduct->country_from;
        }
        if ($this->isPhysical()){
            $array['type'] = 'physical';
        }
        if ($this->isDigital()){
            $array['type'] = 'digital';
        }

        $array['category'] []= $product->category->name;
        // add parents
        foreach ($product->category->parents() as $parent){
            $array['category'] []= $parent->name;
        }



        $array['user'] = $product->user->username;
        $array['price'] = $this->price_from;
        return $array;
    }

    /**
     * Returns if product is digital
     *
     * @return bool
     */
    public function isDigital() {
        return DigitalProduct::where('id', $this->id)->exists();
    }

    public function digital() {
        return $this->hasOne(\App\DigitalProduct::class, 'id', 'id');
    }

    public function isAutodelivery()
    {
        return $this -> digital && $this -> digital -> autodelivery;
    }

    public function isUnlimited()
    {
        return $this -> digital && $this -> digital -> unlimited;
    }

    /**
     * Updates the quantity for autodelivery products
     */
    public function updateQuantity()
    {
        if ($this -> isAutodelivery()){
            $this -> quantity = $this -> digital -> newQuantity();
        }
    }

    /**
     * \App\Category of the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category() {
        return $this->hasOne(\App\Category::class, 'id', 'category_id');
    }

    /**
     * Returns if product is physical
     *
     * @return bool
     */
    public function isPhysical() {
        return PhysicalProduct::where('id', $this->id)->exists();
    }

    public function physical() {
        return $this->hasOne(\App\PhysicalProduct::class, 'id', 'id');
    }

    /**
     * Attribute that returns type of the product
     *
     * @return string
     */
    public function getTypeAttribute() {
        return $this->isPhysical() ? 'physical' : 'digital';
    }

    /**
     * Accessor for description
     *
     * @param $oldDescription
     * @return string
     */

    public function getDescriptionHtmlAttribute()
    {
        return nl2br($this -> description);
    }

    /**
     * Returns the short version of the description
     *
     * @return bool|string
     */
    public function getShortDescriptionAttribute()
    {
        return substr($this -> description, 0, 200) . '...';
    }

    public function getRulesHtmlAttribute()
    {
        return nl2br($this -> rules);

    }


    /**
     * Returns the specific object of the product \App\PhysicalProduct or \App\DigitalProduct
     *
     * @return DigitalProduct|DigitalProduct[]|PhysicalProduct|PhysicalProduct[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function specificProduct() {
        if ($this->isPhysical())
            return $this->physical;
        return $this->digital;
    }

    /**
     * Collection of offers connected with this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offers() {
        return $this->hasMany(\App\Offer::class, 'product_id', 'id')
            ->where('deleted', '=', 0) // if offer is not deleted
            ->orderBy('price');

    }

    /**
     * Returns best \App\Offer with lowest price for given $quantity
     *
     * @throws RequestException
     * @param $quantity
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|null|object
     */
    public function bestOffer($quantity): Offer {
        $firstOffer = $this->offers()
            ->where('deleted', '=', 0)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('price')
            ->first();

        if ($firstOffer == null)
            throw new RequestException('There is no offer for this quantity!');
        return $firstOffer;
    }

    /**
     * Collection of images
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images() {
        return $this->hasMany(\App\Image::class, 'product_id');
    }

    /**
     * Returns the default \App\Image If there is no images returns placeholder instance of the \App\Image
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|null|object
     */
    public function frontImage() {
        if($this->images()->doesntExist())
        {
            $placeholderImage = new Image;
            $placeholderImage -> image = '../img/product.png';

            return $placeholderImage;
        }
        return $this->images()->where('first', true)->first() ?? $this->images()->first();
    }


    /**
     * Returns minimum price from collection of connected offers
     *
     * @return mixed
     */
    public function getPriceFromAttribute() {
        return $this->offers->min('price');
    }

    /**
     * Returns number of orders
     *
     * @return int
     */
    public function getOrdersAttribute() {
        $numberOfOrders = 0;
        foreach ($this->offers()->get() as $offer) {
            $numberOfOrders += $offer->purchases()->count();
        }

        return $numberOfOrders;
    }

    /**
     * Returns the user of the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user() {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Subtract quantity of the products
     *
     * @param $amount
     * @throws RequestException
     */
    public function substractQuantity($amount) {
    
        if ($amount > $this->quantity)
            throw new RequestException('Not enough items, it appears that someone bought in the meantime.');
        $this->quantity -= $amount;
        // if the product quantity is 0, delete it from search index
        if($this->quantity == 0){
            $this->unsearchable();
        }
    }

    /**
     * Relationship one to many with feedback
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feedback() {
        return $this->hasMany(\App\Feedback::class, 'product_id', 'id');
    }

    /**
     * @return mixed
     */
    public function hasFeedback() {
        return $this->feedback->isNotEmpty();
    }

    /**
     * Return string number with two decimals of average rate
     *
     * @param $type
     * @return string
     */
    public function avgRate($type) {
        if (!$this->hasFeedback())
            return '';

        if (!in_array($type, Feedback::$rates))
            $type = 'quality_rate';

        return number_format($this->feedback->avg($type), 2);

    }

    /**
     * Return which view will be shown when you click next in product editing or adding
     *
     * @return string
     */
    public function afterOffers()
    {
        if($this -> isDigital())
            return 'digital';
        return 'delivery';
    }

    /**
     *  Mark this product as inactive so nobody can see or edit
     */
    public function deactivate()
    {
        $this -> active = false;
        $this -> save();
        $this -> unsearchable();
    }

    /**
     * Returns if the this product supports coin
     *
     * @param string $coin
     * @return bool
     */
    public function supportsCoin($coin)
    {
        return in_array($coin, explode(",", $this -> coins));
    }

    /**
     * Returns if the this product supports type of purchase
     *
     * @param string $coin
     * @return bool
     */
    public function supportsType($type)
    {
        return in_array($type, explode(",", $this -> types));
    }

    /**
     * Returns array of types for this product
     *
     * @return array
     */
    public function getTypes() : array
    {
        $types = explode(',', $this->types);
        $types = array_filter($types);
        $feName = FinalizeEarlyPayment::$shortName;
        if (!FinalizeEarlyPayment::isEnabled() && in_array($feName,$types)){
            unset($types[array_search($feName,$types)]);
        }
        return array_values($types);
    }

    /**
     * Sets the coins
     *
     * @param array $coins
     */
    public function setCoins(array $coins)
    {
        $this -> coins = implode(',', $coins);
    }


    /**
     * Set the types of the coin
     *
     * @param array $types
     */
    public function setTypes(array $types)
    {
        $this->types = implode(',', $types);
    }

    /**
     * Returns supported coins in array
     *
     * @return array
     */
    public function getCoins() : array
    {
        $coinsFromProduct = explode(",", $this -> coins);
        $supportedCoinsFromProduct = array_filter($coinsFromProduct, function($coinName){
            return in_array($coinName, array_keys(config('coins.coin_list')));
        });

        return $supportedCoinsFromProduct;
    }

    public function getLocalPriceFrom(){

        return CurrencyConverter::convertToLocal($this->price_from);
    }
    public function getLocalSymbol(){
        return CurrencyConverter::getLocalSymbol();
    }

}
