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

use paul999\tfa\exceptions\module_exception;
use paul999\tfa\modules\module_interface;
use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\di\service_collection;
use phpbb\template\template;
use phpbb\user;

/**
 * helper method which is used to detect if a user needs to use 2FA
 */
class session_helper implements session_helper_interface
{
	/**
	 * @var driver_interface
	 */
	private $db;

	/**
	 * @var config
	 */
	private $config;

	/**
	 * @var user
	 */
	private $user;

	/**
	 * @var array
	 */
	private $modules;

	private $module_data = [];

	/**
	 * @var string
	 */
	private $registration_table;

	/**
	 * @var string
	 */
	private $user_table;

	/**
	 * @var array
	 */
	private $user_array = [];

	/**
	 * @var \phpbb\template\template
	 */
	private $template;

	/**
	 * @var \phpbb\controller\helper
	 */
	private $controller_helper;

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * @param driver_interface         $db
	 * @param config                   $config
	 * @param user                     $user
	 * @param service_collection       $modules
	 * @param \phpbb\template\template $template
	 * @param \phpbb\controller\helper $controller_helper
	 * @param string                   $registration_table
	 * @param string                   $user_table
	 */
	public function __construct(driver_interface $db, config $config, user $user, service_collection $modules, template $template, helper $controller_helper, $registration_table, $user_table)
	{
		$this->db					= $db;
		$this->user					= $user;
		$this->config				= $config;
		$this->template 			= $template;
		$this->controller_helper 	= $controller_helper;
		$this->registration_table	= $registration_table;
		$this->user_table			= $user_table;
		$this->module_data			= $modules;
	}

	/**
	 * Register the tagged modules if they are enabled.
	 */
	private function validate_modules()
	{
		/**
		 * @var module_interface $module
		 */
		foreach ($this->module_data as $module)
		{
			if ($module instanceof module_interface)
			{
				// Only add them if they are actually a module_interface.
				$priority = $module->get_priority();
				if (isset($this->modules[$module->get_priority()]))
				{
					throw new module_exception(400, 'TFA_DOUBLE_PRIORITY', array($priority, get_class($module), get_class($this->modules[$priority])));
				}
				if ($module->is_enabled())
				{
					$this->modules[$priority] = $module;
				}
			}
		}
	}

	/**
	 * @param $requested_module
	 * @return null|module_interface
	 */
	public function find_module($requested_module)
	{
		/**
		 * @var module_interface $module
		 */
		foreach ($this->get_modules() as $module)
		{
			if ($module->get_name() == $requested_module)
			{
				return $module;
			}
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function get_modules()
	{
		if (empty($this->modules))
		{
			$this->validate_modules();
		}
		return $this->modules;
	}

	/**
	 * @param int $user_id
	 * @param bool $admin
	 * @param array $userdata
	 * @return bool
	 */
	public function is_tfa_required($user_id, $admin = false, $userdata = array())
	{
		if (sizeof($this->get_modules()) == 0)
		{
			return false;
		}
		switch ($this->config['tfa_mode'])
		{
			case session_helper_interface::MODE_DISABLED:
				return false;

			case session_helper_interface::MODE_NOT_REQUIRED:
				return $this->is_tfa_registered($user_id);

			case session_helper_interface::MODE_REQUIRED_FOR_ACP_LOGIN:
			case session_helper_interface::MODE_REQUIRED_FOR_ADMIN:
				return $this->do_permission_check($user_id, $userdata, 'a_');

			case session_helper_interface::MODE_REQUIRED_FOR_MODERATOR:
				return $this->do_permission_check($user_id, $userdata, array('m_', 'a_'));

			case session_helper_interface::MODE_REQUIRED:
				return true;

			default:
				return false;
		}
	}

	/**
	 * Check if the user has two factor authentication added to his account.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function is_tfa_registered($user_id)
	{
		if (isset($this->user_array[$user_id]))
		{
			return $this->user_array[$user_id];
		}

		$this->user_array[$user_id] = false; // Preset to false.

		/**
		 * @var int $priority
		 * @var module_interface $module
		 */
		foreach ($this->get_modules() as $priority => $module)
		{
			$this->user_array[$user_id] = $this->user_array[$user_id] || $module->is_usable($user_id);
		}
		return $this->user_array[$user_id];
	}

	/**
	 * Check if the user has any key registred, even if the module is not available.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function is_tfa_key_registred($user_id)
	{
		/**
		 * @var int $priority
		 * @var module_interface $module
		 */
		foreach ($this->get_modules() as $priority => $module)
		{
			if ($module->key_registered($user_id))
			{
				return true;
			}
		}
		return false;
	}


