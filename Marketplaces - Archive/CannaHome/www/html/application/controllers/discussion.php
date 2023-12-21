<?php

/**
 * Class Discussion
 * Alias for forum/discussion/
 */
class Discussion{
    function __call($name, $arguments){
		require('forum.php');
		
		$browse = new Forum();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'discussion'), $args);
	}
}
