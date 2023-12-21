<?php

/**
 * Class Blogs
 * Alias for forum/blogs/
 */
class Blogs{
    function __call($name, $arguments){
		require('forum.php');
		
		$forum = new Forum();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($forum, 'blogs'), $args);
	}
}
