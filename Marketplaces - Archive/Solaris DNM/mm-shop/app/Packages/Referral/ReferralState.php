<?php

namespace App\Packages\Referral;

use App\User;
use App\Shop;

class ReferralState
{
    public $isEnabled = false;

    // See: App\Http\Middleware\UpdateReferralState
    public $isReferralUrl = false;
    /** @var float */
    public $fee = 0;
    /** @var User */
    public $invitedBy = null;

    function __construct()
    {
        $this->isEnabled = (bool) Shop::getDefaultShop()->referral_enabled;
    }
}