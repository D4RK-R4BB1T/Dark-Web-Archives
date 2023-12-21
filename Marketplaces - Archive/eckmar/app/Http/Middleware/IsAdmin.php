<?php

namespace App\Http\Middleware;

use Closure;

class IsAdmin
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
        // If user is not admin
        if(auth() -> check()){
            if (auth()->user()->isAdmin())
                return $next($request);
        }

        return redirect() -> route('home');

    }
}
