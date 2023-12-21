<?php

namespace App\Http\Requests\Admin;


use App\Events\Admin\ProductDeleted;
use App\Product;
use Illuminate\Foundation\Http\FormRequest;

class DeleteProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id'
        ];
    }
    public function messages(){
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product with that ID does not exist'
        ];
    }
    public function persist(){
        $product = Product::where('id',$this->product_id)->with('user')->first();
        event(new ProductDeleted($product,$product->user,auth()->user()));
        $product->delete();
        session() -> flash('success', 'You have successfully deleted product!');
    }
}
