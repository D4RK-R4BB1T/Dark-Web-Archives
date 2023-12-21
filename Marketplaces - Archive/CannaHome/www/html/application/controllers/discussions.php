<?php

/**
 * Class Discussions
 * Alias for forum/discussions/
 */
class Discussions{
    function __call($name, $arguments){
		require('forum.php');
		
		$browse = new Forum();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'discussions'), $args);
	}
}
