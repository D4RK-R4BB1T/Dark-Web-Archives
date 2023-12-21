<?php
/**
 * File: PendingController.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Packages\Utils\BitcoinUtils;

class PendingController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Show the account created view.
     *
     */
    public function show()
    {
        $user = \Auth::user();

        if (!$user->active) {
            return view('auth.user_pending');
        }
        
        if ($user->role === \App\User::ROLE_SHOP_PENDING) {
            if (!BitcoinUtils::isPaymentsEnabled()) {
                return view('auth.shop_pending_payment');
            }

            $balance = $user->getExpectedBalance(BitcoinUtils::CURRENCY_USD);
            
            // no payments - just showing request for payment
            if ($balance === 0) {
                return view('auth.shop_pending_payment');
            }
            
            // has payments, but money is not enough
            if ($balance <= config('mm2.shop_usd_price') * config('mm2.shop_usd_price_approx')) {
                \Session::flash('not_enough_money', $balance);
                return view('auth.shop_pending_payment');
            }
            
            return view('auth.shop_pending');
        }

        $path = \Session::pull('url.intended', '/');
        return redirect()->to(url($path));
    }
}