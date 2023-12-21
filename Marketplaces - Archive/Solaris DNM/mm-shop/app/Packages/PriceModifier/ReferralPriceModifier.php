<?php

namespace App\Packages\PriceModifier;

use App\Packages\Referral\ReferralState;

class ReferralPriceModifier implements IPriceModifier
{
    /** @var ReferralState */
    private $referralState;

    public function __construct()
    {
        $this->referralState = app('referral_state');
    }

    public function applyModifier($price, $currency, $arguments = [])
    {
        if (!$this->referralState->isEnabled || !$this->referralState->isReferralUrl || empty($this->referralState->fee)) {
            return $price;
        }

        return self::applyFee($price, $currency, $this->referralState->fee);
    }

    public static function applyFee($price, $currency, $fee) {
        // Fee is set by percent amount (e.g. 10) so we need to convert it to fraction.
        $fee = 1 + $fee / 100;

        // Fee can't decrease final amount
        assert($fee >= 1);

        return $price * $fee;
    }
}