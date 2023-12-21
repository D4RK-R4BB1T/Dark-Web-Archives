<?php

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 * Whenever a controller is created, we also
 * 1. initialize a session
 * 2. check if the user is not logged in anymore (session timeout) but has a cookie
 * 3. create a database connection (that will be passed to all models that need a database connection)
 * 4. create a view object
 */
class Controller
{
	function __construct($template = FALSE, $logged_in = FALSE, $invite_only = FALSE, $forum = FALSE){
		// create database connection
		try {
			$this->db = new Database();
		} catch (mysqli_sql_exception $e) {
			die('Database connection could not be established.');
		}
		
		$this->session = new Session($this->db);
		
		$logged_in = $logged_in ? $logged_in : ($invite_only ? $this->db->invite_only : FALSE);
		
		$requestedURI = substr(strtok($_SERVER['REQUEST_URI'], '?'), 1);
		
		if ($this->User = Auth::handleLogin($this->db, $logged_in)){
			if (
				$redirectToExpiredTransactions =
					!$this->db->forum &&
					$this->User->expiredTransactionCount > 0 &&
					$requestedURI !== DEFAULT_DESTINATION_EXPIRED_TRANSACTIONS &&
					substr($requestedURI, 0, 2) !== 'v/' &&
					$requestedURI !== 'login/logout/'
			){
				header('Location: ' . URL . DEFAULT_DESTINATION_EXPIRED_TRANSACTIONS);
				die;
			} elseif (
				$redirectToLateDepositOrder =
					!$this->db->forum &&
					$this->User->pendingLateDepositTransactionIdentifier &&
					$requestedURI !== 'login/logout/' &&
					substr($requestedURI, 0, 3) !== 'tx/' &&
					substr($requestedURI, 0, 39) !== 'transactions/renew_order_payment_window' &&
					substr($requestedURI, 0, 39) !== 'transactions/claim_order_deposit_refund' &&
					substr($requestedURI, 0, 20) !== 'transactions/qr_code' &&
					substr($requestedURI, 0, 14) !== 'account/orders' &&
					substr($requestedURI, 0, 33) !== 'transactions/prepare_transactions' &&
					substr($requestedURI, 0, 30) !== 'transactions/sign_transactions' &&
					substr($requestedURI, 0, 25) !== 'account/send_chat_message'
			){
				$_SESSION['seen_late_deposit_order_identifiers'][] = $this->User->pendingLateDepositTransactionIdentifier;
				header('Location: ' . URL . 'tx/' . $this->User->pendingLateDepositTransactionIdentifier . '/pay/#pay');
				die;
			}
		}
	
		//$this->db->lockdown = $this->db->lockdown ?: ($this->User->ID && !$this->User->IsVendor);
	
		if(
			$this->db->lockdown &&
			!isset($_GET['topSecretAccessToken_hunter2']) &&
			!isset($_COOKIE['topSecretAccessToken_hunter2'])
		)
			$template = LOCKDOWN_TEMPLATE;
		elseif( $this->db->forum ){
			if( !$forum ){
				NXS::showError();
			}
			$template = $template == 'narrow' ? $template : FORUM_TEMPLATE;
		}
	
		// create a view object (that does nothing, but provides the view render() method)
		
		if($template && file_exists('application/' . $this->db->site_name . '/' . TEMPLATE_PATH . strtolower($template) . '/View.php')){
			require_once 'application/' . $this->db->site_name . '/' . TEMPLATE_PATH . strtolower($template) . '/View.php';
			$this->view = new templateView($this->db, $this->User);
		} elseif($template && file_exists('application/'.TEMPLATE_PATH . strtolower($template) . '/View.php')){
			require_once 'application/'.TEMPLATE_PATH . strtolower($template) . '/View.php';
			$this->view = new templateView($this->db, $this->User);
		} else {
			$this->view = new View($template, $this->db); // JUST LOAD GENERAL CLASS
		}
		
		if(
			isset($_GET['topSecretAccessToken_hunter2']) &&
			!isset($_COOKIE['topSecretAccessToken_hunter2'])
		)
			setcookie('topSecretAccessToken_hunter2', TRUE);
		
		if(
			$this->db->lockdown &&
			(
				!isset($_COOKIE['topSecretAccessToken_hunter2']) &&
				!isset($_GET['topSecretAccessToken_hunter2'])
			)
		){
			$this->view->ForumURL = $this->db->getSiteInfo('ForumURL');
			$this->view->render('error/lockdown');
			die;
		}
	}
	
