<?php

/**
 * LoginModel
 *
 * Handles the user's login / logout / registration stuff
 */

class LoginModel
{
	/**
	 * Constructor, expects a Database connection
	 * @param Database $db The Database object
	 */
	public function __construct(Database $db)
	{
		$this->db = $db;
	}
	
	/**
	 * Login process (for DEFAULT user accounts).
	 * @return bool success state
	 */
	public function login($username = false, $password = false, $prehashed = false, $has_captcha = true){
		if(isset($_POST['username'])) Session::set('login_username', strip_tags($_POST['username']));
		if(isset($_POST['prehashed'])) Session::set('login_prehashed', strip_tags($_POST['prehashed']));
		if(isset($_POST['return'])) Session::set('login_return', $_POST['return']);
		
		$username = isset($_POST['username']) ? $_POST['username'] : $username;
		$password = isset($_POST['password']) ? $_POST['password'] : $password;
		$prehashed = isset($_POST['prehashed']) ? true : $prehashed;
		
		if ($username == false) {
			$_SESSION["feedback_negative"]['username'] = FEEDBACK_USERNAME_FIELD_EMPTY;
			return false;
		} elseif ($password == false) {
			$_SESSION["feedback_negative"]['password'] = FEEDBACK_PASSWORD_FIELD_EMPTY;
			return false;
		} elseif ($prehashed && !(preg_match('/^[a-f0-9]{32}$/', $username) && preg_match('/^[a-f0-9]{128}$/', $password)) ){
			$_SESSION["feedback_negative"]['general'][] = FEEDBACK_INVALID_HASH;
			return false;
		} elseif($has_captcha && !$this->checkCaptcha()) {
			$_SESSION["feedback_negative"]['captcha'] = FEEDBACK_CAPTCHA_WRONG;
			return false;
		}
		
		$username = $prehashed ? $username : sha1( strtolower($username) );
		$u = sha1(SITEWIDE_USERNAME_SALT.$username);
		
		if ( $stmt_Authenticate = $this->db->prepare("
			SELECT
				`s`,
				`p`
			FROM `aID`
			WHERE `u` = ?
			LIMIT 1
		") ){
			$stmt_Authenticate->bind_param('s', $u);
			$stmt_Authenticate->execute();
			$stmt_Authenticate->store_result();
			if ( $stmt_Authenticate->num_rows == 1 ){
				if ($this->checkbrute($u) == true) {
					$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_LOCKED_BRUTE;
					return false;
				} else {
					$stmt_Authenticate->bind_result($salt, $db_password);
					$stmt_Authenticate->fetch();
					
					$password = $prehashed ? $password : hash('sha512', $password);
					$p0 = hash('sha512', $password . $salt);
					$p = hash('sha512', $p0 . $salt);
					
					if ($db_password == $p) {
						$aID = sha1($username . $salt);
						if ($stmt_User = $this->db->prepare("
							SELECT
								`User`.`ID`,
								`User`.`JoinDateTime`,
								`Alias`,
								`PrivateKey`,
								IF(`2FA` = TRUE, `PGP`, FALSE),
								`Attributes`,
								`User`.`Vendor`,
								IF(
									`User`.`Vendor`,
									FALSE,
									(
										SELECT
											COUNT(`Transaction`.`ID`)
										FROM
											`Transaction`
										WHERE
											`BuyerID` = `User`.`ID`
										AND	`Status` = 'pending deposit'
										AND	`RedeemScript` IS NOT NULL
										AND	`Timeout` > NOW()
									)
								),
								`User`.`2FA`,
								`User`.`Admin`,
								`User`.`AllowMultipleSessions`
							FROM `User`
							WHERE
								`aID` = ?
							AND	`Banned` = FALSE
							LIMIT 1
						")){
							$stmt_User->bind_param('s', $aID);
							$stmt_User->execute();
							$stmt_User->store_result();
							if ( $stmt_User->num_rows == 1 ){
								// LOGIN SUCCESSFUL
								$stmt_User->bind_result(
									$user_id,
									$joinDateTime,
									$user_alias,
									$user_privatekey,
									$user_pgp,
									$user_attributes,
									$isVendor,
									$pending_deposit_transaction_count,
									$twoFA,
									$isAdmin,
									$allowMultipleSessions
								);
								$stmt_User->fetch();
								
								// 2FA Check
								if(
									$twoFA &&
									$this->authenticate2FA($u, $user_pgp) == FALSE
								){
									// Needs to do or re-do 2FA authentication
									return false;
								} elseif( isset($_SESSION['credentials']) )
									unset($_SESSION['credentials']); // REMOVE STORED CREDENTIALS
								
								// Get the user-agent string of the user.
								$user_browser = $_SERVER['HTTP_USER_AGENT'];
			
								// XSS protection as we might print this value
								$user_id = preg_replace("/[^0-9]+/", "", $user_id);
								Session::set('user_id', $user_id);
								
								Session::set('u', $u);
			
								// XSS protection as we might print this value
								$alias = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $user_alias);
								
								Session::set('isAdmin', $isAdmin);
								Session::set('login_string', hash('sha512', $p . $user_browser));
								Session::set('alias', $user_alias);
								Session::set('pgp', $user_pgp);
								Session::set('joinDateTime', $joinDateTime);
								Session::set('allowMultipleSessions', $allowMultipleSessions);
								
								if( empty($user_privatekey) ){
									$rsa2 = new Crypt_RSA();
			
									$rsa2->setHash('sha256');
									$rsa2->setMGFHash('sha256');
									 
									$rsa2->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
									$rsa2->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
									
									define('CRYPT_RSA_EXPONENT', 65537);
									define('CRYPT_RSA_SMALLEST_PRIME', 64); 
									extract($rsa2->createKey(2048));
									
									$rsa2->loadKey($privatekey);
									
									$rsa2->setPassword($p0);
									$privatekey = $rsa2->getPrivateKey();
									
									if( $stmt_updateKeypairs = $this->db->prepare("
										UPDATE
											`User`
										SET
											`PrivateKey` = ?,
											`PublicKey` = ?
										WHERE
											`ID` = ?
									") ){
										$stmt_updateKeypairs->bind_param('ssi', $privatekey, $publickey, $user_id);
										$stmt_updateKeypairs->execute();
									}
									
									$user_privatekey = $privatekey;
								}
								
								// Fetch Encryption Keys
								$rsa = new Crypt_RSA();
								$rsa->setHash('sha256');
								$rsa->setMGFHash('sha256');
								$rsa->setPassword($p0); 
								$rsa->loadKey($user_privatekey);
								$rsa->setPassword();
								Session::set('private_key', $rsa->getPrivateKey());
								Session::set('attributes', json_decode( $rsa->decrypt($user_attributes), true ) );
			
								// Login successful
								
								$this->db->qQuery(
									"
										UPDATE
											`aID`
										SET
											`si` = ?
										WHERE
											`u` = ?
									",
									'ss',
									array(
										session_id(),
										$u
									)
								);
								$this->db->incrementStatistic('logins', 1);
								
								unset(
									$_SESSION['feedback_negative'],
									$_SESSION['login_username'],
									$_SESSION['login_prehashed']
								);
								
								
								$followReturn =
									isset($_POST['return']) &&
									$_POST['return'] !== 'login/' &&
									$_POST['return'] !== '/' &&
									preg_match('/^\w[\/\w]*\/$/', $_POST['return']);
								if( $followReturn )
									return $_POST['return'];	
								else {
									if ($this->db->forum)
										return DEFAULT_LOGIN_DESTINATION_FORUM;
									/*if( $this->db->prefix )
										return DEFAULT_LOGIN_DESTINATION;*/
									if ($isVendor)
										return DEFAULT_LOGIN_DESTINATION_VENDOR;
									if ($pending_deposit_transaction_count > 0)
										return DEFAULT_LOGIN_DESTINATION_PENDING_DEPOSIT;
										
									return DEFAULT_LOGIN_DESTINATION;
								}
							} {
								//die($this->db->error);
								
								return $this->registerNewUser([$salt,$aID]);
								
								// Account was likely banned
								//$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_BANNED;
							}
						} else {
							// aID didn't match any User
							// Should probably delete aid
							
							
							return false;
						}
					} else {
						$_SESSION["feedback_negative"]['password'] = FEEDBACK_LOGIN_FAILED;
						// Password is not correct 
						// We record this attempt in the database 
						
						$this->_recordFailedAuthenticationAttempt($u);
						
						return false;
					}
				}
			} else {
				$_SESSION["feedback_negative"]['password'] = FEEDBACK_LOGIN_FAILED;
				return false;
			}
		}
	
		// default return
		return false;
	}
	
	private function _recordFailedAuthenticationAttempt($u){
		return $this->db->qQuery(
			"
				INSERT INTO
					`LoginAttempt` (`u`, `DateTime`)
				VALUES
					(?, NOW())
			",
			's',
			array(
				$u
			)
		);
	}
	
	private function _generatePGPMessage($publicKey_ASCII, $sessionName, &$answer = NULL){
		unset( $_SESSION[$sessionName] );
		
		try {
			$PGP = new PGP($publicKey_ASCII);
		} catch (Exception $e) {
			// Unsupported PGP Public Key.
			// SHOULDN'T HAPPEN UNDER NORMAL CIRCUMSTANCES
			
			// Notify User to Raise Alarm
			$_SESSION["feedback_negative"]['general'][] = FEEDBACK_UNSUPPORTED_PGP_PUBLIC_KEY;
		
			return false; // INVALID PGP
		}
		
		$username = !empty($_POST['username']) ? $_POST['username'] : $_SESSION['credentials']['username'];
		
		if( $message = NXS::generateAuthenticationPGPMessage($PGP, $sessionName, $this->db->site_name, $this->db->accessDomain, $username, $answer) ){
			$_SESSION[ $sessionName ]['PGP'] = $publicKey_ASCII;
			return $message;
		}
		
		return FALSE;
	}
	
	private function authenticate2FA($u, $PGP, $sessionName = 'twoFA', $postedVariableName = 'authentication_code'){
		$memcachedVariableName = 'twoFA-' . $u . '-answer';
		$m = new Memcached();
		$m->addServer('localhost', 11211);
		
		if( isset($_SESSION[$sessionName]) ){
			// Check if correct PGP and Authentication code
			$correct2FA =
				!empty( $_POST[$postedVariableName] ) &&
				preg_match(
					'/(\d{10})/',
					$_POST[$postedVariableName],
					$matches
				) &&
				($_POST[$postedVariableName] = $matches[1]) &&
				$PGP == $_SESSION[$sessionName]['PGP'] &&
				$_POST[$postedVariableName] == $_SESSION[$sessionName]['answer'] &&
				$m->get($memcachedVariableName) == $_POST[$postedVariableName]; // Check 2FA not expired
			
			if($correct2FA){
				unset( $_SESSION[$sessionName] );
			
				return TRUE;
			} else {
				// Log Failed Login Attempt
				//$this->_recordFailedAuthenticationAttempt($u);
				
				$_SESSION["feedback_negative"][$postedVariableName] = FEEDBACK_FAILED_2FA_ATTEMPT;
			}
		}
		
		// No 2FA AuthenticationCode. Generate one and present it to user.
		$answer = FALSE;
		if( $this->_generatePGPMessage($PGP, $sessionName, $answer) ){
			if(empty($_SESSION['credentials']) )
				$_SESSION['credentials'] = array(
					'username'	=> $_POST['username'],
					'password'	=> $_POST['password']
				);
			
			$m->set(
				$memcachedVariableName,
				$answer,
				CACHE_EXPIRATION_2FA_AUTHENTICATION_CODE
			);
		}
		return FALSE;
	}
	
	/**
	 * Log out process, deletes cookie, deletes session
	 */
	public function logout()
	{
		$_SESSION = array();
	
		// get session parameters 
		$params = session_get_cookie_params();
		
		// Delete the actual cookie. 
		setcookie(session_name(),'', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		
		// Destroy session 
		session_destroy();
	}
	
	public function checkbrute($u) {
		if ($stmt = $this->db->prepare("
			SELECT `DateTime` 
			FROM `LoginAttempt`
			WHERE
				`u` = ?
			AND	`DateTime` > (NOW() - INTERVAL 2 HOUR)
		")) {
			$stmt->bind_param('s', $u);
	
			$stmt->execute();
			$stmt->store_result();
	
			if ($stmt->num_rows > 5) {
				return true;
			} else {
				return false;
			}
		} else {
			//die('statement '.$this->db->error);
			// Could not create a prepared statement
			return false;
		}
	}
	
	public function registerNewUser($existingAID = false){
		// perform all necessary form checks
		if( isset($_POST['username']) ) Session::set('login_username', strip_tags($_POST['username']));
		if( isset($_POST['prehashed']) ) Session::set('login_prehashed', strip_tags($_POST['prehashed']));
		if( isset($_POST['invite_code']) ) Session::set('login_invite_code', strip_tags($_POST['invite_code']));
		
		$_SESSION['register_attempt'] = true;
		
		if (
			empty($_POST['username']) ||
			!preg_match('/' . REGEX_USERNAME . '/', $_POST['username'])
		) {
			$_SESSION["feedback_negative"]['username'] = FEEDBACK_USERNAME_FIELD_EMPTY;
			return false;
		} elseif (empty($_POST['password'])) {
			$_SESSION["feedback_negative"]['password'] = FEEDBACK_PASSWORD_FIELD_EMPTY;
			return false;
		} elseif(
			!isset($_POST['reserved_pgp_code']) &&
			!$existingAID &&
			empty($_POST['password_confirm'])
		){
			$_SESSION["feedback_negative"]['failed_register'] = true;
			return false;
		} elseif (
			!isset($_POST['reserved_pgp_code']) &&
			!$existingAID &&
			(
				empty($_POST['password_confirm']) ||
				$_POST['password'] !== $_POST['password_confirm']
			)
		){
			$_SESSION["feedback_negative"]['password'] = 'The two passwords were not the same.';
			return false;
		} elseif (isset($_POST['prehashed']) && !(preg_match('/^[a-f0-9]{40}$/', $_POST['username']) && preg_match('/^[a-f0-9]{128}$/', $_POST['password'])) ) {
			$_SESSION["feedback_negative"]['general'][] = FEEDBACK_INVALID_HASH;
			return false;
		}elseif (!$this->checkCaptcha() && !isset($_POST['reserved_pgp_code']) ) {
			$_SESSION["feedback_negative"]['captcha'] = FEEDBACK_CAPTCHA_WRONG;
			return false;
		} elseif (isset($_POST['reserved_pgp_code']) && !$this->checkReserved(strtolower($_POST['username'])) ) {
			$_SESSION["feedback_negative"]['reserved_pgp_code'] = 'Wrong authentication code';
			return false;
		} elseif (
			!$existingAID &&
			(
				(
					$this->db->invite_only &&
					empty($_POST['invite_code'])
				) ||
				(
					!empty($_POST['invite_code']) &&
					!$this->checkInviteCode($_POST['invite_code'], $isVendor)
				)
			)
		){
			$_SESSION["feedback_negative"]['invite_code'] = 'Invalid invite code';
			return false;
		} elseif (!empty($_POST['username']) && !empty($_POST['password'])){
		
			$username = isset($_POST['prehashed']) ? $_POST['username'] : sha1( strtolower($_POST['username']) );
			$u = sha1(SITEWIDE_USERNAME_SALT.$username);
			
			if($stmt_ExistingUser = $this->db->prepare("
				SELECT
					count(*)
				FROM
					`aID`
				WHERE
					`u` = ?
				LIMIT 1
			")){
				if (!$existingAID){
					$stmt_ExistingUser->bind_param('s', $u);
					$stmt_ExistingUser->execute();
					$stmt_ExistingUser->store_result();
					$stmt_ExistingUser->bind_result($user_count);
				
					$stmt_ExistingUser->fetch();
					if ($user_count > 0) {
						$_SESSION["feedback_negative"]['username'] = FEEDBACK_USERNAME_ALREADY_TAKEN;
						return false;
					} elseif($isVendor){
						$existingAlias = $this->db->qSelect(
							"
								SELECT
									COUNT(*) count
								FROM
									`User`
								WHERE
									`Alias` = ?
								LIMIT 1
							",
							's',
							array($_POST['username'])
						);
					
						if( $existingAlias[0]['count'] > 0 ){
							$_SESSION["feedback_negative"]['username'] = FEEDBACK_USERNAME_ALREADY_TAKEN;
							return false;
						}
					}
				}
			} else
				return false;
			
			list ($s, $a) = $existingAID ?: $this->get_salt_a($username);
			
			$password = isset($_POST['prehashed']) ? $_POST['password'] : hash('sha512', $_POST['password']);
			$p0 = hash('sha512', $password . $s);
			$p = hash('sha512', $p0 . $s);
		
			$rsa = new Crypt_RSA();
			
			$rsa->setHash('sha256');
			$rsa->setMGFHash('sha256');
			 
			$rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
			$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
			
			define('CRYPT_RSA_EXPONENT', 65537);
			define('CRYPT_RSA_SMALLEST_PRIME', 64); 
			extract($rsa->createKey(2048));
			
			$rsa->loadKey($privatekey);
			
			$rsa->setPassword($p0);
			$privatekey = $rsa->getPrivateKey();
			
			$alias = $_POST['username'];
			    // $isVendor ? $_POST['username'] : $this->getUniqueAlias();
			
			if( $this->checkReserved(strtolower($_POST['username']), $alias) )
				unset($_SESSION['reserved_username']);
			else
				return false;
			
			$oldFriend = $_POST['username'] == $alias ? TRUE : FALSE;
			
			$stmt_InsertAID = $this->db->prepare("
				INSERT INTO
					`aID` (`u`, `s`, `p`)
				VALUES
					(?, ?, ?)
			");
			
			$stmt_InsertUser = $this->db->prepare("
				INSERT INTO
					`User` (`aID`, `PublicKey`, `PrivateKey`, `Alias`, `JoinDateTime`)
				VALUES
					(?, ?, ?, ?, NOW())
				ON DUPLICATE KEY UPDATE
					`aID` = IF(`aID` IS NULL, ?, `aID`),
					`PublicKey` = IF(`aID` IS NULL, ?, `PublicKey`),
					`PrivateKey` = IF(`aID` IS NULL, ?, `PrivateKey`),
					`JoinDateTime` = NOW()
			");
			
			$stmt_updateUser = $this->db->prepare("
				UPDATE
					`User`
				SET
					`Attributes` = ?
				WHERE
					`ID` = ?
			");
			
			if( $stmt_InsertAID !== FALSE && $stmt_InsertUser !== FALSE && $stmt_updateUser !== FALSE ){
				if (!$existingAID){
					$stmt_InsertAID->bind_param('sss', $u, $s, $p);
					if(!$stmt_InsertAID->execute()) {
						//die( $this->db->error );
						$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_CREATION_FAILED;
						return false;
					}
				}
				$stmt_InsertUser->bind_param('sssssss', $a, $publickey, $privatekey, $alias, $a, $publickey, $privatekey);
				if(!$stmt_InsertUser->execute()) {
					//die( $this->db->error );
					$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_CREATION_FAILED;
					return false;
				}
				
				$user_id = $stmt_InsertUser->insert_id;
				
				$rsa->loadKey($publickey);
			
				// BIP32 Key Generation
				$bip32_master = BIP32::master_key( bin2hex(openssl_random_pseudo_bytes(16)) );
				
				$bip32_extended_private = BIP32::build_key($bip32_master[0], $user_id . "'");
				
				$bip32_extended_public = BIP32::extended_private_to_public( $bip32_extended_private);
				
				//$bip32_extended_public = BIP32::extended_private_to_public( $bip32_extended_private );
				
				$default_attributes = array(
					'Version'		=> 2,
					'BIP32Encrypted'	=> false,
					'BIP32Master'		=> $bip32_master[0],
					'BIP32ExtendedPrivate'	=> $bip32_extended_private,
					'BIP32ExtendedPublic'	=> $bip32_extended_public,
					'Preferences'			=> array(
						'CurrencyID'	=> 3,
						'CatalogFilter' => array(
							'verified_vendors'	=> FALSE,
							'ships_to'			=> '0',
							'ships_from'		=> '0'
						),
						'ForumFilter'	=> array(
							'hide_comments'		=> FALSE
						),
						'CollapsedNav'	=> FALSE
					),
					'LastSeen' => array(
						'Reputation'				=> 0,
						'NotificationID'			=> 0,
						'InTransit_Transaction_ID'	=> 0,
						'TransactionRating_ID'		=> 0,
						'MessageID'					=> 0
					)
				);
				
				$User_Attributes = $rsa->encrypt(json_encode($default_attributes));
				
				$stmt_updateUser->bind_param('si', $User_Attributes, $user_id);
				$stmt_updateUser->execute();
				
				if(
					(
						!empty($_POST['invite_code']) &&
						$isVendor
					) ||
					$existingAID
				){
					$this->db->qQuery(
						"
							UPDATE
								`User`
							SET
								`Vendor` = TRUE
							WHERE
								`ID` = ?
						",
						'i',
						array($user_id)
					);
					
					$this->db->qQuery(
						"
							INSERT INTO
								`User_Section` (`VendorID`, `Type`, `Name`, `Content`, `HTML`)
							VALUES
								(?, 'policy', 'Refund Policy', '', '')
						",
						'i',
						array($user_id)
					);
				} elseif( !$oldFriend )
					$_SESSION['new_user'] = true;
				
				if (!empty($_POST['invite_code']))
					$this->claimInviteCode($_POST['invite_code'], $user_id);
				
				$this->db->incrementStatistic('registrations', 1);
				
				unset(
					$_SESSION['feedback_negative'],
					$_SESSION['login_username'],
					$_SESSION['login_prehashed'],
					$_SESSION['register_attempt']
				);
				$_SESSION['newly_registered'] = true;
				
				if($this->login($username, $password, false, false)){
					if(isset($_POST['return']) && preg_match('/^\w[\/\w]*\/$/', $_POST['return']) )
						return $_POST['return'];
					else
						return $this->db->forum
								?	DEFAULT_LOGIN_DESTINATION_FORUM
								: 	$this->db->invite_only
										? DEFAULT_REGISTRATION_DESTINATION_INVITE_ONLY
										: DEFAULT_LOGIN_DESTINATION;
				}
			} else {
				// STATEMENT PREPERATION FAILED
				return false;
			}
		
		} else {
			$_SESSION["feedback_negative"][] = FEEDBACK_UNKNOWN_ERROR;
			return false;
		}
		// default return, returns only true of really successful (see above)
		return false;
	}
	
	public function get_salt_a($username){
		$s = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
		$aID = sha1($username . $s);
		if( $stmt_aIDs = $this->db->prepare("
			SELECT
				count(*)
			FROM
				`User`
			WHERE
				`aID` = ?
			LIMIT 1
		") ){
			$stmt_aIDs->bind_param('s', $aID);
			$stmt_aIDs->execute();
			$stmt_aIDs->store_result();
			$stmt_aIDs->bind_result($aIDCount);
			if ($aIDCount > 0){
				return $this->get_salt_a($username);
			} else {
				return array($s, $aID);
			}
		}
	}
	
	/*private function getUniqueAlias(){
		
		if( $stmt_checkAlias = $this->db->prepare("
			SELECT	COUNT(`ID`)
			FROM	`User`
			WHERE	`Alias` = ?
		") ){
			
			$random_alias = substr( md5(uniqid()), 0, 10 );
			
			$stmt_checkAlias->bind_param('s', $random_alias);
			$stmt_checkAlias->execute();
			$stmt_checkAlias->store_result();
			$stmt_checkAlias->bind_result($alias_count);
			$stmt_checkAlias->fetch();
			
			if( $alias_count == 0 ){
				return $random_alias;
			} else {
				return $this->getUniqueAlias();
			}
			
		}
		
	}*/
	
	/* private function getUniqueAlias(){
		
		$stmt_countRandomAliases = $this->db->prepare("
			SELECT
				COUNT(*)
			FROM
				`RandomAlias`
			LEFT JOIN	`User`
				ON	`RandomAlias`.`Alias` = `User`.`Alias`
			WHERE
				`User`.`Alias` IS NULL
		");
		
		$stmt_getRandomAlias = $this->db->prepare("
			SELECT
				`RandomAlias`.`Alias`
			FROM
				`RandomAlias`
			LEFT JOIN	`User`
				ON	`RandomAlias`.`Alias` = `User`.`Alias`
			WHERE
				`User`.`Alias` IS NULL
			LIMIT ?, 1
		");
		
		if( false !== $stmt_countRandomAliases && false !== $stmt_getRandomAlias ) {
			
			$stmt_countRandomAliases->execute();
			$stmt_countRandomAliases->store_result();
			$stmt_countRandomAliases->bind_result($alias_count);
			$stmt_countRandomAliases->fetch();
			
			$row = rand(0, $alias_count);
			
			$stmt_getRandomAlias->bind_param('i', $row);
			$stmt_getRandomAlias->execute();
			$stmt_getRandomAlias->store_result();
			$stmt_getRandomAlias->bind_result($alias);
			$stmt_getRandomAlias->fetch();
			
			return $alias;
			
		}
		
	} */
	
	/**
	 * Generates the captcha, "returns" a real image,
	 * this is why there is header('Content-type: image/jpeg')
	 * Note: This is a very special method, as this is echoes out binary data.
	 * Eventually this is something to refactor
	 */
	public function generateCaptcha()
	{
		$img = new Captcha;
		
		// OPTIONS
		//$img->width     = 306;
		$img->height    = (int)(306 * 0.35);
		$img->width	= 200;
		$img->backgroundText = $this->db->accessDomain;
		$img->colors = [
			[82, 152, 91],
			[152, 82, 108],
			[167, 68, 68],
			[93, 123, 137],
			[247, 152, 0]
		];
		
		//$img->perturbation    = 1;                               // 1.0 = high distortion, higher numbers = more distortion
		//$img->text_color      = new Securimage_Color( "#" . $this->db->getSiteInfo('PrimaryColor') );   // captcha text color
		//$img->text_color      = new Securimage_Color('#AFAFAF');
		//$img->line_color      = new Securimage_Color("#5A5656");   // color of lines over the image
		
		$img->resourcesPath = LIBRARY_PATH . '/cool-php-captcha/resources';
		//$img->scale = 3;
		
		//header('Content-type: image/jpeg');
		//$img->show(LIBRARY_PATH . 'securimage/backgrounds/bg4.jpg');
		
		$img->CreateImage();
	}
	
	/**
	 * Checks if the entered captcha is the same like the one from the rendered image which has been saved in session
	 * @return bool success of captcha check
	 */
	private function checkCaptcha()
	{
		$captcha = new Captcha();
		
		$validCaptcha = (
			isset($_POST["captcha"]) &&
			//$captcha->check($_POST['captcha']) == true
			!empty($_SESSION['captcha']) &&
			strtolower(trim($_POST['captcha'])) == $_SESSION['captcha']
		);
		
		if ($validCaptcha){
			$this->insertAccessCookie();
			return true;
		}
		
		unset($_SESSION['captcha']);
		return false;
	}
	
	private function checkAlias($username){
		  
		  if( $stmt_checkAlias = $this->db->prepare("
			  SELECT	COUNT(*)
			  FROM	User
			  WHERE	aID IS NOT NULL
			  AND	Alias = ?
		  ") ){
		  
			  $stmt_checkAlias->bind_param('s', $username);
			  $stmt_checkAlias->execute();
			  $stmt_checkAlias->store_result();
			  $stmt_checkAlias->bind_result($user_count);
			  $stmt_checkAlias->fetch();
			  
			  return $user_count == 0;
		  
		  }
		  
	}
	
	private function checkReserved($username, &$alias = false){
	
		if(
			isset($_POST['reserved_pgp_code']) &&
			$_POST['reserved_pgp_code'] == $_SESSION['reserved_username']['auth_code'] &&
			$_SESSION['reserved_username']['username'] = $username
		){
			$alias = $username;
			return true;
		}
		  
		unset($_SESSION['reserved_username']);
	
		if( $stmt_checkReservedUsername = $this->db->prepare("
			SELECT	`PGP`
			FROM	`User`
			WHERE
				`aID` IS NULL
			AND	`Alias` = ?
		") ){
		
			$stmt_checkReservedUsername->bind_param('s', $username);
			$stmt_checkReservedUsername->execute();
			$stmt_checkReservedUsername->store_result();
			
			if( $stmt_checkReservedUsername->num_rows > 0 ){
			
				$stmt_checkReservedUsername->bind_result($user_pgp);
				$stmt_checkReservedUsername->fetch();
				
				try {
					$pgp = new PGP($user_pgp);
				} catch (Exception $e) {
					$_SESSION["feedback_negative"]['general'][] = 'This username is reserved. Please contact a member of staff.';
					return false; // INVALID PGP
				}
				
				$authentication_code = substr(hexdec(hash('sha256', uniqid(openssl_random_pseudo_bytes(16), TRUE))), 2, 10);
				
				try {
					$message = $pgp->qEncrypt('Your authentication code is: '.$authentication_code, true);
				} catch (Exception $e) {
					$_SESSION["feedback_negative"]['general'][] = 'This username is reserved. Please contact a member of staff.';
					return false;
				}
				
				$_SESSION['reserved_username']['username'] = $username;
				$_SESSION['reserved_username']['auth_code'] = $authentication_code;
				$_SESSION['reserved_username']['message'] = $message;
				$_SESSION['reserved_username']['stored_password'] = $_POST['password'];
				
				return false;
			
			} else
				return true;
		
		}
		
		return false;
	
	}
	
	private function insertAccessCookie()
	{
		setcookie('GUEST_ADMITTANCE_TOKEN', md5(GUEST_ADMITTANCE_SALT . session_id()), time() + 60*60*12, '/' );
	}
	
	public function checkInviteCode($invite_code, &$is_vendor){
		$inviteCode = $this->db->qSelect(
			"
				SELECT
					`Type`
				FROM
					`InviteCode`
				LEFT JOIN
					`User` ON
						`InviteCode`.`UserID` = `User`.`ID`
				WHERE
					" . ($this->db->invite_only ? '`ClaimedID` IS NULL AND' : false) . "
					`Code` = ? AND
					(
						`InviteCode`.`UserID` IS NULL OR
						`User`.`Banned` = FALSE
					)
			",
			's',
			array($invite_code)
		);
		
		if( $inviteCode ){
			$is_vendor = $inviteCode[0]['Type'] == 'market';
			
			return true;
		} else
			return false;
	}
	
	private function _duplicateAndClaimInviteCode(
		$inviteCode,
		$userID
	){
		return	$this->db->qQuery(
				"
					INSERT INTO `InviteCode` (
						`Code`,
						`Type`,
						`UserID`,
						`ClaimedID`
					)
					SELECT
						NULL,
						'buyer',
						`UserID`,
						?
					FROM
						`InviteCode`
					WHERE
						`Code` = ?
				",
				'is',
				[
					$userID,
					$inviteCode
				]
			);
	}
	
	private function claimInviteCode($invite_code, $user_id){
		return	$this->db->qQuery(
				"
					UPDATE
						`InviteCode`
					SET
						`ClaimedID` = ?
					WHERE
						`Code` = ?
					AND	`ClaimedID` IS NULL
				",
				'is',
				array(
					$user_id,
					$invite_code
				)
			)
				?: (
					!$this->db->invite_only &&
					$this->_duplicateAndClaimInviteCode(
						$invite_code,
						$user_id
					)
				);
	}
}
