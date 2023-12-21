<?php

class Session {
	public function _open(){
		return true;
	}
	public function _close(){
		return true;
	}
	public function _read($sessionID){
		if (
			$results = $this->db->qSelect(
				"
					SELECT SQL_NO_CACHE 
						`Data`
					FROM
						`Session`
					WHERE
						`ID` = ?
				",
				's',
				[$sessionID]
			)
		)
			return $results[0]['Data'];
		
		return '';
	}
	public function _write(
		$sessionID,
		$data
	){
		return $this->db->qQuery(
			"
				REPLACE INTO
					`Session` (
						`ID`,
						`Access`,
						`Data`,
						`ServerID`
					)
				VALUES (
					?,
					NOW(),
					?,
					@@server_id
				)
			",
			'ss',
			[
				$sessionID,
				$data
			]
		) !== false;
	}
	public function _destroy($sessionID){
		return $this->db->qQuery(
			"
				DELETE FROM
					`Session`
				WHERE
					`ID` = ?
			",
			's',
			[$sessionID]
		) !== false;
	}
	public function _clean($lifetime){
		return $this->db->qQuery(
			"
				DELETE FROM
					`Session`
				WHERE
					`Access` < NOW() - INTERVAL ? SECOND
			",
			'i',
			[$lifetime]
		) !== false;
	}
	
	public function __construct(Database $db){
		$this->db = $db;
		
		if (session_id() == '') {
			if (
				isset($_GET['xyz']) &&
				$xyz = explode('-', $_GET['xyz'])
			){
				session_id($xyz[0]);
				setcookie(
					'GUEST_ADMITTANCE_TOKEN',
					$xyz[1],
					time() + 60*60*12,
					'/'
				);
			}
			
			// Forces sessions to only use cookies.
			if (ini_set('session.use_only_cookies', 1) === FALSE) {
				header("Location: " . URL . "login");
				exit();
			}
		
			// Gets current cookies params.
			$cookieParams = session_get_cookie_params();
			session_set_cookie_params(
				$cookieParams["lifetime"],
				$cookieParams["path"],
				$cookieParams["domain"],
				SECURE,
				true
			);
		
			// Sets the session name to the one set above.
			session_name(SESSION_NAME);
			
			session_set_save_handler(
				[
					$this,
					'_open'
				],
				[
					$this,
					'_close'
				],
				[
					$this,
					'_read'
				],
				[
					$this,
					'_write'
				],
				[
					$this,
					'_destroy'
				],
				[
					$this,
					'_clean'
				]
			);
			
			session_start(); // Start the PHP session
			
			if (isset($_GET['xyz'])){
				$url = substr(strtok($_SERVER['REQUEST_URI'], '?'), 1);
				header('Location: /'.$url); die;
				die;
			}
		}
	}
	
	public static function set($key, $value){
		$_SESSION[$key] = $value;
	}
	
	public static function get($key){
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}
	}
	
	public static function destroy(){
		// Unset all session values 
		$_SESSION = array();
		
		// get session parameters 
		$params = session_get_cookie_params();
		
		// Delete the actual cookie. 
		setcookie(session_name(),'', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		
		// Destroy session 
		session_destroy();
	}
}
