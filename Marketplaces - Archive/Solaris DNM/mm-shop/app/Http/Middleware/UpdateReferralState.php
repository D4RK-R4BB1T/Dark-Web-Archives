<?php

namespace App\Http\Middleware;

use App\Packages\Referral\ReferralState;
use App\Shop;
use App\User;
use Closure;

class UpdateReferralState
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
        $referralUrl = config('mm2.application_referral_url');
        $isReferralDomain = $referralUrl == $request->getSchemeAndHttpHost();

        /** @var ReferralState $referralState */
        $referralState = app('referral_state');

        if (!$isReferralDomain) {
            $referralState->isReferralUrl = false;
            return $next($request);
        } else {
            $referralState->isReferralUrl = true;
            $isSystemEnabled = Shop::getDefaultShop()->referral_enabled;
            if (!$isSystemEnabled) {
                return response()->view('errors.unavailable');
            }

            $info = $request->session()->get('referral');
            if (!$info || !($invitedBy = User::whereId($info['user_id'])->first())) {
                return response()->view('errors.bad_referral');
            }

            if ($authUser = \Auth::user()) {
                $referralState->fee = $authUser->referral_fee;
                $referralState->invitedBy = $authUser;
            } else {
                $referralState->fee = $info['fee'];
                $referralState->invitedBy = $invitedBy;
            }
            return $next($request);
        }

    }
}
