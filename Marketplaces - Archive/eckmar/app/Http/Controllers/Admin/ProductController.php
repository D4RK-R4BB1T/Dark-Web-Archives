<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Exceptions\RequestException;
use App\Http\Requests\Admin\DeleteProductRequest;
use App\Http\Requests\Admin\DisplayProductsRequest;
use App\Http\Requests\Admin\RemoveProductFromFeaturedReuqest;
use App\Product;
use App\Purchase;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Check if the this admin/moderator has access to edit/remove products
     */
    private function checkProducts(){
        if(Gate::denies('has-access', 'products'))
            abort(403);
    }


    public function __construct() {
        $this->middleware('admin_panel_access');
    }

    /**
     * Displaying list of all products in Admin Panel
     *
     * @param DisplayProductsRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function products(DisplayProductsRequest $request){
        $this -> checkProducts();

        $request->persist();
        $products = $request->getProducts();
        return view('admin.products')->with([
           'products' => $products
        ]);
    }
    public function productsPost(Request $request){
        $this -> checkProducts();

        return redirect()->route('admin.products',[
            'order_by' => $request->order_by,
            'user' => $request->user,
            'product' => $request -> product
        ]);
    }

    /**
     * Deleteing product from Admin panel
     *
     * @param DeleteProductRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteProduct(DeleteProductRequest $request){
        $this -> checkProducts();

        try{
            $request->persist();
        } catch (RequestException $e){
            Log::warning($e);
            $e->flashError();
        }
        return redirect()->back();
    }


    /**
     * Method for showing all editing forms for the product
     *
     * @param $id of the product
     * @param string $section
     * @return \Illuminate\Http\RedirectResponse|mixed
     *
     */
    public function editProduct($id, $section = 'basic')
    {

        $myProduct = Product::findOrFail($id);
        $this -> authorize('update', $myProduct);


        // if product is not vendor's
        if($myProduct == null)
            return redirect() -> route('admin.products');

        // digital product cant have delivery section
        if($myProduct -> isDigital() && $section == 'delivery')
            return redirect() -> route('admin.index');

        // physical product cant have digtial section
        if($myProduct -> isPhysical() && $section == 'digital')
            return redirect() -> route('admin.index');

        // set product type section
        session() -> put('product_type', $myProduct -> type);

        // string to view map to retrive which view
        $sectionMap = [
            'basic' =>
                view('admin.product.basic',
                    [
                        'type' => $myProduct -> type,
                        'allCategories' => Category::nameOrdered(),
                        'basicProduct' => $myProduct,]),
            'offers' =>
                view('admin.product.offers',
                    [
                        'basicProduct' => $myProduct,
                        'productsOffers' => $myProduct -> offers() -> get()
                    ]),
            'images' =>
                view('admin.product.images',
                    [
                        'basicProduct' => $myProduct,
                        'productsImages' => $myProduct -> images() -> get(),
                    ]),
            'delivery' =>
                view('admin.product.delivery', [
                    'productsShipping' => $myProduct -> isPhysical() ? $myProduct -> specificProduct() -> shippings() -> get() : null,
                    'physicalProduct' => $myProduct -> specificProduct(),
                    'basicProduct' => $myProduct,
                ]),
            'digital' =>
                view('admin.product.digital', [
                    'digitalProduct' => $myProduct -> specificProduct(),
                    'basicProduct' => $myProduct,
                ]),

        ];

        // if the section is not allowed strings
        if(!in_array($section, array_keys($sectionMap)))
            $section = 'basic';

        return $sectionMap[$section];
    }

    /**
     * List of all purchases
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function purchases()
    {
        return view('admin.purchases', [
            'purchases' => Purchase::orderByDesc('created_at')->paginate(config('marketplace.products_per_page')),
        ]);
    }
    
    public function featuredProductsShow(){

        $products = Product::where('featured',1)->paginate(25);

        return view('admin.featuredproducts')->with([
            'products' => $products
        ]);
    }
    /**
     * Deleteing product from Admin panel
     *
     * @param DeleteProductRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeFromFeatured(RemoveProductFromFeaturedReuqest $request){
        $this -> checkProducts();

        try{
            $request->persist();
        } catch (RequestException $e){
            Log::warning($e);
            $e->flashError();
        }
        return redirect()->back();
    }
    
    
    public function markAsFeatured(Product $product){
        $this -> checkProducts();
        $product->featured = 1;
        $product->save();
        return redirect()->back();
    }
}
