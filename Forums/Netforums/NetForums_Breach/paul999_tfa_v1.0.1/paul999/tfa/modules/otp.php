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

use OTPAuthenticate\OTPAuthenticate;
use OTPAuthenticate\OTPHelper;
use phpbb\db\driver\driver_interface;
use phpbb\exception\http_exception;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;

class otp extends abstract_module
{
	/**
	 * @var OTPHelper
	 */
	private $otp_helper;

	/**
	 * @var OTPAuthenticate
	 */
	private $otp;

	/**
	 * @var request_interface
	 */
	private $request;

	/**
	 * @var string
	 */
	private $otp_registration_table;

	/**
	 * OTP constructor.
	 *
	 * @param driver_interface $db
	 * @param user $user
	 * @param request_interface $request
	 * @param template $template
	 * @param string                            $otp_registration_table
	 */
	public function __construct(driver_interface $db, user $user, request_interface $request, template $template, $otp_registration_table)
	{
		$this->otp_helper = new OTPHelper();
		$this->otp = new OTPAuthenticate();
		$this->db = $db;
		$this->user = $user;
		$this->request = $request;
		$this->template = $template;
		$this->otp_registration_table = $otp_registration_table;
	}

	/**
	 * Get a language key for this specific module.
	 * @return string
	 */
	public function get_translatable_name()
	{
		return 'TFA_OTP';
	}

	/**
	 * Return the name of the current module
	 * This is for internal use only
	 * @return string
	 */
	public function get_name()
	{
		return 'otp';
	}

	/**
	 * Return if this module is enabled by the admin
	 * (And all server requirements are met).
	 *
	 * Do not return false in case a specific user disabled this module,
	 * OR if the user is unable to use this specific module,
	 * OR if a browser specific item is missing/incorrect.
	 * @return boolean
	 */
	public function is_enabled()
	{
		return true;
	}

	/**
	 * Check if the current user is able to use this module.
	 *
	 * This means that the user enabled it in the UCP,
	 * And has it setup up correctly.
	 * This method will be called during login, not during registration/
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_usable($user_id)
	{
		return $this->check_table_for_user($this->otp_registration_table, $user_id);
	}

	/**
	 * Check if the user can potentially use this.
	 * This method is called at registration page.
	 *
	 * You can, for example, check if the current browser is suitable.
	 *
	 * @param int|boolean $user_id Use false to ignore user
	 *
	 * @return bool
	 */
	public function is_potentially_usable($user_id = false)
	{
		return true;
	}

	/**
	 * Check if the user has any key registered with this module.
	 * There should be no check done if the key is usable, it should
	 * only return if a key is registered.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function key_registered($user_id)
	{
		return $this->check_table_for_user($this->otp_registration_table, $user_id);
	}

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
	public function get_priority()
	{
		return 15;
	}

	/**
	 * Start of the login procedure.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function login_start($user_id)
	{
		return array(
			'S_TFA_INCLUDE_HTML'	=> '@paul999_tfa/tfa_otp_authenticate.html',
		);
	}

	/**
	 * Actual login procedure
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function login($user_id)
	{
		$key = $this->request->variable('authenticate', '');

		if (empty($key))
		{
			throw new http_exception(400, 'TFA_NO_KEY_PROVIDED');
		}

		foreach ($this->getRegistrations($user_id) as $registration)
		{
			if ($this->otp->checkTOTP($registration['secret'], $key, 'sha1'))
			{
				// We found a valid key.
				$sql_ary = array(
					'last_used' => time(),
				);
				$sql = 'UPDATE ' . $this->otp_registration_table . ' 
					SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' 
					WHERE 
						registration_id = ' . (int) $registration['registration_id'];
				$this->db->sql_query($sql);
				return true;
			}
		}
		return false;
	}

	/**
	 * If this module can add new keys (Or other things)
	 *
	 * @return boolean
	 */
	public function can_register()
	{
		return true;
	}

	/**
	 * Start with the registration of a new security key.
	 * This page should return a name of a template, and
	 * it should assign the required variables for this template.
	 *
	 * @return string
	 */
	public function register_start()
	{
		$secret = $this->otp->generateSecret();
		$QR = $this->otp_helper->generateKeyURI('totp', $secret, $this->user->data['username'], generate_board_url(), 0, 'sha1');
		$this->template->assign_vars(array(
			'TFA_QR_CODE'				=> 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . $QR,
			'TFA_SECRET'				=> $secret,
			'L_TFA_ADD_OTP_KEY_EXPLAIN'	=> $this->user->lang('TFA_ADD_OTP_KEY_EXPLAIN', $secret),
			'S_HIDDEN_FIELDS_MODULE'	=> build_hidden_fields(array(
				'secret'	=> $secret,
			)),
		));

		return 'tfa_otp_ucp_new';
	}

	/**
	 * Do the actual registration of a new security key.
	 *
	 * @throws http_exception
	 */
	public function register()
	{
		$secret = $this->request->variable('secret', '');
		$otp	= $this->request->variable('register', '');

		if (!$this->otp->checkTOTP($secret, $otp, 'sha1'))
		{
			throw new http_exception(400, 'TFA_OTP_INVALID_KEY');
		}

		$sql_ary = array(
			'user_id' 		=> $this->user->data['user_id'],
			'secret'		=> $secret,
			'registered' 	=> time(),
			'last_used' 	=> time(),
		);

		$sql = 'INSERT INTO ' . $this->otp_registration_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
	}

	/**
	 * This method is called to show the UCP page.
	 * You can assign template variables to the template, or do anything else here.
	 */
	public function show_ucp()
	{
		$this->show_ucp_complete($this->otp_registration_table);
	}

	/**
	 * Delete a specific row from the UCP.
	 * The data is based on the data provided in show_ucp.
	 *
	 * @param int $key
	 *
	 * @return void
	 */
	public function delete($key)
	{
		$sql = 'DELETE FROM ' . $this->otp_registration_table . '
			WHERE user_id = ' . (int) $this->user->data['user_id'] . '
			AND registration_id =' . (int) $key;

		$this->db->sql_query($sql);
	}

	/**
	 * Select all registration objects from the database
	 * @param integer $user_id
	 * @return array
	 */
	private function getRegistrations($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->otp_registration_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);

		$this->db->sql_freeresult($result);
		return $rows;
	}
}
