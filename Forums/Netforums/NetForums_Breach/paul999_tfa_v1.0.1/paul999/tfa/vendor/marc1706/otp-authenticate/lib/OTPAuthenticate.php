<?php

/**
 * OTPAuthenticate
 * @package OTPAuthenticate
 * @copyright (c) Marc Alexander <admin@m-a-styles.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OTPAuthenticate;

use Base32\Base32;

class OTPAuthenticate
{
	/** int verification code modulus */
	const VERIFICATION_CODE_MODULUS = 1e6;

	/** int Secret length */
	protected $secret_length;

	/** int code length */
	protected $code_length;

	/** \Base32\Base32 */
	protected $base32;

	/**
	 * Constructor for OTPAuthenticate
	 *
	 * @param int $code_length Code length
	 * @param int $secret_length Secret length
	 */
	public function __construct($code_length = 6, $secret_length = 10)
	{
		$this->code_length = $code_length;
		$this->secret_length = $secret_length;

		$this->base32 = new Base32();
	}

	/**
	 * Generates code based on timestamp and secret
	 *
	 * @param string $secret Secret shared with user
	 * @param int $counter Counter for code generation
	 * @param string $algorithm Algorithm to use for HMAC hash.
	 *			Defaults to sha512. The following hash types are allowed:
	 *				TOTP: sha1, sha256, sha512
	 *				HOTP: sha1
	 *
	 * @return string Generated OTP code
	 */
	public function generateCode($secret, $counter, $algorithm = 'sha512')
	{
		$key = $this->base32->decode($secret);

		if (empty($counter))
		{
			return '';
		}

		$hash = hash_hmac($algorithm, $this->getBinaryCounter($counter), $key, true);

		return str_pad($this->truncate($hash), $this->code_length, '0', STR_PAD_LEFT);
	}

	/**
	 * Check if supplied TOTP code is valid
	 *
	 * @param string $secret Secret to use for comparison
	 * @param int $code Supplied TOTP code
	 * @param string $hash_type Hash type
	 *
	 * @return bool True if code is valid, false if not
	 */
	public function checkTOTP($secret, $code, $hash_type = 'sha512')
	{
		$time = $this->getTimestampCounter(time());

		for ($i = -1; $i <= 1; $i++)
		{
			if ($this->stringCompare($code, $this->generateCode($secret, $time + $i, $hash_type)) === true)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if supplied HOTP code is valid
	 *
	 * @param string $secret Secret to use for comparison
	 * @param int $counter Current counter
	 * @param int $code Supplied HOTP code
	 * @param string $hash_type Hash type
	 *
	 * @return bool True if code is valid, false if not
	 */
	public function checkHOTP($secret, $counter, $code, $hash_type = 'sha512')
	{
		return $this->stringCompare($code, $this->generateCode($secret, $counter, $hash_type));
	}

	/**
	 * Truncate HMAC hash to binary for generating a TOTP code
	 *
	 * @param string $hash HMAC hash
	 *
	 * @return int Truncated binary hash
	 */
	protected function truncate($hash)
	{
		$truncated_hash = 0;
		$offset = ord(substr($hash, -1)) & 0xF;

		// Truncate hash using supplied sha1 hash
		for ($i = 0; $i < 4; ++$i)
		{
			$truncated_hash <<= 8;
			$truncated_hash  |= ord($hash[$offset + $i]);
		}

		// Truncate to a smaller number of digits.
		$truncated_hash &= 0x7FFFFFFF;
		$truncated_hash %= self::VERIFICATION_CODE_MODULUS;

		return $truncated_hash;
	}

	/**
	 * Get binary version of time counter
	 *
	 * @param int $counter Timestamp or counter
	 *
	 * @return string Binary time counter
	 */
	protected function getBinaryCounter($counter)
	{
		return pack('N*', 0) . pack('N*', $counter);
	}

	/**
	 * Get counter from timestamp
	 *
	 * @param int $time Timestamp
	 *
	 * @return int Counter
	 */
	public function getTimestampCounter($time)
	{
		return floor($time / 30);
	}

	/**
	 * Compare two strings in constant time to prevent timing attacks.
	 *
	 * @param string $string_a Initial string
	 * @param string $string_b String to compare initial string to
	 *
	 * @return bool True if strings are the same, false if not
	 */
	public function stringCompare($string_a, $string_b)
	{
		$diff = strlen($string_a) ^ strlen($string_b);

		for ($i = 0; $i < strlen($string_a) && $i < strlen($string_b); $i++)
		{
			$diff |= ord($string_a[$i]) ^ ord($string_b[$i]);
		}

		return $diff === 0;
	}

	/**
	 * Generate secret with specified length
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function generateSecret($length = 10)
	{
		$strong_secret = false;

		// Try to get $crypto_strong to evaluate to true. Give it 5 tries.
		for ($i = 0; $i < 5; $i++)
		{
			$secret = openssl_random_pseudo_bytes($length, $strong_secret);

			if ($strong_secret === true)
			{
				return $this->base32->encode($secret);
			}
		}

		return '';
	}
}
