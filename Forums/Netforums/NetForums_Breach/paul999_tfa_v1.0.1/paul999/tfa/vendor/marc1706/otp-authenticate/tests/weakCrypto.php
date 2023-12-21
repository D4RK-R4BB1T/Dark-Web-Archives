<?php

/**
 * weakCrypto
 * @package OTPAuthenticate
 * @copyright (c) Marc Alexander <admin@m-a-styles.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OTPAuthenticate;

require_once(dirname(__FILE__) . '/../lib/OTPAuthenticate.php');
require_once(dirname(__FILE__) . '/../vendor/christian-riesen/base32/src/Base32.php');

class weakCrypto extends \PHPUnit_Framework_TestCase
{
	protected $secret = "MRTGW2TEONWDQMR7";

	static public $weak_crypto = false;

	/** @var \OTPAuthenticate\OTPAuthenticate */
	protected $otp_auth;

	public function setUp()
	{
		parent::setUp();

		$this->otp_auth = new \OTPAuthenticate\OTPAuthenticate();
	}

	public function testWeakCrypto()
	{
		$this->assertNotSame('', $this->otp_auth->generateSecret());
		self::$weak_crypto = true;
		$this->assertSame('', $this->otp_auth->generateSecret());
		self::$weak_crypto = false;
		$this->assertNotSame('', $this->otp_auth->generateSecret());
	}
}

function openssl_random_pseudo_bytes($length, &$strong_secret)
{
	$random_string = \openssl_random_pseudo_bytes($length, $strong_secret);
	if (\OTPAuthenticate\weakCrypto::$weak_crypto === true)
	{
		$strong_secret = false;
	}

	return $random_string;
}
