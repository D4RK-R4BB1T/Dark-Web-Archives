<?php

/**
 * Class Listings
 * Alias for catalog/listings/
 */
class Listings
{

    function __call($name, $arguments){
		
		require('catalog.php');
		
		$catalog = new Catalog();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($catalog, 'listings'), $args);
		
	}
}
