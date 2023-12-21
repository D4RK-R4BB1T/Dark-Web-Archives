<?php

/**
 * Class Comment
 * Alias for forum/comment/
 */
class Comment{
    function __call($name, $arguments){
		require('forum.php');
		
		$browse = new Forum();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'comment'), $args);
	}
}
