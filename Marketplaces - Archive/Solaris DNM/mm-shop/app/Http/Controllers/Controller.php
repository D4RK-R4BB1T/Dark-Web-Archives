<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        \View::share('page', '');
        $this->middleware('service');
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                \View::share('unreadNotifications', Auth::user()->unreadNotifications());
            }
            return $next($request);
        });
    }

    protected function getRedirectUrl()
    {
        if (request()->has('redirect_to')) {
            try {
                return url(request()->get('redirect_to'));
            } catch (\Exception $e) {
                return app(UrlGenerator::class)->previous();
            }
        } else {
            return app(UrlGenerator::class)->previous();
        }
    }

    protected function parseDayPeriod($request, $defaultStartDate = null, $defaultEndDate = null, $minStartDate = null, $periodStartKey = 'period_start', $periodEndKey = 'period_end')
    {
        if ($request->has($periodStartKey)) {
            $this->validate($request, [
                $periodStartKey => 'date'
            ]);
        }

        if ($request->has($periodEndKey)) {
            $this->validate($request, [
                $periodEndKey => 'date'
            ]);
        }

        $defaultStartDate = $defaultStartDate ?: Carbon::now()->startOfDay();
        $periodStart = $request->has($periodStartKey)
            ? (new Carbon($request->get($periodStartKey)))->startOfDay()
            : $defaultStartDate;

        $defaultEndDate = $defaultEndDate ?: Carbon::now()->endOfDay();
        $periodEnd = $request->has($periodEndKey)
            ? (new Carbon($request->get($periodEndKey)))->endOfDay()
            : $defaultEndDate;

        if ($minStartDate !== null) {
            if ($periodStart->lessThan($minStartDate)) {
                $periodStart = clone $minStartDate;
            }
        }

        if ($periodEnd->lessThan($periodStart)) {
            $periodEnd = (clone $periodStart)->endOfDay();
        }

        return [$periodStart, $periodEnd];
    }
}
