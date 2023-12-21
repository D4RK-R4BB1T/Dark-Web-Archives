<?php

namespace App\Http\Middleware;

use Closure;

class HasAdminPanelAccess
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
        /**
         * Admins and users with permissions has Admin Panel Access
         */
        if(auth() -> check()){
            if (auth()->user()->isAdmin()
                ||  auth() -> user() -> hasPermissions())
                return $next($request);
        }

        return redirect() -> route('home');
    }
}
