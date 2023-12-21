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

class tfa_info
{
	public function module()
	{
		return array(
			'filename'	=> '\paul999\tfa\acp\tfa_module',
			'title'		=> 'ACP_TFA',
			'modes'		=> array(
				'manage'	=> array(
					'title'	=> 'ACP_TFA_MANAGE',
					'auth'	=> 'ext_paul999/tfa && acl_a_tfa',
					'cat'	=> array('ACP_TFA')),
			),
		);
	}
}
