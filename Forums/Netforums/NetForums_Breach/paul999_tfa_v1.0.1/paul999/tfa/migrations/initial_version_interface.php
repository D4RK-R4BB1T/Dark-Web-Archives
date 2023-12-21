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

class initial_version_interface extends \phpbb\db\migration\migration
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
			// Other extension can use this version to determine if the extension is installed,
			// And if the interface for the module is compatible with the version they use.
			array('config.add', array('tfa_module_interface_version', '1.0.0')),
		);
	}
}
