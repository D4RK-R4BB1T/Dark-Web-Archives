<?php

/**
 * OverviewModel
 * Handles data for overviews (pages that show user profiles / lists)
 */
class GuestModel
{
	/**
	 * Constructor, expects a Database connection
	 * @param Database $db The Database object
	 */
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	public function handleCaptcha(){
		
		$captcha = new Captcha();
		
		if ($captcha->check($_POST['captcha']) == true){
			setcookie(
				'GUEST_ADMITTANCE_TOKEN',
				md5(GUEST_ADMITTANCE_SALT . session_id()),
				time() + 60*60*12,
				'/'
			);
			
			return true;
		} else {
			return false;
			$invalid = true;
		}
	}
}