<?php

/**
 * OTPAuthenticate Test
 * @package OTPAuthenticate
 * @copyright (c) Marc Alexander <admin@m-a-styles.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OTPAuthenticate\tests;

require_once(dirname(__FILE__) . '/../lib/OTPAuthenticate.php');
require_once(dirname(__FILE__) . '/../vendor/christian-riesen/base32/src/Base32.php');

class OTPAuthenticate extends \PHPUnit_Framework_TestCase
{
	protected $secret = "MRTGW2TEONWDQMR7";

	protected $hash_types = array(
		'sha1',
		'sha256',
		'sha512',
	);

	/** @var \OTPAuthenticate\OTPAuthenticate */
	protected $otp_auth;

	public function setUp()
	{
		parent::setUp();

		$this->otp_auth = new \OTPAuthenticate\OTPAuthenticate();
	}

	protected $hotp_codes = array(
		'020662',
		'297855',
		'293646',
		'438611',
		'795847',
		'381952',
		'900745',
		'565187',
	);

	protected $totp_codes = array(
		'049958',
		'522693',
		'483631',
		'747816',
		'894758',
		'279356',
		'227505',
		'515792',
	);

	protected $totp_512_codes = array(
		'876388',
		'543910',
		'359798',
		'103390',
		'473509',
		'110596',
		'368131',
		'786714',
	);

	public function testGenerateCodeHOTP()
	{
		$counter = 1;

		foreach ($this->hotp_codes as $code)
		{
			$this->assertSame($code, $this->otp_auth->generateCode($this->secret, $counter, 'sha1'));
			$counter++;
		}
	}

	public function testGenerateCodeTOTP()
	{
		$start_time = 1420906262;

		foreach ($this->totp_codes as $code)
		{
			$this->assertSame($code, $this->otp_auth->generateCode($this->secret, $this->otp_auth->getTimestampCounter($start_time), 'sha1'));
			$start_time = $start_time + 30;
		}
	}

	public function testGenerateCodeTOTP512()
	{
		$start_time = 1420937310;

		foreach ($this->totp_512_codes as $code)
		{
			$this->assertSame($code, $this->otp_auth->generateCode($this->secret, $this->otp_auth->getTimestampCounter($start_time)));
			$start_time = $start_time + 30;
		}
	}

	public function data_testStringCompare()
	{
		return array(
			array('foobar', 'foobar', true),
			array('baffoo', 'foobar', false),
			array(0, 0, true),
			array(true, true, true),
			array(false, true, false),
		);
	}

	/**
	 * @dataProvider data_testStringCompare
	 */
	public function testStringCompare($a, $b, $expected)
	{
		$this->assertSame($expected, $this->otp_auth->stringCompare($a, $b));
	}

	public function testGenerateSecret()
	{
		$time = microtime(true);
		$secret = '';

		while ((microtime(true) - $time) < 1)
		{
			$new_secret = $this->otp_auth->generateSecret(10);
			$this->assertNotSame($secret, $new_secret);
			$this->assertEquals(16, strlen($new_secret));
			$secret = $new_secret;
		}
	}

	public function data_testCheckTOTP()
	{
		return array(
			array(-1, true),
			array(-5, false),
			array(0, true),
			array(1, true),
			array(2, false),
		);
	}

	/**
	 * @dataProvider data_testCheckTOTP
	 */
	public function testCheckTOTP($offset, $expected)
	{
		foreach ($this->hash_types as $type)
		{
			$code = $this->otp_auth->generateCode($this->secret, $this->otp_auth->getTimestampCounter(time()) + $offset, $type);

			$this->assertSame($expected, $this->otp_auth->checkTOTP($this->secret, $code, $type));
		}
	}

	public function testEmptyCounter()
	{
		$this->assertSame('', $this->otp_auth->generateCode($this->secret, ''));
		$this->assertSame('', $this->otp_auth->generateCode($this->secret, 0));
	}

	public function data_testCheckHOTP()
	{
		return array(
			array(1, '996554', '344551', '439887', true),
			array(2, '602287', '730792', '644671', true),
			array(3, '143627', '653637', '829955', true),
			array(4, '960129', '766270', '708699', true),
			array(5, '768897', '302147', '923460', true),
		);
	}

	/**
	 * @dataProvider data_testCheckHOTP
	 */
	public function testCheckHOTP($counter, $code_sha1, $code_sha256, $code_sha512, $expected)
	{
		$this->assertSame($expected, $this->otp_auth->checkHOTP('JBSWY3DPEHPK3PXP', $counter, $code_sha1, 'sha1'));
		$this->assertSame($expected, $this->otp_auth->checkHOTP('JBSWY3DPEHPK3PXP', $counter, $code_sha256, 'sha256'));
		$this->assertSame($expected, $this->otp_auth->checkHOTP('JBSWY3DPEHPK3PXP', $counter, $code_sha512, 'sha512'));
	}
}
