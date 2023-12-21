<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\modules;

/**
 * Interface module_interface
 *
 * This interface is required for all modules implementing a Two Factor Authentication module.
 * All methods within this interface are required to be implemented by the actual module. The abstract_module class
 * provides some basic helpers, but no other methods are implemented.
 *
 * Please make sure to read the docblocks carefully. Some methods are nearly the same, but the details do matter.
 *
 * The version below matches the version in tfa_module_interface_version. If the interface changes, this version number
 * will be increased. Extension authors creating extra modules should check the tfa_module_interface_version before enabling
 * their extension to make sure it is compatible.
 *
 * @version 1.0.0
 * @package paul999\tfa\modules
 * @api
 *
 */
interface module_interface
{
	/**
	 * Get a language key for this specific module.
	 * @return string
	 */
	public function get_translatable_name();

	/**
	 * Return the name of the current module
	 * This is for internal use only
	 * @return string
	 */
	public function get_name();

	/**
	 * Return if this module is enabled by the admin
	 * (And all server requirements are met).
	 *
	 * Do not return false in case a specific user disabled this module,
	 * OR if the user is unable to use this specific module,
	 * OR if a browser specific item is missing/incorrect.
	 * @return boolean
	 */
	public function is_enabled();

	/**
	 * Check if the current user is able to use this module.
	 *
	 * This means that the user enabled it in the UCP,
	 * And has it setup up correctly.
	 * This method will be called during login, not during registration/
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function is_usable($user_id);

	/**
	 * Check if the user can potentially use this.
	 * This method is called at registration page.
	 *
	 * You can, for example, check if the current browser is suitable.
	 *
	 * @param int|boolean $user_id Use false to ignore user
	 * @return bool
	 */
	public function is_potentially_usable($user_id = false);

	/**
	 * Check if the user has any key registered with this module.
	 * There should be no check done if the key is usable, it should
	 * only return if a key is registered.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function key_registered($user_id);

	/**
	 * Get the priority for this module.
	 * A lower priority means more chance it gets selected as default option
	 *
	 * There can be only one module with a specific priority!
	 * If there is already a module registered with this priority,
	 * a Exception might be thrown
	 *
	 * @return int
	 */
	public function get_priority();

	/**
	 * Start of the login procedure.
	 * @param int $user_id
	 * @return array with data to be assign to the template.
	 */
	public function login_start($user_id);

	/**
	 * Actual login procedure
	 * @param int $user_id
	 * @return boolean
	 */
	public function login($user_id);

	/**
	 * If this module can add new keys (Or other things)
	 *
	 * @return boolean
	 */
	public function can_register();

	/**
	 * Start with the registration of a new security key.
	 * This page should return a name of a template, and
	 * it should assign the required variables for this template.
	 *
	 * @return string
	 */
	public function register_start();

	/**
	 * Do the actual registration of a new security key.
	 *
	 * @return boolean Result of the registration.
	 */
	public function register();

	/**
	 * This method is called to show the UCP page.
	 * You can assign template variables to the template, or do anything else here.
	 */
	public function show_ucp();

	/**
	 * Delete a specific row from the UCP.
	 * The data is based on the data provided in show_ucp.
	 * @param int $key
	 * @return void
	 */
	public function delete($key);
}
