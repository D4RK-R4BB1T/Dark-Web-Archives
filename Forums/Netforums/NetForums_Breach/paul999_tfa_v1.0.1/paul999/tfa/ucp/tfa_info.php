<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\ucp;

class tfa_info
{
	public function module()
	{
		return array(
			'filename'	=> '\paul999\tfa\ucp\tfa_module',
			'title'		=> 'UCP_TFA_SETTINGS',
			'modes'		=> array(
				'manage'	=> array(
					'title'	=> 'UCP_TFA_MANAGE',
					'auth'	=> 'ext_paul999/tfa && cfg_tfa_mode',
					'cat'	=> array('UCP_TFA')),
			),
		);
	}
}
