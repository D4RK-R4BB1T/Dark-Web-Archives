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

use phpbb\db\migration\migration;

class initial_permissions extends migration
{
	public function update_data()
	{
		return array(
			array('permission.add', array('a_tfa')),
		);
	}
}
