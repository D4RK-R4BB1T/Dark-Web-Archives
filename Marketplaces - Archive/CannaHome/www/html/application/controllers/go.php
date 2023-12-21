<?php

class Go extends Controller {
	function __construct(){
		parent::__construct(FALSE, TRUE, FALSE, TRUE);
	}
	
	function __call($name, $arguments){
		switch($name){
			case 'forum':
				$forumURL	= $this->db->getSiteInfo('ForumURL');
				$location	= $forumURL . '/' . ($arguments ? implode('/', $arguments) . '/' : FALSE);
				header('Location: http://' . $location . '?xyz=' . session_id() . '-' . $_COOKIE['GUEST_ADMITTANCE_TOKEN']);
				break;
			case 'market':
				header('Location: http://' . $this->db->accessDomain . '/' . ($arguments ? implode('/', $arguments) . '/' : FALSE));
		}
		
		die;
	}
}
