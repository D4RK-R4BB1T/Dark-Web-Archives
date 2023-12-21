<?php

/**
 * Alias for login/index/[invite_code]
 */
class r
{

    function __call($name, $arguments){
		require('login.php');
		
		$login = new Login();
		call_user_func_array(
			[
				$login,
				'index'
			],
			[$name]
		);
		
	}
}
