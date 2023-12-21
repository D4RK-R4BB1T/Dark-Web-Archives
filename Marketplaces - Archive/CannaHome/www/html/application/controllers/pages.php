<?php

/**
 * Class Pages
 */
class Pages extends Controller
{

	function __construct(){
		parent::__construct('main', TRUE, TRUE);
	}
	
	function __call($page, $arguments = false){
		if (!$page){
			header('Location: ' . URL . 'error/');
			die;
		} else
			$this->view->current_page = $page;
		
		$page_model = $this->loadModel('Pages');
		
		if ($page = $this->view->page = $page_model->fetchPage($page)){
			$this->view->pages = $page_model->fetchPageTitles();
			
			$this->view->SiteName = $page['title'] . ': ' . $this->view->SiteName;
			if ($page['view'])
				$this->view->render('pages/' . $page['view']);
			else
				$this->view->render('pages/index');	
		} else {
			header('Location: ' . URL . 'error/');
			die;
		}
	}
}
