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
use phpbb\template\template;
use phpbb\user;

abstract class abstract_module implements module_interface
{
	/**
	 * @var driver_interface
	 */
	protected $db;

	/**
	 * @var user
	 */
	protected $user;

	/**
	 * @var template
	 */
	protected $template;

	/**
	 * This method is called to show the UCP page.
	 * You can assign template variables to the template, or do anything else here.
	 *
	 * @param string $table
	 * @param string $where Extra where clause. Please make sure to use AND as first.
	 */
	protected function show_ucp_complete($table, $where = '')
	{
		$sql = 'SELECT *
			FROM ' . $this->db->sql_escape($table) . '
			WHERE user_id = ' . (int) $this->user->data['user_id'] . ' ' . $where . '
			ORDER BY registration_id ASC';

		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('keys', array(
				'CLASS'         => $this->get_name(),
				'ID'            => $row['registration_id'],
				'REGISTERED'    => $this->user->format_date($row['registered']),
				'LAST_USED'     => $row['last_used'] ? $this->user->format_date($row['last_used']) : false,
				'TYPE'			=> $this->user->lang($this->get_translatable_name()),
			));
		}
		$this->db->sql_freeresult($result);
	}

	/**
	 * Check if the provided user has a specific key in the table provided
	 *
	 * @param string $table   Table to check in
	 * @param int    $user_id The specific user
	 * @param string $where	  Extra where clause. Be sure to include AND
	 *
	 * @return bool
	 */
	protected function check_table_for_user($table, $user_id, $where = '')
	{
		$sql = 'SELECT COUNT(registration_id) as reg_id 
			FROM ' . $this->db->sql_escape($table) . '
			WHERE user_id = ' . (int) $user_id . ' ' . $where;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row && $row['reg_id'] > 0;
	}
}
