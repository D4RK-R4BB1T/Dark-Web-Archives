<?php
namespace Base32;

/**
 * Base32 encoder and decoder
 *
 * Last update: 2012-06-20
 *
 * RFC 4648 compliant
 * @link http://www.ietf.org/rfc/rfc4648.txt
 *
 * Some groundwork based on this class
 * https://github.com/NTICompass/PHP-Base32
 *
 * @author Christian Riesen <chris.riesen@gmail.com>
 * @link http://christianriesen.com
 * @license MIT License see LICENSE file
 */
class Base32
{
    /**
	 * Table for encoding base32
	 *
	 * @var array
	 */
    private static $encode = array(
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
        4 => 'E',
        5 => 'F',
        6 => 'G',
        7 => 'H',
        8 => 'I',
        9 => 'J',
        10 => 'K',
        11 => 'L',
        12 => 'M',
        13 => 'N',
        14 => 'O',
        15 => 'P',
        16 => 'Q',
        17 => 'R',
        18 => 'S',
        19 => 'T',
        20 => 'U',
        21 => 'V',
        22 => 'W',
        23 => 'X',
        24 => 'Y',
        25 => 'Z',
        26 => 2,
        27 => 3,
        28 => 4,
        29 => 5,
        30 => 6,
        31 => 7,
        32 => '=',
    );

    /**
	 * Table for decoding base32
	 *
	 * @var array
	 */
    private static $decode = array(
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'I' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'M' => 12,
        'N' => 13,
        'O' => 14,
        'P' => 15,
        'Q' => 16,
        'R' => 17,
        'S' => 18,
        'T' => 19,
        'U' => 20,
        'V' => 21,
        'W' => 22,
        'X' => 23,
        'Y' => 24,
        'Z' => 25,
        2 => 26,
        3 => 27,
        4 => 28,
        5 => 29,
        6 => 30,
        7 => 31,
        '=' => 32,
    );

    /**
	 * Creates an array from a binary string into a given chunk size
	 *
	 * @param string $binaryString String to chunk
	 * @param integer $bits Number of bits per chunk
	 * @return array
	 */
    private static function chunk($binaryString, $bits)
    {
        $binaryString = chunk_split($binaryString, $bits, ' ');

        if (substr($binaryString, (strlen($binaryString)) - 1)  == ' ') {
            $binaryString = substr($binaryString, 0, strlen($binaryString)-1);
        }

        return explode(' ', $binaryString);
    }

    /**
	 * Encodes into base32
	 *
	 * @param string $string Clear text string
	 * @return string Base32 encoded string
	 */
    public static function encode($string)
    {
        if (strlen($string) == 0) {
			// Gives an empty string

            return '';
		}

        // Convert string to binary
        $binaryString = '';

		foreach (str_split($string) as $s) {
			// Return each character as an 8-bit binary string
            $s = decbin(ord($s));
			$binaryString .= str_pad($s, 8, 0, STR_PAD_LEFT);
		}

        // Break into 5-bit chunks, then break that into an array
        $binaryArray = self::chunk($binaryString, 5);

        // Pad array to be divisible by 8
        while (count($binaryArray) % 8 !== 0) {
            $binaryArray[] = null;
        }

        $base32String = '';

        // Encode in base32
        foreach ($binaryArray as $bin) {
            $char = 32;

            if (!is_null($bin)) {
                // Pad the binary strings
                $bin = str_pad($bin, 5, 0, STR_PAD_RIGHT);
                $char = bindec($bin);
            }

            // Base32 character
            $base32String .= self::$encode[$char];
        }

        return $base32String;
    }

    /**
	 * Decodes base32
	 *
	 * @param string $base32String Base32 encoded string
	 * @return string Clear text string
	 */
    public static function decode($base32String)
    {
        if (strlen($base32String) == 0) {
            // Gives an empty string
            return '';
        }

        // Only work in upper cases
        $base32String = strtoupper($base32String);

        // Remove anything that is not base32 alphabet
        $pattern = '/[^A-Z2-7]/';

		$base32String = preg_replace($pattern, '', $base32String);

        $base32Array = str_split($base32String);

        $string = '';

        foreach ($base32Array as $str) {
            $char = self::$decode[$str];

            // Ignore the padding character
            if ($char !== 32) {
                $char = decbin($char);
                $string .= str_pad($char, 5, 0, STR_PAD_LEFT);
            }
        }

        while (strlen($string) %8 !== 0) {
            $string = substr($string, 0, strlen($string)-1);
        }

        $binaryArray = self::chunk($string, 8);

		$realString = '';

		foreach ($binaryArray as $bin) {
			// Pad each value to 8 bits
            $bin = str_pad($bin, 8, 0, STR_PAD_RIGHT);
			// Convert binary strings to ASCII
            $realString .= chr(bindec($bin));
		}

		return $realString;
    }
}