	public function checkCSRFToken($token = false){
		$token = $token ?: $_POST['csrf'];
		if (
			empty($token) ||
			!hash_equals($_SESSION['csrf_token'], $token)
		)
			die();
	}
	
	public function iterativeRedirect (
		$method,
		&$parameterArrays,
		$action,
		$status = '',
		$objectName = '',
		&$requestBody = null,
		$redirectID = false,
		$stopAtFailure = false,
		$maxWait = ITERATIVE_REDIRECT_WAIT_SECONDS_DEFAULT
	){
		$initialTime = time();
		
		$redirectID = !empty($_GET['iterative_redirection_id']) ? $_GET['iterative_redirection_id'] : uniqid();
		
		if (isset($_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID])){
			$requestBody = $_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID][ITERATIVE_REDIRECT_SESSION_KEY_REQUEST_BODY];
			$parameterArrays = $_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID][ITERATIVE_REDIRECT_SESSION_KEY_PARAMETERS];
		} elseif (is_array($parameterArrays))
			$parameterArrays = array_reverse(
				$parameterArrays,
				true
			);
		else
			return true;
		
		$arrayKeys = array_keys($parameterArrays);
		$totalIterations = $arrayKeys[0] + 1;
		$iterationsElapsed = end($arrayKeys);
		while (
			is_array($parameterArrays) &&
			time() - $initialTime < $maxWait &&
			$parameterArray = array_pop($parameterArrays)
		){
			$result = call_user_func_array(
				$method,
				is_array($parameterArray) ? $parameterArray : [$parameterArray]
			);
			$iterationsElapsed++;
		}
		
		if (
			empty($parameterArrays) ||
			(
				$stopAtFailure &&
				!$result
			)
		){
			unset($_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID]);
			return true;
		}
		
		$_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID][ITERATIVE_REDIRECT_SESSION_KEY_REQUEST_BODY] = $requestBody;
		$_SESSION[ITERATIVE_REDIRECT_SESSION_KEY][$redirectID][ITERATIVE_REDIRECT_SESSION_KEY_PARAMETERS] = $parameterArrays;
		
		$this->view->iterative = true;
		$this->view->redirectDestination = URL . $action . '/?iterative_redirection_id=' . $redirectID;
		$this->view->status = $status;
		$this->view->objectsString =
			$iterationsElapsed .
			(
				$objectName
					?	' ' .
						(
							is_array($objectName) &&
							$iterationsElapsed == 1
								? 	$objectName[0]
								: 	(
										is_array($objectName)
											? $objectName[1]
											: $objectName
									)
						)
					:	''
			);
		$this->view->total = $totalIterations;
		$this->view->percentage = floor(100 * $iterationsElapsed / $totalIterations);
		
		$this->view->render(
			'redirect/index',
			false
		);
		die;
	}
	
	/**
	 * loads the model with the given name.
	 * @param $name string name of the model
	 */
	public function loadModel($name)
	{
		$path = MODELS_PATH . strtolower($name) . '_model.php';
	
		if (file_exists($path)) {
			require MODELS_PATH . strtolower($name) . '_model.php';
			// The "Model" has a capital letter as this is the second part of the model class name,
			// all models have names like "LoginModel"
			$modelName = $name . 'Model';
			// return the new model object while passing the database connection to the model
			return new $modelName($this->db, $this->User);
		}
	}
	
	public function redirectLoggedIn(){
		if( null !== Session::get('login_string') ){
			header('location: ' . URL );
			die();
		} else {
			return false;
		}
	}
	
	public function floodCheck($key, $minimumInterval, $burst = 1){
		$m = $this->db->m;
		//$m->addServer('localhost', 11211);
		
		$recentRequest = $m->get('flood-' . $this->User->ID . '-' . $key);
		if($recentRequest){
			if( $recentRequest > $burst ){
				Session::destroy();
				header('Location: ' . URL);
				die;
			}
		  
			$_SESSION['temp_notifications'][] = array(
				'Content' => 'You\'re doing that a little too often',
				'Dismiss' => '.',
				'Design' => array(
					  'Color' => 'red',
					  'Icon' => 'fa-exclamation-triangle'
				)
			);
		  
			$newRequest = $recentRequest + 1;
			$m->set(
				'flood-' . $this->User->ID . '-' . $key,
				$newRequest,
				$minimumInterval
			);
			
			return FALSE;
		}

		$m->set(
			'flood-' . $this->User->ID . '-' . $key,
			1,
			$minimumInterval
		);
		
		return TRUE;
	}
}
