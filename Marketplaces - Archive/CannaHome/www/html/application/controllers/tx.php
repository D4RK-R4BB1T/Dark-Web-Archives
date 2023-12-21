<?php

/**
 * Class TX
 * Alias for transactions/transaction/
 */
class Tx {
	function __call($name, $arguments){
		require('transactions.php');
	
		$browse = new Transactions();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($browse, 'transaction'), $args);
	}
}
