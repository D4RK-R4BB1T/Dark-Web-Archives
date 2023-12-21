<?php

namespace App\Http\Middleware;

use Closure;

class CanReadMessages
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

        if ($request->otherParty){
            session()->put('new_conversation_other_party',$request->otherParty);
        }
        if(!session()->has('private_rsa_key_decrypted'))
            return redirect()->route('profile.messages.decrypt.show');
        return $next($request);
    }
}
