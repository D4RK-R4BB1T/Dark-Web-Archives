<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\ucp;

use paul999\tfa\helper\session_helper;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;

class tfa_module
{
	/**
	 * @var string
	 */
	public $u_action;

	/**
	 * @var  string
	 */
	public $page_title;

	/**
	 * @var string
	 */
	public $tpl_name;

	/**
	 * @var user
	 */
	private $user;

	/**
	 * @var template
	 */
	private $template;

	/**
	 * @var request_interface
	 */
	private $request;

	/**
	 * @var session_helper
	 */
	private $session_helper;

	/**
	 * @param user              $user
	 * @param template          $template
	 * @param request_interface $request
	 * @param session_helper    $session_helper
	 */
	private function setup(user $user, template $template, request_interface $request, session_helper $session_helper)
	{
		$this->user = $user;
		$this->template = $template;
		$this->request = $request;
		$this->session_helper = $session_helper;
	}

	/**
	 * @param $id
	 * @param $mode
	 */
	public function main($id, $mode)
	{
		global $user, $template;
		global $request, $phpbb_container;

		$user->add_lang('posting');
		$user->add_lang_ext('paul999/tfa', array('common', 'ucp_tfa'));

		$this->setup($user, $template, $request, $phpbb_container->get('paul999.tfa.session_helper'));

		$this->create_page();
	}

	/**
	 * return array
	 */
	private function register_security_key()
	{
		try
		{
			$error = array();
			$class = $this->request->variable('class', '');
			$module = $this->session_helper->find_module($class);
			$submit = $this->request->variable('register', false, false, request_interface::POST);

			if ($module != null)
			{
				if ($submit)
				{
					$module->register();
					meta_refresh(3, $this->u_action);
					$message = $this->user->lang('TFA_KEY_ADDED') . '<br /><br />' . $this->user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
					trigger_error($message);
				}
				if ($module->can_register())
				{
					$this->template->assign_vars(array(
						'S_HIDDEN_FIELDS' => build_hidden_fields(array('class' => $class)),
						'S_UCP_ACTION'    => $this->u_action,
					));
					$this->tpl_name = $module->register_start();
				}
				else
				{
					$error[] = 'TFA_MODULE_NO_REGISTER';
				}
			}
			else
			{
				$error[] = $this->user->lang('TFA_MODULE_NOT_FOUND', $class);
			}
		}
		catch (\Exception $e)
		{
			$error[] = $e->getMessage();
		}

		return $error;
	}

	/**
	 *
	 */
	private function create_page()
	{
		$error = array();
		$s_hidden_fields = '';

		add_form_key('ucp_tfa_keys');

		$module_row = $this->request->variable('md', '', true, request_interface::POST);

		// Set desired template
		$this->tpl_name = 'ucp_tfa';
		$this->page_title = 'UCP_TFA';

		if (!empty($module_row))
		{
			switch ($module_row)
			{
				case $this->user->lang('DELETE_MARKED'):
					if (!check_form_key('ucp_tfa_keys'))
					{
						$error[] = 'FORM_INVALID';
					}
					else
					{
							$this->delete_keys();
					}
					break;

				case $this->user->lang('TFA_NEW'):
					$error = array_merge($this->register_security_key(), $error);

					if (!sizeof($error))
					{
						return; // register_security_key has its own template stuff, so we return here.
					}
					break;

			}
		}

		// Replace "error" strings with their real, localised form
		$error = array_map(array(
			$this->user,
			'lang',
		), $error);

		/**
		 * @var $module_row \paul999\tfa\modules\module_interface
		 */
		foreach ($this->session_helper->get_modules() as $module_row)
		{
			$module_row->show_ucp();

			if ($module_row->can_register())
			{
				$this->template->assign_block_vars('new_keys', array(
					'CLASS' => $module_row->get_name(),
					'NAME'	=> $this->user->lang($module_row->get_translatable_name()),
				));
			}
		}

		$this->template->assign_vars(array(
			'ERROR'           => (sizeof($error)) ? implode('<br />', $error) : '',
			'L_TITLE'         => $this->user->lang('UCP_TFA'),
			'S_HIDDEN_FIELDS' => $s_hidden_fields,
			'S_UCP_ACTION'    => $this->u_action,
		));
	}

	/**
	 *
	 */
	private function delete_keys()
	{
		$keys = $this->request->variable('keys', array(''));
		if (!empty($keys))
		{
			foreach ($keys as $row)
			{
				$row = explode('-', $row); // 0 is class, 1 is ID
				if (isset($row[0]))
				{
					$module = $this->session_helper->find_module($row[0]);
					if ($module != null)
					{
						$module->delete(intval($row[1]));
					}
				}
			}
		}
		meta_refresh(3, $this->u_action);
		$message = $this->user->lang('TFA_KEYS_DELETED') . '<br /><br />' . $this->user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
		trigger_error($message);
	}
}
