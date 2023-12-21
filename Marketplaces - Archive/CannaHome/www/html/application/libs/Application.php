<?php

/**
 * Class Application
 * The heart of the app
 */
class Application {
	/** @var null The controller part of the URL */
	private $url_controller;
	/** @var null The method part (of the above controller) of the URL */
	private $url_action;
	/** @var null Parameter one of the URL */
	private $url_parameter_1;
	/** @var null Parameter two of the URL */
	private $url_parameter_2;
	/** @var null Parameter three of the URL */
	private $url_parameter_3;
	/** @var null Parameter four of the URL */
	private $url_parameter_4;
	
	/**
	 * Starts the Application
	 * Takes the parts of the URL and loads the according controller & method and passes the parameter arguments to it
	 * TODO: get rid of deep if/else nesting
	 * TODO: make the hardcoded locations ("error/index", "index.php", new Index()) dynamic, maybe via config.php
	 */
	public function __construct()
	{
		$this->splitUrl() || $this->getArgv();
	
		$url = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
	
		// check for controller: is the url_controller NOT empty ?
		if ($this->url_controller) {
			// check for controller: does such a controller exist ?
			if (file_exists(CONTROLLER_PATH . $this->url_controller . '.php')) {
				// if so, then load this file and create this controller
				// example: if controller would be "car", then this line would translate into: $this->car = new car();
				require CONTROLLER_PATH . $this->url_controller . '.php';
				$this->url_controller = new $this->url_controller();
	
				// check for method: does such a method exist in the controller ?
				if ($this->url_action) {
					if (method_exists($this->url_controller, $this->url_action) || method_exists($this->url_controller, '__call') ) {
	
						// call the method and pass the arguments to it
						if (isset($this->url_parameter_4)) {
							$this->url_controller->{$this->url_action}($this->url_parameter_1, $this->url_parameter_2, $this->url_parameter_3, $this->url_parameter_4);
						} elseif (isset($this->url_parameter_3)) {
							$this->url_controller->{$this->url_action}($this->url_parameter_1, $this->url_parameter_2, $this->url_parameter_3);
						} elseif (isset($this->url_parameter_2)) {
							$this->url_controller->{$this->url_action}($this->url_parameter_1, $this->url_parameter_2);
						} elseif (isset($this->url_parameter_1)) {
							$this->url_controller->{$this->url_action}($this->url_parameter_1);
						} else {
							// if no parameters given, just call the method without arguments
							$this->url_controller->{$this->url_action}();
						}
					} else {
						// redirect user to error page (there's a controller for that)
						
						header('Location: ' . $url . 'error/');
						die;
					}
				} else {
					// default/fallback: call the index() method of a selected controller
					$this->url_controller->index();
				}
			// obviously mistyped controller name, therefore show 404
			} else {
				// redirect user to error page (there's a controller for that)
				header('location: ' . $url . 'error/');
				die;
			}
		// if url_controller is empty, simply show the main page (index/index)
		} else {
			require CONTROLLER_PATH . 'front.php';
			$controller = new Front();
			$controller->index();
		}
	}
	
	private function splitUrl(){
		if (isset($_GET['url'])) {
			$url = trim($_GET['url'], '/');
			$url = filter_var($url, FILTER_SANITIZE_URL);
			$url = explode('/', $url);
			
			/* FORCED WITHDRAW MODE
			if (
				(
					$url[0] !== 'v' ||
					$url[2]
				) &&
				$url[0] !== 'cron' &&
				$url[0] !== 'faq' &&
				$url[0] !== 'p' &&
				$url[0] !== 'tx' &&
				$url[0] !== 'login' &&
				$url[0] !== 'redirect' &&
				$url[0] !== 'transactions' &&
				(
					$url[0] !== 'account' ||
					(
						$url[1] !== 'transactions' &&
						$url[1] !== 'orders' &&
						$url[1] !== 'profile'
					)
				)
			){
				header('Location: /account/orders/');
				die;
			}
			*/

			$this->url_controller = (isset($url[0]) ? $url[0] : null);
			$this->url_action = (isset($url[1]) ? $url[1] : null);
			$this->url_parameter_1 = (isset($url[2]) ? $url[2] : null);
			$this->url_parameter_2 = (isset($url[3]) ? $url[3] : null);
			$this->url_parameter_3 = (isset($url[4]) ? $url[4] : null);
			$this->url_parameter_4 = (isset($url[5]) ? $url[5] : null);
			
			return TRUE;
		}
		
		global $argv;
		
		/*
		if (!isset($argv)){
			header('Location: /account/orders/');
			die;
		}
		*/
		
		return FALSE;
	}
	
	private function getArgv(){
		global $argv;
		
		if( isset($argv) ){
			$this->url_controller = (isset($argv[1]) ? $argv[1] : null);
			$this->url_action = (isset($argv[2]) ? $argv[2] : null);
			$this->url_parameter_1 = (isset($argv[3]) ? $argv[3] : null);
			$this->url_parameter_2 = (isset($argv[4]) ? $argv[4] : null);
			$this->url_parameter_3 = (isset($argv[5]) ? $argv[5] : null);
			$this->url_parameter_4 = (isset($argv[6]) ? $argv[6] : null);
			
			ini_set("display_errors", TRUE);
			set_time_limit(0);
		}
		
		return TRUE;
	}
}
