<?php

/**
 * Class Listing
 * Alias for browse/listing/
 */
class I
{

    function __call($name, $arguments){
		
		require('browse.php');
		
		$browse = new Browse();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'listing'), $args);
		
	}
}
