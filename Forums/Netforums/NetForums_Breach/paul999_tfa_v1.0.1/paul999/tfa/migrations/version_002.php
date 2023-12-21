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

class version_002 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\paul999\tfa\migrations\version_001',
			'\paul999\tfa\migrations\initial_otp_schema',
			'\paul999\tfa\migrations\initial_backup_schema',
			'\paul999\tfa\migrations\update_sessions2',
		);
	}
}
