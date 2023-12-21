<?php

class Guest extends Controller
{
	/**
	 * Construct this object by extending the basic Controller class
	 */
	function __construct()
	{
		parent::__construct(false, false);
		
	}
	
	function index(){
		
		$guest_model = $this->loadModel('Guest');
		
		$this->view->invalid = false;
		if( !empty($_POST) && isset($_POST['captcha']) )
			if( $guest_model->handleCaptcha() ){
				header('Location: ' . $_SERVER['REQUEST_URI']);
				die;
			} else {
				$this->view->invalid = true;
			}
				
		list($this->view->color, $this->view->customStylesheet) = $this->db->getSiteInfo('PrimaryColor', 'Stylesheet_CaptchaPage');
		
		if( $this->view->first = empty($_COOKIE['visitor']) )
			$this->view->customStylesheet_First = $this->db->getSiteInfo('Stylesheet_CaptchaPage_First');
		
		setcookie("visitor", 1, time()+157680000);
		
		$this->view->render('guest/index');
	
	}
	
}
