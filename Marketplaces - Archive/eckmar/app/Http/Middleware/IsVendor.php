<?php

namespace App\Http\Middleware;

use Closure;

class IsVendor
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
        // if logged in user is not vendor
        if(auth() -> check()){
            if (auth()->user()->isVendor())
                return $next($request);
        }

        return redirect() -> route('home');
    }
}
