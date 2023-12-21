<?php

namespace App\Http\Requests\Product;

use App\Exceptions\RequestException;
use App\Marketplace\Utility\CurrencyConverter;
use App\PhysicalProduct;
use App\Product;
use App\Shipping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewShippingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'duration' => 'required|string',
            'price' => 'required|numeric',
            'from_quantity' => 'required|numeric|min:1',
            'to_quantity' => 'required|numeric|min:1'
        ];
    }

    public function persist(Product $product = null)
    {
        // get product from session
        $productsShippings = session('product_shippings') ?? collect();

        $newShipping = new Shipping;
        $newShipping -> name = $this -> name;
        $newShipping -> duration = $this -> duration;
        $newShipping -> from_quantity = $this -> from_quantity;
        $newShipping -> to_quantity = $this -> to_quantity;
        $newShipping -> price = CurrencyConverter::convertToUsd($this -> price);

        // shippings on existring product
        if($product && $product -> exists()){
            $newShipping -> setProduct($product);
            $newShipping -> save();
        }
        // shippings on new product
        else{
            $productsShippings -> push($newShipping); // put new offer
            $productsShippings = $productsShippings -> sortBy(function($shipment){ return $shipment -> price; });

            session() -> put('product_shippings', $productsShippings);
        }


    }
}
