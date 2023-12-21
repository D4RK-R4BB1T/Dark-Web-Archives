<?php


namespace App\Marketplace\Utility;
use Illuminate\Support\Facades\Cache;


class CoinConverter
{

    /**
     * Converting from one Symbol to another in amount
     *
     * @param string $fromSym
     * @param string $toSym
     * @param float $amount
     * @return float|int
     */
    public static function conversion(string $fromSym, string $toSym, float $amount)
    {

        $coinPrice = Cache::remember($fromSym . '_' . $toSym . '_price',
            config('coins.caching_price_interval'),
            function() use($fromSym, $toSym){
                // get from symb price
                $url = "https://min-api.cryptocompare.com/data/price?fsym=$fromSym&tsyms=$toSym";
                $json = json_decode(file_get_contents($url), true);
                $coin_price = $json[$toSym];

                return $coin_price;
            }
        );
        // calculate bitcoins and store
        return $amount * $coinPrice;
    }

}