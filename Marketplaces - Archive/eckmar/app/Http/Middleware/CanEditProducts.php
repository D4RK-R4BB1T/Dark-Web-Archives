<?php

namespace App\Http\Middleware;

use Closure;

class CanEditProducts
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
        if(auth() -> check() && (
            auth() -> user() -> isVendor() ||
            auth() -> user() -> hasPermission('products') ||
            auth() -> user() -> isAdmin()
            ))
            return $next($request);

        return redirect() -> route('home');
    }
}
