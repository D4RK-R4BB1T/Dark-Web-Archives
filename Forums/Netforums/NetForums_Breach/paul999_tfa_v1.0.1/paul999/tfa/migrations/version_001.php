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

class version_001 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v320\v320',
			'\paul999\tfa\migrations\initial_schema',
			'\paul999\tfa\migrations\update_sessions',
			'\paul999\tfa\migrations\initial_module',
			'\paul999\tfa\migrations\initial_permissions',
			'\paul999\tfa\migrations\set_role_data',
			'\paul999\tfa\migrations\initial_config',
		);
	}
}
