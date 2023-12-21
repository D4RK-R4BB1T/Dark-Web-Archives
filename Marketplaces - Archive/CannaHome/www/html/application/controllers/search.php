<?php

class Search extends Controller {
	function listings(){
		$args = func_get_args();
		
		if( isset($_POST['q']) )
			$_SESSION['search']['q'] = htmlspecialchars($_POST['q']);
		
		require('catalog.php');
		
		$catalog = new Catalog();
		
		$catalog->listings(
			isset($args[0]) ? $args[0] : FALSE,
			FALSE,
			isset($args[2]) ? $args[2] : FALSE,
			$_SESSION['search']['q']
		);
	}
	
	function forum(){
		$args = func_get_args();
		
		if( isset($_GET['q']) )
			$_SESSION['search']['q'] = htmlspecialchars($_POST['q']);
		
		require('forum.php');
		
		$forum = new Forum();
		
		$forum->discussions(
			isset($args[0]) ? $args[0] : FALSE,
			isset($args[1]) ? $args[1] : 'recency',
			isset($args[2]) ? $args[2] : 1,
			$_SESSION['search']['q']
		);
	}
}
