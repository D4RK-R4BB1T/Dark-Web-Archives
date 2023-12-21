<?php

/**
 * Class V
 * Alias for browse/user/
 */
class V
{
	function __call($name, $arguments){
		require('browse.php');
		
		$browse = new Browse();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'user'), $args);
	}
}
