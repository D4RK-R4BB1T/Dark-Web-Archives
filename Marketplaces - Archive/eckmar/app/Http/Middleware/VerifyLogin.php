<?php

namespace App\Http\Middleware;

use Closure;

class VerifyLogin
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
        // if user is logged in and has not checked the validation string
        if(auth() -> check() && auth() -> user() -> login_2fa && session() -> has('login_validation_string'))
            return redirect() -> route('auth.verify');
        return $next($request);
    }
}
