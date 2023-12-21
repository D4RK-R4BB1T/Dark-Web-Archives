<?php

namespace App\Packages\PriceModifier;

interface IPriceModifier {
    function applyModifier($price, $currency, $arguments = []);
}