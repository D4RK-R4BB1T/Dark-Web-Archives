<?php

namespace App\Http\Requests\Product;

use App\Exceptions\RedirectException;
use App\Exceptions\RequestException;
use App\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class NewProductRequest extends FormRequest
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
            //
        ];
    }

    public function persist()
    {
        // doesnt have basic product details
        if (!session() -> has('product_adding'))
            throw new RedirectException('You didn\'t define basic product details!', route('profile.vendor.product.add'));
        // doesnt have offers
        if (!session() -> has('product_offers') ||  optional(session('product_offers')) -> isEmpty() || empty(session('product_offers')))
            throw new RedirectException('You don\'t have your offers specified!', route('profile.vendor.product.offers'));
        // physcical product
        if (session('product_type') == 'physical'){
            // doesnt have shipping
            if(!session() -> has('product_shippings') || empty(session('product_shippings')))
                throw new RedirectException('You must have at least one shipping specified!', route('profile.vendor.product.delivery'));
            // doesnt have shipping details
            if(!session() -> has('product_details'))
                throw new RedirectException('You must have shipping details set!', route('profile.vendor.product.delivery'));
        }
        // digital product
        else {
            if(!session() -> has('product_details'))
                throw new RedirectException('You must have digital details set!', route('profile.vendor.product.digital'));
        }
        // doesnt have images
        if (!session() -> has('product_images') || empty(session('product_images')))
            throw new RedirectException('You must have at least one image added!', route('profile.vendor.product.images'));

        $baseProduct = session() -> get('product_adding');
        $detailsProduct = session() -> get('product_details'); // physical or digital
        $offersArray = session() -> get('product_offers');
        $shippingArray = session() -> get('product_shippings');
        $imagesArray = session() -> get('product_images');


        $baseProduct -> save();
//        dd([$baseProduct, $detailsProduct]);
        // save to db all
        try{
            DB::beginTransaction();

            // save product details
            $detailsProduct -> id = $baseProduct -> id;

            $detailsProduct -> save();

            // save offers
            foreach ($offersArray as $offer){
                $offer -> product_id = $baseProduct -> id;
                $offer -> save();
            }
            // save images
            foreach ($imagesArray as $image) {
                $image -> product_id = $baseProduct -> id;
                $image -> save();
            }

            // save shippings if physical product
            if(session() -> has('product_type') && session('product_type') == 'physical')
                foreach ($shippingArray as $shipping){
                    $shipping -> product_id = $baseProduct -> id;
                    $shipping -> save();
                }

            DB::commit();
        }
        catch (\Exception $e){
            DB::rollBack();
            throw new RequestException('Something went wrong try again!' . $e -> getMessage());
        }
        // importing product to elasticsearch index
        if ($baseProduct->quantity > 0){
            Product::where('id',$baseProduct->id)->searchable();
        }

        // TODO Unit test for product importing
        // TODO Sold products = unsearchable
        // TODO Remove products with 0 quantity from display

        // forget all
        session() -> forget('product_type');
        session() -> forget('product_adding');
        session() -> forget('product_offers');
        session() -> forget('product_images');
        session() -> forget('product_shippings');
        session() -> forget('product_details');
    }
}
