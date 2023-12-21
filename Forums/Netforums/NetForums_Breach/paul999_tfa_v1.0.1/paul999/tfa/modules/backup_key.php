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

use phpbb\db\driver\driver_interface;
use phpbb\exception\http_exception;
use phpbb\passwords\manager;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;

class backup_key extends abstract_module
{
	/**
	 * @var request_interface
	 */
	private $request;

	/**
	 * @var string
	 */
	private $backup_registration_table;

	/**
	 * Number of keys that is generated
	 */
	const NUMBER_OF_KEYS = 6;

	/**
	 * @var manager
	 */
	private $password_manager;

	/**
	 * backup_key constructor.
	 *
	 * @param driver_interface $db
	 * @param user $user
	 * @param request_interface $request
	 * @param template $template
	 * @param manager $password_manager
	 * @param string $backup_registration_table
	 */
	public function __construct(driver_interface $db, user $user, request_interface $request, template $template, manager $password_manager, $backup_registration_table)
	{
		$this->db = $db;
		$this->user = $user;
		$this->request = $request;
		$this->template = $template;
		$this->backup_registration_table = $backup_registration_table;
		$this->password_manager = $password_manager;
	}

	/**
	 * Get a language key for this specific module.
	 * @return string
	 */
	public function get_translatable_name()
	{
		return 'TFA_BACKUP_KEY';
	}

	/**
	 * Return the name of the current module
	 * This is for internal use only
	 * @return string
	 */
	public function get_name()
	{
		return 'backup_key';
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
		return $this->check_table_for_user($this->backup_registration_table, $user_id, ' AND valid = 1');
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
		return $this->check_table_for_user($this->backup_registration_table, $user_id);
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
		return 1337; // We want the backup keys as priority as low as possible, because they are a backup.
	}

	/**
	 * Start of the login procedure.
	 *
	 * @param int $user_id
	 *
	 * @return array with data to be assign to the template.
	 */
	public function login_start($user_id)
	{
		return array(
			'S_TFA_INCLUDE_HTML'	=> '@paul999_tfa/tfa_backup_authenticate.html',
		);
	}

	/**
	 * Actual login procedure
	 *
	 * @param int $user_id
	 *
	 * @return boolean
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
			if (!$registration['valid'] || $registration['last_used'])
			{
				continue;
			}
			if ($this->password_manager->check($key, $registration['secret']))
			{
				// We found a valid key.
				$sql_ary = array(
					'last_used' => time(),
					'valid'		=> false,
				);
				$sql = 'UPDATE ' . $this->backup_registration_table . ' 
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
		return !$this->check_table_for_user($this->backup_registration_table, $this->user->data['user_id'], ' AND valid = 1');
	}

	/**
	 * Start with the registration of a new security key.
	 * This page should return a name of a template, and
	 * it should assign the required variables for this template.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function register_start()
	{
		$sql = array();

		for ($i = 0; $i < self::NUMBER_OF_KEYS; $i++)
		{
			$time = time();
			$key = bin2hex(random_bytes(16));
			$sql[] = array(
				'user_id' 		=> $this->user->data['user_id'],
				'valid'			=> true,
				'secret'		=> $this->password_manager->hash($key),
				'registered' 	=> $time,
			);
			$this->template->assign_block_vars('backup', [
				'KEY'	=> $key,
				'DATE'	=> $this->user->format_date($time),
			]);
		}
		$this->db->sql_multi_insert($this->backup_registration_table, $sql);

		return 'tfa_backup_ucp_new';
	}

	/**
	 * Do the actual registration of a new security key.
	 *
	 * @return boolean Result of the registration.
	 */
	public function register()
	{
		// We don't need to do anything here.
		return true;
	}

	/**
	 * This method is called to show the UCP page.
	 * You can assign template variables to the template, or do anything else here.
	 */
	public function show_ucp()
	{
		$this->show_ucp_complete($this->backup_registration_table);
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
		$sql = 'DELETE FROM ' . $this->backup_registration_table . '
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
		$sql = 'SELECT * FROM ' . $this->backup_registration_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);

		$this->db->sql_freeresult($result);
		return $rows;
	}
}
