<?php

/**
 * Class Auth
 * Simply checks if user is logged in. In the app, several controllers use Auth::handleLogin() to
 * check if user if user is logged in, useful to show controllers/methods only to logged-in users.
 */
class Auth
{
	function __construct($db = false){
		$this->handleLogin($db);
	}
		
	public static function handleLogin(
		$db = false,
		$logged_in = false
	){	
		if (isset($_SESSION['u'], $_SESSION['user_id'], $_SESSION['login_string'], $_SESSION['private_key'])) {
			$u0 = $_SESSION['u'];
			$login_string = $_SESSION['login_string'];
	
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
	
			if ($stmt_checkLogin = $db->prepare("
				SELECT
					SQL_NO_CACHE `p`
				FROM
					`aID` 
				WHERE
					`u`	= ? " . (
						$_SESSION['allowMultipleSessions']
							? false
							: 'AND `si` = ?'
					) . "
				LIMIT 0, 1
			")) {
				$sessionID = session_id();
				
				$stmt_types = 's';
				$stmt_params = [&$u0];
				
				if(!$_SESSION['allowMultipleSessions']){
					$stmt_types .= 's';
					$stmt_params[] = &$sessionID;
				}
				call_user_func_array(
					[
						$stmt_checkLogin,
						'bind_param'
					],
					array_merge(
						[$stmt_types],
						$stmt_params
					)
				);
				$stmt_checkLogin->execute();   // Execute the prepared query.
				$stmt_checkLogin->store_result();
	
				if ($stmt_checkLogin->num_rows == 1) {
					// If the user exists get variables from result.
					$stmt_checkLogin->bind_result($p);
					$stmt_checkLogin->fetch();
					$login_check = hash('sha512', $p . $user_browser);
	
					// Check if user deleted or banned
					$userExistsAndNotBanned = $db->qSelect(
						"
							SELECT
								`ID`
							FROM
								`User`
							WHERE
								`ID` = ?
							AND	`Banned` = FALSE
						",
						'i',
						array(
							$_SESSION['user_id']
						)
					);
					if( !$userExistsAndNotBanned ){
						Session::destroy();
						$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_BANNED;
						header('location: ' . URL . DEFAULT_LOGGED_OUT_DESTINATION);
						die();
					}
	
					if ($login_check != $login_string) {
						// Not logged in 
						if($logged_in){
							Session::destroy();
							header('location: ' . URL . DEFAULT_LOGGED_OUT_DESTINATION);
							die();
						} else
							return new User($db, false);
					} else
						return new User($db, true);
				} else {
					// Does not exist
					if($logged_in){
						Session::destroy();
						header('location: ' . URL . DEFAULT_LOGGED_OUT_DESTINATION);
						die();
					} else
						return new User($db, false);
				}
			} else {
				if($logged_in){
					Session::destroy();
					header('location: ' . URL . DEFAULT_LOGGED_OUT_DESTINATION);
					die();
				} else
					return new User($db, false);
			}
		} else {
			// Not logged in 
			if($logged_in){
				//unset($_SESSION);
				if ($attemptedAccess = filter_var(trim($_GET['url'], '/'), FILTER_SANITIZE_URL))
					$_SESSION['redirect_suffix'] = $attemptedAccess . '/';
					
				header('location: ' . URL . DEFAULT_LOGGED_OUT_DESTINATION);
				die();
			} else
				return new User($db, false);
		}
	}
}
