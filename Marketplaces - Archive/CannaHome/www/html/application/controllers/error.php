<?php

/**
 * Class Error
 * This controller simply shows a page that will be displayed when a controller/method is not found.
 * Simple 404 handling.
 */
class Error extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct()
    {
        parent::__construct(FALSE, FALSE, FALSE, TRUE);
    }

    /**
     * This method controls what happens / what the user sees when an error happens (404)
     */
    function index($error_code = 404)
    {
		$this->view->errorCode = $error_code;
		
		switch($error_code){
			case 404:
				$this->view->errorDescription = "Page does not exist";
			break;
			default:
				$this->view->errorDescription = "Internal server error";
		}
		
        $this->view->render('error/index', 'narrow');
    }
	
}