	/**
	 * @param int $user_id
	 * @param bool $admin
	 * @param bool $auto_login
	 * @param bool $viewonline
	 * @param string $redirect
	 * @param bool $secure
	 * @throws \Exception
	 */
	public function generate_page($user_id, $admin, $auto_login, $viewonline, $redirect, $secure = false)
	{
		$this->user->add_lang_ext('paul999/tfa', 'common');
		$modules = $this->get_modules();

		/**
		 * @var module_interface $row
		 */
		foreach ($modules as $row)
		{
			if ($row->is_usable($user_id))
			{
				$this->template->assign_block_vars('tfa_options', array_merge(array(
					'ID'	=> $row->get_name(),
					'NAME'	=> $this->user->lang($row->get_translatable_name()),
					'U_SUBMIT_AUTH'	=> $this->controller_helper->route('paul999_tfa_read_controller_submit', array(
						'user_id'		=> (int) $user_id,
						'admin'			=> (int) $admin,
						'auto_login'	=> (int) $auto_login,
						'viewonline'	=> (int) $viewonline,
						'class'			=> $row->get_name(),
					)),
					'S_HIDDEN_FIELDS' => build_hidden_fields(['sid' => $this->user->session_id]),
				), $row->login_start($user_id)));
			}
		}

		add_form_key('tfa_login_page');

		$random = sha1(random_bytes(32));

		$sql_ary = array(
			'tfa_random' 	=> $random,
			'tfa_uid'		=> $user_id,
		);
		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE
				session_id = '" . $this->db->sql_escape($this->user->data['session_id']) . "' AND
				session_user_id = " . (int) $this->user->data['user_id'];
		$this->db->sql_query($sql);

		$this->template->assign_vars(array(
			'REDIRECT'		=> $redirect,
			'RANDOM'		=> $random,
			'RELOGIN_NOTE'	=> $secure,
		));

		page_header('TFA_KEY_REQUIRED');

		$this->template->set_filenames(array(
			'body' => '@paul999_tfa/authenticate_main.html'
		));
		page_footer(false); // Do not include cron on this page!
	}

	/**
	 * Return the userdata for a specific user.
	 *
	 * @param int $user_id
	 * @param array $userdata
	 * @return array
	 */
	private function user_data($user_id, $userdata = array())
	{
		if (empty($userdata))
		{
			$sql = 'SELECT * FROM ' . $this->user_table . ' WHERE user_id = ' . (int) $user_id;
			$result = $this->db->sql_query($sql);
			$userdata = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
		}
		return $userdata;
	}

	/**
	 * @param int $user_id
	 * @param array $userdata
	 * @param string|array $permission
	 * @return bool
	 */
	private function do_permission_check($user_id, $userdata, $permission)
	{
		if ($this->is_tfa_registered($user_id))
		{
			return true;
		}
		$userdata = $this->user_data($user_id, $userdata);
		$auth = new auth();
		$auth->acl($userdata);

		if (!is_array($permission))
		{
			$permission = array($permission);
		}
		foreach ($permission as $perm)
		{
			if ($auth->acl_get($perm))
			{
				return true;
			}
		}
		return false;
	}
}
