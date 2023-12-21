<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\helper;

use paul999\tfa\modules\module_interface;

interface session_helper_interface
{
	const MODE_DISABLED = 0;
	const MODE_NOT_REQUIRED = 1;
	const MODE_REQUIRED_FOR_ACP_LOGIN = 2;
	const MODE_REQUIRED_FOR_ADMIN = 3;
	const MODE_REQUIRED_FOR_MODERATOR = 4;
	const MODE_REQUIRED = 5;

	const ACP_ENABLED = 1;
	const ACP_DISABLED = 0;

	/**
	 * @param $requested_module
	 * @return null|module_interface
	 */
	public function find_module($requested_module);

	/**
	 * Get the current active two factor auth modules.
	 *
	 * @return array
	 */
	public function get_modules();

	/**
	 * Check if Two Factor authentication for this user is required
	 *
	 * @param int $user_id The user id for this user
	 * @param bool $admin Is this user trying to login into the ACP?
	 * @param array $userdata Optional user array, used to select permissions. If in need of permissions, and this paramter isn't provided,
	 *              it will result in a extra query!
	 * @return bool
	 */
	public function is_tfa_required($user_id, $admin = false, $userdata = array());

	/**
	 * Check if the user has two factor authentication added to his account.
	 *
	 * @param array $user_id
	 * @return bool
	 */
	public function is_tfa_registered($user_id);

	/**
	 * Check if the user has any key registred, even if the module is not available.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function is_tfa_key_registred($user_id);

	/**
	 * Generate the key page after login
	 *
	 * @param int  $user_id
	 * @param bool $admin
	 * @param bool $auto_login
	 * @param bool $viewonline
	 * @param string     $redirect
	 * @param bool $secure Set this to add a message to the user that it is requrired to fill in a key for security reasons
	 *
	 * @return
	 */
	public function generate_page($user_id, $admin, $auto_login, $viewonline, $redirect, $secure = false);
}
