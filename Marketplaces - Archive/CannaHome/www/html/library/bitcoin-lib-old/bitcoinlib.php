<?php

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

class BitcoinLib {
	
	/**
	 * HexChars
	 * 
	 * This is a string containing the allowed characters in base16.
	 */
	private static $hexchars = "0123456789ABCDEF";
	
	/**
	 * Base58Chars
	 * 
	 * This is a string containing the allowed characters in base58.
	 */
	private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
	
	/**
	 * Hex Encode 
	 * 
	 * Encodes a decimal $number into a hexadecimal string.
	 * 
	 * @param	int	$number
	 * @return	string
	 */
	public static function hex_encode($number) {
		$hex = gmp_strval(gmp_init($number, 10), 16);
		return (strlen($hex)%2 != 0) ? '0'.$hex : $hex;
	}
	
	/**
	 * Hex Decode
	 * 
	 * Decodes a hexadecimal $hex string into a decimal number.
	 * 
	 * @param	string	$hex
	 * @return	int
	 */
	public static function hex_decode($hex) {
		return gmp_strval(gmp_init($hex, 16), 10);
	}

	/**
	 * Base58 Decode
	 * 
	 * This function accepts a base58 encoded string, and decodes the 
	 * string into a number, which is converted to hexadecimal. It is then
	 * padded with zero's.

     * @param $base58
     * @return string
     */
    public static function base58_decode($base58) {
		$origbase58 = $base58;
		$return = "0";
		
		for($i = 0; $i < strlen($base58); $i++) {
			// return = return*58 + current position of $base58[i]in self::$base58chars
			$return = gmp_add(gmp_mul($return, 58), strpos(self::$base58chars, $base58[$i]));
		}
		$return = gmp_strval($return, 16);
		for($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
			$return = "00".$return;
		}
		if(strlen($return) %2 != 0) {
			$return = "0".$return;
		}
		return $return;
	}


