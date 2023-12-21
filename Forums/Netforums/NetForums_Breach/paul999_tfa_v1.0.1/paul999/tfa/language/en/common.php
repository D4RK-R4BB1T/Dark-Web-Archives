<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge(
	$lang, array(
		'TFA_REQUIRED_KEY_MISSING'      => 'The administrator of this forum requires that you have added a Two Factor Authentication key for access to (limited) parts of this forum, however you currently have no (compatible) keys registerted to your account. You can add a new security key %s here%s.
												<br />For security reasons, the board has been disabled until you add a security key to your account. You might be required to fill in your password while adding a security key!
												<br />Please note that you will be logged out now as well.',

		'TFA_REQUIRED_KEY_AVAILABLE_BUT_UNUSABLE' => 'The administrator of this forum requires that you have added Two Factor Authentication keys for your account to (limited) parts of this forum. You have registered Two Factor authentication keys, however they are currently not (compatible) with your current browser, or setup, or are otherwise not available. 
														<br />For security reasons, we do not allow users with already registered keys to add a new one without fully being logged in. You can try to login for with a browser that worked before, or otherwise contact the %s board admin%s to request resets of your two factor authentication keys.',
		// Controller
		'ERR_NO_MATCHING_REQUEST'       => 'No matching request found',
		'ERR_NO_MATCHING_REGISTRATION'  => 'No matching registration found',
		'ERR_AUTHENTICATION_FAILURE'    => 'Authentication failed',
		'ERR_UNMATCHED_CHALLENGE'       => 'Registration challenge does not match',
		'ERR_ATTESTATION_SIGNATURE'     => 'Attestation signature does not match',
		'ERR_ATTESTATION_VERIFICATION'  => 'Attestation certificate can not be validated',
		'ERR_BAD_RANDOM'                => 'Unable to obtain a good source of randomness',
		'ERR_COUNTER_TOO_LOW'           => 'Counter too low',
		'ERR_PUBKEY_DECODE'             => 'Decoding of public key failed',
		'ERR_BAD_UA_RETURNING'          => 'User-agent returned error',
		'ERR_OLD_OPENSSL'               => 'OpenSSL has to be at least version 1.0.0, this is %s',
		'UNKNOWN_ERROR'                 => 'An unknown error happened during the validation of your security key. Please try again later.',

		'ERR_TFA_NO_REQUEST_FOUND_IN_SESSION'	=> 'No request found in the current session. Did you submit via a different page?',
		'TFA_NOT_REGISTERED'				=> 'The used security key was not registered to your account',

		'FTA_NO_RESPONSE'                   => 'No response received',
		'TFA_SELECT_KEY'                    => 'Select key type',
		'TFA_NO_RESPONSE_RECEIVED'          => 'We did not receive a response from your U2F security key. Did you press the button?',
		'TFA_NOT_SUPPORTED'                 => 'Browser not supported',
		'TFA_BROWSER_SEEMS_NOT_SUPPORTED'   => 'Sorry, currently only Google Chrome is supported.',
		'TFA_INSERT_KEY'                    => 'Insert your security key',
		'TFA_INSERT_KEY_EXPLAIN'            => 'Insert your security key into your computer and click “key inserted”.',
		'TFA_START_AUTH'                    => 'Key inserted',
		'TFA_NO_ACCESS'						=> 'It seems you have no access to this page?',
		'TFA_UNABLE_TO_UPDATE_SESSION'		=> 'Unable to update session. Please contact the board administrator',
		'TFA_DISABLED'						=> 'Two factor authentication has been disabled',

		'TFA_OTP_KEY_LOG'					=> 'OTP key',
		'TFA_OTP_KEY_LOG_EXPLAIN'			=> 'Please open the authenticator app and take over the current displayed key in the textfield below',
		'TFA_INCORRECT_KEY'					=> 'Provided key was incorrect.',
		'TFA_NO_KEY_PROVIDED'				=> 'No key provided',
		'TFA_KEY_REQUIRED'					=> 'Please provide your security key',

		'TFA_BACKUP_KEY'			=> 'Backup keys',
		'TFA_OTP'					=> 'OTP',
		'TFA_U2F'					=> 'U2F',

		'TFA_CP_TXT'				=> 'phpBB Two Factor Authentication',
		'TFA_CP_NAME'				=> 'paul999',

		'TFA_BACKUP_KEY_LOG'				=> 'Backup key',
		'TFA_BACKUP_KEY_LOG_EXPLAIN'		=> 'Please provide a backup key, which hasn’t been used before.',

		'TFA_DOUBLE_PRIORITY'				=> 'The chosen priority (%d) for module %s is already in use for module %s',

		'TFA_SOMETHING_WENT_WRONG'			=> 'Something went wrong during the request. Please try again later',

		// Module names
		'MODULE_U2F'        => 'U2F',

	)
);
