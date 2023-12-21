<?php

/**
 * Class P
 * Alias for pages/
 */
class P {
	function __call($name, $arguments){
		require('pages.php');
		
		$pages = new Pages();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($pages, '__call'), $args);
		
	}
}
