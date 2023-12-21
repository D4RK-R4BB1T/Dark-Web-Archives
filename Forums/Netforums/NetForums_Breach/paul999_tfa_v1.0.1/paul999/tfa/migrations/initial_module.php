<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\migrations;

class initial_module extends \phpbb\db\migration\migration
{
	/**
	 * Add or update data in the database
	 *
	 * @return array Array of table data
	 * @access public
	 */
	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_TFA')),
			array('module.add', array(
				'acp', 'ACP_TFA', array(
					'module_basename'	=> '\paul999\tfa\acp\tfa_module',
					'modes'				=> array('manage'),
				),
			)),
			array('module.add', array('ucp', '', 'UCP_TFA')),
			array('module.add', array(
				'ucp', 'UCP_TFA', array(
					'module_basename'	=> '\paul999\tfa\ucp\tfa_module',
					'modes'				=> array('manage'),
				),
			)),
		);
	}
}
