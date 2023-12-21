<?php


namespace App\Packages\PriceModifier;


use App\Packages\Utils\BitcoinUtils;
use App\Promocode;

class PromocodePriceModifier implements IPriceModifier
{
    public function applyModifier($price, $currency, $arguments = [])
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return $price;
        }

        if (!isset($arguments['promocode']) || !($arguments['promocode'] instanceof Promocode)) {
            return $price;
        }
        /** @var Promocode $promocode */
        $promocode = $arguments['promocode'];
        if (!$promocode->isActive()) {
            return $price;
        }

        switch ($promocode->discount_mode) {
            case Promocode::DISCOUNT_MODE_PERCENT:
                $percent = ((double) $promocode->percent_amount)/100;
                return max(0, $price * (1 - $percent));

            case Promocode::DISCOUNT_MODE_PRICE:
                $amount = BitcoinUtils::convert($promocode->price_amount, $promocode->price_currency, $currency);
                return max(0, $price - $amount);

            default:
                return $price;
        }

    }

}