<?php

namespace App\Http\Middleware;

use Closure;

class UpdateForceRootURL
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
        if (!empty($gateUrl = $request->header('X-Gate-URL'))) {
            $gateUrl = rtrim(trim($gateUrl), '/');
            \URL::forceRootUrl($gateUrl . '/');
        }

        return $next($request);
    }
}
