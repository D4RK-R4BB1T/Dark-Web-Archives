<?php

/**
 * OTPHelper Test
 * @package OTPAuthenticate
 * @copyright (c) Marc Alexander <admin@m-a-styles.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OTPAuthenticate\tests;

require_once(dirname(__FILE__) . '/../lib/OTPHelper.php');

class OTPHelper extends \PHPUnit_Framework_TestCase
{
	/** @var \OTPAuthenticate\OTPHelper */
	protected $OTPHelper;

	public function setUp()
	{
		parent::setUp();

		$this->OTPHelper = new \OTPAuthenticate\OTPHelper();
	}

	public function data_testGetURI()
	{
		return array(
			array('otpauth://totp/meh@meh.com?secret=FOOBAR', array(
				'totp',
				'FOOBAR',
				'meh@meh.com',
			)),
			array('otpauth://totp/Meh%20Meh:meh@meh.com?secret=FOOBAR&issuer=Meh%20Meh', array(
				'totp',
				'FOOBAR',
				'meh@meh.com',
				'Meh Meh'
			)),
			array('otpauth://totp/meh@meh.com?secret=FOOBAR&algorithm=sha512&digits=8&period=30', array(
				'totp',
				'FOOBAR',
				'meh@meh.com',
				'',
				0,
				'sha512',
				8,
				30,
			)),
			array('otpauth://hotp/meh@meh.com?secret=FOOBAR&counter=0', array(
				'hotp',
				'FOOBAR',
				'meh@meh.com',
			)),
			array('otpauth://hotp/Meh%20Meh:meh@meh.com?secret=FOOBAR&issuer=Meh%20Meh&counter=0', array(
				'hotp',
				'FOOBAR',
				'meh@meh.com',
				'Meh Meh'
			)),
			array('otpauth://hotp/meh@meh.com?secret=FOOBAR&counter=5', array(
				'hotp',
				'FOOBAR',
				'meh@meh.com',
				'',
				5,
			)),
			array('otpauth://hotp/meh@meh.com?secret=FOOBAR&counter=5&algorithm=sha512&digits=8&period=30', array(
				'hotp',
				'FOOBAR',
				'meh@meh.com',
				'',
				5,
				'sha512',
				8,
				30,
			)),
		);
	}

	/**
	 * @dataProvider data_testGetURI
	 */
	public function testGetURI($expected, $input)
	{
		$this->assertSame($expected, call_user_func_array(array($this->OTPHelper, 'generateKeyURI'), $input));
	}

	public function data_testGetURIExceptions()
	{
		return array(
			array('InvalidArgumentException', 'The OTP type foobar is not supported', array(
				'foobar',
				'FOOBAR',
				'meh@meh.com',
			)),
			array('InvalidArgumentException', "Label can't contain empty strings", array(
				'totp',
				'FOOBAR',
				'',
			)),
			array('InvalidArgumentException', "Label can't contain empty strings", array(
				'totp',
				'FOOBAR',
				' ',
			)),
			array('InvalidArgumentException', 'The algorithm foobar is not supported', array(
				'totp',
				'FOOBAR',
				'meh@meh.com',
				'',
				0,
				'foobar',
			)),
			array('InvalidArgumentException', "Counter can't be empty if HOTP is being used", array(
				'hotp',
				'FOOBAR',
				'meh@meh.com',
				'',
				'',
			)),
		);
	}

	/**
	 * @dataProvider data_testGetURIExceptions
	 */
	public function testGetURIExceptions($expectedException, $exceptionText, $input)
	{
		$this->setExpectedException($expectedException, $exceptionText);

		var_dump(call_user_func_array(array($this->OTPHelper, 'generateKeyURI'), $input));
	}
}
