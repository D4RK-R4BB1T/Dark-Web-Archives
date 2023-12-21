<?php

namespace App\Http\Requests\Product;

use App\PhysicalProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewShippingOptionsRequest extends FormRequest
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
            'country_from' => ['required', Rule::in(array_keys(config('countries')))],
            'countries_option' => ['required', Rule::in(array_keys(PhysicalProduct::$countriesOptions))],
            'countries' => 'array|nullable'
        ];
    }

    public function persist(PhysicalProduct $product = null)
    {
        // product is not set = new product from session
        if(!$product || !$product -> exists()) {
            // get product from session
            $product = session('product_details') ?? new PhysicalProduct();
            if (!($product instanceof PhysicalProduct)) {
                $product = new PhysicalProduct;
            }
        }
        // set parameters
        $product -> country_from = $this -> country_from;
        $product -> countries_option = $this -> countries_option;
        $product -> setCountries($this -> countries); // empty string if it is array empty


        // new product
        if($product == null || !$product ->exists){
            session() -> put('product_details', $product);
            return redirect() -> route('profile.vendor.product.images');
        }

        $product -> save();
        session() -> flash('success', 'You have successfully changed shipping options!');
        return redirect() -> back();
    }
}
