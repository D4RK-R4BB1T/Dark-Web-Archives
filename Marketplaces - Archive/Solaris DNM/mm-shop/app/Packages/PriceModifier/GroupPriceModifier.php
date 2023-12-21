<?php


namespace App\Packages\PriceModifier;


use App\User;

class GroupPriceModifier implements IPriceModifier
{

    function applyModifier($price, $currency, $arguments = [])
    {
        if (!isset($arguments['user']) || !($arguments['user'] instanceof User)) {
            return $price;
        }

        /** @var User $user */
        $user = $arguments['user'];

        if (!$user->group_id || !($group = $user->group)) {
            return $price;
        }

        return self::apply($price, $currency, $user->group->percent_amount);
    }

    public static function apply($price, $currency, $percentAmount) {
        $percent = ((double) $percentAmount)/100;
        return max(0, $price * (1 - $percent));
    }


}