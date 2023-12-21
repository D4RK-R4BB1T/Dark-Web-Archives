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
		'ACP_TFA_SETTINGS'			=> 'Two factor authentication settings',

		// As we are re-using the acp_board template, we can't add custom stuff to that page.
		// As such, we do need to have some HTML here :(.
		'ACP_TFA_SETTINGS_EXPLAIN'	=> 'Here you can set the configuration for two factor settings.
										The suggested configuration option for the requirement is either do not require Two factor authentication,
										or only require it for the ACP login. <br /><br />
										There are for the U2F security key some browser requirements:
										<ul>
											<li>Google Chrome (At least version 41)</li>
										</ul>
										Not supported:
										<ul>
											<li>Internet Explorer</li>
											<li>Edge</li>
											<li>Firefox</li>
											<li>Safari</li>
										</ul>
										<p>However, several browser vendors promised it might be supported in a newer release.
										When a browser does not meet these requirements, the user won’t be able to select U2F.</p>
										
										<h2>Receiving support</h2>
										<p>Support is only provided on www.phpbb.com, in the extension <a href="https://www.phpbb.com/customise/db/extension/phpbb_two_factor_authentication/" target="_blank">customisations database</a>. Please make sure to read the FAQ before asking your questions.</p>
										
										<h2>Want to support the development of this extension?</h2>
										<p>This extension is developed fully in my free time, however you can help me by providing a small donation to get this extension being developed.</p>
										<ul>
											<li>Become a sponsor on github: <a href="https://github.com/sponsors/paul999" target="_blank">https://github.com/sponsors/paul999</a></li>
											<li>Make a paypal donation: <a href="https://paypal.me/sohier" target="_blank">https://paypal.me/sohier</a></li>
											<li>Make a donation via bunq: <a href="https://bunq.me/Paul999" target="_blank">https://bunq.me/Paul999</a></li>
										</ul>
										',
		'TFA_REQUIRES_SSL'			=> 'You seem to be using a non secure connection. This extension requires a secure SSL connection for some security keys to work. Users won’t be able to choose these options unless you enable a secure connection to your board.',

		'TFA_MODE'						=> 'Two factor authentication mode',
		'TFA_MODE_EXPLAIN'				=> 'Here you can select which users are required (If any at all) to use two factor authentication mode. Selecting “Two factor authentication disabled” will disable the functionality completely.',
		'TFA_DISABLED'					=> 'Two factor authentication disabled',
		'TFA_NOT_REQUIRED'				=> 'Do not require two factor authentication',
		'TFA_REQUIRED_FOR_ACP_LOGIN'	=> 'Require two factor authentication for the ACP login only',
		'TFA_REQUIRED_FOR_ADMIN'		=> 'Require two factor authentication for all administrators',
		'TFA_REQUIRED_FOR_MODERATOR'	=> 'Require two factor authentication for all moderators and administrators',
		'TFA_REQUIRED'					=> 'Require two factor authentication for all users',

		'TFA_ACP'           => 'Require two factor authentication for administration panel',
		'TFA_ACP_EXPLAIN'   => 'When set to no, administrators don’t need to use a two factor authentication key when logging in for the ACP. Disabling this might not be suggested.'
	)
);
