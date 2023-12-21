<?php

/**
 * Class Register
 * Alias for login/
 */

class Register
{
	function __call($name, $arguments){
		require('login.php');
		
		$login = new Login();
		return $login->index($name);
	}
}
