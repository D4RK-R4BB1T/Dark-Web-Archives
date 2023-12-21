<?php

namespace BitWasp\BitcoinLib;

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Primitives\PointInterface;
use Mdanter\Ecc\Crypto\Signature\Signature;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\Crypto\Key\PublicKey;

/**
 * BitcoinLib
 *
 * This library is largely a rewrite of theymos' bitcoin library,
 * along with some more functions for key manipulation.
 *
 * It depends on php-ecc, written by Mathyas Danter.
 *
 * Thomas Kerin
 */
class BitcoinLib
{

    /**
     * Base58Chars
     *
     * This is a string containing the allowed characters in base58.
     */
    private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /**
     * magic_byte presets
     *
     * MAKE SURE alphabetic chars in the HEX are LOWERCASE!
     *
     * @var array
     */
    private static $magic_byte_presets = array(
        'bitcoin' => '00|05',
        'bitcoin-testnet' => '6f|c4',
    );

    /**
     * use self::magicByte() instead of directly accessing this property
     *  which will initiated this to the 'bitcoin' preset on first call
     *
     * @var string
     */
    private static $magic_byte;

    /**
     * use self::magicByte('p2sh') instead of directly accessing this property
     *  which will initiated this to the 'bitcoin' 'p2sh' preset on first call
     *
     * @var string
     */
    private static $magic_p2sh_byte;

    public static function setMagicByteDefaults($magic_byte_defaults)
    {
        if (isset(self::$magic_byte_presets[$magic_byte_defaults])) {
            $magic_byte_defaults = self::$magic_byte_presets[$magic_byte_defaults];
        }

        $magic_byte_defaults = explode('|', $magic_byte_defaults);

        if (count($magic_byte_defaults) != 2) {
            throw new \InvalidArgumentException("magic_byte_defaults should magic_byte|magic_p2sh_byte");
        }

        self::$magic_byte = $magic_byte_defaults[0];
        self::$magic_p2sh_byte = $magic_byte_defaults[1];
    }

    /**
     * normalizes magic_byte
     *
     * accepted:
     *  - '<magic_byte>' -> <magic_byte>
     *  - NULL           -> default magic_byte
     *  - '<alias>'      -> magic_byte for specified alias
     *  - 'p2sh'         -> default magic_p2sh_byte
     *
     * @param   string $magic_byte
     * @return  string
     * @throws  \Exception
     */
    public static function magicByte($magic_byte = null)
    {
        if (self::$magic_byte === null) {
            self::setMagicByteDefaults('bitcoin');
        }

        if ($magic_byte === null) {
            return self::$magic_byte;
        }

        if (strlen($magic_byte) == 5 && strpos($magic_byte, '|') == 2) {
            $magic_byte = explode('|', $magic_byte);
            return $magic_byte[0];
        }

        if (strlen($magic_byte) == 2) {
            return $magic_byte;
        }

        if ($magic_byte == 'p2sh') {
            return self::magicP2SHByte();
        }

        if (isset(self::$magic_byte_presets[$magic_byte])) {
            $preset_magic_byte = explode('|', self::$magic_byte_presets[$magic_byte]);
            return $preset_magic_byte[0];
        }

        throw new \InvalidArgumentException("Failed to determine magic_byte");
    }


    /**
     * normalizes magic_p2sh_byte
     *
     * accepted:
     *  - '<magic_p2sh_byte>' -> <magic_p2sh_byte>
     *  - NULL                -> default magic_p2sh_byte
     *  - '<alias>'           -> magic_p2sh_byte for specified alias
     *
     * @param   string $magic_byte
     * @return  string
     * @throws  \Exception
     */
    public static function magicP2SHByte($magic_byte = null)
    {
        if (self::$magic_p2sh_byte === null) {
            self::setMagicByteDefaults('bitcoin');
        }

        if ($magic_byte === null) {
            return self::$magic_p2sh_byte;
        }

        if (strlen($magic_byte) == 5 && strpos($magic_byte, '|') == 2) {
            $magic_byte = explode('|', $magic_byte);
            return $magic_byte[1];
        }

        if (strlen($magic_byte) == 2) {
            return $magic_byte;
        }


        if (isset(self::$magic_byte_presets[$magic_byte])) {
            $preset_magic_byte = explode('|', self::$magic_byte_presets[$magic_byte]);
            return $preset_magic_byte[1];
        }

        throw new \InvalidArgumentException("Failed to determine magic_p2sh_byte");
    }

