<?php

namespace App\Http\Middleware;

use App\AdvStatsCache;
use Closure;
use Illuminate\Http\Request;

class AdvStats
{
    public function handle(Request $request, Closure $next)
    {
        $param = 'advstats';
        $cookie = 'advstats';
        $id = $request->cookie($cookie);
        if (is_numeric($id)) {
            AdvStatsCache::add($id);
        } elseif (is_numeric($request->$param)) {
            AdvStatsCache::add($request->$param, 0, 1);
            return redirect($request->fullUrlWithQuery([$param => NULL]))
                ->cookie($cookie, $request->$param);
        }

        return $next($request);
    }
}