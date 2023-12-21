<?php
/**
*
* 2FA extension for the phpBB Forum Software package.
*
* @copyright (c) 2015 Paul Sohier
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace paul999\tfa\event;

use paul999\tfa\helper\session_helper_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\event\data;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
	/**
	 * @var session_helper_interface
	 */
	private $session_helper;

	/**
	 * @var user
	 */
	private $user;

	/**
	 * @var request_interface
	 */
	private $request;

	/**
	 * @var driver_interface
	 */
	private $db;

	/**
	 * @var template
	 */
	private $template;

	/**
	 * @var config
	 */
	private $config;

	/**
	 * @var string
	 */
	private $php_ext;

	/**
	 * @var string
	 */
	private $root_path;

	/**
	 * Constructor
	 *
	 * @access   public
	 *
	 * @param session_helper_interface          $session_helper
	 * @param user                              $user
	 * @param request_interface                 $request
	 * @param driver_interface $db
	 * @param template $template
	 * @param config $config
	 * @param string                            $php_ext
	 * @param string                            $root_path
	 */
	public function __construct(session_helper_interface $session_helper, user $user, request_interface $request, driver_interface $db, template $template, config $config, $php_ext, $root_path)
	{
		$this->session_helper		= $session_helper;
		$this->user					= $user;
		$this->request				= $request;
		$this->config				= $config;
		$this->db					= $db;
		$this->template				= $template;
		$this->php_ext				= $php_ext;
		$this->root_path			= $root_path;
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 *
	 * @return array
	 * @static
	 * @access public
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.auth_login_session_create_before'		=> 'auth_login_session_create_before',
			'core.user_setup_after'						=> 'user_setup_after',
			'core.permissions'			        		=> 'add_permission',
		);
	}

	/**
	 * @param data $event
	 */
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['a_tfa'] = array('lang' => 'ACL_A_TFA', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	/**
	 * @param data $event
	 */
	public function user_setup_after($event)
	{
		$this->user->add_lang_ext('paul999/tfa', 'common');
		if (strpos($this->user->page['page'], 'paul999/tfa/save') !== false)
		{
			// We are at our controller. Don't do anything.  In all cases.
			@define('SKIP_CHECK_DISABLED', true);
			return;
		}

		// We skip this when tfa is disabled or we are at a page related to login (This includes logout :))
		if ($this->config['tfa_mode'] == session_helper_interface::MODE_DISABLED || defined('IN_LOGIN'))
		{
			return;
		}

		if ($this->user->data['is_bot'] == false && $this->user->data['user_id'] != ANONYMOUS && $this->session_helper->is_tfa_required($this->user->data['user_id'], false, $this->user->data) && !$this->session_helper->is_tfa_registered($this->user->data['user_id']))
		{
			@define('SKIP_CHECK_DISABLED', true);
			if ($this->user->page['page_name'] === 'memberlist.' . $this->php_ext && $this->request->variable('mode', '') == 'contactadmin')
			{
				// We are at the contact admin page. We will allow this in all cases.
				return;
			}

			$this->user->set_cookie('rn', $this->user->data['session_id'], time() + 3600 * 24, true);

			$msg_title =  $this->user->lang['INFORMATION'];
			if ($this->session_helper->is_tfa_key_registred($this->user->data['user_id']))
			{
				// the user has keys registered, but they are not usable (Might be due to browser requirements, or others)
				// We will not allow them to register a new key. They will need to contact the admin instead unfortunately.
				$url = phpbb_get_board_contact_link($this->config, $this->root_path, $this->php_ext);
				$msg_text = $this->user->lang('TFA_REQUIRED_KEY_AVAILABLE_BUT_UNUSABLE', '<a href="' . $url . '">', '</a>');
				$this->user->session_kill();
				$this->generate_fatal_error($msg_title, $msg_text);
			}

			$sql = 'SELECT module_id FROM ' . MODULES_TABLE . " WHERE module_langname = 'UCP_TFA' OR module_langname = 'UCP_TFA_MANAGE'";
			$result = $this->db->sql_query($sql, 3600);
			$allowed_i = array();

			while ($row = $this->db->sql_fetchrow($result))
			{
				$allowed_i[] = $row['module_id'];
			}
			$this->db->sql_freeresult($result);
			$ucp_mode = '-paul999-tfa-ucp-tfa_module';
			$allowed_i[] = $ucp_mode;

			if ($this->user->page['page_name'] === 'ucp.' . $this->php_ext && in_array($this->request->variable('i', ''), $allowed_i))
			{
				return; // We are at our UCP page, so skip any other checks. This page is always available
			}
			$url = append_sid("{$this->root_path}ucp.{$this->php_ext}", "i={$ucp_mode}");
			$msg_text = $this->user->lang('TFA_REQUIRED_KEY_MISSING', '<a href="' . $url . '">', '</a>');

			$this->generate_fatal_error($msg_title, $msg_text);
		}

		// If the user had no key when logged in, but now has a key, we will force him to use the key.
		if ($this->user->data['is_bot'] == false && $this->user->data['user_id'] != ANONYMOUS && $this->request->variable($this->config['cookie_name'] . '_rn', '', false, request_interface::COOKIE) !== '' && $this->session_helper->is_tfa_required($this->user->data['user_id'], false, $this->user->data))
		{
			$this->session_helper->generate_page($this->user->data['user_id'], false, $this->user->data['session_autologin'], $this->user->data['session_viewonline'], $this->user->page['page'], true);
		}
	}

	/**
	 * @param data $event
	 *
	 * @return data $event|null
	 * @throw http_exception
	 */
	public function auth_login_session_create_before($event)
	{
		if ($this->config['tfa_mode'] == session_helper_interface::MODE_DISABLED)
		{
			return $event;
		}
		if ($event['admin'] && $this->config['tfa_acp'] == session_helper_interface::ACP_DISABLED)
		{
			// two factor authentication is disabled for the ACP.
			return $event;
		}
		if (isset($event['login'], $event['login']['status']) && $event['login']['status'] == LOGIN_SUCCESS)
		{
			// We have a LOGIN_SUCCESS result.
			if ($this->session_helper->is_tfa_required($event['login']['user_row']['user_id'], $event['admin'], $event['user_row']))
			{
				if (!$this->session_helper->is_tfa_registered($event['login']['user_row']['user_id']))
				{
					// While 2FA is enabled, the user has no methods added.
					// We simply return and continue the login procedure (The normal way :)),
					// and will disable all pages until he has added a 2FA key.
					return $event;
				}
				else
				{
					$this->session_helper->generate_page($event['login']['user_row']['user_id'], $event['admin'], $event['autologin'], !$this->request->is_set_post('viewonline'), $this->request->variable('redirect', ''));
				}
			}
		}
		return null;
	}

	/**
	 * Generate a fatal error. This method will always exit.
	 *
	 * @param $msg_title string Error title
	 * @param $msg_text string Error message
	 */
	private function generate_fatal_error($msg_title, $msg_text)
	{
		page_header($msg_title);

		$this->template->set_filenames(array(
				'body' => 'message_body.html')
		);

		$this->template->assign_vars(array(
			'MESSAGE_TITLE' => $msg_title,
			'MESSAGE_TEXT' => $msg_text,
			'S_USER_WARNING' => true,
			'S_USER_NOTICE' => false,
		));

		// We do not want the cron script to be called on error messages
		define('IN_CRON', true);

		page_footer();
	}
}
