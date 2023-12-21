<?php

namespace App\Http\Controllers;

use App\Good;
use App\Packages\CatalogSync\CatalogSynchronization;
use App\Shop;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

class CatalogController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        if (!Shop::getDefaultShop()->guest_enabled) {
            $this->middleware('auth');
        }

        \View::share('page', 'catalog');
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return redirect('/shop/' . Shop::getDefaultShop()->slug);

//        $goods = Good::where('has_quests', true)
//            ->applySearchFilters($request)
//            ->with(['shop', 'city', 'availablePackages'])
//            ->get();
//
//        return view('catalog.index', [
//            'goods' => $goods
//        ]);
    }

    public function notificationsRead(Request $request)
    {
        if ($request->get('_token') !== csrf_token()) {
            throw new TokenMismatchException;
        }

        $user = Auth::user();
        $user->notification_last_read_at = Carbon::now();
        $user->save();
        return redirect()->back();
    }
}
