<?php

namespace BitWasp\Secp256k1Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    static private $context;

    public static function getContext()
    {
        if (self::$context == null) {
            self::$context = \secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        }

        return self::$context;
    }

    public function pack($string)
    {
        if (strlen($string) % 2 !== 0) {
            $string = '0' . $string;
        }

        return pack("H*", $string);
    }

    public function toBinary32($str)
    {
        return str_pad(pack("H*", (string)$str), 32, chr(0), STR_PAD_LEFT);
    }

    public function getPrivate()
    {
        do {
            $key = \openssl_random_pseudo_bytes(32);
        } while (secp256k1_ec_seckey_verify(self::getContext(), $key) == 0);
        return $key;
    }
}
