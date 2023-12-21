<?php

namespace App\Http\Middleware;

use Closure;

class RedirectIfShopNotActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $shop = null;
        try {
            $shop = \Auth::user()->shop();
        } catch (\Exception $e) {
            \App::abort(403);
        }

        if (!$shop->enabled && $request->path() !== 'shop/management/init') {
            return redirect('/shop/management/init');
        }

        return $next($request);
    }
}
