<?php
/**
 * File: HomeController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers;

use App\ReferralUrl;
use App\Shop;
use App\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Redirects user to page belongs to his role
     */
    public function index()
    {
        $shop = Shop::getDefaultShop();
        $user = \Auth::user();

        if (!$shop->guest_enabled) {
            $this->middleware('auth');
        }

        if (!$user || in_array($user->role, [User::ROLE_USER, User::ROLE_CATALOG, User::ROLE_TELEGRAM])) {
            return redirect('/shop/' . $shop->slug);
        }

        if ($user->role === User::ROLE_SHOP) {
            $shop = $user->shop();

            if (!$shop->enabled) {
                return redirect('/shop/management/init');
            } else {
                return redirect('/shop/' . $shop->slug);
            }
        }

        // This should never happen
        throw new \Exception('No rule to redirect.');
    }

    public function referral(Request $request, $slug)
    {
        $referralUrl = ReferralUrl::whereSlug($slug)->first();
        if (!$referralUrl) {
            return view('errors.bad_referral');
        }

        $request->session()->put('referral', [
            'user_id' => $referralUrl->user_id,
            'fee' => $referralUrl->fee
        ]);

        return redirect('/');
    }
}