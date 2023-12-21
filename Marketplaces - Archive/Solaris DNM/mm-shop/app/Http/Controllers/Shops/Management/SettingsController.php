<?php
/**
 * File: SettingsController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Http\Requests\ShopSettingsAppearanceRequest;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use phpDocumentor\Reflection\Types\Null_;

class SettingsController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-settings');
            return $next($request);
        });

        \View::share('page', 'settings');
    }

    public function index(Request $request)
    {
        return redirect('/shop/management/settings/appearance');
    }

    public function showAppearanceForm(Request $request)
    {
        \View::share('section', 'appearance');
        return view('shop.management.settings.appearance');
    }

    public function appearance(ShopSettingsAppearanceRequest $request)
    {
        $this->shop->update([
            'title' => $request->get('title'),
            'image_url' => !empty($request->imageURL) ? $request->imageURL : $this->shop->image_url,
            'banner_url' => !empty($request->bannerURL) ? $request->bannerURL : $this->shop->banner_url,
            'guest_enabled' => $request->has('guest_enabled')
        ]);

        return redirect('/shop/management/settings/appearance')->with('flash_success', 'Настройки отредактированы.');
    }

    public function appearanceDeleteAvatar(Request $request)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }

        $this->shop->update([
            'image_url' => NULL
        ]);

        return redirect('/shop/management/settings/appearance')->with('flash_success', 'Аватар удален.');
    }

    public function appearanceDeleteBanner(Request $request)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }

        $this->shop->update([
            'banner_url' => NULL
        ]);

        return redirect('/shop/management/settings/appearance')->with('flash_success', 'Баннер удален.');
    }

    public function showBlocksForm(Request $request)
    {
        \View::share('section', 'blocks');
        return view('shop.management.settings.blocks');
    }

    public function blocks(Request $request)
    {
        $this->validate($request, [
            'information' => 'max:3000',
            'problem' => 'max:3000'
        ]);

        $this->shop->update([
            'information' => $request->get('information'),
            'problem' => $request->get('problem'),
            'search_enabled' => $request->has('search_enabled'),
            'categories_enabled' => $request->has('categories_enabled')
        ]);

        return redirect('/shop/management/settings/blocks')->with('flash_success', 'Настройки сохранены.');
    }

    public function showReferralForm(Request $request) {
        \View::share('section', 'referral');
        return view('shop.management.settings.referral');
    }

    public function referral(Request $request) {
        $this->shop->update([
            'referral_enabled' => $request->has('referral_enabled')
        ]);
        return redirect('/shop/management/settings/referral')->with('flash_success', 'Настройки сохранены.');
    }
}