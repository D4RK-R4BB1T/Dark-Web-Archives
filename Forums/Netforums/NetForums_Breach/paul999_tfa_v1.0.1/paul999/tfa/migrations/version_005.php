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

class version_005 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\paul999\tfa\migrations\version_004',
			'\paul999\tfa\migrations\add_config',
			'\paul999\tfa\migrations\initial_version_interface',
		);
	}
}
