<?php

namespace App\Marketplace\Utility\MoneroRPC;
class base58
{
    static $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    static $encoded_block_sizes = [0, 2, 3, 5, 6, 7, 9, 10, 11];
    static $full_block_size = 8;
    static $full_encoded_block_size = 11;

    /**
     *
     * Convert a hexadecimal string to a binary array
     *
     * @param    string $hex A hexadecimal string to convert to a binary array
     *
     * @return   array
     *
     */
    private function hex_to_bin($hex) {
        if (gettype($hex) != 'string') {
            throw new Exception('base58->hex_to_bin(): Invalid input type (must be a string)');
        }
        if (strlen($hex) % 2 != 0) {
            throw new Exception('base58->hex_to_bin(): Invalid input length (must be even)');
        }

        $res = array_fill(0, strlen($hex) / 2, 0);
        for ($i = 0; $i < strlen($hex) / 2; $i++) {
            $res[$i] = intval(substr($hex, $i * 2, $i * 2 + 2 - $i * 2), 16);
        }
        return $res;
    }

    /**
     *
     * Convert a binary array to a hexadecimal string
     *
     * @param    array $bin A binary array to convert to a hexadecimal string
     *
     * @return   string
     *
     */
    private function bin_to_hex($bin) {
        if (gettype($bin) != 'array') {
            throw new Exception('base58->bin_to_hex(): Invalid input type (must be an array)');
        }

        $res = [];
        for ($i = 0; $i < count($bin); $i++) {
            $res[] = substr('0' . dechex($bin[$i]), -2);
        }
        return join($res);
    }

    /**
     *
     * Convert a string to a binary array
     *
     * @param    string $str A string to convert to a binary array
     *
     * @return   array
     *
     */
    private function str_to_bin($str) {
        if (gettype($str) != 'string') {
            throw new Exception('base58->str_to_bin(): Invalid input type (must be a string)');
        }

        $res = array_fill(0, strlen($str), 0);
        for ($i = 0; $i < strlen($str); $i++) {
            $res[$i] = ord($str[$i]);
        }
        return $res;
    }

    /**
     *
     * Convert a binary array to a string
     *
     * @param    array $bin A binary array to convert to a string
     *
     * @return   string
     *
     */
    private function bin_to_str($bin) {
        if (gettype($bin) != 'array') {
            throw new Exception('base58->bin_to_str(): Invalid input type (must be an array)');
        }

        $res = array_fill(0, count($bin), 0);
        for ($i = 0; $i < count($bin); $i++) {
            $res[$i] = chr($bin[$i]);
        }
        return preg_replace('/[[:^print:]]/', '', join($res)); // preg_replace necessary to strip errant non-ASCII characters eg. ''
    }

    /**
     *
     * Convert a UInt8BE (one unsigned big endian byte) array to UInt64
     *
     * @param    array $data A UInt8BE array to convert to UInt64
     *
     * @return   number
     *
     */
    private function uint8_be_to_64($data) {
        if (gettype($data) != 'array') {
            throw new Exception ('base58->uint8_be_to_64(): Invalid input type (must be an array)');
        }

        $res = 0;
        $i = 0;
        switch (9 - count($data)) {
            case 1:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 2:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 3:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 4:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 5:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 6:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 7:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
            case 8:
                $res = bcadd(bcmul($res, bcpow(2, 8)), $data[$i++]);
                break;
            default:
                throw new Exception('base58->uint8_be_to_64: Invalid input length (1 <= count($data) <= 8)');
        }
        return $res;
    }

    /**
     *
     * Convert a UInt64 (unsigned 64 bit integer) to a UInt8BE array
     *
     * @param    number $num A UInt64 number to convert to a UInt8BE array
     * @param    integer $size Size of array to return
     *
     * @return   array
     *
     */
    private function uint64_to_8_be($num, $size) {
        if (gettype($num) != ('integer' || 'double')) {
            throw new Exception ('base58->uint64_to_8_be(): Invalid input type ($num must be a number)');
        }
        if (gettype($size) != 'integer') {
            throw new Exception ('base58->uint64_to_8_be(): Invalid input type ($size must be an integer)');
        }
        if ($size < 1 || $size > 8) {
            throw new Exception ('base58->uint64_to_8_be(): Invalid size (1 <= $size <= 8)');
        }

        $res = array_fill(0, $size, 0);
        for ($i = $size - 1; $i >= 0; $i--) {
            $res[$i] = bcmod($num, bcpow(2, 8));
            $num = bcdiv($num, bcpow(2, 8));
        }
        return $res;
    }

    /**
     *
     * Convert a hexadecimal (Base16) array to a Base58 string
     *
     * @param    array $data
     * @param    array $buf
     * @param    number $index
     *
     * @return   array
     *
     */
    private function encode_block($data, $buf, $index) {
        if (gettype($data) != 'array') {
            throw new Exception('base58->encode_block(): Invalid input type ($data must be an array)');
        }
        if (gettype($buf) != 'array') {
            throw new Exception('base58->encode_block(): Invalid input type ($buf must be an array)');
        }
        if (gettype($index) != ('integer' || 'double')) {
            throw new Exception('base58->encode_block(): Invalid input type ($index must be a number)');
        }
        if (count($data) < 1 or count($data) > self::$full_encoded_block_size) {
            throw new Exception('base58->encode_block(): Invalid input length (1 <= count($data) <= 8)');
        }

        $num = self::uint8_be_to_64($data);
        $i = self::$encoded_block_sizes[count($data)] - 1;
        while ($num > 0) {
            $remainder = bcmod($num, 58);
            $num = bcdiv($num, 58);
            $buf[$index + $i] = ord(self::$alphabet[$remainder]);
            $i--;
        }
        return $buf;
    }

