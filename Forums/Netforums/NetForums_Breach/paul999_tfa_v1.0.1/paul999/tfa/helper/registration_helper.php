<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\helper;

use paul999\u2f\Registration;

class registration_helper extends Registration
{
	/**
	 * @var int
	 */
	private $id = 0;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return registration_helper
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}
}