	/**
	 * Base58 Encode
	 *
	 * Encodes a $hex string in base58 format. Borrowed from prusnaks
	 * addrgen code: https://github.com/prusnak/addrgen/blob/master/php/addrgen.php
	 *
	 * @param	string	$hex
	 * @return	string
	 * @author	Pavel Rusnak
	 */
	public static function base58_encode($hex) {
        if(strlen($hex) == 0)
            return '';

        // Convert the hex string to a base10 integer
		$num = gmp_strval(gmp_init($hex, 16), 58);

        // Check that number isn't just 0 - which would be all padding.
        if($num != '0') {
		    $num = strtr($num
    		, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv'
    		, self::$base58chars);
        } else {
            $num = '';
        }

        // Pad the leading 1's
        $pad = ''; $n = 0;
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
	 * @param	string	$hex
	 * @return	string
	 */
	public static function base58_encode_checksum($hex) {
		$checksum = self::hash256($hex);
		$checksum = substr($checksum, 0, 8);
		$hash = $hex.$checksum;
		return self::base58_encode($hash);
	}

	/**
	 * Base58 Decode Checksum
	 * 
	 * Returns the original hex data that was encoded in base58 check format.
	 * 
	 * @param	string	$base58
	 * @return	string
	 */
	public static function base58_decode_checksum($base58) {
		$hex = self::base58_decode($base58);
		return substr($hex, 2, strlen($hex)-10);
	}

	/**
	 * Hash256
	 * 
	 * Takes a sha256(sha256()) hash of the $string. Intended only for
	 * hex strings, as it is packed into raw bytes.
	 * 
	 * @param	string	$string
	 * @return  string
	 */
	public static function hash256($string) {
		$bs = @pack("H*", $string);
		return hash("sha256", hash("sha256", $bs, true));
	}

	/**
	 * Hash160
	 * 
	 * Takes $data as input and returns a ripemd160(sha256()) hash of $string.
	 * Intended for only hex strings, as it is packed into raw bytes.
	 * 
	 * @param	string	$string
	 * @return	string
	 */
	public static function hash160($string) {
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
	 * @param	string	$hash160
	 * @param	string	$address_version
	 * @return	string
	 */
	public static function hash160_to_address($hash160, $address_version) {
		$hash160 = $address_version . $hash160;
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
	 * @param	string	$public_key
	 * @param	string	$address_version
	 * @return	string
	 */
	public static function public_key_to_address($public_key, $address_version) {
		$hash160 = self::hash160($public_key);
		return self::hash160_to_address($hash160, $address_version);
	}

	/**
	 * Get New Private Key
	 * 
	 * This function generates a new private key, a number from 1 to $n. 
	 * Once it finds an acceptable value, it will encode it in hex, pad it, 
	 * and return the private key.
	 * 
	 * @return	string
	 */
	public static function get_new_private_key() {
		$g = \SECcurve::generator_secp256k1();
		$n = $g->getOrder();

		$privKey = gmp_strval(gmp_init(bin2hex(openssl_random_pseudo_bytes(32)),16));
		while($privKey >= $n) {
			$privKey = gmp_strval(gmp_init(bin2hex(openssl_random_pseudo_bytes(32)),16));
		}
		$privKeyHex = self::hex_encode($privKey);
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
	 * @param	string	$privKey
	 * @param	boolean	$compressed
	 * @return	string
	 */
	public static function private_key_to_public_key($privKey, $compressed = FALSE) {
		$g = \SECcurve::generator_secp256k1();
    
		$privKey = self::hex_decode($privKey);  
		try 
		{
			$secretG = \Point::mul($privKey, $g);
		}
		catch (\Exception $e) 
		{
			return FALSE;
		}
	
		$xHex = self::hex_encode($secretG->getX());  
		$yHex = self::hex_encode($secretG->getY());

		$xHex = str_pad($xHex, 64, '0', STR_PAD_LEFT);
		$yHex = str_pad($yHex, 64, '0', STR_PAD_LEFT);
		$public_key = '04'.$xHex.$yHex;
		
		return ($compressed == TRUE) ? self::compress_public_key($public_key) : $public_key;
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
    public static function private_key_to_address($private_key, $address_version) {

		$public_key = self::private_key_to_public_key($private_key);
		return self::public_key_to_address($public_key, $address_version);
	}

	/**
	 * Get New Key Pair
	 * 
	 * Generate a new private key, and convert to an uncompressed public key.
	 * 
	 * @return array
	 */
	public static function get_new_key_pair() {
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
     * @param $address_version
     * @param bool $compressed
     * @return array
     */
    public static function get_new_key_set($address_version, $compressed = FALSE) {
		do {
			$key_pair = self::get_new_key_pair();
			$private_WIF = self::private_key_to_WIF($key_pair['privKey'], $compressed, $address_version);

			if ($compressed == TRUE)
				$key_pair['pubKey'] = self::compress_public_key($key_pair['pubKey']);
				
			$public_address = self::public_key_to_address($key_pair['pubKey'], $address_version);
		} while (!self::validate_address($public_address, $address_version));

		return array('privKey' => $key_pair['privKey'],
					 'pubKey' => $key_pair['pubKey'],
					 'privWIF' => $private_WIF,
					 'pubAdd' => $public_address);
	}
	
	/**
	 * Get Private Address Version
	 * 
	 * This function 
	 * Generates a private key address version (the prefix) from the 
	 * supplied public key address version, by adding 0x80 to the number.
	 * 
	 * @param	string	$address_version
	 * @return	string
	 */
	public static function get_private_key_address_version($address_version)
	{
		return gmp_strval(
					gmp_add(
						gmp_init($address_version, 16),
						gmp_init('80',16)
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
	 * $compressed = TRUE will yield the private key for the compressed 
	 * public key address.
	 *
     * @param $privKey
     * @param bool $compressed
     * @param $address_version
     * @return string
     */
    public static function private_key_to_WIF($privKey, $compressed = FALSE, $address_version)
	{
		$key = $privKey.(($compressed == TRUE)?'01':'');
		return self::hash160_to_address($key, self::get_private_key_address_version($address_version));
	}
	
	/**
	 * WIF To Private Key
	 * 
	 * Convert a base58 encoded $WIF private key to a hexadecimal private key.
	 * 
	 * @param	string	$WIF
	 * @return	string
	 */
	public static function WIF_to_private_key($WIF)
	{
		$decode = self::base58_decode($WIF);
		return array('key' => substr($decode, 2, 64),
					 'is_compressed' => (( (strlen($decode)-10) == 66 && substr($decode, 66, 2) == '01') ? TRUE : FALSE));		
	}
	
	/**
	 * Import Public Key
	 * 
	 * Imports an arbitrary $public_key, and returns it untreated if the
	 * left-most bit is '04', or else decompressed the public key if the
	 * left-most bit is '02' or '03'.
	 * 
	 * @param	string	$public_key
	 * @return	string
	 */
	public static function import_public_key($public_key) 
	{
		$first = substr($public_key, 0, 2);
		if (($first == '02' || $first == '03') && strlen($public_key) == '66') 
		{
			// Compressed public key, need to decompress.
			$decompressed = self::decompress_public_key($public_key);
			return ($decompressed == FALSE) ? FALSE : $decompressed['public_key'];
		}
		else if($first == '04')
		{
			// Regular public key, pass back untreated.
			return $public_key;
		} 
		
		// Not a valid public key
		return FALSE;
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
	 * @param	string	$public_key
	 * @return	string
	 */
	public static function compress_public_key($public_key)
	{
		return '0'.(((\gmp_Utils::gmp_mod2(gmp_init(substr($public_key, 66, 64), 16), 2))==0) ? '2' : '3').substr($public_key, 2, 64);
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
		$y_byte = substr($key, 0, 2);
		$x_coordinate = substr($key, 2);
		
		$x = gmp_strval(gmp_init($x_coordinate, 16),10);
		$curve = \SECcurve::curve_secp256k1();
		$generator = \SECcurve::generator_secp256k1();

		try
		{
			$x3 = \NumberTheory::modular_exp( $x, 3, $curve->getPrime() );
			
			$y2 = gmp_add(
						$x3,
						$curve->getB()
					);
			
			$y0 = \NumberTheory::square_root_mod_prime(
						gmp_strval($y2, 10),
						$curve->getPrime()
					);

			if($y0 == FALSE)
				return FALSE;

			$y1 = gmp_strval(gmp_sub($curve->getPrime(), $y0), 10);
			
			$y_coordinate = ($y_byte == '02') 
									? ((\gmp_Utils::gmp_mod2(gmp_init($y0, 10), 2) == '0') ? $y0 : $y1)
									: ((\gmp_Utils::gmp_mod2(gmp_init($y0, 10), 2) !== '0') ? $y0 : $y1);

			$y_coordinate = str_pad(gmp_strval($y_coordinate, 16),64,'0',STR_PAD_LEFT);
			
			$point = new \Point($curve, gmp_strval(gmp_init($x_coordinate, 16),10), gmp_strval(gmp_init($y_coordinate, 16),10), $generator->getOrder());
		} 
		catch (\Exception $e)
		{
			return FALSE;
		}
		
		return array('x' => $x_coordinate, 
					 'y' => $y_coordinate,
					 'point' => $point,
					 'public_key' => '04'.$x_coordinate.$y_coordinate);
	}

	/**
	 * Validate Public Key
	 * 
	 * Validates a public key by attempting to create a point on the
	 * secp256k1 curve. 
	 * 
	 * @param	string	$public_key
	 * @return	boolean
	 */
	public static function validate_public_key($public_key) {
		if (strlen($public_key) == '66')
		{
			// Compressed key
			// Attempt to decompress the public key. If the point is not
			// generated, or the function fails, then the key is invalid.
			$decompressed = self::decompress_public_key($public_key);
			return $decompressed == TRUE;
		}
		else if (strlen($public_key) == '130')
		{
			// Uncompressed key, try to create the point
			$curve = \SECcurve::curve_secp256k1();
			$generator = \SECcurve::generator_secp256k1();
		
			$x = substr($public_key, 2, 64);
			$y = substr($public_key, 66, 64);

			// Attempt to create the point. Point returns false in the 
			// constructor if anything is invalid.
			try
			{
				$point = new \Point($curve, gmp_strval(gmp_init($x, 16),10), gmp_strval(gmp_init($y, 16),10), $generator->getOrder());
			}
			catch (\Exception $e) 
			{
				return FALSE;
			}
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Validate Address
	 * 
	 * This function accepts a base58check encoded $address, which is
	 * decoded and checked for validity. Returns FALSE for an invalid 
	 * address, otherwise returns TRUE;
	 * 
	 * @param	string	$address
	 * @param	string	$address_version
	 * @return	boolean
	 */
	public static function validate_address($address, $address_version)
	{
		// Check the address is decoded correctly.
		$decode = self::base58_decode($address);
		if (strlen($decode) !== 50) 
			return FALSE;

		// Compare the version.
		$version = substr($decode, 0, 2);
		if (hexdec($version) > hexdec($address_version))
			return FALSE;

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
	 * @param	string	$wif
	 * @param	string	$ver
	 * @return	boolean
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

        if($ver !== NULL && $ver !== $version)
            return FALSE;

		// Determine if pubkey is compressed
		$compressed = FALSE;
		if (strlen($hex) == 66 && substr($hex, 64, 2) == '01')
		{
			$compressed = TRUE;
			$hex = substr($hex, 0, 64);
		}

		// Check private key within limit.
		$g = \SECcurve::generator_secp256k1();
		$n = $g->getOrder();
		if (gmp_strval(gmp_init($hex, 16),10) >= $n)
			return FALSE;

		// Calculate checksum for what we have, see if it matches.
		$checksum = self::hash256($version.$hex.(($compressed)?'01':''));
		$checksum = substr($checksum, 0, 8);
		return $checksum == $crc;
	}
};
