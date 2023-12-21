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

class initial_schema extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'tfa_registration'	=> array(
					'COLUMNS'	=> array(
						'registration_id'		=> array('UINT', null, 'auto_increment'),
						'user_id'				=> array('UINT', 0),
						'key_handle'			=> array('VCHAR:255', ''),
						'public_key'			=> array('VCHAR:255', ''),
						'certificate'			=> array('TEXT', ''),
						'counter'				=> array('UINT', 0),
						'last_used'				=> array('TIMESTAMP', 0),
						'registered'			=> array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'registration_id',
					'KEYS'			=> array(
						'user_id'		=> array('INDEX', array('user_id')),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'tfa_registration',
			),
		);
	}
}
