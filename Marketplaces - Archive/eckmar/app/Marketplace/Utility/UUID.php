<?php


namespace App\Marketplace\Utility;


class UUID
{
    private static function gmp_base_convert(string $value, int $initialBase, int $newBase): string
    {
        return gmp_strval(gmp_init($value, $initialBase), $newBase);
    }
    public static function encode(string $uuid): string
    {
        return self::gmp_base_convert(str_replace('-', '', $uuid), 16, 62);
    }
    public static function decode(string $hashid): string
    {
        return array_reduce([20, 16, 12, 8], function ($uuid, $offset) {
            return substr_replace($uuid, '-', $offset, 0);
        }, str_pad(self::gmp_base_convert($hashid, 62, 16), 32, '0', STR_PAD_LEFT));
    }
}