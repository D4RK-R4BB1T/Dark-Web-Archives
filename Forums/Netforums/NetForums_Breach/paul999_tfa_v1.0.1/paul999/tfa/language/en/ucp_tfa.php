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
		'TFA_NO_KEYS'				=> 'No two factor authentication keys found. You can add one below.',
		'TFA_KEYS'					=> 'On this page you can manage your two factor authentication keys.
										You can add multiple keys to your account.
											If you lose your keys, make sure to remove them from your account!
										<br /><br />
										Depending on the configuration chosen by the forum administrator,
										you might be required to add a security key before accessing the forum.
										<br /><br />
										Some security keys (Like the U2F standard) currently only work in specific 
										browsers. Due to that, it is possible that there are keys registered to your 
										account, but the access to the board is blocked because no valid keys are found
										that work with your browser. It is suggested to at least register some backup keys
										and store them in a secure location.',
		'TFA_NO_MODE'				=> 'No Mode',
		'TFA_KEYS_DELETED'			=> 'Selected keys removed.',
		'TFA_NEW'                   => 'Add new key',
		'TFA_ERROR'					=> 'It seems something went wrong...',
		'TFA_REG_FAILED'			=> 'Registration failed with error: ',
		'TFA_REG_EXISTS'			=> 'The provided key has already been registered to your account',
		'TFA_ADD_KEY'				=> 'Register new key',
		'TFA_KEY_ADDED'				=> 'Your security key has been added and can be used.',
		'TFA_INSERT_KEY'			=> 'Insert your security key, and press the button on the key',
		'TFA_REGISTERED'			=> 'Key registered',
		'TFA_LAST_USED'				=> 'Key last used',
		'TFA_MODULE_NOT_FOUND'		=> 'The selected module (%s) has not been found',
		'TFA_MODULE_NO_REGISTER'	=> 'The selected module does not accept new keys for registration',
		'TFA_SELECT_NEW'			=> 'Add new key',
		'TFA_ADD_NEW_U2F_KEY'		=> 'Add a new U2F key to your account',
		'TFA_ADD_NEW_OTP_KEY'		=> 'Add a new OTP key to your account',
		'TFA_ADD_OTP_KEY_EXPLAIN'	=> 'Scan the QR code below with a Authenticator app (Like Google Authenticator), 
		or fill in the next secret in the app: %s. After that, confirm by providing a key from your Authenticator app.',
		'TFA_OTP_KEY'				=> 'OTP key',
		'TFA_OTP_INVALID_KEY'		=> 'Invalid key provided.',
		'TFA_KEYTYPE'				=> 'Key type',
		'TFA_KEY_NOT_USED'			=> 'Not used yet',
		'TFA_KEY'                   => 'Backup key',
		'TFA_BACKUP_KEY_EXPLAIN'	=> 'Below are backup keys, generated for in case you lose you keys or you key doesn’t
										work. Please make sure to store these keys in a secure location. <br />
										In general, you only should use a backup key as last resort. <br /><br />
										When all keys are used, you can generate new keys. ',
	)
);