    /**
     * normalizes magic_p2sh_byte pair
     *
     * accepted:
     *  - '<magic_byte>|<magic_p2sh_byte>' -> [<magic_byte>, <magic_p2sh_byte>]
     *  - '<magic_byte>'                   -> default pair if '<magic_byte>' is the default magic_byte
     *  - NULL                             -> default pair
     *  - '<alias>'                        -> pair for specified alias
     *
     * @param   string $magic_byte_pair
     * @return  string[]                    [magic_byte, magic_p2sh_byte]
     * @throws  \Exception
     */
    public static function magicBytePair($magic_byte_pair = null)
    {
        if (self::$magic_byte === null || self::$magic_p2sh_byte === null) {
            self::setMagicByteDefaults('bitcoin');
        }

        if ($magic_byte_pair === null) {
            return array(self::$magic_byte, self::$magic_p2sh_byte);
        }

        if (strlen($magic_byte_pair) == 5 && strpos($magic_byte_pair, '|') == 2) {
            return explode('|', $magic_byte_pair);
        }

        if (isset(self::$magic_byte_presets[$magic_byte_pair])) {
            $preset_magic_byte = explode('|', self::$magic_byte_presets[$magic_byte_pair]);
            return $preset_magic_byte;
        }

        throw new \InvalidArgumentException("Failed to determine magic_byte_pair");
    }

    /**
     * Hex Encode
     *
     * Encodes a decimal $number into a hexadecimal string.
     *
     * @param    int $number
     * @return    string
     */
    public static function hex_encode($number)
    {
        $hex = gmp_strval(gmp_init($number, 10), 16);
        return (strlen($hex) % 2 != 0) ? '0' . $hex : $hex;
    }

    /**
     * Hex Decode
     *
     * Decodes a hexadecimal $hex string into a decimal number.
     *
     * @param    string $hex
     * @return    int
     */
    public static function hex_decode($hex)
    {
        return gmp_strval(gmp_init($hex, 16), 10);
    }

