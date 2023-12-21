<?php
/**
 * File: ManagementController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Http\Controllers\Controller;
use App\Http\Requests\ShopInitRequest;
use App\Providers\DynamicPropertiesProvider;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;

class ManagementController extends Controller
{
    /** @var \App\Shop $shop */
    protected $shop;
    protected $propertiesProvider;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        $this->middleware('shopactive');
        $this->middleware(function($request, $next) {
            $this->shop = \Auth::user()->shop();
            $this->propertiesProvider = \App::make(DynamicPropertiesProvider::class);
            $this->propertiesProvider->register($this->shop->id);
            \View::share('shop', $this->shop);
            \View::share('propertiesProvider', $this->propertiesProvider);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return redirect('/shop/management/goods');
    }
}