    /**
     *
     * Encode a hexadecimal (Base16) string to Base58
     *
     * @param    string $hex A hexadecimal (Base16) string to convert to Base58
     *
     * @return   string
     *
     */
    public function encode($hex) {
        if (gettype($hex) != 'string') {
            throw new Exception ('base58->encode(): Invalid input type (must be a string)');
        }

        $data = self::hex_to_bin($hex);
        if (count($data) == 0) {
            return '';
        }

        $full_block_count = floor(count($data) / self::$full_block_size);
        $last_block_size = count($data) % self::$full_block_size;
        $res_size = $full_block_count * self::$full_encoded_block_size + self::$encoded_block_sizes[$last_block_size];

        $res = array_fill(0, $res_size, ord(self::$alphabet[0]));

        for ($i = 0; $i < $full_block_count; $i++) {
            $res = self::encode_block(array_slice($data, $i * self::$full_block_size, ($i * self::$full_block_size + self::$full_block_size) - ($i * self::$full_block_size)), $res, $i * self::$full_encoded_block_size);
        }

        if ($last_block_size > 0) {
            $res = self::encode_block(array_slice($data, $full_block_count * self::$full_block_size, $full_block_count * self::$full_block_size + $last_block_size), $res, $full_block_count * self::$full_encoded_block_size);
        }

        return self::bin_to_str($res);
    }

    /**
     *
     * Convert a Base58 input to hexadecimal (Base16)
     *
     * @param    array $data
     * @param    array $buf
     * @param    integer $index
     *
     * @return   array
     *
     */
    private function decode_block($data, $buf, $index) {
        if (gettype($data) != 'array') {
            throw new Exception('base58->decode_block(): Invalid input type ($data must be an array)');
        }
        if (gettype($buf) != 'array') {
            throw new Exception('base58->decode_block(): Invalid input type ($buf must be an array)');
        }
        if (gettype($index) != ('integer' || 'double')) {
            throw new Exception('base58->decode_block(): Invalid input type ($index must be a number)');
        }

        $res_size = self::index_of(self::$encoded_block_sizes, count($data));
        if ($res_size <= 0) {
            throw new Exception('base58->decode_block(): Invalid input length ($data must be a value from base58::$encoded_block_sizes)');
        }

        $res_num = 0;
        $order = 1;
        for ($i = count($data) - 1; $i >= 0; $i--) {
            $digit = strpos(self::$alphabet, chr($data[$i]));
            if ($digit < 0) {
                throw new Exception("base58->decode_block(): Invalid character ($digit \"{$digit}\" not found in base58::$alphabet)");
            }

            $product = bcadd(bcmul($order, $digit), $res_num);
            if ($product > bcpow(2, 64)) {
                throw new Exception('base58->decode_block(): Integer overflow ($product exceeds the maximum 64bit integer)');
            }

            $res_num = $product;
            $order = bcmul($order, 58);
        }
        if ($res_size < self::$full_block_size && bcpow(2, 8 * $res_size) <= 0) {
            throw new Exception('base58->decode_block(): Integer overflow (bcpow(2, 8 * $res_size) exceeds the maximum 64bit integer)');
        }

        $tmp_buf = self::uint64_to_8_be($res_num, $res_size);
        for ($i = 0; $i < count($tmp_buf); $i++) {
            $buf[$i + $index] = $tmp_buf[$i];
        }
        return $buf;
    }

    /**
     *
     * Decode a Base58 string to hexadecimal (Base16)
     *
     * @param    string $hex A Base58 string to convert to hexadecimal (Base16)
     *
     * @return   string
     *
     */
    public function decode($enc) {
        if (gettype($enc) != 'string') {
            throw new Exception ('base58->decode(): Invalid input type (must be a string)');
        }

        $enc = self::str_to_bin($enc);
        if (count($enc) == 0) {
            return '';
        }
        $full_block_count = floor(bcdiv(count($enc), self::$full_encoded_block_size));
        $last_block_size = bcmod(count($enc), self::$full_encoded_block_size);
        $last_block_decoded_size = self::index_of(self::$encoded_block_sizes, $last_block_size);

        $data_size = $full_block_count * self::$full_block_size + $last_block_decoded_size;

        $data = array_fill(0, $data_size, 0);
        for ($i = 0; $i <= $full_block_count; $i++) {
            $data = self::decode_block(array_slice($enc, $i * self::$full_encoded_block_size, ($i * self::$full_encoded_block_size + self::$full_encoded_block_size) - ($i * self::$full_encoded_block_size)), $data, $i * self::$full_block_size);
        }

        if ($last_block_size > 0) {
            $data = self::decode_block(array_slice($enc, $full_block_count * self::$full_encoded_block_size, $full_block_count * self::$full_encoded_block_size + $last_block_size), $data, $full_block_count * self::$full_block_size);
        }

        return self::bin_to_hex($data);
    }

    /**
     *
     * Search an array for a value
     * Source: https://stackoverflow.com/a/30994678
     *
     * @param    array $haystack An array to search
     * @param    string $needle A string to search for
     *)
     * @return   number             The index of the element found (or -1 for no match)
     *
     */
    private function index_of($haystack, $needle) {
        if (gettype($haystack) != 'array') {
            throw new Exception ('base58->decode(): Invalid input type ($haystack must be an array)');
        }
        // if (gettype($needle) != 'string') {
        //   throw new Exception ('base58->decode(): Invalid input type ($needle must be a string)');
        // }

        foreach ($haystack as $key => $value) if ($value === $needle) return $key;
        return -1;
    }
}
