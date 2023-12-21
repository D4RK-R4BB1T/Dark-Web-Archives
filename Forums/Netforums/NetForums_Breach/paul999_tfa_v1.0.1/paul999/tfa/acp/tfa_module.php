<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\acp;

use paul999\tfa\helper\session_helper_interface;

class tfa_module
{
	/** @var  string */
	public $u_action;

	/** @var array  */
	public $new_config = array();

	/** @var   */
	public $page_title;

	/** @var   */
	public $tpl_name;

	public function main($id, $mode)
	{
		global $user, $template, $request;
		global $config, $phpbb_dispatcher, $phpbb_log;

		$user->add_lang_ext('paul999/tfa', 'acp_tfa');
		$user->add_lang('acp/board');

		$submit = $request->is_set('submit');

		$form_key = 'acp_tfa';
		add_form_key($form_key);

		$display_vars = array(
			'title'	=> 'ACP_TFA_SETTINGS',
			'vars'	=> array(
				'legend1'				=> 'ACP_TFA_SETTINGS',
				'tfa_mode'				=> array('lang' => 'TFA_MODE', 'validate' => 'int',	'type' => 'select', 'method' => 'select_tfa_method', 'explain' => true),
				'tfa_acp'               => array('lang' => 'TFA_ACP',  'validate' => 'int', 'type' => 'radio:no_yes', 'explain' => true),

				'legend4'				=> 'ACP_SUBMIT_CHANGES',
			)
		);

		/**
		 * Event to add and/or modify acp_board configurations
		 *
		 * @event paul999.tfa.tfa_config_edit_add
		 * @var	array	display_vars	Array of config values to display and process
		 * @var	string	mode			Mode of the config page we are displaying
		 * @var	boolean	submit			Do we display the form or process the submission
		 * @since 1.0.0-b2
		 */
		$vars = array('display_vars', 'mode', 'submit');
		extract($phpbb_dispatcher->trigger_event('paul999.tfa.tfa_config_edit_add', compact($vars)));

		$this->new_config = $config;
		// Copied from acp_board.php
		$cfg_array = ($request->is_set('config')) ? $request->variable('config', array('' => ''), true) : $this->new_config;
		$error = array();

		// We validate the complete config if wished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang('FORM_INVALID');
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				$config->set($config_name, $config_value);
			}
		}

		if ($submit)
		{
			$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_TFA_CONFIG_' . strtoupper($mode));

			$message = $user->lang('CONFIG_UPDATED');
			$message_type = E_USER_NOTICE;

			trigger_error($message . adm_back_link($this->u_action), $message_type);
		}

		if (!$request->is_secure())
		{
			$error[] = $user->lang('TFA_REQUIRES_SSL');
		}

		$this->tpl_name = 'acp_board';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang($display_vars['title']),
			'L_TITLE_EXPLAIN'	=> $user->lang($display_vars['title'] . '_EXPLAIN'),

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action,
		));

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND' => true,
					'LEGEND'   => array_key_exists($vars, $user->lang) ? $user->lang($vars) : $vars,
				));

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && array_key_exists($vars['lang'] . '_EXPLAIN', $user->lang))
			{
				$l_explain =  $user->lang($vars['lang'] . '_EXPLAIN');
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (array_key_exists($vars['lang'], $user->lang)) ? $user->lang($vars['lang']) : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
			));

			unset($display_vars['vars'][$config_key]);
		}
	}

	/**
	 * Select tfa method
	 */
	public function select_tfa_method($selected_value, $value)
	{
		global $user;
		$act_ary = array(
			'TFA_DISABLED'					=> session_helper_interface::MODE_DISABLED,
			'TFA_NOT_REQUIRED'				=> session_helper_interface::MODE_NOT_REQUIRED,
			'TFA_REQUIRED_FOR_ACP_LOGIN'	=> session_helper_interface::MODE_REQUIRED_FOR_ACP_LOGIN,
			'TFA_REQUIRED_FOR_ADMIN'		=> session_helper_interface::MODE_REQUIRED_FOR_ADMIN,
			'TFA_REQUIRED_FOR_MODERATOR'	=> session_helper_interface::MODE_REQUIRED_FOR_MODERATOR,
			'TFA_REQUIRED'					=> session_helper_interface::MODE_REQUIRED,
		);
		$act_options = '';
		foreach ($act_ary as $key => $data)
		{
			$selected = ($data == $selected_value) ? ' selected="selected"' : '';
			$act_options .= '<option value="' . $data . '"' . $selected . '>' . $user->lang($key) . '</option>';
		}
		return $act_options;
	}
}
