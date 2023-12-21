<?php

namespace App;

use App\Exceptions\RequestException;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends User
{
    use Uuids;

    /**
     * Return instance of the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this -> hasOne(\App\Product::class, 'id', 'id');
    }

    public function shippings()
    {
        return null;
    }

    /**
     * Set the new content for the product
     *
     * @param $newContent
     */
    public function setContent(?string $newContent)
    {
        $newContent = empty($newContent) ? '' : $newContent;
        // remove consecutive new lines, and trim balnk chars
        $formatedContent = trim(preg_replace("/[\r\n]{2,}/", "\n", $newContent));
        $this -> content = $formatedContent;
    }

    /**
     * Return new quantity by counting number of lines in product's content
     *
     * @return int
     */
    public function newQuantity()
    {
        return !empty($this -> content) ? substr_count($this -> content, "\n") + 1 : 0;
    }


    /**
     * Get autodelivered products
     *
     * @param int $quantity number of autodelivered products
     * @return array
     * @throws RequestException
     */
    public function getProducts(int $quantity) : array
    {
        if($quantity > $this -> newQuantity())
            throw new RequestException('There is not enough products in the stock!');

        $productsToDelivery = [];

        // push to products from product contnet
        while($quantity > 0){
            // extract the first product
            $firstProduct = substr($this -> content, 0, strpos($this -> content, "\n"));

            // push product to array
            array_push($productsToDelivery, $firstProduct);

            // remove first product
            $this -> content = substr($this -> content, strpos($this -> content, "\n") + 1);

            $quantity--;
        }


        $this -> save();
        // update \App\Product quantity
        $this -> product -> updateQuantity();
        $this -> product -> save();


        return $productsToDelivery;
    }
}
