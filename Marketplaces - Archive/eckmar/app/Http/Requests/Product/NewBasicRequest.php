<?php

namespace App\Http\Requests\Product;

use App\Exceptions\RequestException;
use App\Product;
use Illuminate\Foundation\Http\FormRequest;

class NewBasicRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'rules' => 'required|string',
            'quantity' => 'required|numeric|min:1',
            'mesure' => 'required|string|max:10',
            'coins' => 'required|array',
            'types' => 'required|array',
        ];
    }

    public function persist(Product $currentProduct = null)
    {
        if(empty($this -> coins))
            throw new RequestException('Product must support payments in at least one coin!');
        // check if there is coin that is not supported
        foreach ($this -> coins as $coin)
            if(!in_array($coin, array_keys(config('coins.coin_list'))))
                throw new RequestException('Coin "' . $coin . '" is not supported!');
        // check if it is in database


        /**
         * Check if user can use all product types
         */
        foreach ($this -> types as $type) {
           if (!auth()->user()->vendor->canUseType($type))
               throw new RequestException('Unallowed purchase type used');
        }
        
        // make new product
        $editingProduct = $currentProduct ?? new Product;

        $editingProduct->category_id = $this->category_id;
        if(is_null($currentProduct)) $editingProduct->user_id = auth()->user()->id; // set user id for new products
        $editingProduct->name = $this->name;
        $editingProduct->description = $this->description;
        $editingProduct->rules = $this->rules;
        $editingProduct->mesure = $this->mesure;
        $editingProduct -> setCoins($this -> coins);
        $editingProduct -> setTypes($this -> types);

        // edit quantity if it is not autodelivery
        if(!$editingProduct -> isAutodelivery())
            $editingProduct->quantity = $this->quantity;
        // if editing product
        if ($currentProduct && $currentProduct->exists()) {
            // save
            $editingProduct->save();

            session()->flash('success', 'You have successfully edited product!');
            // return to back
            return redirect()->back();
        }
        // if making new product
        // generate new id
        $editingProduct->id = \Uuid::generate()->string;
        // put in session
        session()->put('product_adding', $editingProduct);

        return redirect()->route('profile.vendor.product.offers');

    }
}
