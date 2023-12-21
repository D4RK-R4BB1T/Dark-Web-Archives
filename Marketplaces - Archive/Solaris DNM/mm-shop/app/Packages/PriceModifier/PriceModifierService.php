<?php

namespace App\Packages\PriceModifier;

class PriceModifierService
{
    const REFERRAL_MODIFIER = ReferralPriceModifier::class;
    const PROMOCODE_MODIFIER = PromocodePriceModifier::class;
    const GROUP_MODIFIER = GroupPriceModifier::class;

    function apply($price, $currency, $modifiers = [], $arguments = []) {
        $resultPrice = $price;
        foreach ($modifiers as $modifier) {
            $modifierInstance = new $modifier();
            if ($modifierInstance instanceof IPriceModifier) {
                $resultPrice = $modifierInstance->applyModifier($resultPrice, $currency, $arguments);
            }
        }
        return $resultPrice;
    }
}