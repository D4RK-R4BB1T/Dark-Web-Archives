<?php

/**
 * Class Order
 * Alias for transactions/start/
 */
class Order
{

    function __call($name, $arguments){
		
		require('transactions.php');
		
		$transactions = new Transactions();
		$args = array_merge(array($name), $arguments);
		call_user_func_array(array($transactions, 'start'), $args);
		
	}
}