    /**
     * Base58 Decode
     *
     * This function accepts a base58 encoded string, and decodes the
     * string into a number, which is converted to hexadecimal. It is then
     * padded with zero's.
     *
     * @param $base58
     * @return string
     */
    public static function base58_decode($base58)
    {
        $origbase58 = $base58;
        $return = "0";

        for ($i = 0; $i < strlen($base58); $i++) {
            // return = return*58 + current position of $base58[i]in self::$base58chars
            $return = gmp_add(gmp_mul($return, 58), strpos(self::$base58chars, $base58[$i]));
        }
        $return = gmp_strval($return, 16);
        for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
            $return = "00" . $return;
        }
        if (strlen($return) % 2 != 0) {
            $return = "0" . $return;
        }
        return $return;

    }

    /**
     * Base58 Encode
     *
     * Encodes a $hex string in base58 format. Borrowed from prusnaks
     * addrgen code: https://github.com/prusnak/addrgen/blob/master/php/addrgen.php
     *
     * @param    string $hex
     * @return    string
     * @author    Pavel Rusnak
     */
    public static function base58_encode($hex)
    {
        if (strlen($hex) == 0) {
            return '';
        }

        // Convert the hex string to a base10 integer
        $num = gmp_strval(gmp_init($hex, 16), 58);

        // Check that number isn't just 0 - which would be all padding.
        if ($num != '0') {
            $num = strtr(
                $num,
                '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv',
                self::$base58chars
            );
        } else {
            $num = '';
        }

        // Pad the leading 1's
        $pad = '';
        $n = 0;
        while (substr($hex, $n, 2) == '00') {
            $pad .= '1';
            $n += 2;
        }

        return $pad . $num;
    }


    /**
     * Base58 Encode Checksum
     *
     * This function takes a checksum of the input $hex data, concatenates
     * it with the input, and returns a base58 encoded string with checksum.
     *
     * @param    string $hex
     * @return    string
     */
    public static function base58_encode_checksum($hex)
    {
        $checksum = self::hash256($hex);
        $checksum = substr($checksum, 0, 8);
        $hash = $hex . $checksum;
        return self::base58_encode($hash);
    }

    /**
     * Base58 Decode Checksum
     *
     * Returns the original hex data that was encoded in base58 check format.
     *
     * @param    string $base58
     * @return    string
     */
    public static function base58_decode_checksum($base58)
    {
        $hex = self::base58_decode($base58);
        return substr($hex, 2, strlen($hex) - 10);
    }

    /**
     * Hash256
     *
     * Takes a sha256(sha256()) hash of the $string. Intended only for
     * hex strings, as it is packed into raw bytes.
     *
     * @param    string $string
     * @return  string
     */
    public static function hash256($string)
    {
        $bs = @pack("H*", $string);
        return hash("sha256", hash("sha256", $bs, true));
    }

    /**
     * Hash160
     *
     * Takes $data as input and returns a ripemd160(sha256()) hash of $string.
     * Intended for only hex strings, as it is packed into raw bytes.
     *
     * @param    string $string
     * @return    string
     */
    public static function hash160($string)
    {
        $bs = @pack("H*", $string);
        return hash("ripemd160", hash("sha256", $bs, true));
    }

    /**
     * Hash160 To Address
     *
     * This function accepts an $address_version (used to specify the
     * protocol or the purpose of the address) which is concatenated with
     * the $hash160 string, and converted to the basee58 encoded format
     * (with a checksum)
     *
     * @param    string $hash160
     * @param    string $address_version
     * @return    string
     */
    public static function hash160_to_address($hash160, $address_version = null)
    {
        $hash160 = self::magicByte($address_version) . $hash160;
        return self::base58_encode_checksum($hash160);
    }

    /**
     * Public Key To Address
     *
     * This function accepts the $public_key, and $address_version (used
     * to specify the protocol or purpose for the address) as input, and
     * returns a bitcoin address by taking the hash160 of the $public_key,
     * and converting this to a base_
     *
     * @param    string $public_key
     * @param    string $address_version
     * @return    string
     */
    public static function public_key_to_address($public_key, $address_version = null)
    {
        $hash160 = self::hash160($public_key);
        return self::hash160_to_address($hash160, self::magicByte($address_version));
    }

    /**
     * Get New Private Key
     *
     * This function generates a new private key, a number from 1 to $n.
     * Once it finds an acceptable value, it will encode it in hex, pad it,
     * and return the private key.
     *
     * @return    string
     */
    public static function get_new_private_key()
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();

        $privKey = gmp_strval(gmp_init(bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)), 16));
        while ($math->cmp($privKey, $g->getOrder()) !== -1) {
            $privKey = gmp_strval(gmp_init(bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)), 16));
        }

        $privKeyHex = $math->dechex($privKey);
        return str_pad($privKeyHex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * Private Key To Public Key
     *
     * Accepts a $privKey as input, and does EC multiplication to obtain
     * a new point along the curve. The X and Y coordinates are the public
     * key, which are returned as a hexadecimal string in uncompressed
     * format.
     *
     * @param    string  $privKey
     * @param    boolean $compressed
     * @return    string
     */
    public static function private_key_to_public_key($privKey, $compressed = false)
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();
        $privKey = self::hex_decode($privKey);

        $secretG = $g->mul($privKey);

        $xHex = self::hex_encode($secretG->getX());
        $yHex = self::hex_encode($secretG->getY());

        $xHex = str_pad($xHex, 64, '0', STR_PAD_LEFT);
        $yHex = str_pad($yHex, 64, '0', STR_PAD_LEFT);
        $public_key = '04' . $xHex . $yHex;

        return ($compressed == true) ? self::compress_public_key($public_key) : $public_key;
    }

    /**
     * Private Key To Address
     *
     * Converts a $privKey to the corresponding public key, and then
     * converts to the bitcoin address, using the $address_version.
     *
     * @param $private_key
     * @param $address_version
     * @return string
     */
    public static function private_key_to_address($private_key, $address_version = null)
    {
        $address_version = self::magicByte($address_version);

        $public_key = self::private_key_to_public_key($private_key);
        return self::public_key_to_address($public_key, self::magicByte($address_version));
    }

    /**
     * Get New Key Pair
     *
     * Generate a new private key, and convert to an uncompressed public key.
     *
     * @return array
     */
    public static function get_new_key_pair()
    {
        $private_key = self::get_new_private_key();
        $public_key = self::private_key_to_public_key($private_key);

        return array('privKey' => $private_key,
            'pubKey' => $public_key);
    }

    /**
     * Get New Key Set
     *
     * This function requires the $address_version to be supplied in order
     * to generate the correct privateWIF and pubAddress. It returns an
     * array containing the hex private key, WIF private key, public key,
     * and bitcoin address
     *
     * @param      $address_version
     * @param bool $compressed
     * @return array
     */
    public static function get_new_key_set($address_version = null, $compressed = false)
    {
        $address_version = self::magicByte($address_version);

        do {
            $key_pair = self::get_new_key_pair();
            $private_WIF = self::private_key_to_WIF($key_pair['privKey'], $compressed, $address_version);

            if ($compressed == true) {
                $key_pair['pubKey'] = self::compress_public_key($key_pair['pubKey']);
            }

            $public_address = self::public_key_to_address($key_pair['pubKey'], $address_version);
        } while (!self::validate_address($public_address, $address_version));

        return array(
            'privKey' => $key_pair['privKey'],
            'pubKey' => $key_pair['pubKey'],
            'privWIF' => $private_WIF,
            'pubAdd' => $public_address
        );
    }

    /**
     * Get Private Address Version
     *
     * This function
     * Generates a private key address version (the prefix) from the
     * supplied public key address version, by adding 0x80 to the number.
     *
     * @param    string $address_version
     * @return    string
     */
    public static function get_private_key_address_version($address_version = null)
    {
        $address_version = self::magicByte($address_version);

        return gmp_strval(
            gmp_add(
                gmp_init($address_version, 16),
                gmp_init('80', 16)
            ),
            16
        );
    }

    /**
     * Private Key To WIF
     *
     * Converts a hexadecimal $privKey to WIF key, using the $address_version
     * to yield the correct privkey version byte for that network (byte+0x80).
     *
     * $compressed = true will yield the private key for the compressed
     * public key address.
     *
     * @param      $privKey
     * @param bool $compressed
     * @param      $address_version
     * @return string
     */
    public static function private_key_to_WIF($privKey, $compressed = false, $address_version = null)
    {
        $key = $privKey . (($compressed == true) ? '01' : '');
        return self::hash160_to_address($key, self::get_private_key_address_version(self::magicByte($address_version)));
    }

    /**
     * WIF To Private Key
     *
     * Convert a base58 encoded $WIF private key to a hexadecimal private key.
     *
     * @param    string $WIF
     * @return    string
     */
    public static function WIF_to_private_key($WIF)
    {
        $decode = self::base58_decode($WIF);
        return array('key' => substr($decode, 2, 64),
            'is_compressed' => (((strlen($decode) - 10) == 66 && substr($decode, 66, 2) == '01') ? true : false));
    }

    /**
     * Import Public Key
     *
     * Imports an arbitrary $public_key, and returns it untreated if the
     * left-most bit is '04', or else decompressed the public key if the
     * left-most bit is '02' or '03'.
     *
     * @param    string $public_key
     * @return    string
     */
    public static function import_public_key($public_key)
    {
        $first = substr($public_key, 0, 2);
        if (($first == '02' || $first == '03') && strlen($public_key) == '66') {
            // Compressed public key, need to decompress.
            $decompressed = self::decompress_public_key($public_key);
            return ($decompressed == false) ? false : $decompressed['public_key'];
        } else if ($first == '04') {
            // Regular public key, pass back untreated.
            return $public_key;
        }

        throw new \InvalidArgumentException("Invalid public key");
    }

    /**
     * Compress Public Key
     *
     * Converts an uncompressed public key to the shorter format. These
     * compressed public key's have a prefix of 02 or 03, indicating whether
     * Y is odd or even (tested by gmp_mod2(). With this information, and
     * the X coordinate, it is possible to regenerate the uncompressed key
     * at a later stage.
     *
     * @param    string $public_key
     * @return    string
     */
    public static function compress_public_key($public_key)
    {
        $math = EccFactory::getAdapter();

        $x_hex = substr($public_key, 2, 64);
        $y = $math->hexDec(substr($public_key, 66, 64));

        $parity = $math->mod($y, 2);
        return (($parity == 0) ? '02' : '03') . $x_hex;

    }

    /**
     * Decompress Public Key
     *
     * Accepts a y_byte, 02 or 03 indicating whether the Y coordinate is
     * odd or even, and $passpoint, which is simply a hexadecimal X coordinate.
     * Using this data, it is possible to deconstruct the original
     * uncompressed public key.
     *
     * @param $key
     * @return array|bool
     */
    public static function decompress_public_key($key)
    {
        $math = EccFactory::getAdapter();
        $y_byte = substr($key, 0, 2);
        $x_coordinate = substr($key, 2);

        $x = self::hex_decode($x_coordinate);

        $theory = EccFactory::getNumberTheory($math);
        $generator = EccFactory::getSecgCurves($math)->generator256k1();
        $curve = $generator->getCurve();

        try {
            $x3 = $math->powmod($x, 3, $curve->getPrime());

            $y2 = $math->add($x3, $curve->getB());

            $y0 = $theory->squareRootModP($y2, $curve->getPrime());

            if ($y0 == null) {
                throw new \InvalidArgumentException("Invalid public key");
            }

            $y1 = $math->sub($curve->getPrime(), $y0);

            $y = ($y_byte == '02')
                ? (($math->mod($y0, 2) == '0') ? $y0 : $y1)
                : (($math->mod($y0, 2) !== '0') ? $y0 : $y1);

            $y_coordinate = str_pad($math->decHex($y), 64, '0', STR_PAD_LEFT);
            $point = $curve->getPoint($x, $y);

        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid public key");
        }

        return array(
            'x' => $x_coordinate,
            'y' => $y_coordinate,
            'point' => $point,
            'public_key' => '04' . $x_coordinate . $y_coordinate
        );
    }

    /**
     * Validate Public Key
     *
     * Validates a public key by attempting to create a point on the
     * secp256k1 curve.
     *
     * @param    string $public_key
     * @return    boolean
     */
    public static function validate_public_key($public_key)
    {
        if (strlen($public_key) == '66') {
            // Compressed key
            // Attempt to decompress the public key. If the point is not
            // generated, or the function fails, then the key is invalid.
            $decompressed = self::decompress_public_key($public_key);
            return $decompressed == true;
        } else if (strlen($public_key) == '130') {
            $math = EccFactory::getAdapter();
            $generator = EccFactory::getSecgCurves($math)->generator256k1();
            // Uncompressed key, try to create the point

            $x = $math->hexDec(substr($public_key, 2, 64));
            $y = $math->hexDec(substr($public_key, 66, 64));

            // Attempt to create the point. Point returns false in the
            // constructor if anything is invalid.
            try {
                $generator->getCurve()->getPoint($x, $y);
                return true;
            } catch (\Exception $e) {
                return false;
            }

        }

        return false;
    }

    /**
     * Validate Address
     *
     * This function accepts a base58check encoded $address, which is
     * decoded and checked for validity. Returns FALSE for an invalid
     * address, otherwise returns TRUE;
     *
     * @param    string $address
     * @param    string $magic_byte
     * @param    string $magic_p2sh_byte
     * @return    boolean
     */
    public static function validate_address($address, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $magic_byte = $magic_byte !== false ? self::magicByte($magic_byte) : false;
        $magic_p2sh_byte = $magic_p2sh_byte !== false ? self::magicP2SHByte($magic_p2sh_byte) : false;

        // Check the address is decoded correctly.
        $decode = self::base58_decode($address);
        if (strlen($decode) !== 50) {
            return false;
        }

        // Compare the version.
        $version = substr($decode, 0, 2);
        if ($version !== $magic_byte && $version !== $magic_p2sh_byte) {
            return false;
        }

        // Finally compare the checksums.
        return substr($decode, -8) == substr(self::hash256(substr($decode, 0, 42)), 0, 8);
    }

    /**
     * Validate WIF
     *
     * This function validates that a WIFs checksum validates, and that
     * the private key is a valid number within the range 1 - n
     *
     * $ver is unused at the moment.
     *
     * @param    string $wif
     * @param    string $ver
     * @return    boolean
     */
    public static function validate_WIF($wif, $ver = null)
    {
        $hex = self::base58_decode($wif);

        // Learn checksum
        $crc = substr($hex, -8);
        $hex = substr($hex, 0, -8);

        // Learn version
        $version = substr($hex, 0, 2);
        $hex = substr($hex, 2);

        if ($ver !== null && $ver !== $version) {
            return false;
        }

        // Determine if pubkey is compressed
        $compressed = false;
        if (strlen($hex) == 66 && substr($hex, 64, 2) == '01') {
            $compressed = true;
            $hex = substr($hex, 0, 64);
        }

        // Check private key within limit.
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();
        $n = $generator->getOrder();
        if ($math->cmp($math->hexDec($hex), $n) > 0) {
            return false;
        }

        // Calculate checksum for what we have, see if it matches.
        $checksum = self::hash256($version . $hex . (($compressed) ? '01' : ''));
        $checksum = substr($checksum, 0, 8);
        return $checksum == $crc;
    }

    /**
     * sign a message with specified private key
     *
     * @param      $message
     * @param      $privateKey
     * @param null $k               used for testing, don't use it!
     * @return string
     * @throws \Exception
     */
    public static function signMessage($message, $privateKey, $k = null)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();

        $messageHash = "\x18Bitcoin Signed Message:\n" . hex2bin(RawTransaction::_encode_vint(strlen($message))) . $message;
        $messageHash = hash('sha256', hash('sha256', $messageHash, true), true);
        $messageHash = $math->hexDec(bin2hex($messageHash));
        $key_dec = $math->hexDec($privateKey['key']);

        $pubKey = self::private_key_to_public_key($privateKey['key'], false);
        $x = $math->hexDec(substr($pubKey, 2, 64));
        $y = $math->hexDec(substr($pubKey, 66, 64));
        $point = $generator->getCurve()->getPoint($x, $y);

        $_publicKey = new PublicKey($math, $generator, $point);
        $_privateKey = $generator->getPrivateKeyFrom($key_dec);
        $signer = new Signer($math);
        $sign = $signer->sign($_privateKey, $messageHash, $k ?: $math->hexDec((string)bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM))));

        // calculate the recovery param
        //  there should be a way to get this when signing too, but idk how ...
        $i = self::calcPubKeyRecoveryParam($sign->getR(), $sign->getS(), $messageHash, $_publicKey->getPoint());

        return base64_encode(self::encodeMessageSignature($sign, $i, true));
    }

    /**
     * attempt to calculate the public key recovery param by trial and error
     *
     * @param                $r
     * @param                $s
     * @param                $e
     * @param PointInterface $Q
     * @return int
     * @throws \Exception
     */
    private static function calcPubKeyRecoveryParam($r, $s, $e, PointInterface $Q)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();

        for ($i = 0; $i < 4; $i++) {
            if ($pubKey = self::recoverPubKey($r, $s, $e, $i, $generator)) {
                if ($pubKey->getPoint()->getX() == $Q->getX() && $pubKey->getPoint()->getY() == $Q->getY()) {
                    return $i;
                }
            }
        }

        throw new \Exception("Failed to find valid recovery factor");
    }

    /**
     * encode a message signature
     *
     * @param Signature $signature
     * @param           $i
     * @param bool      $compressed
     * @return string
     * @throws \Exception
     */
    private static function encodeMessageSignature(Signature $signature, $i, $compressed = false)
    {
        if (!$compressed) {
            throw new \Exception("Uncompressed message signing not supported!");
        }

        $val = $i + 27;
        if ($compressed) {
            $val += 4;
        }

        return hex2bin(implode("", [
            BitcoinLib::hex_encode($val),
            str_pad(BitcoinLib::hex_encode($signature->getR()), 64, '0', STR_PAD_LEFT),
            str_pad(BitcoinLib::hex_encode($signature->getS()), 64, '0', STR_PAD_LEFT),
        ]));
    }

    /**
     * based on php-bitcoin-signature-routines implementation (which is based on bitcoinjs-lib's implementation)
     * which is SEC 1: Elliptic Curve Cryptography, section 4.1.6, "Public Key Recovery Operation"
     * http://www.secg.org/sec1-v2.pdf
     *
     * @param                $r
     * @param                $s
     * @param                $e
     * @param                $recoveryFlags
     * @param GeneratorPoint $G
     * @return bool|PublicKey
     */
    private static function recoverPubKey($r, $s, $e, $recoveryFlags, GeneratorPoint $G)
    {
        $math = EccFactory::getAdapter();

        $isYEven = ($recoveryFlags & 1) != 0;
        $isSecondKey = ($recoveryFlags & 2) != 0;
        $curve = $G->getCurve();
        $signature = new Signature($r, $s);

        // Precalculate (p + 1) / 4 where p is the field order
        $p_over_four = $math->div($math->add($curve->getPrime(), 1), 4);

        // 1.1 Compute x
        if (!$isSecondKey) {
            $x = $r;
        } else {
            $x = $math->add($r, $G->getOrder());
        }

        // 1.3 Convert x to point
        $alpha = $math->mod($math->add($math->add($math->pow($x, 3), $math->mul($curve->getA(), $x)), $curve->getB()), $curve->getPrime());
        $beta = $math->powmod($alpha, $p_over_four, $curve->getPrime());

        // If beta is even, but y isn't or vice versa, then convert it,
        // otherwise we're done and y == beta.
        if (($math->mod($beta, 2) == 0) == $isYEven) {
            $y = $math->sub($curve->getPrime(), $beta);
        } else {
            $y = $beta;
        }

        // 1.4 Check that nR is at infinity (implicitly done in constructor)
        $R = $G->getCurve()->getPoint($x, $y);

        $point_negate = function (PointInterface $p) use ($math, $G) {
            return $G->getCurve()->getPoint($p->getX(), $math->mul($p->getY(), -1));
        };

        // 1.6.1 Compute a candidate public key Q = r^-1 (sR - eG)
        $rInv = $math->inverseMod($r, $G->getOrder());
        $eGNeg = $point_negate($G->mul($e));
        $Q = $R->mul($s)->add($eGNeg)->mul($rInv);

        // 1.6.2 Test Q as a public key
        $signer = new Signer($math);
        $Qk = new PublicKey($math, $G, $Q);
        if ($signer->verify($Qk, $signature, $e)) {
            return $Qk;
        }

        return false;
    }

    /**
     * verify a signed message
     *
     * @param $address
     * @param $signature
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public static function verifyMessage($address, $signature, $message)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();

        // extract parameters
        $address = substr(hex2bin(self::base58_decode($address)), 0, -4);
        if (strlen($address) != 21 || $address[0] != hex2bin(self::magicByte())) {
            throw new \InvalidArgumentException('invalid Bitcoin address');
        }

        $signature = base64_decode($signature, true);
        if ($signature === false) {
            throw new \InvalidArgumentException('invalid base64 signature');
        }

        if (strlen($signature) != 65) {
            throw new \InvalidArgumentException('invalid signature length');
        }

        $recoveryFlags = ord($signature[0]) - 27;

        if ($recoveryFlags < 0 || $recoveryFlags > 7) {
            throw new \InvalidArgumentException('invalid signature type');
        }

        $isCompressed = ($recoveryFlags & 4) != 0;

        // message is <varInt><prefix><varInt><message>
        $messageHash = "\x18Bitcoin Signed Message:\n" . hex2bin(RawTransaction::_encode_vint(strlen($message))) . $message;
        $messageHash = hash('sha256', hash('sha256', $messageHash, true), true);

        try {
            $pubkey = self::recoverPubKey(
                $math->hexDec(bin2hex(substr($signature, 1, 32))),
                $math->hexDec(bin2hex(substr($signature, 33, 32))),
                $math->hexDec(bin2hex($messageHash)),
                $recoveryFlags,
                $generator
            );
        } catch (\Exception $e) {
            throw new \Exception("unable to recover key", 0, $e);
        }

        if ($pubkey === false) {
            throw new \Exception('unable to recover key');
        }

        $point = $pubkey->getPoint();

        // see that the key we recovered is for the address given
        if (!$isCompressed) {
            $pubBinStr =
                "\x04" .
                str_pad(hex2bin(self::padHex($math->decHex($point->getX()))), 32, "\x00", STR_PAD_LEFT) .
                str_pad(hex2bin(self::padHex($math->decHex($point->getY()))), 32, "\x00", STR_PAD_LEFT);
        } else {
            $pubBinStr =
                (($math->mod($point->getY(), 2) == 0) ? "\x02" : "\x03") .
                str_pad(hex2bin(self::padHex($math->decHex($point->getX()))), 32, "\x00", STR_PAD_LEFT);
        }

        $derivedAddress = hex2bin(self::magicByte()) . hash('ripemd160', hash('sha256', $pubBinStr, true), true);

        return $address === $derivedAddress;
    }

    /**
     * helper function to ensure a hex has all it's preceding 0's (which PHP tends to trim off)
     *
     * @param      $hex
     * @param null $length
     * @return string
     */
    public static function padHex($hex, $length = null)
    {
        if (!$length) {
            $length = strlen($hex);
            if ($length % 2 !== 0) {
                $length += 1;
            }
        }

        return str_pad($hex, $length, "0", STR_PAD_LEFT);
    }

    /**
     * convert a Satoshi value (int) to a BTC value (float value but as a string)
     *
     * @param int $satoshi
     * @return string
     */
    public static function toBTC($satoshi)
    {
        return bcdiv((int)(string)$satoshi, 100000000, 8);
    }

    /**
     * convert a Satoshi value (int) to a BTC value (float)
     *  and return it as a string formatted with 8 decimals
     *
     * @param int $satoshi
     * @return string
     */
    public static function toBTCString($satoshi)
    {
        return self::toBTC($satoshi);
    }

    /**
     * convert a BTC value (float) to a Satoshi value (int)
     *
     * @param float $btc
     * @return int
     */
    public static function toSatoshi($btc)
    {
        return (int)self::toSatoshiString($btc);
    }

    /**
     * convert a BTC value (float) to a Satoshi value (int)
     *  and return it as a string
     *
     * @param float $btc
     * @return string
     */
    public static function toSatoshiString($btc)
    {
        return bcmul(sprintf("%.8f", (float)$btc), 100000000, 0);
    }
}
