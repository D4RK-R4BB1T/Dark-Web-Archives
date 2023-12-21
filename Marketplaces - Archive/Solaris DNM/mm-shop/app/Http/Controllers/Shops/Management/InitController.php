<?php
/**
 * File: InitController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Http\Controllers\Controller;
use App\Http\Requests\ShopInitRequest;

class InitController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showInitForm()
    {
        return view('shop.management.init');
    }

    public function init(ShopInitRequest $request)
    {
        $this->shop->update([
            'title' => $request->get('title'),
            'slug' => $request->get('slug'),
            'enabled' => true,
            'integrations_catalog' => true, // true is default now
            'image_url' => !empty($request->imageURL) ? $request->imageURL : null
        ]);

        $this->shop->save();

        return redirect('/shop/' . $this->shop->slug)->with('flash_success', 'Магазин успешно создан!');
    }
}