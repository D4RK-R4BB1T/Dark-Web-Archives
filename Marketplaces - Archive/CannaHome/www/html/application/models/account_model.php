<?php
class AccountModel {
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	public function fetchModUsernames(){
		return	array_map(
				function ($row){
					return $row['Alias'];
				},
				$this->db->qSelect(
					"
						SELECT `Alias`
						FROM `User`
						WHERE `Moderator` = TRUE
					"
				)
			);
	}
	
	public function renderUserQueryGraph($queryIdentifier){
		if ($query = $this->_getUserQuery($queryIdentifier)){
			$queryResults = $this->_getUserQueryResults(
				$query['Query'],
				true
			);
			
			$arrayKeys = array_keys($queryResults[0]);
			$queryResults_indexed = [];
			
			foreach ($queryResults as $queryResult)
				$queryResults_indexed[$queryResult[$arrayKeys[0]]] = $queryResult[$arrayKeys[1]];
			
			$graph = new Graph(550,350);
		
			$graph->addData($queryResults_indexed);
			$graph->setTitle($arrayKeys[1]);
			$graph->setGradient("lime", "green");
			$graph->setBarOutlineColor("black");
			
			$graph->setBackgroundColor("251, 251, 248");
			
			$graph->createGraph();
		}
	}
	
	public function getUserQueries(){
		return	$this->db->qSelect(
				"
					SELECT
						`Title`,
						`Identifier`
					FROM
						`UserQuery`
					WHERE
						`Access` = IF(
							?,
							'Vendor',
							'Buyer'
						)
					ORDER BY
						`Sort` ASC
				",
				'i',
				[$this->User->IsVendor]
			);
	}
	
	private function _getUserQuery(
		$identifier,
		$chart = false
	){
		if (
			$query = $this->db->qSelect(
				"
					SELECT
						`Title`,
						`Type`,
						`Query`,
						`Identifier`
					FROM
						`UserQuery`
					WHERE
						`Access` = IF(
							?,
							'Vendor',
							'Buyer'
						) AND
						`Identifier` = ? " . (
							$chart
								? "AND `Type` = 'bar-chart'"
								: false
						) . "
				",
				'is',
				[
					$this->User->IsVendor,
					$identifier
				]
			)
		)
			return $query[0];
			
		return false;
	}
	
	private function _getUserQueryResults($query){
		if (
			$hasParameter =
				strpos(
					$query,
					'?'
				) !== false
		)
			$parameterCount = substr_count(
				$query,
				'?'
			);
		
		return	$this->db->qSelect(
				$query,
				$hasParameter
					? str_repeat(
						'i',
						$parameterCount
					)
					: false,
				$hasParameter
					? array_fill(
						0,
						$parameterCount,
						$this->User->ID
					)
					: []
			);
	}
	
	public function fetchUserQueryResult(
		&$queryIdentifier,
		$ignoreType = false
	){
		if ($query = $this->_getUserQuery($queryIdentifier)){
			$queryIdentifier = $query['Identifier'];
			return	[
					$query['Title'],
					$query['Type'] == 'table' ||
					$ignoreType
						? $this->_getUserQueryResults($query['Query'])
						: false
				];
		}
		
		return false;
	}
	
	private function _penalizeElectrumServer(
		$electrumServerID,
		$increment
	){
		return $this->db->qQuery(
			"
				UPDATE
					`ElectrumServer`
				SET
					`Failures` = `Failures` + ?
				WHERE
					`ID` = ?
			",
			'ii',
			[
				$increment,
				$electrumServerID
			]
		);
	}
	
	public function _getElectrumServer(
		$cryptocurrencyID,
		&$connectionAttempts,
		&$previousServerIDs,
		$penalizePrecedingElectrumServer = true,
		$maximumAttempts = ELECTRUM_SERVER_REQUEST_MAXIMUM_SERVER_ATTEMPTS
	){
		$connectionAttempts = $connectionAttempts ?: 0;
		$previousServerIDs = $previousServerIDs ?: [];
		if (
			(
				!$connectionAttempts &&
				isset($_POST['lastElectrumServer'][$cryptocurrencyID]) &&
				$electrumServer = $_POST['lastElectrumServer'][$cryptocurrencyID]
			) ||
			(
				$connectionAttempts < $maximumAttempts &&
				(
					$electrumServers = $this->db->qSelect(
						"
							SELECT
								`ID`,
								CONCAT(
									`Protocol`,
									'://',
									`Host`
								) Host,
								`Port`,
								`Failures`
							FROM
								`ElectrumServer`
							WHERE
								`CryptocurrencyID` = ?" . (
									$previousServerIDs
										? " AND `ID` NOT IN (" . rtrim(str_repeat('?, ', count($previousServerIDs)), ', ') . ")"
										: false
								) . "
							ORDER BY
								`BlockHeight` DESC,
								`ConnectTime` ASC,
								`Failures` ASC
							LIMIT
								1
						",
						'i' . str_repeat('i', count($previousServerIDs)),
						array_merge(
							[$cryptocurrencyID],
							$previousServerIDs
						)
					)
				) &&
				$electrumServer = $_POST['lastElectrumServer'][$cryptocurrencyID] = $electrumServers[0]
			)
		){
			/*
			if (
				$previousServerIDs &&
				$penalizePrecedingElectrumServer
			){
				$previousServerID = end($previousServerIDs);
				$this->_penalizeElectrumServer(
					$previousServerID,
					1
				);
			}
			*/
			
			$previousServerIDs[] = $electrumServer['ID'];
			
			$connectionAttempts += 1;
			return $electrumServer;
		}
		
		return false;
	}
	
	public function getAddressBalance(
		$cryptocurrencyID,
		$address,
		$requiredConfirmations = REQUIRED_TX_CONFIRMATIONS_BROADCAST,
		$includeConfirmed = TRUE,
		$showError = FALSE,
		&$errors = FALSE,
		&$previousElectrumServerIDs = null
	){
		$hasPreviousElectrumServers = $previousElectrumServerIDs !== null;
		$errors = false;
		
		// TRY ELECTRUM
		if (
			$requiredConfirmations >= TX_CONFIRMATIONS_ELECTRUM_CONFIRMED ||
			$requiredConfirmations == TX_CONFIRMATIONS_ELECTRUM_UNCONFIRMED
		){
			$electrumOutput = FALSE;
			$m = $this->db->m;
			
			$confirmedBalance = false;
			if (
				!$hasPreviousElectrumServers &&
				$electrumCached = $m->get('electrumBalance-' . $address)
			){
				$confirmedBalance = $electrumCached['confirmed'];
				$unconfirmedBalance = $electrumCached['unconfirmed'];
			}
			
			while (
				$confirmedBalance === false &&
				$electrumServer = $this->_getElectrumServer(
					$cryptocurrencyID,
					$connectionAttempts,
					$previousElectrumServerIDs,
					($hasPreviousElectrumServers == false)
				)
			)
				$confirmedBalance = ElectrumServer::getAddressBalance(
					$electrumServer['Host'],
					$electrumServer['Port'],
					$address,
					$unconfirmedBalance
				);
			
			if ($confirmedBalance !== false) {
				if (!$electrumCached)
					$m->set(
						'electrumBalance-' . $address,
						[
							'confirmed'	=> $confirmedBalance,
							'unconfirmed'	=> $unconfirmedBalance
						],
						ELECTRUM_BALANCE_CACHE_EXPIRATION
					);
			
				if ($requiredConfirmations == TX_CONFIRMATIONS_ELECTRUM_UNCONFIRMED)
					$balance =
						$includeConfirmed
							? $confirmedBalance + $unconfirmedBalance
							: $unconfirmedBalance;
				else
					$balance = $confirmedBalance;	
		
				return $balance;
			}
		}
		
		$errors = TRUE;
		if($showError)
			$_SESSION['temp_notifications']['balance_inretrievable'] = [
				'Content' => 'Could not fetch address balance. Try again later',
				'Design' => [
					'Color' => 'red',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				]
			];
		
		return false;	
	}
	
	public function hasReferralCommissions(){
		return	$this->db->qSelect(
				"
					SELECT
						`ReferralWallet`.`ID`
					FROM
						`ReferralWallet`
					INNER JOIN
						`Transaction` ON
							`Transaction`.`ReferralWalletID` = `ReferralWallet`.`ID`
					WHERE
						`ReferralWallet`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Withdrawn` = TRUE
					LIMIT
						1
				",
				'i',
				[$this->User->ID]
			);
	}
	
	private function _insertReferralWallet_CryptocurrencyBalance(
		$referralWalletID,
		$cryptocurrencyID,
		$balance
	){
		return	$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`ReferralWallet_Cryptocurrency` (
							`ReferralWalletID`,
							`CryptocurrencyID`,
							`Balance`
						)
					VALUES (
						?,
						?,
						?
					)
				",
				'iid',
				[
					$referralWalletID,
					$cryptocurrencyID,
					$balance
				]
			);
	}
	
	private function addReferralWalletOutputAddress(
		$referralWalletID,
		$cryptocurrencyID,
		$outputAddress
	){
		return	$this->db->qQuery(
				"
					UPDATE
						`ReferralWallet_Cryptocurrency`
					INNER JOIN
						`ReferralWallet` ON
							`ReferralWallet_Cryptocurrency`.`ReferralWalletID` = `ReferralWallet`.`ID`
					SET
						`ReferralWallet_Cryptocurrency`.`OutputAddress` = ?
					WHERE
						`ReferralWallet_Cryptocurrency`.`ReferralWalletID` = ? AND
						`ReferralWallet_Cryptocurrency`.`CryptocurrencyID` = ? AND
						`ReferralWallet`.`UserID` = ? AND
						`ReferralWallet_Cryptocurrency`.`Withdrawn` = FALSE AND
						DATE(NOW()) > LAST_DAY(`ReferralWallet`.`DateTime`)
				",
				'siii',
				[
					$outputAddress,
					$referralWalletID,
					$cryptocurrencyID,
					$this->User->ID
				]
			);
	}
	
	public function withdrawReferralWallet($walletID){
		if (!empty($_POST)){
			foreach($_POST as $key => $value)
				$_SESSION['referral_wallet_withdrawal']['post'][$key] = htmlspecialchars($value);
			
			$errors = [];
			foreach ($_POST['cryptocurrencies'] as $cryptocurrencySuffix){
				$cryptocurrencyID = CRYPTOCURRENCIES_IDS[$cryptocurrencySuffix];
				
				if ($cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID)){
					$outputAddress = $_POST['output_address-' . $cryptocurrencySuffix];
					if (
						!empty($outputAddress) &&
						$cryptocurrency->validateAddress($outputAddress)
					)
						$outputAddresses[] = [
							$walletID,
							$cryptocurrencyID,
							$outputAddress
						];	
					else
						$errors[$cryptocurrencySuffix] = true;
				}
			}
			
			if ($errors)
				$_SESSION['referral_wallet_withdrawal']['errors'][$walletID] = $errors;
			else {
				unset($_SESSION['referral_wallet_withdrawal']);
				
				foreach ($outputAddresses as $outputAddress)
					call_user_func_array(
						[
							$this,
							'addReferralWalletOutputAddress'
						],
						$outputAddress
					);
			}
			
			return true;
		}
		
		return false;
	}
	
	public function fetchReferralWallets(){
		if (
			$referralWallets = $this->db->qSelect(
				"
					SELECT
						`ReferralWallet`.`ID`,
						DATE_FORMAT(`ReferralWallet`.`DateTime`, '%M %Y') month,
						COUNT(DISTINCT `Transaction`.`ID`) orders,
						(
							SELECT
								`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`
							FROM
								`ReferralWallet_Cryptocurrency`
							WHERE
								`ReferralWalletID` = `ReferralWallet`.`ID`
							LIMIT 1
						) IS NOT NULL AND
						(
							SELECT
								COUNT(`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`)
							FROM
								`ReferralWallet_Cryptocurrency`
							WHERE
								`ReferralWalletID` = `ReferralWallet`.`ID` AND
								`ReferralWallet_Cryptocurrency`.`Withdrawn` = 0
						) = 0 Withdrawn,
						DATE(NOW()) <= LAST_DAY(`ReferralWallet`.`DateTime`) isLive,
						(
							SELECT
								COUNT(DISTINCT RW2.`ID`)
							FROM
								`ReferralWallet` RW2
							WHERE
								RW2.`DateTime` < `ReferralWallet`.`DateTime` OR
								(
									RW2.`DateTime` = `ReferralWallet`.`DateTime` AND
									RW2.`ID` < `ReferralWallet`.`ID`
								)
						) keyIndex,
						(
							(
								SELECT
									COUNT(`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`)
								FROM
									`ReferralWallet_Cryptocurrency`
								WHERE
									`ReferralWalletID` = `ReferralWallet`.`ID` AND
									`ReferralWallet_Cryptocurrency`.`Withdrawn` = 0
							) > 0 AND
							(
								SELECT
									COUNT(`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`)
								FROM
									`ReferralWallet_Cryptocurrency`
								WHERE
									`ReferralWalletID` = `ReferralWallet`.`ID` AND
									`ReferralWallet_Cryptocurrency`.`OutputAddress` IS NULL
							) = 0
						) isProcessing,
						GROUP_CONCAT(DISTINCT `PaymentMethod`.`CryptocurrencyID` ORDER BY `PaymentMethod`.`CryptocurrencyID` ASC) cryptocurrencies,
						GROUP_CONCAT(DISTINCT CONCAT(`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`, '-', `ReferralWallet_Cryptocurrency`.`Balance`) ORDER BY `ReferralWallet_Cryptocurrency`.`CryptocurrencyID` ASC) cryptocurrencyValues,
						GROUP_CONCAT(DISTINCT CONCAT(`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`, '-', (`ReferralWallet_Cryptocurrency`.`OutputAddress` IS NULL)) ORDER BY `ReferralWallet_Cryptocurrency`.`CryptocurrencyID` ASC) cryptocurrencyWithdrawable
					FROM
						`ReferralWallet`
					INNER JOIN
						`Transaction` ON
							`Transaction`.`ReferralWalletID` = `ReferralWallet`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`PaymentMethod`.`ID` = `Transaction`.`PaymentMethodID`
					LEFT JOIN
						`ReferralWallet_Cryptocurrency` ON
							`ReferralWallet_Cryptocurrency`.`ReferralWalletID` = `ReferralWallet`.`ID`
					WHERE
						`ReferralWallet`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Withdrawn` = TRUE
					GROUP BY
						`ReferralWallet`.`ID`
				",
				'i',
				[$this->User->ID]
			)
		){
			$cryptocurrencies = [];
			foreach ($referralWallets as $referralWalletKey => $referralWallet){
				$referralWallet['cryptocurrencies'] = explode(',', $referralWallet['cryptocurrencies']);
				$referralWallet['cryptocurrencyValues'] = 
					$referralWallet['cryptocurrencyValues']
						? explode(',', preg_replace('/\d+-/', '', $referralWallet['cryptocurrencyValues']))
						: false;
				$referralWallet['cryptocurrencyWithdrawable'] = 
					$referralWallet['cryptocurrencyWithdrawable']
						? explode(',', preg_replace('/\d+-/', '', $referralWallet['cryptocurrencyWithdrawable']))
						: false;
				
				$referralWallet['isWithdrawable'] = $referralWallets[$referralWalletKey]['isWithdrawable'] =
					!$referralWallet['isLive'] &&
					!$referralWallet['Withdrawn'];
				
				foreach ($referralWallet['cryptocurrencies'] as $key => $cryptocurrencyID){
					$cryptocurrency = $cryptocurrencies[$cryptocurrencyID] =
						isset($cryptocurrencies[$cryptocurrencyID])
							? $cryptocurrencies[$cryptocurrencyID]
							: $this->User->getCryptocurrency($cryptocurrencyID);
					
					if ($referralWallet['cryptocurrencyValues'])
						$balance = $referralWallet['cryptocurrencyValues'][$key];
					else {
						$address = NXS::getBIP32Address(
							$referralWallet['keyIndex'],
							REFERRAL_WALLET_EXTENDED_PRIVATE_KEY,
							$cryptocurrency->prefixPublic,
							$cryptocurrency->prefixScriptHash,
							true,
							''
						);
						$balance = $this->getAddressBalance(
							$cryptocurrencyID,
							$address,
							0,
							true,
							false,
							$balanceErrors
						);
						
						if (
							$referralWallet['isWithdrawable'] &&
							!$balanceErrors
						)
							$this->_insertReferralWallet_CryptocurrencyBalance(
								$referralWallet['ID'],
								$cryptocurrencyID,
								$balance
							);
					}
					
					if (
						NXS::compareFloatNumbers(
							$balance,
							$cryptocurrency->smallestIncrement,
							'>='
						)
					)
						$referralWallets[$referralWalletKey]['cryptocurrencyBalances'][$cryptocurrencyID] = $cryptocurrency->formatValue($balance, true);
					
					if ($referralWallet['cryptocurrencyWithdrawable'][$key])
						$referralWallets[$referralWalletKey]['withdrawableCryptocurrency'][$cryptocurrencyID] = $cryptocurrencyID;
				}
				
				if (
					$emptyWallet =
						$referralWallet['isWithdrawable'] &&
						!$referralWallets[$referralWalletKey]['cryptocurrencyBalances']
				)
					unset($referralWallets[$referralWalletKey]);
			}
			
			return $referralWallets;
		}
		
		return false;
	}
	
	public function submitVendorApplication(){
		foreach($_POST as $key => $value)
			$_SESSION['vendorApplication']['post'][$key] = htmlspecialchars($value);
		
		/*if( empty($_POST['btc_public_key']) || !BitcoinLib::validate_public_key($_POST['btc_public_key']) ){
			$_SESSION['vendorApplication']['response']['btc_public_key'] = 'This does not appear to be a valid public key.';
			return FALSE;
		}
		
		$this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`User`.`BIP32Public` = NULL,
					`BTCPublic` = ?
				WHERE
					`ID` = ?
			",
			'si',
			array(
				$_POST['btc_public_key'],
				$this->User->ID
			)
		);
		$this->User->updateAttributes(
			array(
				'BIP32Encrypted' => FALSE,
				'BIP32Master' => FALSE,
				'BIP32ExtendedPrivate' => FALSE,
				'BIP32ExtendedPublic' => FALSE,
				'BTCPublic' => $_POST['btc_public_key']
			)
		);*/
		
		$this->db->qQuery(
			"
				INSERT INTO
					`VendorApplication` (`UserID`, `Policy`, `Application`)
				VALUES
					(?, ?, ?)
				ON DUPLICATE KEY UPDATE
					`Policy`		= ?,
					`Application`	= ?
			",
			'issss',
			array(
				$this->User->ID,
				$_POST['policy'],
				$_POST['application'],
				$_POST['policy'],
				$_POST['application']
			)
		);
		
		$errors = FALSE;
		
		if( !empty($_POST['codes']) ) {
			
			// VALIDATION
			$codes = array_map('trim', explode(PHP_EOL, $_POST['codes']));
			
			foreach($codes as $code){
				$affected_rows = $this->db->qQuery(
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
						$this->User->ID,
						$code
					)
				);
				if( !$affected_rows ){
					$errors = TRUE;
				}
			}
			
		}
		
		if( $_POST['alias'] !== $this->User->Alias ){
			
			if( !preg_match("/^[A-Za-z0-9_-]{3,70}$/", $_POST['alias']) ){
				$_SESSION['vendorApplication']['response']['alias'] = 'This is not a valid vendor alias.';
				return false;
			}
			
			$alias = strip_tags($_POST['alias']);
				
			if( $this->checkUserAliasTaken($alias) ){
				$_SESSION['vendorApplication']['response']['alias'] = 'This alias is already taken. If you feel you have claim to this name, please contact a member of staff.';
				return false;
			} else {
				$this->db->qQuery(
					"
						UPDATE
							`User`
						SET
							`Alias` = ?
						WHERE
							`ID` = ?
					",
					'si',
					array(
						$alias,
						$this->User->ID
					)
				);
				Session::set('alias', $alias);
			}
			
		}
		
		if( $errors )
			$_SESSION['vendorApplication']['response']['codes'] = 'One or more codes were invalid.';
		else
			unset($_SESSION['vendorApplication']['response']);
		
		$this->checkVendorApplicationStatus($_POST['policy']);
			
		unset($_SESSION['vendorApplication']['post']);
		
	}
	
	private function checkUserAliasTaken($alias){
		
		$existingAlias = $this->db->qSelect(
			"
				SELECT
					`ID`
				FROM
					`User`
				WHERE
					`Alias` = ?
			",
			's',
			array($alias)
		);
		
		if( $existingAlias )
			return TRUE;
		else
			return FALSE;
		
	}
	
	private function checkVendorApplicationStatus($policy = FALSE){
		$applicationStatus = $this->db->qSelect(
			"
				SELECT
					(
						SELECT	COUNT(`ID`)
						FROM	`InviteCode`
						WHERE
							`ClaimedID` = `User`.`ID`
						AND	`Type` = 'market'
					) marketInvites
				FROM	`User`
				WHERE
					`User`.`ID` = ?
			",
			'i',
			array($this->User->ID)
		);
		$applicationStatus = $applicationStatus[0];
		
		if( $applicationStatus['marketInvites'] > 0 ){
			
			$policyHTML = NXS::formatText($policy);
			
			$this->db->qQuery(
				"
					INSERT INTO
						`User_Section` (`VendorID`, `Type`, `Name`, `Content`, `HTML`)
					VALUES
						(?, 'policy', 'Refund Policy', ?, ?)
				",
				'iss',
				array(
					$this->User->ID,
					$policy,
					$policyHTML
				)
			);
			
			// GRANT VENDOR STATUS
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
				array($this->User->ID)
			);
			
			// Notify Vendor
			$this->User->sendMessage(
				'[b]Welcome aboard![/b]' . PHP_EOL . PHP_EOL . 'It is with great pleasure that I inform you that your application for becoming a vendor has been accepted.' . PHP_EOL . PHP_EOL . 'You may now create and submit your listings to the marketplace.',
				$this->User->ID
			);
		}
		
	}
	
	public function getBoxData() {
		$data = $this->User->Info('Reputation', 'BuyCount', 'SellCount', 'ReferralCount');
		foreach($data as $key => $item){
			$data[$key] = $item < 0 ? false : $item;
		}
		return $data;
	}
	
	public function getUserStats() {
		$array = $this->User->Info(
			'AverageUserRating',
			'TransactionRatingCount',
			'TransactionCommentCount',
			'BuyCount',
			'SellCount',
			'FollowerCount'
		);
		
		return [
			'rating'		=> $array[0],
			'ratingCount'		=> $array[1],
			'commentCount'		=> $array[2],
			'purchasesCount'	=> $array[3],
			'salesCount'		=> max($array[4], $array[1]),
			'followersCount'	=> $array[5],
			'fundsInEscrow'		=> $this->User->getFundsInEscrow()
		];
	}
	
	public function getUserDistinctions(){
		$distinctions = $this->db->qSelect(
			"
				SELECT
					`Distinction`.`Name` AS name,
					`Distinction`.`Icon` AS icon,
					`Distinction`.`Style` AS style
				FROM
					`User_Distinction`
				INNER JOIN	`Distinction`
					ON	`User_Distinction`.`DistinctionID` = `Distinction`.`ID`
				WHERE
					`User_Distinction`.`UserID` = ?
			",
			'i',
			[$this->User->ID]
		);
		
		return $distinctions;
	}
	
	public function getDashboardNotifications() {
		/*list(
			$PendingTransactionCount,
			$InDisputeTransactionCount,
			$unwithdrawnFinalizedTransactionCount,
			$PendingFeedbackTransactionCount,
			$TransactionRatingCountChange,
			$InTransitTransactionCountChange,
			$UnansweredListingQuestionCount,
			$UnsuccessfulBroadcastCount,
			$SubscribedForumEntriesCountChange,
			$UnwithdrawnRejectedTransactionCount,
			$PartiallySignedFeeBumpCount
		) = $this->User->Info(
			'PendingTransactionCount',
			'InDisputeTransactionCount',
			'UnwithdrawnFinalizedTransactionCount',
			'PendingFeedbackTransactionCount',
			'TransactionRatingCountChange',
			'InTransitBuyingTransactionCountChange',
			'UnansweredListingQuestionCount',
			'UnsuccessfulBroadcastCount',
			'SubscribedForumEntriesCountChange',
			'UnwithdrawnRejectedTransactionCount',
			'PartiallySignedFeeBumpCount'
		);*/
		
		$MessageCount				= $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES);
		$PendingTransactionCount		=
			$this->User->IsVendor
				? $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT)
				: false;
		$InDisputeTransactionCount		= $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE);
		$unwithdrawnFinalizedTransactionCount	=
			$this->User->IsVendor
				? $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL)
				: false;
		$PendingFeedbackTransactionCount	=
			$this->User->IsVendor
				? false
				: $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK);
		
		$InTransitTransactionCountChange	=
			$this->User->IsVendor
				? false
				: $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED);
				
		$UnansweredListingQuestionCount		=
			$this->User->IsVendor
				? $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_LISTING_NEW_QUESTION)
				: false;
		$SubscribedForumEntriesCountChange	= 0; //$this->User->getUserNotification(USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS);
		$UnwithdrawnRejectedTransactionCount	= $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL);
		$UnsuccessfulBroadcastCount		= $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL);
		
		$OutOfStockListingCount =
			$this->User->IsVendor
				? $this->User->getUserNotification(USER_NOTIFICATION_TYPEID_LISTING_OUT_OF_STOCK)
				: false;
		
		if($MessageCount > 0)
			$this->User->Notifications->custom(
				'You have ' . ( $MessageCount == 1 ? 'an' : '<span>'.$MessageCount.'</span>' ) . ' unread private message'.($MessageCount > 1 ? 's' : false),
				URL . 'account/messages/',
				false,
				'Dashboard',
				['Icon' => Icon::getClass('ENVELOPE')],
				'_self',
				true
			);
		
		if($PendingTransactionCount > 0)
			$this->User->Notifications->custom(
				'You have ' . ( $PendingTransactionCount == 1 ? 'a' : '<span>'.$PendingTransactionCount.'</span>' ) . ' new order'.($PendingTransactionCount > 1 ? 's' : false),
				URL . 'account/orders/?pendingAccept',
				false,
				'Dashboard',
				[
					'Color' => 'green',
					'Icon' => Icon::getClass('TRUCK')
				],
				'_self',
				true
			);
		
		if($InTransitTransactionCountChange > 0)
			$this->User->Notifications->custom(
				'You have '.( $InTransitTransactionCountChange == 1 ? 'an' : '<span>'.$InTransitTransactionCountChange.'</span>' ).' order'.($InTransitTransactionCountChange > 1 ? 's' : false).' with updated status',
				URL.'account/orders/',
				false,
				'Dashboard',
				[
					'Color' => 'yellow',
					'Icon' => Icon::getClass('SHOPPING_CART')
				],
				'_self',
				true
			);
		
		if($InDisputeTransactionCount > 0)
			$this->User->Notifications->custom(
				'You have '.( $InDisputeTransactionCount == 1 ? 'an' : '<span>'.$InDisputeTransactionCount.'</span>' ).' unresolved escrow dispute'.($InDisputeTransactionCount > 1 ? 's' : false),
				URL.'account/orders/?inDispute',
				false,
				'Dashboard',
				[
					'Color' => 'purple',
					'Icon' => Icon::getClass('GAVEL')
				],
				'_self',
				true
			);
		
		if($PendingFeedbackTransactionCount > 0)
			$this->User->Notifications->custom(
				'Your feedback is wanted for '.( $PendingFeedbackTransactionCount == 1 ? 'a' : '<span>'.$PendingFeedbackTransactionCount.'</span>' ).' finalized order'.($PendingFeedbackTransactionCount > 1 ? 's' : false),
				URL.'account/orders/?pendingFeedback',
				false,
				'Dashboard',
				array(
					'Color' => 'green',
					'Icon' => Icon::getClass('STAR-HALF-O')
				)
			);
		
		if ($unwithdrawnFinalizedTransactionCount > 0)
			$this->User->Notifications->custom(
				'You have '.( $unwithdrawnFinalizedTransactionCount == 1 ? 'a' : '<span>'.$unwithdrawnFinalizedTransactionCount.'</span>' ).' finalized order'.($unwithdrawnFinalizedTransactionCount > 1 ? 's' : false) . ' with funds available',
				URL.'account/orders/',
				false,
				'Dashboard',
				[
					'Color' => 'yellow',
					'Icon' => Icon::getClass('DOLLAR', true)
				],
				'_self',
				true
			);
		
		if($UnwithdrawnRejectedTransactionCount > 0)
			$this->User->Notifications->custom(
				'You have '.( $UnwithdrawnRejectedTransactionCount == 1 ? 'an' : '<span>'.$UnwithdrawnRejectedTransactionCount.'</span>' ).' order' . ($UnwithdrawnRejectedTransactionCount > 1 ? 's' : false) . ' that need' . ($UnwithdrawnRejectedTransactionCount == 1 ? 's' : false) . ' to be refunded',
				URL.'account/orders/',
				false,
				'Dashboard',
				[
					'Color' => 'red',
					'Icon' => Icon::getClass('DOLLAR', true)
				],
				'_self',
				true
			);
		
		if ($OutOfStockListingCount)
			$this->User->Notifications->custom(
				'You have run out of stock on ' . ( $OutOfStockListingCount == 1 ? 'a' : '<span>'.$OutOfStockListingCount.'</span>' ) . ' listing'.($OutOfStockListingCount > 1 ? 's' : false),
				URL . 'account/listings/',
				false,
				'Dashboard',
				[
					'Color' => 'purple',
					'Icon' => Icon::getClass('TAGS')
				],
				'_self',
				true
			);
		
		if($SubscribedForumEntriesCountChange > 0){
			$forumURL = $this->db->getSiteInfo('ForumLink');
			$this->User->Notifications->custom(
				'The forum has new posts or comments for you',
				$forumURL,
				'?do[DismissSubscribedDiscussionCountChange]',
				'Dashboard',
				[
					'Color' => 'blue',
					'Icon' => Icon::getClass('COMMENT')
				],
				'_blank',
				true
			);
		}
		
		if($UnansweredListingQuestionCount > 0){
			$singular = $UnansweredListingQuestionCount == 1;
			$this->User->Notifications->custom(
				'You have '.( $singular ? 'an' : '<span>' . $UnansweredListingQuestionCount.'</span>' ) . ' unanswered question' . ( !$singular ? 's' : false).' on ' . ( $singular ? 'a ' : false ) . 'listing' . ( !$singular ? 's' : false ),
				URL.'account/listings/',
				false,
				'Dashboard',
				[
					'Color' => 'blue',
					'Icon' => Icon::getClass('QUESTION_MARK', true)
				]
			);
		}
		
		if($UnsuccessfulBroadcastCount > 0){
			$singular = $UnsuccessfulBroadcastCount == 1;
			$this->User->Notifications->custom(
				( $singular ? 'One' : '<span>' . $UnsuccessfulBroadcastCount.'</span>' ) . ' of your transactions could not be broadcast',
				URL.'account/transactions/?unsuccessfulBroadcast',
				false,
				'Dashboard',
				[
					'Color' => 'red',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				],
				'_self',
				true
			);
		}
		
		if($this->User->IsMod){
			$inDisputeTransactionUnattendedCount = $this->_countTransactionsInDisputeTimedOut($inDisputeTransactionInvolvedCount);
			
			if($inDisputeTransactionUnattendedCount)
				$this->User->Notifications->custom(
					'There '.( $inDisputeTransactionUnattendedCount == 1 ? 'is an' : 'are <span>'.$inDisputeTransactionUnattendedCount.'</span>' ).' unresolved escrow dispute'.($inDisputeTransactionUnattendedCount > 1 ? 's' : false),
					URL.'admin/disputes/',
					false,
					'Dashboard',
					[
						'Color' => 'black',
						'Icon' => Icon::getClass('GAVEL')
					],
					'_blank'
				);
			
			if($inDisputeTransactionInvolvedCount)
				$this->User->Notifications->custom(
					'You are mediating '.( $inDisputeTransactionInvolvedCount == 1 ? 'an' : '<span>'.$inDisputeTransactionInvolvedCount.'</span>' ).' ongoing escrow dispute'.($inDisputeTransactionInvolvedCount > 1 ? 's' : false),
					URL.'admin/disputes/',
					false,
					'Dashboard',
					[
						'Color' => 'black',
						'Icon' => Icon::getClass('GAVEL')
					],
					'_blank'
				);
		}
	}
	
	public function fetchListingGroupOptions(
		$listingID = FALSE,
		$excludeListing = TRUE
	){
		if(
			$listings = $this->db->qSelect(
				"
					SELECT
						DISTINCT `Listing`.`ID`,
						`Listing`.`Name`,
						" . (
							$listingID
								? "
									(
										`Listing_Group`.`GroupID` IS NOT NULL AND
										`Listing_Group`.`GroupID` = targetListing_Group.`GroupID`
									)
								"
								: 'FALSE'
						) . " inGroup,
						`Listing_Group`.`Label`,
						`Listing`.`Quantity`,
						IF(
							`Listing`.`Quantity` > 1,
							`Unit`.`Name_Plural`,
							`Unit`.`Name_Singular`
						) unitName
					FROM
						`Listing`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					INNER JOIN
						`Unit` ON
							`Listing`.`UnitID` = `Unit`.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					" . (
						$listingID
							? "
								LEFT JOIN
									`Listing` targetListing ON
										targetListing.`ID` = ?
								LEFT JOIN
									`Listing_Group` targetListing_Group ON
										targetListing.`ID` = targetListing_Group.`ListingID`
							"
							: FALSE
					) . "
					WHERE
						`Listing`.`VendorID` = thisUser.`ID` AND
						`Listing`.`Archived` = FALSE AND
						(
							`Listing_Group`.`GroupID` IS NULL " . (
								$listingID
									? 'OR `Listing_Group`.`GroupID` = targetListing_Group.`GroupID`'
									: FALSE
							) . "
						) " . (
							$listingID && $excludeListing
								? 'AND `Listing`.`ID` != targetListing.`ID`'
								: FALSE
						) . "
				",
				'i' . ($listingID ? 'i' : FALSE),
				$listingID
					? [
						$this->User->ID,
						$listingID
					]
					: [$this->User->ID]
			)
		){			
			$groupDetails = $listingID
				? $this->_getListingGroup($listingID)
				: FALSE;
				
			return [
				'listings' => array_map(
					function($listing){
						$listing['quantityLabel'] =
							NXS::formatDecimal(
								$listing['Quantity'],
								2,
								DEFAULT_DECIMAL_SEPARATOR,
								DEFAULT_THOUSANDS_SEPARATOR,
								2
							) . ' ' .
							$listing['unitName'];
							
						return $listing;
					},
					$listings
				),
				'group' => $groupDetails
			];
		}
		
		return FALSE;
	}
	
	private function _getListingGroup($listingID){
		if(
			$groups = $this->db->qSelect(
				"
					SELECT
						`ListingGroup`.*
					FROM
						`ListingGroup`
					INNER JOIN
						`Listing_Group` ON
							`ListingGroup`.`ID` = `Listing_Group`.`GroupID`
					WHERE
						`Listing_Group`.`ListingID` = ?
				",
				'i',
				[$listingID]
			)
		)
			return $groups[0];
		
		return FALSE;
	}
	
	private function _countTransactionsInDisputeTimedOut(&$involvedCount = 0){
		if(
			$results = $this->db->qSelect(
				"
					SELECT
						(
							SELECT
								COUNT(`Transaction`.`ID`)
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Status` = 'in dispute' AND
								`Transaction`.`Timeout` < NOW() AND
								`Transaction`.`MediatorID` IS NULL
						) unattendedCount,
						(
							SELECT
								COUNT(`Transaction`.`ID`)
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Status` = 'in dispute' AND
								`Transaction`.`Timeout` < NOW() AND
								`Transaction`.`MediatorID` = ?
						) involvedCount
				",
				'i',
				[
					$this->User->ID
				]
			)
		){
			$involvedCount = $results[0]['involvedCount'];
			return $results[0]['unattendedCount'];
		}	
		
		return FALSE; 
	}
	
	public function generateUserBackup(){
		header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=".(empty($this->User->Alias) ? 'ALP-'.$this->User->ID : $this->User->Alias).".txt");
		
		// Backup Info
		$backup = array(
			'ID' => $this->User->ID,
			'RSA Public Key' => $this->User->Info('PublicKey'),
			'RSA Private Key' => $this->User->pKey,
			'BIP32 Master Key' => $this->User->Attributes['BIP32Master'],
			'BIP32 Public Key' => $this->User->Attributes['BIP32ExtendedPublic'],
			'BIP32 Private Key' => $this->User->Attributes['BIP32ExtendedPrivate'],
		);
		
		$backup = json_encode($backup);
		
		// PGP Encrypt if Applicable
		list($pgp, $encrypt_pgp) = $this->User->Info('PGP', 'EncryptPGP');
		if( $encrypt_pgp == 1 ){
			
			$pgp = new PGP($pgp);
			
			$backup = $pgp->qEncrypt($backup);
			
		}
		
		echo $backup;
		die();
	}
	
	public function deleteDiscussionCommentImage(
		$discussionCommentID,
		$imageID
	){
		return	$this->db->qQuery(
				"
					DELETE
						`DiscussionComment_Image`
					FROM
						`DiscussionComment_Image`
					INNER JOIN
						`Discussion_Comment` ON
							`DiscussionComment_Image`.`DiscussionCommentID` = `Discussion_Comment`.`ID`
					WHERE
						`DiscussionComment_Image`.`DiscussionCommentID` = ? AND
						`DiscussionComment_Image`.`ImageID` = ?
						" . ($this->User->IsMod ? false : 'AND `Discussion_Comment`.`PosterID` = ?') . "
				",
				(
					$this->User->IsMod
						? 'ii'
						: 'iii'
				),
				(
					$this->User->IsMod
						? [
							$discussionCommentID,
							$imageID
						]
						: [
							$discussionCommentID,
							$imageID,
							$this->User->ID
						]
				)
			);
	}
	
	public function addDiscussionCommentImage(
		$discussionCommentID,
		$imageID
	){
		return	$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`DiscussionComment_Image` (
							`DiscussionCommentID`,
							`ImageID`
						)
					VALUES (
						?,
						?
					)
				",
				'ii',
				[
					$discussionCommentID,
					$imageID
				]
			);
	}
	
	public function checkDiscussionCommentImageLimit($discussionCommentID){
		if (
			$imageCounts = $this->db->qSelect(
				"
					SELECT
						IF(
							" . ($this->User->IsMod ? '1 = 1' : '`Discussion_Comment`.`ID` IS NOT NULL') . ",
							COUNT(`DiscussionComment_Image`.`ImageID`),
							NULL
						) count
					FROM
						`DiscussionComment_Image`
					" . (
						$this->User->IsMod
							? false
							: "
								INNER JOIN
									`Discussion_Comment` ON
										`DiscussionComment_Image`.`DiscussionCommentID` = `Discussion_Comment`.`ID` AND
										`Discussion_Comment`.`PosterID` = ?
							"
					) . "
					WHERE
						`DiscussionCommentID` = ?
				",
				(
					$this->User->IsMod
						? 'i'
						: 'ii'
				),
				(
					$this->User->IsMod
						? [$discussionCommentID]
						: [
							$this->User->ID,
							$discussionCommentID
						]
				)
			)
		)
			return	$imageCounts[0]['count'] !== null &&
				$imageCounts[0]['count'] < FORUM_MAX_IMAGES_PER_DISCUSSION_COMMENT;
		
		return false;
	}
	
	public function fetchFavoriteListings($sort, $page){
		$cryptocurrency = $this->User->Cryptocurrency;
		
		$parse_result = function($array) use ($cryptocurrency){
			if ($array['exceededMaximumVisibleRatings'] = $array['rating_count'] > MAX_VISIBLE_INDIVIDUAL_RATINGS)
				$array['rating_count'] = floor($array['rating_count'] / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
			
			return array_merge(
				$array,
				array(
					'price'		=> NXS::formatPrice($this->User->Currency, $array['price']),
					'price_crypto'	=> $cryptocurrency->formatPrice($array['price']),
					'image'		=> NXS::getPictureVariant($array['image'], IMAGE_MEDIUM_SUFFIX)
				)
			);
		};
		
		switch($sort){
			case 'price_asc':
				$sort = "price ASC";
			break;
			case 'price_desc':
				$sort = "price DESC";
			break;
			case 'name_asc':
				$sort = "`Listing`.`Name` ASC";
			break;
			case 'name_desc':
				$sort = "`Listing`.`Name` DESC";
			break;
			// case 'rating':
			default:
				$sort = '
				IFNULL(
					(
						SELECT	AVG(Listing_Rating.`Rating_Vendor`)
						FROM  	`Transaction_Rating` Listing_Rating
						WHERE 	Listing_Rating.`ListingID` = `Listing`.`ID`
					),
					0
				) DESC,
				Vendor.`JoinDateTime` ASC, 
				Vendor.`ID` ASC';
		}
		
		$listingCount = $this->db->qSelect(
			"
				SELECT
					COUNT(`Listing`.`ID`) AS listingCount
				FROM
					`Listing`
				INNER JOIN	`User_Listing`
					ON	`Listing`.`ID` = `User_Listing`.`ListingID`
					AND	`User_Listing`.`UserID` = ?
				WHERE
					#`Listing`.`Inactive` = FALSE AND
					`Listing`.`Approved` = TRUE
			",
			'i',
			array($this->User->ID),
			TRUE
		)[0]['listingCount'];
		
		if ($listingCount > 0){
			if( ceil($listingCount/FAVORITE_LISTINGS_PER_PAGE) < $page ){
				$offset = 0;
				//$this->User->Notifications->quick('FatalError', 'Invalid Page');
			} else {
				$offset = FAVORITE_LISTINGS_PER_PAGE*($page - 1);
			}
			
			$listings = array_map(
				$parse_result,
				$this->db->qSelect(
					"
						SELECT
							DISTINCT `Listing`.`ID` AS id,
							`Listing`.`Name` AS name,
							`Listing`.`Price`/`Currency`.`1EUR` AS price,
							CONCAT(
								'/" . UPLOADS_PATH . "',
								`Image`.`Filename`
							) AS image,
							LEAST(
								(
									SELECT	COUNT(Listing_Rating.`Rating_Vendor`)
									FROM	`Transaction_Rating` Listing_Rating
									WHERE	Listing_Rating.`ListingID` = `Listing`.`ID`
								),
								IFNULL(
									Vendor.`MaxVisibleRatings`,
									" . MAX_VISIBLE_RATINGS_DEFAULT . "
								)
							) AS rating_count,
							(
								SELECT	AVG(Listing_Rating.`Rating_Vendor`)
								FROM	`Transaction_Rating` Listing_Rating
								WHERE	Listing_Rating.`ListingID` = `Listing`.`ID`
							) AS rating,
							Vendor.`Alias` AS alias,
							`Listing`.`Inactive` ||
							`Listing`.`Stealth` ||
							`PaymentMethod`.`ID` IS NULL OR
							`Listing_Group`.`OutOfStock` = TRUE inactive
						FROM
							`Listing`
						LEFT JOIN
							`Listing_Image` ON
								`Listing_Image`.`ListingID` = `Listing`.`ID` AND
								`Listing_Image`.`Primary` = TRUE
						LEFT JOIN
							`Image` ON
								`Listing_Image`.`ImageID` = `Image`.`ID`
						LEFT JOIN
							`Listing_PaymentMethod` ON
								`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
						LEFT JOIN
							`PaymentMethod` ON
								`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
								`PaymentMethod`.`Enabled` = TRUE
						LEFT JOIN
							`Listing_Group` ON
								`Listing`.`ID` = `Listing_Group`.`ListingID`
						INNER JOIN	`Currency`
							ON	`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN	`User` Vendor
							ON	`Listing`.`VendorID` = Vendor.`ID`
						INNER JOIN	`User_Listing`
							ON	`Listing`.`ID` = `User_Listing`.`ListingID`
							AND	`User_Listing`.`UserID` = ?
						WHERE
							#`Listing`.`Inactive` = FALSE AND
							`Listing`.`Approved` = TRUE
						ORDER BY
							" . $sort . "
						LIMIT ?, " . FAVORITE_LISTINGS_PER_PAGE . "
					",
					'ii',
					array(
						$this->User->ID,
						$offset
					),
					TRUE
				)
			);
			
		} else
			return FALSE;
		
		return array(
			$listingCount,
			$listings
		);
		
	}
	
	public function deleteAccount(){
		if( !$this->checkAuthentication('Confirm permanently deleting your account', 'authorize', URL . 'account/delete_account/') ){
			header('Location: ' . URL . 'account/#authorize');
			die;
		}
		
		$u = Session::get('u');
		$uid = Session::get('user_id');
		
		if($stmt_DeleteAccount = $this->db->prepare("
			DELETE
				`Activity`, `aID`, `Listing`, `LoginAttempt`, `Message`, `User`
			FROM `User`
				LEFT JOIN `Activity` ON `User`.`ID` = `Activity`.`UserID`
				LEFT JOIN `aID` ON `aID`.`u` = ?
				LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`
				LEFT JOIN `LoginAttempt` ON `aID`.`u` = `LoginAttempt`.`u`
				LEFT JOIN `Message` ON `User`.`ID` = `Message`.`SenderID` OR `User`.`ID` = `Message`.`RecipientID`
			WHERE
				`User`.`ID` = ?
		") ){
			$stmt_DeleteAccount->bind_param('si', $u, $uid);
			if($stmt_DeleteAccount->execute()){
				return true;
			} else {
				$_SESSION["feedback_negative"]['general'][] = FEEDBACK_ACCOUNT_DELETION_FAILED;
				return false;
			}
		}
	}
	
	public function fetchUnits(
		$groupByDimensions = false,
		&$units = null
	){
		if (
			$units = $this->db->qSelect(
				"
					SELECT
						`ID` as id,
						`Name_Plural` as name,
						`Abbreviation` as abbreviation,
						`DimensionID`
					FROM
						`Unit`
				"
			)
		){
			if ($groupByDimensions){
				$dimensions = [];
				foreach ($units as $unit)
					$dimensions[$unit['DimensionID']][] = $unit;
					
				return $dimensions;
			}
				
			return $units;
		}
		
		return false;
	}
	
	public function fetchContinentsCountries(){
		$stmt_Continent = $this->db->prepare("
			SELECT
				`ID`,
				`Name`
			FROM
				`Continent`
		");
		
		$stmt_Country = $this->db->prepare("
			SELECT
				`ID`,
				`ContinentID`,
				`Name`
			FROM
				`Country`
			WHERE
				`Enabled` = TRUE
			ORDER BY
				`Name`
		");
		
		if( false != $stmt_Continent && false != $stmt_Country ){
			$stmt_Continent->execute();
			$stmt_Continent->store_result();
			$stmt_Continent->bind_result($continent_id, $continent_name);
			$continents = array();
			while($stmt_Continent->fetch() ){
				$continents[$continent_id] = array(
					'name' => $continent_name
				);
			}
			
			$stmt_Country->execute();
			$stmt_Country->store_result();
			$stmt_Country->bind_result($country_id, $country_continent_id, $country_name);
			while($stmt_Country->fetch() ){
				$continents[$country_continent_id]['countries'][] = array(
					'id' => $country_id,
					'name' => $country_name);
			}
			
			return $continents;
		}
	}
	
	public function getUserPreferences(){
		if (
			$stmt_getPreferences = $this->db->prepare("
				SELECT
					`User`.`PGP`,
					`User`.`InvalidPGP`,
					`User`.`Description`,
					CONCAT(
						'/" . UPLOADS_PATH . "',
						`Image`.`Filename`
					) Image,
					`User`.`Signature_Raw`,
					`User`.`2FA`,
					`User`.`AllowMultipleSessions`
				FROM
					`User`
				LEFT JOIN
					`Image` ON
						`User`.`ImageID` = `Image`.`ID`
				WHERE
					`User`.`ID` = ?
				LIMIT 1
			")
		){
			$stmt_getPreferences->bind_param('i', $this->User->ID);
			$stmt_getPreferences->execute();
			$stmt_getPreferences->store_result();
			$stmt_getPreferences->bind_result(
				$pgp,
				$invalidPGP,
				$description,
				$image,
				$signature,
				$twoFA,
				$allowMultipleSessions
			);
			$stmt_getPreferences->fetch();
			
			$paymentMethods = $this->getUserPaymentMethods();
			
			return array(
				'pgp' => [
					'key'		=> $pgp,
					'invalid'	=> $invalidPGP == 1,
					'twoFA'		=> $twoFA == 1
				],
				'profile'		=> $description,
				'image'			=> $image ? NXS::getPictureVariant($image, IMAGE_MEDIUM_SUFFIX) : false,
				'signature'		=> $signature,
				'allowMultipleSessions' => $allowMultipleSessions,
				'paymentMethods'	=> $paymentMethods
			);
		}
		
		return false;
	}
	
	private function getPaymentMethodsListings(
		$paymentMethodID,
		&$allActive
	){
		$allActive = true;
		if (
			$listings = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Listing`.`ID`,
						`Listing`.`Name`,
						`Listing_PaymentMethod`.`ListingID` IS NOT NULL Enabled
					FROM
						`Listing`
					LEFT JOIN
						`Listing_PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = ? AND
							`Listing_PaymentMethod`.`ListingID` = `Listing`.`ID`
					WHERE
						`Listing`.`VendorID` = ? AND
						`Listing`.`Archived` = FALSE
				",
				'ii',
				[
					$paymentMethodID,
					$this->User->ID
				]
			)
		)
			return array_map(
				function($listing) use (&$allActive){
					if (!$listing['Enabled'])
						$allActive = false;
					
					return array_merge(
						$listing,
						[
							'label' => '#' . NXS::getB36($listing['ID']) . '&emsp;' . $listing['Name']
						]
					);
				},
				$listings
			);
		
		return false;
	}
	
	private function getUserPaymentMethods(){
		if (
			$paymentMethods = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Currency`.`Name`,
						IFNULL(
							`PaymentMethod`.`ID`,
							`Currency`.`ISO`
						) Identifier,
						`PaymentMethod`.`Enabled`,
						`PaymentMethod`.`PublicKey`,
						`PaymentMethod`.`ExtendedPublicKey`
					FROM
						`Currency`
					LEFT JOIN
						`PaymentMethod` ON
							`PaymentMethod`.`CryptocurrencyID` = `Currency`.`ID` AND
							`PaymentMethod`.`UserID` = ?
					WHERE
						`Currency`.`Crypto` = TRUE	
				",
				'i',
				[$this->User->ID]
			)
		)
			return array_map(
				function($paymentMethod){
					$hasID = is_numeric($paymentMethod['Identifier']);
					
					$paymentMethod['configured'] =
						$hasID &&
						$paymentMethod['ExtendedPublicKey'];
					$paymentMethod['listings'] = $this->getPaymentMethodsListings(
						$hasID ? $paymentMethod['Identifier'] : null,
						$paymentMethod['allActive']
					);
					$paymentMethod['Enabled'] = $paymentMethod['Enabled'] || !$this->User->IsVendor;
					$paymentMethod['Icon'] = Icon::getClass($paymentMethod['Name']);
					
					return $paymentMethod;
				},
				$paymentMethods
			);
		
		return false;
	}
	
	public function updateSettings(){
		if (!empty($_POST)) {
			// Store Form
			foreach($_POST as $key => $value)
				$_SESSION['settings_post'][$key] = 
					is_array($value)
						? array_map(
							'htmlspecialchars',
							$value
						)
						: htmlspecialchars($value);
			
			// Preliminary Validation
			$tryAgain = false;
			if (
				isset($_POST['password']) &&
				$_POST['password'] != $_POST['password_repeat']
			){
				$_SESSION["settings_feedback"]['password_repeat'] = FEEDBACK_PASSWORD_REPEAT_WRONG;
				$tryAgain = true;
			}
			
			if(
				!empty($_POST['pgp']) &&
				$_POST['pgp'] !== $this->User->Info(0,'PGP')
			){
				if(!$this->checkPGP($_POST['new_pgp_code'], 'new_pgp', $_POST['pgp'])){
					if( !empty($_POST['new_pgp_code']))
						$_SESSION['settings_feedback']['new_pgp_code'] = FEEDBACK_INVALID_AUTHENTICATION_CODE;
					if( $this->generatePGPMessage($_POST['pgp'], 'new_pgp', true) ){
						header('Location: ' . URL . 'account/settings/#verify-pgp');
						die;
					} else {
						unset($_POST['double_encryption'], $_POST['pgp_authentication'], $_POST['two_factor_authentication']);
						$update[] = '`User`.`InvalidPGP` = TRUE';
						// Unsupported Public Key Format
						//$_SESSION["settings_feedback"]["pgp"] = 'This public key format is not supported.';
						//$tryAgain = true;
					}
				} else
					$update[] = '`User`.`InvalidPGP` = FALSE';
				
			}
			
			// Format PGP
			if( !empty($_POST['pgp']) ){
				$_POST['pgp'] = trim($_POST['pgp']);
				
				$pgp_lines = explode(PHP_EOL, $_POST['pgp']);
				foreach($pgp_lines as $key => $line){
					$start = strtolower( substr( $line, 0, 7 ) );
					if( $start == 'version' || $start == 'comment' ) {
						unset($pgp_lines[$key]);
					}
				}
				$_POST['pgp'] = implode(PHP_EOL, $pgp_lines);
			}
			
			if (!$this->db->forum){
				$paymentMethods = [];
				foreach ($_POST['payment_method'] as $paymentMethodIdentifier){
					$inputID = 'payment_method-' . $paymentMethodIdentifier;
					
					/*
					$paymentMethodPublicKeyName = $inputID . '-public_key';
					$paymentMethodPublicKey = trim($_POST[$paymentMethodPublicKeyName]);
					
					if (
						empty($paymentMethodPublicKey) &&
						!$this->User->IsVendor
					)
						continue;
					
					if (
						$invalidPaymentMethodPublicKey =
							empty($paymentMethodPublicKey) ||
							!BitcoinLib::validate_public_key($paymentMethodPublicKey) ||
							strlen($paymentMethodPublicKey) < 10 ||
							preg_match('/\s/', $paymentMethodPublicKey)
					){
						$_SESSION["settings_feedback"][$paymentMethodPublicKeyName] = true;
						$tryAgain = 'crypto';
					}
					*/
					
					$paymentMethodExtendedPublicKeyName = $inputID . '-extended_public_key';
					$paymentMethodExtendedPublicKey = trim($_POST[$paymentMethodExtendedPublicKeyName]);
					
					if (
						$invalidPaymentMethodExtendedPublicKey =
							(
								!empty($paymentMethodExtendedPublicKey) ||
								(
									$this->User->IsVendor &&
									!isset($_POST[$inputID . '-configured'])
								)
							) &&
							!NXS::validateXPUB($paymentMethodExtendedPublicKey)
					){
						$_SESSION["settings_feedback"][$paymentMethodExtendedPublicKeyName] = true;
						$tryAgain = 'crypto';
					}
					
					if (
						!$invalidPaymentMethodPublicKey &&
						!$invalidPaymentMethodExtendedPublicKey
					){
						$paymentMethod = [
							'Identifier' => $paymentMethodIdentifier,
							'PublicKey' => null, //$paymentMethodPublicKey,
							'ExtendedPublicKey' => $paymentMethodExtendedPublicKey ?: null
						];
						
						if ($this->User->IsVendor){
							if (
								isset($_POST[$inputID . '-configure_listings-toggle']) &&
								$_POST[$inputID . '-configure_listings-toggle'] == 'selected'
							)
								$paymentMethod['listingIDs'] =
									isset($_POST[$inputID . '-configure_listings-listings'])
										? $_POST[$inputID . '-configure_listings-listings']
										: [];
							else
								$paymentMethod['listingIDs'] = false;
						}
						
						$paymentMethods[] = $paymentMethod;
					}
				}
			}
			
			if ($tryAgain)
				return $tryAgain;
			
			// Authentication
			$needs_authentication =
				!empty($_POST['authorizing']) ||
				!empty($_POST['username']) ||
				!empty($_POST['password']) ||
				(
					!isset($_POST['double_encryption']) &&
					$this->User->Info(0,'EncryptPGP') == 1
				) ||
				(
					!isset($_POST['two_factor_authentication']) &&
					$this->User->Info(0,'2FA') == 1
				) ||
				(
					empty($_POST['pgp']) &&
					!empty($_SESSION['pgp'])
				);
			
			if (
				$needs_authentication &&
				!$this->checkAuthentication('Please authorize to confirm these settings.', 'authorize_settings')
			){
				header('Location: ' . URL . 'account/settings/#authorize');
				die;
			}
			
			// Actual Updating
			if (
				!empty($_POST['username']) ||
				!empty($_POST['password'])
			){	
				$u0 = Session::get('u');
				
				if( !empty($_POST['username']) && $_POST['username'] != Session::get('u') ) {
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
						$stmt_ExistingUser->bind_param('s', $u);
						$stmt_ExistingUser->execute();
						$stmt_ExistingUser->store_result();
						$stmt_ExistingUser->bind_result($user_count);
						$stmt_ExistingUser->fetch();
						if ($user_count > 0) {
							$_SESSION["settings_feedback"]['username'] = FEEDBACK_USERNAME_ALREADY_TAKEN;
							return false;
						}
					} else {
						return false;
					}
				} else {
					$username = sha1( strtolower($_POST['authorize_username']) );
					$u = $u0;
				}
				
				list ($s, $a) = $this->get_salt_a($username);
				
				if( !empty($_POST['password']) ){
					$password = isset($_POST['prehashed']) ? $_POST['password'] : hash('sha512', $_POST['password']);
				} else {
					$password = hash('sha512', $_POST['authorize_password']);
				}
				
				$p0 = hash('sha512', $password . $s);
				$p = hash('sha512', $p0 . $s);
				
				$rsa = new Crypt_RSA();
				
				$rsa->setHash('sha256');
				$rsa->setMGFHash('sha256');
				 
				$rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
				
				$privatekey = Session::get('private_key');
				
				$rsa->loadKey($privatekey);
				
				$rsa->setPassword($p0);
				$privatekey = $rsa->getPrivateKey();
				
				$stmt_UpdateAID = $this->db->prepare("
					UPDATE
						`aID`
					SET
						`u` = ?,
						`s` = ?,
						`p` = ?
					WHERE
						`u` = ?
				");
				$stmt_UpdateUserAuth = $this->db->prepare("
					UPDATE
						`User`
					SET
						`aID` = ?,
						`PrivateKey` = ?
					WHERE
						`ID` = ?
				");
				
				if( false != $stmt_UpdateAID && false != $stmt_UpdateUserAuth ){
					$stmt_UpdateAID->bind_param('ssss', $u, $s, $p, $u0 );
					$stmt_UpdateAID->execute();
					
					$stmt_UpdateUserAuth->bind_param('ssi', $a, $privatekey, $this->User->ID);
					$stmt_UpdateUserAuth->execute();
					
					$user_browser = $_SERVER['HTTP_USER_AGENT'];
					Session::set('u', $u);
					Session::set('login_string', hash('sha512', $p . $user_browser));
				}
			}
			
			$new_attributes = array();
			
			$stmt_types = '';
			$stmt_params = array();
			
			if ($this->db->forum){
				if( !empty($_POST['signature']) ){
					$update[] = '`User`.`Signature_Raw` = ?';
					$stmt_types .= 's';
					$stmt_params[] = &$_POST['signature'];
					
					$formatted = NXS::formatText($_POST['signature']);
					$update[] = '`User`.`Signature` = ?';
					$stmt_types .= 's';
					$stmt_params[] = &$formatted;
				} else {
					$update[] = "`User`.`Signature_Raw` = NULL";
					$update[] = "`User`.`Signature` = NULL";	
				}
			}
			
			if (!empty($_POST['uploads']['file'])) {
				$update[] = '`User`.`ImageID` = ?';
				$update[] = '`User`.`ColorShift` = NULL';
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['uploads']['file'];
			} elseif (isset($_POST['delete_pic']))
				$update[] = "`User`.`ImageID` = NULL";
			
			
			if (!empty($_POST['pgp'])){
				$pgp = $_POST['pgp'];
				
				if( $pgp !== $this->User->Info(0,'PGP') ){
					$update[] = '`User`.`PGP` = ?';
					$stmt_types .= 's';
					$stmt_params[] = &$pgp;
				}
				
				if( isset($_POST['show_pgp']) ) {
					$update[] = '`User`.`PublicPGP` = TRUE';
				} else {
					$update[] = '`User`.`PublicPGP` = FALSE';
				}
				
				if( isset($_POST['pgp_authentication']) ) {
					$_SESSION['pgp'] = $pgp;
					$update[] = "`User`.`AuthPGP` = TRUE";
				} else {
					unset($_SESSION['pgp']);
					$update[] = "`User`.`AuthPGP` = FALSE";
				}
				
				if( isset($_POST['double_encryption']) ){
					$update[] = "`User`.`EncryptPGP` = TRUE";
				} else {
					$update[] = "`User`.`EncryptPGP` = FALSE";
				}
				
				if( isset($_POST['two_factor_authentication']) ){
					$_SESSION['pgp'] = $pgp;
					$update[] = "`User`.`2FA` = TRUE";
				} else {
					unset($_SESSION['pgp']);
					$update[] = "`User`.`2FA` = FALSE";
				}
			} else {
				$update[] = '`User`.`PublicPGP` = FALSE';
				$update[] = '`User`.`PGP` = NULL';
				$update[] = "`User`.`AuthPGP` = FALSE";
				$update[] = "`User`.`EncryptPGP` = FALSE";
				$update[] = "`User`.`2FA` = FALSE";
				unset($_SESSION['pgp']);
			}
			
			/*if(
				$_SESSION['allowMultipleSessions'] =
					$this->User->IsVendor &&
					isset($_POST['allow_multiple_sessions'])
			)
				$update[] = '`User`.`AllowMultipleSessions` = TRUE';
			else
				$update[] = '`User`.`AllowMultipleSessions` = FALSE';*/
			
			$listing_count = $this->User->Info(0,'ListingCount');
			
			if( !empty($new_attributes) ){
				$this->User->updateAttributes($new_attributes);
			}
			
			if( !empty($update) ){
				
				$stmt_types .= 'i';
				$stmt_params[] = &$this->User->ID;
				
				$stmt_args = array_merge( array($stmt_types), $stmt_params );
				
				if( $stmt_updateUser = $this->db->prepare("
					UPDATE
						`User`
					SET
						".implode(', ', $update)."
					WHERE
						`ID` = ?
					LIMIT 1
				") ){
					
					call_user_func_array(array($stmt_updateUser, 'bind_param'), $stmt_args); 
					if (!$stmt_updateUser->execute())
						return false;
					else {
						if (!$this->db->forum)
							$this->updateUserPaymentMethods($paymentMethods);
							
						// FINISHING TOUCHES
						if( isset($alias) ){
							
							Session::set('alias', $alias);
							
							/*if( $stmt_deleteRandomAlias = $this->db->prepare("
								DELETE FROM
									`RandomAlias`
								WHERE
									`Alias` = ?
								LIMIT 1
							") ){
								
								$stmt_deleteRandomAlias->bind_param('s', $alias);
								$stmt_deleteRandomAlias->execute();
								
							}*/
						}
					}
				} else
					die(); //die($this->db->error);
			}
			
			$_SESSION['temp_notifications'][] = array(
				'Content'	=> 'Settings saved successfully',
				'Anchor'		=> false,
				'Dismiss'	=> '.',
				'Group'		=> 'Settings',
				'Design'		=> array(
					'Color' => 'green',
					'Icon' => Icon::getClass('CHECK')
				)
			);
			
			unset($_SESSION['settings_feedback'], $_SESSION['settings_post'], $_SESSION['authorize_settings']);
			if(isset($_SESSION['new_user']))
				unset($_SESSION['new_user']);
			return true;
		}
	}
	
	private function checkUserPaymentMethod($paymentMethodID){
		return $this->db->qSelect(
			"
				SELECT
					`ID`
				FROM
					`PaymentMethod`
				WHERE
					`UserID` = ? AND
					`ID` = ?
			",
			'ii',
			[
				$this->User->ID,
				$paymentMethodID
			]
		);
	}
	
	private function _updateUserPaymentMethod($paymentMethod){
		if (
			is_numeric($paymentMethod['Identifier']) &&
			$paymentMethodID = $paymentMethod['Identifier']
		){
			return	$this->db->qQuery(
					"
						UPDATE
							`PaymentMethod`
						INNER JOIN
							`User` ON
								`PaymentMethod`.`UserID` = `User`.`ID`
						SET
							`PaymentMethod`.`Enabled` = TRUE,
							`PaymentMethod`.`PublicKey` = ?,
							`PaymentMethod`.`ExtendedPublicKey` = IF (
								`PaymentMethod`.`ExtendedPublicKey` IS NULL OR
								`User`.`Vendor` = FALSE,
								?,
								`PaymentMethod`.`ExtendedPublicKey`
							)
						WHERE
							`PaymentMethod`.`ID` = ? AND
							`PaymentMethod`.`UserID` = ?
					",
					'ssii',
					[
						$paymentMethod['PublicKey'],
						$paymentMethod['ExtendedPublicKey'],
						$paymentMethodID,
						$this->User->ID
					]
				) ||
				$this->checkUserPaymentMethod($paymentMethodID)
					? $paymentMethodID
					: false;
		} else
			return $this->db->qQuery(
				"
					INSERT INTO
						`PaymentMethod` (
							`Enabled`,
							`UserID`,
							`CryptocurrencyID`,
							`PublicKey`,
							`ExtendedPublicKey`
						)
					VALUES (
						TRUE,
						?,
						(
							SELECT
								`ID`
							FROM
								`Currency`
							WHERE
								`ISO` = ? AND
								`Crypto` = TRUE
						),
						?,
						?
					)
				",
				'isss',
				[
					$this->User->ID,
					$paymentMethod['Identifier'],
					$paymentMethod['PublicKey'],
					$paymentMethod['ExtendedPublicKey']
				]
			);
	}
	
	private function updateListingPaymentMethods(
		$paymentMethodIDs,
		$listingIDs,
		$deleteRest = true
	){
		if (is_array($listingIDs)){
			foreach ($listingIDs as $listingID)
				if ($paymentMethodIDs){
					if ($singlePaymentMethod = !is_array($paymentMethodIDs))
						$paymentMethodIDs = [$paymentMethodIDs];
					
					foreach ($paymentMethodIDs as $paymentMethodID)
						$this->db->qQuery(
							"
								INSERT IGNORE INTO
									`Listing_PaymentMethod` (
										`ListingID`,
										`PaymentMethodID`
									)
								VALUES (
									?,
									?
								)
							",
							'ii',
							[
								$listingID,
								$paymentMethodID
							]
						);
						
					if (!$singlePaymentMethod)
						$this->db->qQuery(
							"
								DELETE
									`Listing_PaymentMethod`
								FROM
									`Listing_PaymentMethod`	
								WHERE
									`ListingID` = ?
									" . (
										$paymentMethodIDs
											? "AND `PaymentMethodID` NOT IN (" . rtrim(str_repeat('?, ', count($paymentMethodIDs)),', ') . ")"
											: false
									) . "
							",
							'i' . str_repeat('i', count($paymentMethodIDs)),
							array_merge(
								[$listingID],
								$paymentMethodIDs
							)
						);
				} else
					$this->db->qQuery(
						"
							INSERT IGNORE INTO
								`Listing_PaymentMethod` (
									`ListingID`,
									`PaymentMethodID`
								)
							SELECT
								?,
								`ID`
							FROM
								`PaymentMethod`
							WHERE
								`UserID` = ?
						",
						'ii',
						[
							$listingID,
							$this->User->ID
						]
					);
				
			return
				$deleteRest
					? $this->db->qQuery(
						"
							DELETE
								`Listing_PaymentMethod`
							FROM
								`Listing_PaymentMethod`	
							INNER JOIN
								`Listing` ON
									`Listing_PaymentMethod`.`ListingID` = `Listing`.`ID`
							WHERE
								`Listing`.`Archived` = FALSE AND
								`PaymentMethodID` = ?
								" . (
									$listingIDs
										? "AND `ListingID` NOT IN (" . rtrim(str_repeat('?, ', count($listingIDs)),', ') . ")"
										: false
								) . "
						",
						'i' . str_repeat('i', count($listingIDs)),
						array_merge(
							[$paymentMethodIDs],
							$listingIDs
						)
					)
					: true;
		} else
			return $this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Listing_PaymentMethod` (
							`ListingID`,
							`PaymentMethodID`
						)
					SELECT
						`ID`,
						?
					FROM
						`Listing`
					WHERE
						`VendorID` = ? AND
						`Archived` = FALSE
				",
				'ii',
				[
					$paymentMethodIDs,
					$this->User->ID
				]
			);
	}
	
	private function updateUserPaymentMethods($paymentMethods){
		$paymentMethodIDs = [];
		foreach ($paymentMethods as $paymentMethod){
			$paymentMethodID = $paymentMethodIDs[] = $this->_updateUserPaymentMethod($paymentMethod);
			
			if (
				$paymentMethodID &&
				isset($paymentMethod['listingIDs'])
			)
				$this->updateListingPaymentMethods(
					$paymentMethodID,
					$paymentMethod['listingIDs']
				);
		}
		
		return $this->db->qQuery(
			"
				UPDATE
					`PaymentMethod`
				SET
					`Enabled` = FALSE
					" . (
						!$this->User->IsVendor
							? ', `PublicKey` = NULL'
							: false
					) . "
				WHERE
					`UserID` = ?
					" . (
						$paymentMethodIDs
							? "AND `ID` NOT IN (" . rtrim(str_repeat('?, ', count($paymentMethodIDs)),', ') . ")"
							: false
					) . "
			",
			'i' . str_repeat('i', count($paymentMethodIDs)),
			array_merge(
				[$this->User->ID],
				$paymentMethodIDs
			)
		);
	}
	
	private function backupPublicKey($publicKey){
		return $this->db->qQuery(
			"
				INSERT INTO
					`User_BTCPublicKey` (`UserID`, `BTCPublicKey`)
				VALUES
					(?, ?)
				ON DUPLICATE KEY UPDATE
					`BTCPublicKey`	= ?
			",
			'iss',
			array(
				$this->User->ID,
				$publicKey,
				$publicKey
			)
		);
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
	
	private function _filter_chats_filterMode(&$filterMode){
		$whereClause = FALSE;
		$types = FALSE;
		$variables = [];
		
		switch(strtolower($filterMode)){
			case 'all':
			break;
			case 'closed':
			case 'open':
			case 'ongoing':
			case 'important':
			case 'urgent':
				$chatStatusID = CONSTANT(CHAT_STATUS_FLAG_PREFIX . strtoupper($filterMode));
				
				$whereClause = "
					(
						`ChatStatus`.`ID` = " . $chatStatusID . " OR
						ParentChatStatus.`ID` = " . $chatStatusID . "
					)
				";
			break;
			case 'assigned':
				$whereClause = "
					MyChatSubscription.`UserID` IS NOT NULL AND
					MyChatSubscription.`Role` = '" . CHAT_ROLE_SUPPORT . "'
				";
			break;
			case 'waiting':
				$whereClause = "
					(
						SELECT
							COUNT(SupporterChatSubscription.`UserID`)
						FROM
							`ChatSubscription` SupporterChatSubscription
						WHERE
							`Chat`.`ID` = SupporterChatSubscription.`ChatID` AND
							SupporterChatSubscription.`Role` = '" . CHAT_ROLE_SUPPORT . "'
					) = 0
				";
			break;
			default:
				$filterMode = SUPPORT_OVERVIEW_DEFAULT_FILTER_MODE;
				return $this->_filter_chats_filterMode($filterMode);
		}
		
		return [
			'query'		=> [
				'whereClause' => $whereClause
			],
			'types'		=> $types,
			'variables'	=> $variables
		];
	}
	
	private function _filter_chats_sortMode(&$sortMode){
		$orderByClause = FALSE;
		$types = FALSE;
		$variables = [];
		
		switch(strtolower($sortMode)){
			case 'priority_desc':
				$orderByClause = "
					(
						Assigned AND
						`ChatStatus`.`ID` != " . CHAT_STATUS_ID_CLOSED . "
					) DESC,
					`ChatStatus`.`Priority` DESC,
					`Chat`.`DateTime` DESC,
					`Chat`.`ID` DESC
				";
			break;
			case 'user_asc':
				$orderByClause = "
					`SubjectUser`.`Alias` ASC
				";
			break;
			case 'user_desc':
				$orderByClause = "
					`SubjectUser`.`Alias` DESC
				";
			break;
			default:
				$sortMode = SUPPORT_OVERVIEW_DEFAULT_SORT_MODE;
				return $this->_filter_chats_sortMode($sortMode);
		}
		
		return [
			'query'		=> [
				'orderByClause' => $orderByClause
			],
			'types'		=> $types,
			'variables'	=> $variables
		];
	}
	
	private function _prepareStmtParams_countChats(&$filterMode){
		$stmtParams_filter = $this->_filter_chats_filterMode($filterMode);
		
		$query = "
			SELECT
				COUNT(DISTINCT `Chat`.`ID`) chatCount
			FROM
				`Chat`
			INNER JOIN
				`ChatStatus` ON
					`Chat`.`StatusID` = `ChatStatus`.`ID`
			LEFT JOIN
				`ChatStatus` ParentChatStatus ON
					`ChatStatus`.`ParentID` = ParentChatStatus.`ID`
			INNER JOIN
				`User` SubjectUser ON
					`Chat`.`SubjectUserID` = SubjectUser.`ID`
			LEFT JOIN
				`ChatSubscription` MyChatSubscription ON
					`Chat`.`ID` = MyChatSubscription.`ChatID` AND
					MyChatSubscription.`UserID` = ?
			LEFT JOIN
				`ChatNote` LatestChatNote ON
					`Chat`.`ID` = LatestChatNote.`ChatID` AND
					LatestChatNote.`ID` = (
						SELECT
							`ChatNote`.`ID`
						FROM
							`ChatNote`
						WHERE
							`ChatNote`.`ChatID` = `Chat`.`ID`
						ORDER BY
							`ChatNote`.`DateTime` DESC,
							`ChatNote`.`ID` DESC
						LIMIT 1
					)
			" . (
				$stmtParams_filter['query']['whereClause']
					? 'WHERE ' . $stmtParams_filter['query']['whereClause']
					: FALSE
			) . "
		";
		
		$types = 'i' . $stmtParams_filter['types'];
		
		$variables = array_merge(
			array(
				$this->User->ID
			),
			$stmtParams_filter['variables']
		);
		
		return [
			'query'		=> $query,
			'types'		=> $types,
			'variables'	=> $variables
		];
	}
	
	public function countChats($filterMode = SUPPORT_OVERVIEW_DEFAULT_FILTER_MODE){
		$stmtParams = $this->_prepareStmtParams_countChats($filterMode);
		
		if(
			$chatCounts = $this->db->qSelect(
				$stmtParams['query'],
				$stmtParams['types'],
				$stmtParams['variables']
			)
		)
			return $chatCounts[0]['chatCount'];
	}
	
	private function _prepareStmtParams_fetchChats(
		&$filterMode,
		&$sortMode,
		$offset,
		$quantity,
		$chatIDs
	){
		$stmtParams_filter = $this->_filter_chats_filterMode($filterMode);
		$stmtParams_sort = $this->_filter_chats_sortMode($sortMode);
		
		$whereClause = $stmtParams_filter['query']['whereClause'];
		
		if($chatIDs){
			$whereClause .= 
				(
					$whereClause
						? " AND "
						: FALSE
				) .
				"`Chat`.`ID` IN (" . 
				rtrim(
					str_repeat('?, ', count($chatIDs)),
					', '
				) .
				")";
			
			$types =
				'i' .
				$stmtParams_filter['types'] .
				str_repeat('i', count($chatIDs)) . 
				$stmtParams_sort['types'] .
				'i';
				
			$variables = array_merge(
				[
					$this->User->ID
				],
				$stmtParams_filter['variables'],
				$chatIDs,
				$stmtParams_sort['variables'],
				[
					$offset
				]
			);
		} else {
			$types =
				'i' .
				$stmtParams_filter['types'] .
				$stmtParams_sort['types'] .
				'ii';
			$variables = array_merge(
				[
					$this->User->ID
				],
				$stmtParams_filter['variables'],
				$stmtParams_sort['variables'],
				[
					$offset,
					$quantity
				]
			);
		}
		
		$query = "
			SELECT
				`Chat`.`ID`,
				`ChatStatus`.`ID` StatusID,
				`ChatStatus`.`Title` StatusTitle,
				`ChatStatus`.`Icon` StatusIcon,
				`ChatStatus`.`Color` StatusColor,
				SubjectUser.`Alias` SubjectAlias,
				(
					SELECT
						COUNT(`ChatMessage`.`ID`)
					FROM
						`ChatMessage`
					WHERE
						`ChatMessage`.`ChatID` = `Chat`.`ID`
				) messageCount,
				(
					MyChatSubscription.`UserID` IS NOT NULL AND
					MyChatSubscription.`Role` = '" . CHAT_ROLE_SUPPORT . "'
				) Assigned,
				LatestChatNote.`Note` LatestNote
			FROM
				`Chat`
			INNER JOIN
				`ChatStatus` ON
					`Chat`.`StatusID` = `ChatStatus`.`ID`
			LEFT JOIN
				`ChatStatus` ParentChatStatus ON
					`ChatStatus`.`ParentID` = ParentChatStatus.`ID`
			INNER JOIN
				`User` SubjectUser ON
					`Chat`.`SubjectUserID` = SubjectUser.`ID`
			LEFT JOIN
				`ChatSubscription` MyChatSubscription ON
					`Chat`.`ID` = MyChatSubscription.`ChatID` AND
					MyChatSubscription.`UserID` = ?
			LEFT JOIN
				`ChatNote` LatestChatNote ON
					`Chat`.`ID` = LatestChatNote.`ChatID` AND
					LatestChatNote.`ID` = (
						SELECT
							`ChatNote`.`ID`
						FROM
							`ChatNote`
						WHERE
							`ChatNote`.`ChatID` = `Chat`.`ID`
						ORDER BY
							`ChatNote`.`DateTime` DESC,
							`ChatNote`.`ID` DESC
						LIMIT 1
					)
			" . (
				$whereClause
					? 'WHERE ' . $whereClause
					: FALSE
			) . "
			ORDER BY
				" . $stmtParams_sort['query']['orderByClause'] . "
			LIMIT
				?,
				" . (
					$chatIDs
						? '18446744073709551615'
						: '?'
				) . "
		";
		
		return [
			'query' 	=> $query,
			'types'		=> $types,
			'variables'	=> $variables
		];
	}
	
	public function fetchChats(
		$filterMode = SUPPORT_OVERVIEW_DEFAULT_FILTER_MODE,
		$sortMode = SUPPORT_OVERVIEW_DEFAULT_SORT_MODE,
		$quantity = SUPPORT_OVERVIEW_CHATS_PER_PAGE,
		$offset = 0,
		$chatIDs = FALSE
	){
		$stmtParams = $this->_prepareStmtParams_fetchChats(
			$filterMode,
			$sortMode,
			$offset,
			$quantity,
			$chatIDs
		);
		
		$chats = $this->db->qSelect(
			$stmtParams['query'],
			$stmtParams['types'],
			$stmtParams['variables']
		);
		
		if($chats){
			foreach($chats as $key => $chat){
				$latestMessages = FALSE;
				if($chat['messageCount'] > 0)
					$latestMessages = $this->fetchChatMessages(
						$chat['ID'],
						SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_SORT_MODE,
						SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_QUANTITY,
						0,
						TRUE,
						FALSE
					);
				
				$chats[ $key ]['latestMessages'] = $latestMessages;
			}
			
			return $chats;
		}
		
		return FALSE;
	}
	
	private function _filter_chatMessages_sortMode(&$sortMode){
		switch(strtolower($sortMode)){
			case CHAT_MESSAGES_SORT_MODE_ID_DESC:
				$orderByClause = "
					`ChatMessage`.`DateTime` DESC,
					`ChatMessage`.`ID` DESC
				";
			break;
			default:
				$sortMode = CHAT_MESSAGES_SORT_MODE_DEFAULT;
				return $this->_filter_chatMessages_sortMode($sortMode);
		}
		
		return [
			'query'		=> [
				'orderByClause' => $orderByClause
			],
			'variables'	=> [],
			'types'		=> ''
		];
	}
	
	private function _prepareStmtParams_fetchChatMessages(
		$chatID,
		&$sortMode,
		$offset,
		$quantity,
		$getRead
	){
		$stmtParams_sort = $this->_filter_chatMessages_sortMode($sortMode);
		
		$query = "
			SELECT
				`ChatMessage`.`ID`,
				Sender.`Alias` SenderAlias,
				UNIX_TIMESTAMP(`ChatMessage`.`DateTime`) Timestamp,
				`ChatMessage`.`Color`,
				`ChatMessage`.`TransactionID`,
				`Transaction`.`Identifier` TransactionIdentifier,
				`UserContent`.`Formatted` HTML,
				IF(
					thisUser.`Moderator`,
					`UserContent`.`Raw`,
					FALSE
				) RawContent,
				(
					`ChatSubscription`.`SeenMessageID` IS NOT NULL AND
					`ChatMessage`.`DateTime` > IFNULL(
						(
							SELECT	`DateTime`
							FROM	`ChatMessage`
							WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
						),
						'" . MYSQL_DATETIME_RANGE_LOWEST . "'
					)
				) Unread,
				Sender.`Admin` isAdmin,
				(
					SubjectChatSubscription.`SeenMessageID` IS NOT NULL AND
					`ChatMessage`.`DateTime` <= IFNULL(
						(
							SELECT	`DateTime`
							FROM	`ChatMessage`
							WHERE	`ID` = SubjectChatSubscription.`SeenMessageID`
						),
						'" . MYSQL_DATETIME_RANGE_LOWEST . "'
					)
				) Seen
			FROM
				`ChatMessage`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			INNER JOIN
				`User` Sender ON
					`ChatMessage`.`SenderID` = Sender.`ID`
			INNER JOIN
				`UserContent` ON
					`ChatMessage`.`ContentID` = `UserContent`.`ID`
			LEFT JOIN
				`ChatSubscription` ON
					`ChatMessage`.`ChatID` = `ChatSubscription`.`ChatID` AND
					`ChatSubscription`.`UserID` = thisUser.`ID`
			LEFT JOIN
				`Transaction` ON
					`ChatMessage`.`TransactionID` = `Transaction`.`ID`
			LEFT JOIN
				`ChatSubscription` SubjectChatSubscription ON
					`ChatMessage`.`ChatID` = SubjectChatSubscription.`ChatID` AND
					SubjectChatSubscription.`Role` = '" . CHAT_ROLE_SUBJECT . "' AND
					SubjectChatSubscription.`UserID` != `ChatMessage`.`SenderID`
			WHERE
				`ChatMessage`.`ChatID` = ? " . (
					$getRead == FALSE
						? "
							AND 
							(
								`ChatSubscription`.`SeenMessageID` IS NULL OR
								`ChatMessage`.`DateTime` > IFNULL(
									(
										SELECT `DateTime`
										FROM	`ChatMessage`
										WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
									),
									'" . MYSQL_DATETIME_RANGE_LOWEST . "'
								)
							)
						"
						: FALSE
				) . "
			ORDER BY
				" . $stmtParams_sort['query']['orderByClause'] . "
			LIMIT
				?, ?
		";
		$types = 'ii' . $stmtParams_sort['types'] . 'ii';
		$variables = array_merge(
			[
				$this->User->ID,
				$chatID
			],
			$stmtParams_sort['variables'],
			[
				$offset,
				$quantity
			]
		);
		
		return [
			'query'		=> $query,
			'types'		=> $types,
			'variables'	=> $variables
		];
	}
	
	public function fetchChatMessages(
		$chatID,
		$sortMode = CHAT_MESSAGES_SORT_MODE_DEFAULT,
		$quantity = SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_QUANTITY,
		$offset = 0,
		$includeEvents = TRUE,
		$updateChatSubscription = TRUE,
		$getRead = TRUE
	){
		$stmtParams = $this->_prepareStmtParams_fetchChatMessages(
			$chatID,
			$sortMode,
			$offset,
			$quantity,
			$getRead
		);
		
		$chatMessages = $this->db->qSelect(
			$stmtParams['query'],
			$stmtParams['types'],
			$stmtParams['variables']
		);
		
		if($chatMessages){
			$chatMessages = array_map(
				function($chatMessage){
					$chatMessage['type'] = CHAT_MESSAGE_ENTRY_TYPE_MESSAGE;
					
					list(
						$chatMessage['date'],
						$chatMessage['time']
					) = NXS::timestampSplitDateTime(
						$chatMessage['Timestamp'],
						CHAT_MESSAGES_DATE_FORMAT,
						CHAT_MESSAGES_TIME_FORMAT,
						TIMEZONE_CHAT_MESSAGES
					);
					
					$chatMessage['status'] = false;
					if (
						$chatMessage['isAdmin'] &&
						$this->User->IsMod
					){
						$chatMessage['time'] = false;
						
						if ($chatMessage['Seen'])
							$chatMessage['status'] = 'seen';
					}
					
					return $chatMessage;
				},
				$chatMessages
			);
			
			if($includeEvents || $updateChatSubscription)
				switch($sortMode){
					case CHAT_MESSAGES_SORT_MODE_ID_DESC:
						$lowestID = array_pop((array_slice($chatMessages, -1)))['ID'];
						$highestID = $chatMessages[0]['ID'];
					break;
				}
			
			if($updateChatSubscription)
				$this->updateChatSubscription(
					$chatID,
					NULL,
					$highestID
				);
			
			if($includeEvents){
				$chatMessageEntries = $chatMessages;
				
				if(
					$events = $this->fetchChatEvents(
						$chatID,
						$lowestID,
						$highestID
					)
				)
					$chatMessageEntries = array_merge(
						$chatMessageEntries,
						$events
					);
				
				if(
					$notes =
						$this->User->IsMod
							? $this->fetchChatNotes(
								$chatID,
								$lowestID,
								$highestID
							)
							: FALSE
				)
					$chatMessageEntries = array_merge(
						$chatMessageEntries,
						$notes
					);
				
				switch($sortMode){
					case CHAT_MESSAGES_SORT_MODE_ID_DESC:
						usort(
							$chatMessageEntries,
							function($a, $b){
								return $a['Timestamp'] - $b['Timestamp'];
							}
						);
					break;
				}
				
				return $chatMessageEntries;
			}
			
			return $chatMessages;
		}
		
		return FALSE;
	}
	
	public function changeChatStatus(
		$chatID,
		$statusID,
		$addEvent = TRUE,
		$targetStatusIDs = []
	){
		$query = "
			UPDATE
				`Chat`
			SET
				`StatusID` = " . (
					$targetStatusIDs
						? "
							IF(
								`StatusID` IN (" . rtrim(str_repeat('?, ', count($targetStatusIDs)),', ') . "),
								?,
								`StatusID`
							)
						"
						: '?'
				) . "
			WHERE
				`ID` = ?
		";
		
		$types =
			(
				$targetStatusIDs
					? str_repeat('i', count($targetStatusIDs))
					: FALSE
			) .
			'ii';
		
		if($targetStatusIDs)
			$variables = array_merge(
				$targetStatusIDs,
				[
					$statusID,
					$chatID
				]
			);
		else
			$variables = [
				$statusID,
				$chatID
			];
		
		
		if(
			$this->db->qQuery(
				$query,
				$types,
				$variables
			)
		){
			if($addEvent)
				$this->insertChatEvent(
					$chatID,
					CHAT_EVENT_TYPE_ID_STATUS_CHANGED,
					$this->User->ID,
					NULL,
					$statusID
				);
				
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function fetchChat(
		$chatID,
		$chatMessagesSortMode = CHAT_MESSAGES_SORT_MODE_DEFAULT,
		$chatMessagesPageNumber = 1
	){
		if(
			$chats = $this->db->qSelect(
				"
					SELECT
						`Chat`.`ID`,
						SubjectUser.`ID` SubjectUserID,
						SubjectUser.`Alias` SubjectUserAlias,
						MyChatSubscription.`Role` SubscriptionRole,
						`Chat`.`StatusID`,
						(
							SELECT
								COUNT(DISTINCT `ChatMessage`.`ID`)
							FROM
								`ChatMessage`
							WHERE
								`ChatMessage`.`ChatID` = `Chat`.`ID`
						) messageCount,
						`ChatStatus`.`Title` StatusTitle,
						`ChatStatus`.`ID` StatusID,
						LatestChatNote.`Note` LatestNote,
						IF(
							( # User is assigned 
								MyChatSubscription.`UserID` IS NOT NULL AND
								MyChatSubscription.`Role` = '" . CHAT_ROLE_SUPPORT . "'
							),
							IF(
								(
									MyChatSubscription.`TransactionID` IS NOT NULL AND
									IFNULL(
										(
											SELECT
												MAX(`ChatMessage`.`DateTime`)
											FROM
												`ChatMessage`
											WHERE
												`ChatMessage`.`ChatID` = `Chat`.`ID` AND
												`ChatMessage`.`TransactionID` IS NOT NULL
										),
										'" . MYSQL_DATETIME_RANGE_LOWEST ."'
									) < MyChatSubscription.`Updated`
								),
								MyChatSubscription.`TransactionID`,
								IFNULL(
									(
										SELECT
											`ChatMessage`.`TransactionID`
										FROM
											`ChatMessage`
										WHERE
											`ChatMessage`.`ChatID` = `Chat`.`ID` AND
											`ChatMessage`.`TransactionID` IS NOT NULL
										ORDER BY
											`ChatMessage`.`DateTime` DESC,
											`ChatMessage`.`ID` DESC
										LIMIT 1
									),
									FALSE
								)
							),
							FALSE
						) ActiveTransactionID
					FROM
						`Chat`
					INNER JOIN
						`ChatStatus` ON
							`Chat`.`StatusID` = `ChatStatus`.`ID`
					INNER JOIN
						`User` SubjectUser ON
							`Chat`.`SubjectUserID` = SubjectUser.`ID`
					LEFT JOIN
						`ChatSubscription` MyChatSubscription ON
							`Chat`.`ID` = MyChatSubscription.`ChatID` AND
							MyChatSubscription.`UserID` = ?
					LEFT JOIN
						`ChatNote` LatestChatNote ON
							`Chat`.`ID` = LatestChatNote.`ChatID` AND
							LatestChatNote.`ID` = (
								SELECT
									`ChatNote`.`ID`
								FROM
									`ChatNote`
								WHERE
									`ChatNote`.`ChatID` = `Chat`.`ID`
								ORDER BY
									`ChatNote`.`DateTime` DESC,
									`ChatNote`.`ID` DESC
								LIMIT 1
							)
					WHERE
						`Chat`.`ID` = ?
				",
				'ii',
				array(
					$this->User->ID,
					$chatID
				)
			)
		){
			$chat = $chats[0];
			
			$chat['messages'] = [];
			if( $chat['messageCount'] > 0 ){
				$offset = NXS::getOffset(
					$chat['messageCount'],
					CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT,
					$chatMessagesPageNumber
				);
			
				$messages = $this->fetchChatMessages(
					$chatID,
					$chatMessagesSortMode,
					CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT,
					$offset,
					TRUE,
					TRUE
				);
				
				$chat['messages'] = $messages;
			}
			
			return $chat;
		}
		
		return FALSE;
	}
	
	public function fetchChatNotes(
		$chatID,
		$lowestChatMessageID = FALSE,
		$highestChatMessageID = FALSE
	){
		$whereClause = ["`ChatNote`.`ChatID` = ?"];
		$types = 'i';
		$variables = [$chatID];
		
		if($lowestChatMessageID && $highestChatMessageID){
			$whereClause[] = "
				`ChatNote`.`DateTime` BETWEEN
					IF(
						? = (
							SELECT
								`ChatMessage`.`ID`
							FROM
								`ChatMessage`
							WHERE
								`ChatMessage`.`ChatID` = `ChatNote`.`ChatID`
							ORDER BY
								`ChatMessage`.`DateTime` ASC,
								`ChatMessage`.`ID` ASC
							LIMIT 1
						),
						'" . MYSQL_DATETIME_RANGE_LOWEST . "',
						(
							SELECT
								MIN(CN2.`DateTime`)
							FROM
								`ChatNote` CN2
							WHERE
								CN2.`ChatID` = `ChatNote`.`ChatID` AND
								CN2.`DateTime` >= (
									SELECT
										`ChatMessage`.`DateTime`
									FROM
										`ChatMessage`
									WHERE
										`ChatMessage`.`ID` = ?
								)
						)
					) AND
					IF(
						? = (
							SELECT
								`ChatMessage`.`ID`
							FROM
								`ChatMessage`
							WHERE
								`ChatMessage`.`ChatID` = `ChatNote`.`ChatID`
							ORDER BY
								`ChatMessage`.`DateTime` DESC,
								`ChatMessage`.`ID` DESC
							LIMIT 1
						),
						'" . MYSQL_DATETIME_RANGE_HIGHEST . "',
						(
							SELECT
								MAX(CN3.`DateTime`)
							FROM
								`ChatNote` CN3
							WHERE
								CN3.`ChatID` = `ChatNote`.`ChatID` AND
								CN3.`DateTime` < (
									SELECT
										`ChatMessage`.`DateTime`
									FROM
										`ChatMessage`
									WHERE
										`ChatMessage`.`ID` = ?
								)
						)
					)
			";
			$types .= 'iiii';
			
			$variables = array_merge(
				$variables,
				[
					$lowestChatMessageID,
					$lowestChatMessageID,
					$highestChatMessageID,
					$highestChatMessageID	
				]
			);
		}
		
		$query = "
			SELECT
				`ChatNote`.`ID`,
				Author.`Alias` AuthorAlias,
				UNIX_TIMESTAMP(`ChatNote`.`DateTime`) Timestamp,
				`ChatNote`.`Note`
			FROM
				`ChatNote`
			INNER JOIN
				`User` Author ON
					`ChatNote`.`AuthorID` = Author.`ID`
			WHERE
				" . implode(' AND ', $whereClause) . "
		";
		
		if(
			$chatNotes = $this->db->qSelect(
				$query,
				$types,
				$variables
			)
		){
			array_walk(
				$chatNotes,
				[
					$this,
					'_parseChatNotes'
				]
			);
			
			return $chatNotes;
		}
		
		return FALSE;
	}
	
	private function _parseChatNotes(&$chatNote){
		$chatNote['type'] = CHAT_MESSAGE_ENTRY_TYPE_NOTE;
		
		list(
			$chatNote['date'],
			$chatNote['time']
		) = NXS::timestampSplitDateTime(
			$chatNote['Timestamp'],
			CHAT_MESSAGES_DATE_FORMAT,
			CHAT_MESSAGES_TIME_FORMAT,
			TIMEZONE_CHAT_MESSAGES
		);
		
		return TRUE;
	}
	
	public function updateChatNote(
		$chatID,
		$note = FALSE,
		$checkDifference = TRUE,
		$addEvent = TRUE
	){
		$note =
			$note ?:
			(
				!empty($_POST['note'])
					? htmlspecialchars($_POST['note'])
					: FALSE
			);
			
		
		
		if(
			$note == FALSE ||
			(
				$checkDifference &&
				$note == htmlspecialchars($_POST['initial_note'])
			)
		)
			return TRUE;
			
		return $this->insertChatNote(
			$chatID,
			$note
		);
	}
	
	public function insertChatEvent(
		$chatID,
		$typeID,
		$subjectUserID = NULL,
		$objectUserID = NULL,
		$statusID = NULL
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`ChatEvent` (
						`ChatID`,
						`TypeID`,
						`SubjectUserID`,
						`ObjectUserID`,
						`StatusID`,
						`DateInserted`
					)
				VALUES
					(
						?,
						?,
						?,
						?,
						?,
						NOW() + INTERVAL 1 SECOND
					)
			",
			'iiiii',
			[
				$chatID,
				$typeID,
				$subjectUserID,
				$objectUserID,
				$statusID
			]
		);
	}
	
	public function fetchChatEvents(
		$chatID,
		$lowestChatMessageID = FALSE,
		$highestChatMessageID = FALSE
	){
		$whereClause = [
			"`ChatEvent`.`ChatID` = ?"
		];
		$types = 'i';
		$variables = [$chatID];
		
		if($lowestChatMessageID && $highestChatMessageID){
			$whereClause[] = "
				`ChatEvent`.`DateInserted` BETWEEN
					IF(
						? = (
							SELECT
								`ChatMessage`.`ID`
							FROM
								`ChatMessage`
							WHERE
								`ChatMessage`.`ChatID` = `ChatEvent`.`ChatID`
							ORDER BY
								`ChatMessage`.`DateTime` ASC,
								`ChatMessage`.`ID` ASC
							LIMIT 1
						),
						'" . MYSQL_DATETIME_RANGE_LOWEST . "',
						(
							SELECT
								MIN(CE2.`DateInserted`)
							FROM
								`ChatEvent` CE2
							WHERE
								CE2.`ChatID` = `ChatEvent`.`ChatID` AND
								CE2.`DateInserted` >= (
									SELECT
										`ChatMessage`.`DateTime`
									FROM
										`ChatMessage`
									WHERE
										`ChatMessage`.`ID` = ?
								)
						)
					) AND
					IF(
						? = (
							SELECT
								`ChatMessage`.`ID`
							FROM
								`ChatMessage`
							WHERE
								`ChatMessage`.`ChatID` = `ChatEvent`.`ChatID`
							ORDER BY
								`ChatMessage`.`DateTime` DESC,
								`ChatMessage`.`ID` DESC
							LIMIT 1
						),
						'" . MYSQL_DATETIME_RANGE_HIGHEST . "',
						(
							SELECT
								MAX(CE3.`DateInserted`)
							FROM
								`ChatEvent` CE3
							WHERE
								CE3.`ChatID` = `ChatEvent`.`ChatID` AND
								CE3.`DateInserted` < (
									SELECT
										`ChatMessage`.`DateTime`
									FROM
										`ChatMessage`
									WHERE
										`ChatMessage`.`ID` = ?
								)
						)
					)
			";
			
			$types .= 'iiii';
			
			$variables = array_merge(
				$variables,
				[
					$lowestChatMessageID,
					$lowestChatMessageID,
					$highestChatMessageID,
					$highestChatMessageID
				]
			);
		}
		
		$query = "
			SELECT
				`ChatEvent`.`ID`,
				`ChatEvent`.`TypeID`,
				`ChatEventType`.`Remark`,
				SubjectUser.`Alias` SubjectUserAlias,
				`ChatStatus`.`Title` StatusTitle,
				UNIX_TIMESTAMP(`ChatEvent`.`DateInserted`) Timestamp
			FROM
				`ChatEvent`
			INNER JOIN
				`ChatEventType` ON
					`ChatEvent`.`TypeID` = `ChatEventType`.`ID`
			LEFT JOIN
				`User` SubjectUser ON
					`ChatEvent`.`SubjectUserID` = SubjectUser.`ID`
			LEFT JOIN
				`ChatStatus` ON
					`ChatEvent`.`StatusID` = `ChatStatus`.`ID`
			WHERE
				" . implode(' AND ', $whereClause)  . "	
		";
		
		if(
			$chatEvents = $this->db->qSelect(
				$query,
				$types,
				$variables
			)
		){
			array_walk(
				$chatEvents,
				[
					$this,
					'_parseChatEvents'
				]
			);
			
			return $chatEvents;
		}
		
		return FALSE;
	}
	
	private function _parseChatEvents(&$chatEvent){
		$chatEvent['type'] = CHAT_MESSAGE_ENTRY_TYPE_EVENT;
		$chatEvent['text'] = $chatEvent['Remark'];
		
		list(
			$chatEvent['date'],
			$chatEvent['time']
		) = NXS::timestampSplitDateTime(
			$chatEvent['Timestamp'],
			CHAT_MESSAGES_DATE_FORMAT,
			CHAT_MESSAGES_TIME_FORMAT,
			TIMEZONE_CHAT_MESSAGES
		);
		
		if($chatEvent['SubjectUserAlias'])
			$chatEvent['text'] = str_replace('$1', $chatEvent['SubjectUserAlias'], $chatEvent['text']);
			
		if($chatEvent['StatusTitle'])
			$chatEvent['text'] = str_replace('$2', $chatEvent['StatusTitle'], $chatEvent['text']);
			
		return TRUE;
	}
	
	public function findChatMessage($chatMessageID){
		if(
			$chatMessages = $this->db->qSelect(
				"
					SELECT
						`ChatMessage`.`ChatID`,
						`User`.`Alias` SubjectUserAlias
					FROM
						`ChatMessage`
					INNER JOIN
						`Chat` ON
							`ChatMessage`.`ChatID` = `Chat`.`ID`
					LEFT JOIN
						`User` ON
							`Chat`.`SubjectUserID` = `User`.`ID`
					WHERE
						`ChatMessage`.`ID` = ?
				",
				'i',
				[$chatMessageID]
			)
		)
			return $chatMessages[0];
			
		return FALSE;
	}
	
	public function getChatID($subjectUserAlias = FALSE){
		$subjectUserAlias = $subjectUserAlias ?: $this->User->Alias;
		
		if(
			$chatIDs = $this->db->qSelect(
				"
					SELECT
						`Chat`.`ID`
					FROM
						`Chat`
					WHERE
						`Chat`.`SubjectUserID` = (
							SELECT
								`User`.`ID`
							FROM
								`User`
							WHERE
								`User`.`Alias` = ?
						)
						
				",
				's',
				array(
					$subjectUserAlias
				)
			)
		)
			return $chatIDs[0]['ID'];
			
		return FALSE;
	}
	
	public function getChatSubjectAlias($chatID){
		if(
			$chatSubjectAliases = $this->db->qSelect(
				"
					SELECT
						`User`.`Alias`
					FROM
						`Chat`
					INNER JOIN
						`User` ON
							`Chat`.`SubjectUserID` = `User`.`ID`
					WHERE
						`Chat`.`ID` = ?
				",
				'i',
				array(
					$chatID
				)
			)
		)
			return $chatSubjectAliases[0]['Alias'];
			
		return FALSE;
	}
	
	public function createChat(
		$statusID,
		$subscriptionRole = FALSE,
		$subjectUserAlias = FALSE,
		$firstMessageRaw = FALSE,
		$firstNote = FALSE,
		$firstMessageColor = FALSE
	){
		$subjectUserAlias = $subjectUserAlias ?: $_POST['subject_alias'];
		$firstNote =
			$firstNote ?:
			(
				$this->User->IsMod && !empty($_POST['note'])
					? htmlspecialchars($_POST['note'])
					: FALSE
			);
		
		$chatID = $this->insertChat(
			$statusID,
			$subjectUserAlias
		);
		if($chatID){
			$chatMessageID = $this->createChatMessage(
				$chatID,
				$firstMessageColor,
				$firstMessageRaw
			);
			
			if($firstNote)
				$this->insertChatNote(
					$chatID,
					$firstNote
				);
			
			if(
				$subjectUserAlias &&
				strtolower($subjectUserAlias) !== strtolower($this->User->Alias)
			){
				$userID = $this->getUserID($subjectUserAlias);
				$this->insertChatSubscription(
					$chatID,
					CHAT_ROLE_SUBJECT,
					$userID,
					0
				);
			}
				
			if($subscriptionRole)
				$this->insertChatSubscription(
					$chatID,
					$subscriptionRole,
					$this->User->ID,
					0
				);
				
			return $chatID;
		}
		
		return FALSE;
	}
	
	public function getUserID($userAlias){
		if(
			$user = $this->db->qSelect(
				"
					SELECT
						`User`.`ID`
					FROM
						`User`
					WHERE
						`User`.`Alias` = ?
				",
				's',
				[
					$userAlias
				]
			)
		)
			return $user[0]['ID'];
			
		return FALSE;
	}
	
	private function _getChatSubscription(
		$chatID,
		$userID = FALSE
	){
		$userID = $userID ?: $this->User->ID;
		
		if(
			$chatSubscriptions = $this->db->qSelect(
				"
					SELECT
						`Role`,
						`SeenMessageID`
					FROM
						`ChatSubscription`
					WHERE
						`UserID` = ? AND
						`ChatID` = ?
				",
				'ii',
				[
					$userID,
					$chatID
				]
			)
		){
			return $chatSubscriptions[0];
		}
		
		return FALSE;
	}
	
	public function insertChatSubscription(
		$chatID,
		$role,
		$userID = FALSE,
		$seenMessageID = NULL
	){
		$userID = $userID ?: $this->User->ID;
		
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`ChatSubscription` (
						`UserID`,
						`ChatID`,
						`Role`,
						`SeenMessageID`
					)
				VALUES
					(
						?,
						?,
						?,
						IFNULL(
							?,
							(
								SELECT
									`ID`
								FROM
									`ChatMessage`
								ORDER BY
									`DateTime` DESC,
									`ID` DESC
								LIMIT	1
							)
						)
					)
			",
			'iisi',
			[
				$userID,
				$chatID,
				$role,
				$seenMessageID
			]
		);
	}
	
	private function _validateTransactionID($transactionID){
		return	$transactionID &&
			is_numeric($transactionID) &&
			$transactionID > 0;
	}
	
	public function setChatSubscriptionTransactionID(
		$chatID,
		$transactionID = FALSE,
		$userID = FALSE
	){
		$userID = $userID ?: $this->User->ID;
		$transactionID =
			$transactionID
				?: (
					!empty($_POST['transaction_id_specify'])
						? $_POST['transaction_id_specify']
						: $_POST['transaction_id_select']
				);
				
		if ($this->_validateTransactionID($transactionID)){
			return $this->db->qQuery(
				"
					UPDATE
						`ChatSubscription`
					SET
						`TransactionID` = ?,
						`Updated` = NOW()
						
					WHERE
						`UserID` = ? AND
						`ChatID` = ?
				",
				'iii',
				[
					$transactionID,
					$userID,
					$chatID
				]
			);
		}
		
		return FALSE;
	}
	
	public function updateChatSubscription(
		$chatID,
		$chatSubscriptionRole = NULL,
		$seenMessageID = NULL,
		$userID = FALSE,
		$updateTXID = TRUE
	){
		$userID = $userID ?: $this->User->ID;
		
		return 	$this->db->qQuery(
				"
					UPDATE
						`ChatSubscription`
					SET
						`Role` = IFNULL(
							?,
							`Role`
						), " . (
							$updateTXID
								? "
									`TransactionID` = IF(
										IFNULL(
											(
												SELECT	`DateTime`
												FROM	`ChatMessage`
												WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
											),
											'" . MYSQL_DATETIME_RANGE_LOWEST . "'
										) < (
											SELECT
												MAX(`ChatMessage`.`DateTime`)
											FROM
												`ChatMessage`
											WHERE
												`ChatMessage`.`ChatID` = `ChatSubscription`.`ChatID` AND
												`ChatMessage`.`TransactionID` IS NOT NULL
										),
										NULL,
										`TransactionID`
									),
								"
								: FALSE
						) . "
						`SeenMessageID` = IF(
							? IS NOT NULL AND
							(
								SELECT	`DateTime`
								FROM	`ChatMessage`
								WHERE	`ID` = ?
							) >
							IFNULL(
								(
									SELECT	`DateTime`
									FROM	`ChatMessage`
									WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
								),
								'" . MYSQL_DATETIME_RANGE_LOWEST . "'
							),
							?,
							`SeenMessageID`
						)
					WHERE
						`ChatID` = ? AND
						`UserID` = ?
				",
				'siiiii',
				[
					$chatSubscriptionRole,
					$seenMessageID,
					$seenMessageID,
					$seenMessageID,
					$chatID,
					$userID
				]
			);
	}
	
	public function deleteChatSubscription(
		$chatID,
		$userID = FALSE
	){
		$userID = $userID ?: $this->User->ID;
		
		return	$this->db->qQuery(
				"
					DELETE FROM
						`ChatSubscription`
					WHERE
						`ChatID` = ? AND
						`UserID` = ?
				",
				'ii',
				[
					$chatID,
					$userID
				]
			);
	}
	
	public function toggleChatSubscription(
		$chatID,
		$chatSubscriptionRole = FALSE,
		$userID = FALSE,
		$deleteSubscriptions = TRUE,
		$firstSeenMessageID = NULL
	){
		$userID = $userID ?: $this->User->ID;
		
		$chatSubscription = $this->_getChatSubscription($chatID, $userID);
		if ($chatSubscription){
			if(
				$chatSubscriptionRole &&
				$chatSubscription['Role'] !== $chatSubscriptionRole
			)
				return	$this->updateChatSubscription(
						$chatID,
						$chatSubscriptionRole,
						NULL,
						$userID
					);
			
			return	$deleteSubscriptions
					? $this->deleteChatSubscription($chatID, $userID)
					: FALSE;
		}
		
		return	$this->insertChatSubscription(
				$chatID,
				$chatSubscriptionRole,
				$userID,
				$firstSeenMessageID
			);
	}
	
	public function insertChat(
		$statusID,
		$subjectUserAlias
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`Chat` (`StatusID`, `SubjectUserID`)
				VALUES
					(
						?,
						(
							SELECT
								`User`.`ID`
							FROM
								`User`
							WHERE
								`Alias` = ?
						)
					)
			",
			'is',
			[
				$statusID,
				$subjectUserAlias
			]
		);
	}
	
	public function createChatMessage(
		$chatID,
		$color = NULL,
		$messageRaw = FALSE,
		$contentID = FALSE,
		$transactionID = NULL,
		$replaceChatStatus = TRUE,
		$targetChatStatusIDs = [CHAT_STATUS_ID_CLOSED],
		$replacementChatStatusID = CHAT_STATUS_ID_OPEN
	){
		if($contentID == FALSE){
			$messageRaw = $messageRaw ?: $_POST['message'];
			
			if( empty($messageRaw) )
				return FALSE;
			
			$messageFormatted = NXS::formatText($messageRaw);
		
			$contentID = $this->insertChatMessageContent(
				$messageRaw,
				$messageFormatted
			);
		}
		
		if($replaceChatStatus)
			$this->changeChatStatus(
				$chatID,
				$replacementChatStatusID,
				TRUE,
				$targetChatStatusIDs
			);
		
		$chatMessageID = $this->insertChatMessage(
			$chatID,
			isset($_POST['sender']) ? $this->getUserID($_POST['sender']) : $this->User->ID,
			$contentID
		);
		$this->editChatMessage(
			$chatMessageID,
			$color,
			$transactionID
		);
		
		return $chatMessageID;
	}
	
	private function getChatStatusID($chatID){
		if(
			$chats = $this->db->qSelect(
				"
					SELECT
						`StatusID`
					FROM
						`Chat`
					WHERE
						`ID` = ?
				",
				'i',
				[
					$chatID
				]
			)
		)
			return $chats[0]['StatusID'];
		
		return FALSE;
	}
	
	public function insertChatMessageContent(
		$raw,
		$formatted
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`UserContent` (
						`Raw`,
						`Formatted`
					)
				VALUES
					(?, ?)
			",
			'ss',
			[
				$raw,
				$formatted
			]
		);
	}
	
	public function editChatMessageContent(
		$chatMessageID,
		$raw = FALSE,
		$formatted = FALSE
	){
		$raw = $raw ?: $_POST['content'];
		$formatted = $formatted ?: NXS::formatText($raw);
		
		return $this->db->qQuery(
			"
				UPDATE
					`ChatMessage`
				INNER JOIN
					`UserContent` ON
						`ChatMessage`.`ContentID` = `UserContent`.`ID`
				SET
					`UserContent`.`Raw` = ?,
					`UserContent`.`Formatted` = ?
				WHERE
					`ChatMessage`.`ID` = ?
			",
			'ssi',
			[
				$raw,
				$formatted,
				$chatMessageID
			]
		);
	}
	
	public function insertChatMessage(
		$chatID,
		$senderID,
		$contentID,
		$transactionID = NULL
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`ChatMessage` (
						`ChatID`,
						`SenderID`,
						`ContentID`,
						`TransactionID`,
						`DateTime`
					)
				VALUES
					(
						?,
						?,
						?,
						?,
						NOW()
					)
			",
			'iiii',
			[
				$chatID,
				$senderID,
				$contentID,
				$transactionID
			]
		);
	}
	
	public function editChatMessage(
		$chatMessageID,
		$color = NULL,
		$transactionID = NULL,
		$senderID = NULL,
		$contentID = NULL
	){
		return $this->db->qQuery(
			"
				UPDATE
					`ChatMessage`
				SET
					`SenderID` = IFNULL(?, `SenderID`),
					`TransactionID` = IFNULL(?, `TransactionID`),
					`ContentID` = IFNULL(?, `ContentID`),
					`Color` = IFNULL(?, `Color`)
				WHERE
					`ID` = ?
			",
			'iiisi',
			[
				$senderID,
				$transactionID,
				$contentID,
				$color,
				$chatMessageID
			]
		);
	}
	
	public function deleteChatMessage($chatMessageID){
		return $this->db->qQuery(
			"
				DELETE FROM
					`ChatMessage`
				WHERE
					`ChatMessage`.`ID` = ?
			",
			'i',
			[$chatMessageID]
		);
	}
	
	public function insertChatNote(
		$chatID,
		$note,
		$authorID = FALSE
	){
		if(!$note)
			return TRUE;
		
		$authorID = $authorID ?: $this->User->ID;
		
		return $this->db->qQuery(
			"
				INSERT INTO
					`ChatNote` (
						`ChatID`,
						`AuthorID`,
						`Note`,
						`DateTime`
					)
				VALUES	(
						?,
						?,
						?,
						NOW() + INTERVAL 2 SECOND
					)
			",
			'iis',
			[
				$chatID,
				$authorID,
				$note
			]
		);
	}
	
	public function fetchChatStatuses(&$nestedChatStatuses = FALSE){
		$chatStatuses = $this->db->qSelect(
			"
				SELECT
					`ID`,
					`Title`,
					`Icon`,
					`Color`,
					IFNULL(`ParentID`, 0) ParentID
				FROM
					`ChatStatus`
			"
		);
		
		if($nestedChatStatuses !== FALSE){
			foreach($chatStatuses as $chatStatus){
				$nestedChatStatuses[ $chatStatus['ID'] ] = $chatStatus;
			}
			$nestedChatStatuses = NXS::makeRecursive(
				$nestedChatStatuses
			);
		}
		
		return $chatStatuses;
	}
	
	public function updateChats(){
		if( !empty($_POST['chat_ids']) ){
			$chats = $this->fetchChats(
				SUPPORT_OVERVIEW_FILTER_MODE_ALL,
				SUPPORT_OVERVIEW_DEFAULT_SORT_MODE,
				NULL,
				0,
				$_POST['chat_ids']
			);
		
			foreach($chats as $chat){
				$chatID = $chat['ID'];
			
				$statusID =
					!empty($_POST['chat_status_id-' . $chatID]) 
						? $_POST['chat_status_id-' . $chatID]
						: FALSE;
				$assigned =
					isset($_POST['assigned-' . $chatID])
						? TRUE
						: FALSE;
				$note = 
					!empty($_POST['chat_note-' . $chatID])
						? htmlspecialchars($_POST['chat_note-' . $chatID])
						: FALSE;
				$newMessageRaw =
					!empty($_POST['chat_message-' . $chatID])
						? $_POST['chat_message-' . $chatID]
						: FALSE;
				$latestMessages =
					!empty($_POST['chat_messages-' . $chatID])
						? $_POST['chat_messages-' . $chatID]
						: FALSE;
				
				if($statusID !== $chat['StatusID'])
					$this->changeChatStatus(
						$chatID,
						$statusID
					);
				
				if( $assigned != $chat['Assigned'] )
					$this->toggleChatSubscription(
						$chatID,
						CHAT_ROLE_SUPPORT
					);
				
				if( $note && $note !== $chat['LatestNote'] )
					$this->insertChatNote(
						$chatID,
						$note
					);
					
				if(
					!empty($_POST['redirect-' . $chatID]) &&
					$newMessageRaw
				){
					$this->createChatMessage(
						$chatID,
						CHAT_ROLE_COLOR_SUPPORT,
						$newMessageRaw,
						FALSE,
						NULL,
						TRUE,
						[
							CHAT_STATUS_ID_OPEN,
							CHAT_STATUS_ID_CLOSED
						],
						CHAT_STATUS_ID_ONGOING
					);
					
					$_POST['redirect'] = $_POST['redirect-' . $chatID];
				}
			}
			
			if( !empty($_POST['redirect']) )
				return $_POST['redirect'];
		}
		
		return FALSE;
	}
	
	public function getTransactionDetails($transactionID){
		if(
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`ID`,
						IFNULL(
							`Transaction`.`Identifier`,
							`Transaction`.`ID`
						) Identifier,
						CONCAT(
							CASE
								WHEN `Transaction`.`Status` = 'in transit' THEN
									CONCAT(
										`Transaction`.`Status`,
										' (',
										IF(
											`Transaction`.`Shipped` = TRUE,
											'Shipped',
											'Accepted'
										),
										')'
									)
								WHEN
									(
										`Transaction`.`Status` IN ('rejected', 'refunded') AND
										`Transaction`.`Paid` = FALSE
									) THEN
									CONCAT(
										`Transaction`.`Status`,
										' (Failed Payment)'
									)
								WHEN
									(
										`Transaction`.`Status` = 'pending deposit' AND
										`Transaction`.`Paid`
									) THEN
									'Pending Payment Confirmation'
								ELSE
									`Transaction`.`Status`
							END,
							IF (
								`Transaction`.`Status` IN ('rejected', 'refunded', 'pending feedback'),
								CONCAT(
									' (',
									IF (
										`Transaction`.`Withdrawn`,
										'Withdrawn',
										'Unwithdrawn'
									),
									')'
								),
								''
							)
						) Status,
						`Listing`.`ID` ListingID,
						`Listing`.`Name` ListingName,
						Vendor.`Alias` VendorAlias,
						`PaymentMethod`.`ExtendedPublicKey` VendorXPUB,
						Buyer.`Alias` BuyerAlias,
						`Transaction`.`NextTX_Site`,
						`Transaction`.`MultiSigAddress`,
						`Transaction`.`RefundAddress` buyerAddress,
						`Transaction`.`AddressKey`,
						`Transaction`.`RedeemScript`,
						`Transaction`.`Escrow`,
						`Transaction`.`Value`,
						`Transaction`.`Timeout`,
						FLOOR(TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60) MinutesRemaining,
						`PaymentMethod`.`CryptocurrencyID`
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					WHERE
						`Transaction`.`ID` = ?
				",
				'i',
				[
					$transactionID
				]
			)
		){
			$transaction = $transactions[0];
			$cryptocurrency = $this->User->getCryptocurrency($transaction['CryptocurrencyID']);
			
			$RSA = new RSA(SITE_RSA_PRIVATE_KEY);
			
			$transaction['Value'] .= ' <strong>' . $cryptocurrency->ISO . '</strong>';
			
			switch ($cryptocurrency->ID){
				case CURRENCY_ID_BTC:
					$blockExplorerPrefix = SUPPORT_TRANSACTION_PANEL_BLOCK_EXPLORER_URL_PREFIX_ADDRESS;
					$blockExplorerSuffix = SUPPORT_TRANSACTION_PANEL_BLOCK_EXPLORER_SUFFIX;
					break;
				case CURRENCY_ID_LTC:
					$blockExplorerPrefix = SUPPORT_TRANSACTION_PANEL_LTC_BLOCK_EXPLORER_URL_PREFIX_ADDRESS;
					$blockExplorerSuffix = '';
			}
			$transaction['blockExplorerPrefix'] = $blockExplorerPrefix;
			$transaction['blockExplorerSuffix'] = $blockExplorerSuffix;
					
			if($transaction['NextTX_Site']){
				$nextTX = json_decode(
					$RSA->qDecrypt($transaction['NextTX_Site']),
					TRUE
				);
				
				$transaction['publicKeys'] = [
					'vendor'	=> $nextTX['PublicKey_Vendor'],
					'marketplace'	=> $nextTX['PublicKey_Marketplace'],
					'buyer'		=>
						isset($nextTX['PublicKey_Buyer'])
							? $nextTX['PublicKey_Buyer']
							: FALSE
				];
			} else 
				$transaction['publicKeys'] = $transaction['buyerAddress'] = FALSE;
			
			$transaction['vendorAddress'] = 
				$transaction['AddressKey'] !== NULL
					? NXS::getBIP32Address(
						$transaction['AddressKey'],
						$transaction['VendorXPUB'],
						$cryptocurrency->prefixPublic
					)
					: false;
			
			if($transaction['MinutesRemaining'] > 0)
				$transaction['Timeout'] .=
					' (' .
					NXS::parseMinutes($transaction['MinutesRemaining']) .
					')';
			
			return $transaction;
		}
		
		return FALSE;
	}
	
	private function _countConversations(){
		if (
			$conversationCounts = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `Conversation`.`ID`) conversationCount
					FROM
						`Conversation`
					INNER JOIN
						`Conversation_User` ON
							`Conversation`.`ID` = `Conversation_User`.`ConversationID`
					WHERE
						`Conversation_User`.`UserID` = ? AND
						`Conversation_User`.`Deleted` = FALSE
				",
				'i',
				[$this->User->ID]
			)
		)
			return $conversationCounts[0]['conversationCount'];
		
		return false;
	}
	
	public function fetchConversations(
		$pageNumber,
		$perPage = CONVERSATIONS_PER_PAGE
	){
		if ($conversationCount = $this->_countConversations()){
			$offset = NXS::getOffset(
				$conversationCount,
				$perPage,
				$pageNumber
			);
			return [
				$conversationCount,
				array_map(
					function($row){
						return array_merge(
							$row,
							[
								'icon'	=> $row['hasImportant'] ? Icon::getClass('STAR', true) : false
							]
						);
					},
					$this->db->qSelect(
						"
							SELECT
								Conversation_Correspondent.`UserID` userID,
								Correspondent.`Alias` userAlias,
								Conversation_thisUser.`Unread` hasUnread,
								Correspondent.`Vendor` isVendor,
								Correspondent.`Admin` isAdmin,
								Correspondent.`Moderator` isModerator,
								Conversation_thisUser.`Important` hasImportant,
								IF(
									Conversation_thisUser.`Unread`,
									(
										SELECT
											`ID`
										FROM
											`Message`
										WHERE
											`SenderID` = Correspondent.`ID` AND
											`RecipientID` = Conversation_thisUser.`UserID` AND
											`Read` = FALSE
										ORDER BY
											`Sent` ASC
										LIMIT
											1
									),
									NULL
								) earliestUnreadMessageID
							FROM
								`Conversation`
							INNER JOIN
								`Conversation_User` Conversation_thisUser ON
									`Conversation`.`ID` = Conversation_thisUser.`ConversationID` AND
									Conversation_thisUser.`UserID` = ?
							INNER JOIN
								`Conversation_User` Conversation_Correspondent ON
									`Conversation`.`ID` = Conversation_Correspondent.`ConversationID` AND
									Conversation_Correspondent.`UserID` != Conversation_thisUser.`UserID`
							INNER JOIN
								`User` Correspondent ON
									Conversation_Correspondent.`UserID` = Correspondent.`ID`
							WHERE
								Conversation_thisUser.`Deleted` = FALSE
							ORDER BY
								`Conversation`.`DateTime` DESC,
								`Conversation`.`LatestMessageID` DESC
							LIMIT
								?, ?
						",
						'iii',
						[
							$this->User->ID,
							$offset,
							$perPage
						]
					)
				)
			];
		}
		
		return false;
	}
	
	public function _getPrimaryLocaleCountry($localeID){
		if(
			$countries = $this->db->qSelect(
				"
					SELECT
						`CountryID`
					FROM
						`Locale_Country`
					WHERE
						`LocaleID` = ?
					LIMIT
						1
				",
				'i',
				[$localeID]
			)
		)
			return $countries[0]['CountryID'];
		
		return false;
	}
	
	public function fetchConversation(
		$alias,
		$page,
		$onlyImportant = false
	){
		if($alias == $this->User->Alias)
			return false;
		
		$stmt_countMessages = $this->db->prepare("
			SELECT
				COUNT(`Message`.`ID`),
				CASE
					WHEN `User`.`Admin` = TRUE
						THEN 'admin'
					WHEN `User`.`Moderator`	= TRUE
						THEN 'moderator'
					WHEN `User`.`Vendor`	= TRUE
						THEN 'vendor'
					WHEN
						thisUser.`Vendor` = TRUE AND
						(
							SELECT
								`Transaction`.`ID`
							FROM
								`Transaction`
							INNER JOIN
								`PaymentMethod` ON
									`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
							WHERE
								`PaymentMethod`.`UserID` = thisUser.`ID` AND
								`Transaction`.`BuyerID` = `User`.`ID` AND
								`Transaction`.`Paid` = TRUE
							LIMIT
								1
						) IS NOT NULL
							THEN 'customer'
					ELSE 'buyer'
				END
			FROM
				`Message`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			INNER JOIN
				`User` ON
					(
						`Message`.`SenderID` = thisUser.`ID` AND	
						`Message`.`RecipientID` = `User`.`ID`
					) OR
					(
						`Message`.`RecipientID` = thisUser.`ID` AND
						`Message`.`SenderID` = `User`.`ID`
					)
			WHERE
				`User`.`Alias` = ? AND
				(
					(
						thisUser.`ID` = `Message`.`RecipientID` AND
						`Message`.`ContentID` IS NOT NULL
					) OR
					(
						thisUser.`ID` = `Message`.`SenderID` AND
						`Message`.`ContentID_Sender` IS NOT NULL
					)
				) " . (
					$onlyImportant
						? "
							AND `Message`.`RecipientID` = thisUser.`ID`
							AND `Message`.`Important` = TRUE
						" 
						: false
				) . "
		");
		
		$stmt_getMessages = $this->db->prepare("
			SELECT
				otherUser.`Alias`,
				`Message`.`ID`,
				IF(
					`Message`.`SenderID` = thisUser.`ID`,
					TRUE,
					FALSE
				),
				`Message`.`Read`,
				(
					thisUser.`EncryptMessages` OR
					otherUser.`EncryptMessages`
				) Encrypted,
				TRUE,
				`UserContent`.`Formatted`,
				IF(
					`Message`.`Sent` IS NULL,
					FALSE,
					UNIX_TIMESTAMP(`Message`.`Sent`)
				),
				(
					`Message`.`RecipientID` = thisUser.`ID` AND	
					`Important`
				)
			FROM
				`Message`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			INNER JOIN
				`User` otherUser ON
					(
						`Message`.`SenderID` = thisUser.`ID` AND
						`Message`.`RecipientID` = otherUser.`ID`
					) OR
					(
						`Message`.`RecipientID` = thisUser.`ID` AND
						`Message`.`SenderID` = otherUser.`ID`
					)
			INNER JOIN
				`UserContent` ON
					(
						`Message`.`RecipientID` = thisUser.`ID` AND	
						`Message`.`ContentID` = `UserContent`.`ID`
					) OR
					(
						`Message`.`SenderID` = thisUser.`ID` AND
						`Message`.`ContentID_Sender` = `UserContent`.`ID`
					)
			WHERE
				otherUser.`Alias` = ? " . (
					$onlyImportant
						? "
							AND `Message`.`RecipientID` = thisUser.`ID`
							AND `Message`.`Important` = TRUE
						" 
						: false
				) . "
			ORDER BY
				`Message`.`Sent` DESC,
				`Message`.`ID` DESC
			LIMIT	?, " . MESSAGES_PER_PAGE . "
		");
		
		if( $stmt_countMessages !== false && $stmt_getMessages !== false ){
			$stmt_countMessages->bind_param(
				'is',
				$this->User->ID,
				$alias
			);
			$stmt_countMessages->execute();
			$stmt_countMessages->store_result();
			$stmt_countMessages->bind_result(
				$message_count,
				$userRole
			);
			$stmt_countMessages->fetch();
			
			if( $message_count > 0 ){
				if (ceil($message_count/MESSAGES_PER_PAGE) < $page){
					$offset = 0;
				} else {
					$offset = MESSAGES_PER_PAGE * ($page - 1);
				}
				
				$stmt_getMessages->bind_param(
					'isi',
					$this->User->ID,
					$alias,
					$offset
				);
				$stmt_getMessages->execute();
				$stmt_getMessages->store_result();
				
				if( $stmt_getMessages->num_rows > 0 ){
					$stmt_getMessages->bind_result(
						$alias,
						$message_id,
						$is_sender,
						$read,
						$shouldBeEncrypted,
						$isNotEncrypted,
						$content,
						$timestamp,
						$isImportant
					);
					
					$messages = $messageIDs = array();
					$hasUnread = FALSE;
					
					$timeFormat = ($userRole == 'admin' ? MESSAGES_TIME_FORMAT_ADMIN : MESSAGES_TIME_FORMAT);
					
					while( $stmt_getMessages->fetch() ){
						if($isNotEncrypted){
							$content = [
								'Message' => $content
							];
							
							if( $timestamp )
								$content['Date'] = NXS::timestampTimezoneFormat(
									$timestamp,
									$timeFormat,
									TIMEZONE_MESSAGES
								);
						} else {
							$rsa = new RSA();
							
							$content = $rsa->qDecrypt($content);
							if( $message = json_decode($content, true) ){
								$content = $message;
							
								$messageHTML = $content['Message'];
							
								if( isset($content['Timestamp']) )
									$content['Date'] = NXS::timestampTimezoneFormat(
										$content['Timestamp'],
										$timeFormat,
										TIMEZONE_MESSAGES
									);
							} else {
								$messageHTML = NXS::formatText($content);
								
								$content = array(
									'Message' => $messageHTML,
									'Date' => FALSE
								);
							}
							
							if($shouldBeEncrypted == FALSE)
								$this->User->insertPlaintextMessage(
									$message_id,
									$messageHTML
								);
						}
						
						$new =
							$read == FALSE &&
							$is_sender == FALSE;
						
						$messages[] = array(
							'id' => $message_id,
							'is_sender' => $is_sender,
							'content' => $content,
							'new' => $new,
							'important' => $isImportant
						);
						$hasUnread = $new ?: $hasUnread;
						if($new)
							$newMessageIDs[] = $message_id;
					}
					
					if (
						$hasUnread &&
						$messagesRead = $this->db->qQuery(
							"
								UPDATE
									`Message`
								SET
									`Read` = TRUE
								WHERE
									`ID` IN (" .
										rtrim(
											str_repeat(
												'?, ',
												count($newMessageIDs)
											),
											', '
										) .
									")
							",
							str_repeat('i', count($newMessageIDs)),
							$newMessageIDs
						)
					){
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
							$messagesRead*-1
						);
						$this->User->refreshConversation(
							$this->User->ID,
							$this->getUserID($alias)
						);
					}
					
				} else
					$messages = false;
			} else {
				$messages = false;
				
				if (!$onlyImportant)
					$_SESSION['temp_notifications'][] = array(
						'Content' => 'Message does not exist or has been deleted',
						'Anchor' => false,
						'Dismiss' => '.',
						'Design' => array(
							'Color' => 'yellow',
							'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_TRIANGLE')
						)
					);
			}
		}
		
		return [$message_count, $alias, $userRole, $messages];
	}
	
	public function toggleMessageImportant($messageID){
		return
			$this->db->qQuery(
				"
					UPDATE
						`Message`
					SET
						`Important` = (`Important` = 0)
					WHERE
						`ID` = ? AND
						`RecipientID` = ?
				",
				'ii',
				[
					$messageID,
					$this->User->ID
				]
			) &&
			$this->User->refreshConversation($messageID);
	}
	
	public function getUserPGP($userAlias){
		if(
			$users = $this->db->qSelect(
				"
					SELECT
						`PGP`
					FROM
						`User`
					WHERE
						`Alias` = ?
				",
				's',
				[
					$userAlias
				]
			)
		)
			return $users[0]['PGP'];
		
		return FALSE;
	}
	
	public function findConversationPage($userAlias){
		if(
			(
				$pages = $this->db->qSelect(
					"
						SELECT
							CEIL(
								COUNT(DISTINCT correspondentUser.`ID`) /
								" . CONVERSATIONS_PER_PAGE . "
							) page
						FROM
							`Message`
						INNER JOIN
							`User` thisUser ON
								thisUser.`ID` = ?
						INNER JOIN
							`User` targetUser ON
								targetUser.`Alias` = ?
						INNER JOIN
							`User` correspondentUser ON
								(
									`Message`.`SenderID` = correspondentUser.`ID` OR
									`Message`.`RecipientID` = correspondentUser.`ID`
								) AND
								correspondentUser.`ID` != thisUser.`ID`
						WHERE
							(
								(
									`Message`.`RecipientID` = thisUser.`ID` AND
									`Message`.`ContentID` IS NOT NULL
								) OR
								(
									`Message`.`SenderID` = thisUser.`ID` AND
									`Message`.`ContentID_Sender` IS NOT NULL
								)
							) AND
							correspondentUser.`Banned` = FALSE AND
							`Message`.`Sent` >=
							(
								SELECT
									MAX(`Message`.`Sent`)
								FROM
									`Message`
								WHERE
									(
										`Message`.`RecipientID` = thisUser.`ID` AND
										`Message`.`SenderID` = targetUser.`ID` AND
										`Message`.`ContentID` IS NOT NULL
									) OR
									(
										`Message`.`SenderID` = thisUser.`ID` AND
										`Message`.`RecipientID` = targetUser.`ID` AND
										`Message`.`ContentID_Sender` IS NOT NULL
									)
							)
					",
					'is',
					[
						$this->User->ID,
						$userAlias
					]
				)
			) &&
			$pages[0]['page'] > 0
		)
			return $pages[0]['page'];
				
		return FALSE;
	}
	
	private function markDeletedConversationMessagesRead($senderID = false){
		if (
			$rowsAffected = $this->db->qQuery(
				"
					UPDATE
						`Message`
					SET
						`Read` = TRUE
					WHERE
						`RecipientID` = ? AND
						`ContentID` IS NULL " . (
							$senderID
								? ' AND `SenderID` = ?'
								: false
						) . "
				",
				(
					$senderID
						? 'ii'
						: 'i'
				),
				(
					$senderID
						? [
							$this->User->ID,
							$senderID
						]
						: [$this->User->ID]
				)
			)
		)
			return	$this->User->incrementUserNotification(
					USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
					-1 * $rowsAffected
				);
		
		return false;
	}
	
	public function deleteConversation($alias){
		if( $stmt_deleteConversation = $this->db->prepare("
			UPDATE
				`Message`
			INNER JOIN	`User`
				ON	(`Message`.`RecipientID` = `User`.`ID` OR `Message`.`SenderID` = `User`.`ID`)
				AND	`User`.`ID` != ?
			SET
				`Content` = IF(`Message`.`RecipientID` = ?, NULL, `Content`),
				`Content_Sender` = IF(`Message`.`SenderID` = ?, NULL, `Content_Sender`),
				`ContentID` = IF(`Message`.`RecipientID` = ?, NULL, `ContentID`),
				`ContentID_Sender` = IF(`Message`.`SenderID` = ?, NULL, `ContentID_Sender`)
			WHERE
				`User`.`Alias` = ?
			AND	(`Message`.`RecipientID` = ? OR `Message`.`SenderID` = ?)
		") ){
			$stmt_deleteConversation->bind_param(
				'iiiiisii',
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$alias,
				$this->User->ID,
				$this->User->ID
			);
			
			$userID = $this->getUserID($alias);
			
			if ($stmt_deleteConversation->execute()){
				$this->markDeletedConversationMessagesRead($userID);
				$this->User->refreshConversation(
					$this->User->ID,
					$userID
				);
				
				return true;
			} else
				return false;
		}	
	}
	
	public function reportUser($alias){
		$trustedVendor =
			(
				$this->User->IsVendor &&
				$this->User->Attributes['TotalTransacted'] >= AMOUNT_TRANSACTED_TRUSTED_VENDOR
			) ||
			$this->User->IsMod ||
			$this->User->IsAdmin;
		
		$userReportID = $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`User_Report` (`UserID`, `ReportedID`)
				VALUES
					(
						?,
						(
							SELECT	`ID`
							FROM	`User`
							WHERE	`Alias` = ?
						)
					)
			",
			'is',
			array(
				$this->User->ID,
				$alias
			)
		);
		
		if( $trustedVendor )
			$this->banUser($alias);
		else
			$this->checkUserReports($alias);
			
		return TRUE;
	}
	
	private function banUser($alias){
		return $this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`Banned` = TRUE
				WHERE
					`Alias` = ?
			",
			's',
			array(
				$alias
			)
		);
	}
	
	private function checkUserReports($alias){
		$vendorReports = $this->db->qSelect(
			"
				SELECT
					COUNT(DISTINCT `User_Report`.`ID`) count
				FROM
					`User_Report`
				INNER JOIN	`User` ReportingUser
					ON	`User_Report`.`UserID` = ReportingUser.`ID`
				INNER JOIN	`User` ReportedUser
					ON	`User_Report`.`ReportedID` = ReportedUser.`ID`
				WHERE
					ReportingUser.`Vendor`	= TRUE
				AND	ReportedUser.`Alias`	= ?
			",
			's',
			array(
				$alias
			)
		);
		
		if($vendorReports[0]['count'] > VENDOR_REPORT_COUNT_AUTO_BAN)
			return $this->banUser($alias);
		else
			return FALSE;
	}
	
	public function findMessage(
		$messageID,
		$userID = false
	){
		$userID = $userID ?: $this->User->ID;
		if (
			$messageCorrespondent = $this->db->qSelect(
				"
					SELECT
						`User`.`ID`,
						`User`.`Alias`
					FROM
						`Message`
					INNER JOIN
						`User` ON
							(
								`Message`.`SenderID` = ? AND
								`Message`.`RecipientID` = `User`.`ID`
							) OR
							(
								`Message`.`SenderID` = `User`.`ID` AND
								`Message`.`RecipientID` = ?
							)
					WHERE
						`Message`.`ID` = ?
				",
				'iii',
				[
					$userID,
					$userID,
					$messageID
				]
			)
		){
			$message = $this->db->qSelect(
				"
					SELECT
						CEIL(COUNT(`Message`.`ID`) / " . MESSAGES_PER_PAGE . ") pageNumber
					FROM
						`Message`
					WHERE
						(
							(
								`Message`.`SenderID` = ? AND
								`Message`.`RecipientID` = ? AND
								`Message`.`ContentID_Sender` IS NOT NULL
							) OR
							(
								`Message`.`RecipientID` = ? AND
								`Message`.`SenderID` = ? AND
								`Message`.`ContentID` IS NOT NULL
							)
						) AND
						`Message`.`Sent` >= (
							SELECT	`Sent`
							FROM	`Message`
							WHERE	`ID` = ?
						)
				",
				'iiiii',
				[
					$userID,
					$messageCorrespondent[0]['ID'],
					$userID,
					$messageCorrespondent[0]['ID'],
					$messageID
				]
			);
			
			return	[
					$messageCorrespondent[0]['Alias'],
					$message[0]['pageNumber']
				];
		}
		
		return false;
	}
	
	public function deleteMessage($id){
		if(is_numeric($id) ){
			if($stmt_deleteMessage = $this->db->prepare("
				UPDATE
					`Message`
				SET
					`ContentID` = IF(`RecipientID` = ?, NULL, `ContentID`),
					`ContentID_Sender` = IF(`SenderID` = ?, NULL, `ContentID_Sender`)
				WHERE
					(`RecipientID` = ?	OR	`SenderID` = ?)
				AND	`ID` = ?
			") ){
				$stmt_deleteMessage->bind_param('iiiii', $this->User->ID, $this->User->ID, $this->User->ID, $this->User->ID, $id);
				
				if( $stmt_selectMessage = $this->db->prepare("
					SELECT
						`SenderID`
					FROM
						`Message`
					WHERE
						`ID` = ?
				") ){
					
					$stmt_selectMessage->bind_param('i', $id);
					$stmt_selectMessage->execute();
					$stmt_selectMessage->store_result();
					$stmt_selectMessage->bind_result($sender_id);
					$stmt_selectMessage->fetch();
					
					if( $stmt_deleteMessage->execute() ){
						
						return $sender_id == $this->User->ID ? 'sent' : 'inbox';
						
					}
					
				}
				
			}
		} else {
			$this->User->Notifications->quick('FatalError', 'Invalid Message ID');
		}
	}
	
	public function deleteAllMessages(){
		return	$this->db->qQuery(
				"
					UPDATE
						`Message`
					INNER JOIN
						`User` ON
							`User`.`ID` = ?
					INNER JOIN
						`Conversation_User` ON
							`Conversation_User`.`UserID` = `User`.`ID`
					SET
						`Message`.`ContentID` = IF(
							`Message`.`RecipientID` = `User`.`ID`,
							NULL,
							`Message`.`ContentID`
						),
						`Message`.`ContentID_Sender` = IF(
							`Message`.`SenderID` = `User`.`ID`,
							NULL,
							`Message`.`ContentID_Sender`
						),
						`Conversation_User`.`Unread` = FALSE,
						`Conversation_User`.`Deleted` = TRUE,
						`Conversation_User`.`Important` = FALSE
					WHERE
						`User`.`ID` IN (`Message`.`RecipientID`, `Message`.`SenderID`)
				",
				'i',
				[$this->User->ID]
			) &&
			$this->markDeletedConversationMessagesRead();
	}
	
	public function sendMessage(){
		$_SESSION['new_message_post']['recipient_alias'] = htmlspecialchars($_POST['recipient_alias']);
		$_SESSION['new_message_post']['auto_delete'] = htmlspecialchars($_POST['auto_delete']);
		$_SESSION['new_message_post']['content'] = htmlspecialchars($_POST['content']);
		$_SESSION['new_message_post']['subject'] = htmlspecialchars($_POST['subject']);
		
		if( strtotime($_SESSION['joinDateTime']) > strtotime("-" . WAIT_UNTIL_CAN_SEND_MESSAGE . " SECONDS") ){
			$_SESSION['temp_notifications'][] = array(
				'Content' => 'You just signed up. Please wait a couple of minutes before you start sending out messages',
				'Group' => 'Specific',
				'Dismiss' => '.',
				'Design' => array(
					'Color' => 'green',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				)
			);
			return false;
		} elseif( !$this->checkCaptcha() ){
			$_SESSION['new_message_response']['captcha'] = TRUE;
			return false;
		} elseif(empty($_POST['recipient_alias'])){
			$_SESSION['new_message_response']['recipient_alias'] = 'This field cannot be empty.';
			return false;
		} elseif (strtolower($_POST['recipient_alias']) == strtolower($this->User->Alias)){
			$_SESSION['new_message_response']['recipient_alias'] = 'You cannot message yourself.';
			return false;
		} elseif (empty($_POST['content']) ){
			$_SESSION['new_message_response']['content'] = 'This field cannot be empty.';
			return false;
		} elseif (
			!$this->User->IsMod &&
			strlen($_POST['content']) > MAX_LENGTH_MESSAGE_CONTENT
		){
			$_SESSION['new_message_response']['content'] = 'Message cannot be longer than ' . MAX_LENGTH_MESSAGE_CONTENT . ' characters.';
			return false;
		}
		/* elseif (
			!empty($_POST['subject']) &&
			!$this->User->IsMod &&
			strlen($_POST['subject']) > MAX_LENGTH_MESSAGE_SUBJECT
		){
			$_SESSION['new_message_response']['subject'] = 'Subject cannot be longer than ' . MAX_LENGTH_MESSAGE_SUBJECT . ' characters.';
			return false;
		} */
		elseif ( !isset($_POST['auto_delete']) ) {
			$_SESSION['new_message_response']['auto_delete'] = 'This field cannot be empty.';
			return false;
		} else {
			$recipient_alias = $_POST['recipient_alias'];
			
			if( $stmt_findRecipient = $this->db->prepare("
				SELECT
					`ID`,
					`PublicKey`,
					`EncryptPGP`,
					`PGP`,
					`EncryptMessages`
				FROM
					`User`
				WHERE
					`Alias` = ?
				LIMIT 1
			") ){
				$stmt_findRecipient->bind_param('s', $recipient_alias);
				$stmt_findRecipient->execute();
				$stmt_findRecipient->store_result();
				if($stmt_findRecipient->num_rows > 0) {
					$stmt_findRecipient->bind_result(
						$recipient_id,
						$recipient_pub,
						$recipient_encrypt_pgp,
						$recipient_pgp,
						$recipient_encryptMessages
					);
					$stmt_findRecipient->fetch();
				} else {
					$_SESSION['new_message_response']['recipient_alias'] = 'No user exists with that username';
					return false;
				}
			}
			
			switch($_POST['auto_delete']){
				case 1:
				case 3:
				case 7:
				case 14:
				case 30:
					$auto_delete = "NOW() + INTERVAL " . $_POST['auto_delete'] . " DAY";
				break;
				default:
					$auto_delete = "NULL";
			}
			
			$array = array(
				'Date'		=> date('j F Y'),
				'Timestamp'	=> time()
			);
			
			if($recipient_encrypt_pgp == 1 && !empty($recipient_pgp) ){
				// Check not already encrypted
				if( preg_match(REGEX_PGP_ENCRYPTED_MESSAGE, $_POST['content']) ){
					$content = '[pgp]' . $_POST['content'] . '[/pgp]';
				} else {
					$pgp = new PGP($recipient_pgp);
					
					$content = $pgp->qEncrypt($_POST['content'], true);
					
					$content = '[pgp]' . $content . '[/pgp]';	
				}
			} else {
				$content = $_POST['content'];
			}
			
			list($my_pgp_encrypt, $my_pgp) = $this->User->Info(0,'EncryptPGP', 'PGP');
			
			if( $my_pgp_encrypt==1 && !empty($my_pgp) ){
				
				// Check not already encrypted
				if( preg_match(REGEX_PGP_ENCRYPTED_MESSAGE, $_POST['content']) ){
					$my_content = '[pgp]' . $_POST['content'] . '[/pgp]';
				} else {
				
					$my_pgp = new PGP($my_pgp);
					
					$my_content = $my_pgp->qEncrypt($_POST['content'], true);
					
					$my_content = '[pgp]' . $my_content . '[/pgp]';
				}
				
			} else
				$my_content = $_POST['content'];
			
			if( !empty($_POST['subject']) ){
				$content = '[b]' . preg_replace('/\[b\]((?:(?!\[\/b).)*)\[\/b]/i', '$1', $_POST['subject']) . '[/b]' . PHP_EOL . PHP_EOL . $content;
				
				$my_content = '[b]' . preg_replace('/\[b\]((?:(?!\[\/b).)*)\[\/b]/i', '$1', $_POST['subject']) . '[/b]' . PHP_EOL . PHP_EOL . $my_content;
			}
			
			$my_content = NXS::formatText(
				$my_content,
				FALSE,
				$dialog,
				FALSE,
				TRUE
			);
			$content = NXS::formatText(
				$content,
				FALSE,
				$dialog,
				FALSE,
				TRUE
			);
			
			$rsa = new RSA();
			
			$recipient_message = $rsa->qEncrypt(
				json_encode(
					array_merge(
						$array,
						array(
							'Message' => $content
						)
					)
				),
				$recipient_pub
			);
			
			$sender_message = $rsa->qEncrypt(
				json_encode(
					array_merge(
						$array,
						array(
							'Message' => $my_content
						)
					)
				)
			);
		
			if($stmt_sendMessage = $this->db->prepare("
				INSERT
					`Message` (
						`SenderID`,
						`RecipientID`,
						`Content`,
						`Content_Sender`,
						`AutoDelete`,
						`Sent`
					)
				VALUES
					(
						?,
						?,
						?,
						?,
						".$auto_delete.",
						NOW()
					)
			") ){
				$stmt_sendMessage->bind_param(
					'iiss',
					$this->User->ID,
					$recipient_id,
					$recipient_message,
					$sender_message
				);
				if($stmt_sendMessage->execute()){
					// Should we save a plaintext copy?
					if(
						$recipient_encryptMessages == FALSE &&
						$this->User->Info(0,'EncryptMessages') == FALSE
					){
						$this->User->insertPlaintextMessage(
							$stmt_sendMessage->insert_id,
							$my_content,
							$this->User->ID
						);
						
						$this->User->insertPlaintextMessage(
							$stmt_sendMessage->insert_id,
							$content,
							$recipient_id
						);
					}
					
					Session::set('new_message_post', null);
					/*NXS::notifyExternally(
						$this->db,
						$recipient_id, 
						array(
							'type' => 'new_message',
							'message' => 'You have received a new message on ' . $this->db->site_name . ' from user ' . $this->User->Alias . ':' . PHP_EOL . PHP_EOL . $_POST['content']
						)
					);*/
					$this->User->incrementUserNotification(
						USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
						1,
						$recipient_id
					);
					$this->User->updateAttributes(
						array(
							'LastSeen' => array(
								'MessageID' => $stmt_sendMessage->insert_id
							)
						)
					);
					$this->db->incrementStatistic('messages', 1);
					$this->User->refreshConversation(
						$recipient_id,
						$this->User->ID
					);
					
					return $stmt_sendMessage->insert_id;
				} else {
					$this->User->Notifications->quick('FatalError', 'Couldn\'t Send Message');
					return false;
				}
			}
		}
	}
	
	public function fetchListing($listing_id){
		if( $stmt_getListing = $this->db->prepare("
			SELECT
				`CategoryID`,
				`Name`,
				`Price`,
				`CurrencyID`,
				`Description`,
				`Excerpt`,
				`Escrow`,
				`EscrowReputation`,
				`EscrowTimeout`,
				`MessageOnRequest`,
				`MessageOnAccept`,
				`Tag_Color`,
				`Tag_Text`,
				`Listing`.`UnitID`,
				`Quantity`,
				`Quantity_Left`,
				`Quantity_Critical`,
				`Inactive`,
				`Stealth`,
				(
					SELECT
						COUNT(`Listing_Attribute`.`ID`)
					FROM
						`Listing_Attribute`
					WHERE
						`Listing_Attribute`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(`Listing_BulkPrice`.`ID`)
					FROM
						`Listing_BulkPrice`
					WHERE
						`Listing_BulkPrice`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(`Listing_Continent`.`ContinentID`)
					FROM
						`Listing_Continent`
					WHERE
						`Listing_Continent`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(`Listing_Country`.`CountryID`)
					FROM
						`Listing_Country`
					WHERE
						`Listing_Country`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(`Listing_Image`.`ID`)
					FROM
						`Listing_Image`
					WHERE
						`Listing_Image`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(`Listing_PromoCode`.`ID`)
					FROM
						`Listing_PromoCode`
					WHERE
						`Listing_PromoCode`.`ListingID` = `Listing`.`ID`
				),
				(
					SELECT
						COUNT(Listing_Rating.`ID`)
					FROM
						`Transaction_Rating` Listing_Rating
					WHERE
						Listing_Rating.`ListingID` = `Listing`.`ID`
				),
				`Listing`.`AllowFE`,
				`Listing`.`Quantity_Minimum`,
				`Listing`.`CountryID`,
				`Listing_Group`.`Label`,
				DATEDIFF(NOW(), `Listing`.`DateAdded`) < " . LISTING_CAN_CHANGE_TITLE_INTERVAL_DAYS . ",
				`Unit`.`DimensionID`
			FROM
				`Listing`
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			LEFT JOIN
				`ListingGroup` ON
					`Listing_Group`.`GroupID` = `ListingGroup`.`ID`
			LEFT JOIN
				`Unit` ON
					`ListingGroup`.`UnitID` = `Unit`.`ID`
			WHERE
				`Listing`.`ID` = ?
			AND	`Listing`.`VendorID` = ?
			LIMIT 1
		") ){
			$stmt_getListing->bind_param('ii', $listing_id, $this->User->ID);
			$stmt_getListing->execute();
			$stmt_getListing->store_result();
			
			if( $stmt_getListing->num_rows == 1 ){
				$stmt_getListing->bind_result(
					$category_id,
					$name,
					$price,
					$currency_id,
					$description,
					$excerpt,
					$escrow,
					$escrow_reputation,
					$escrow_timeout,
					$message_request,
					$message_accept,
					$tag_color,
					$tag_text,
					$unitid,
					$quantity,
					$quantity_left,
					$quantity_critical,
					$inactive,
					$hidden,
					$attribute_count,
					$bulkprice_count,
					$continent_count,
					$country_count,
					$image_count,
					$promocode_count,
					$rating_count,
					$allowFE,
					$quantity_minimum,
					$ships_from,
					$groupLabel,
					$canChangeTitle,
					$unitDimensionID
				);
				
				$stmt_getListing->fetch();
				
				if(!empty($message_accept)){
					$rsa = new RSA();
					$message_accept = $rsa->qDecrypt($message_accept);
				}
				
				$excerptIsFromDescription =
					substr(
						$description,
						0,
						strlen($excerpt)
					) == $excerpt;
				if($excerptIsFromDescription)
					$excerpt = false;
				
				$content = array(
					'category'		=> $category_id,
					'name'			=> htmlspecialchars($name),
					'price'			=> 
						$currency_id != CURRENCY_ID_BTC
							? NXS::formatDecimal($price)
							: $price,
					'currency'		=> $currency_id,
					'description'		=> htmlspecialchars($description),
					'excerpt'		=> htmlspecialchars($excerpt),
					'escrow_enabled'	=> ($escrow==1) ? 'yes' : 'no',
					'escrow_reputation'	=> $escrow_reputation,
					'escrow_timeout'	=> $escrow_timeout,
					'request_message'	=> $message_request,
					'accept_message'	=> $message_accept,
					'tag_color'		=> $tag_color,
					'tag_text'		=> $tag_text,
					'unit'			=> $unitid,
					'quantity'		=> $quantity ? NXS::formatDecimal($quantity) : 1,
					'stock'			=> $quantity_left,
					'critical_quantity'	=> $quantity_critical,
					'listing_active'	=> empty($inactive),
					'listing_visible'	=> empty($hidden),
					'allow_fe'		=> $allowFE,
					'quantity_minimum'	=> $quantity_minimum,
					'ships_from'		=> $ships_from,
					'group_label'		=> $groupLabel,
					'canChangeTitle'	=> $canChangeTitle,
					'unitDimensionID'	=> $unitDimensionID
				);
				
				$content['shipping_options'] = array_map(
					function($array){
						return $array['ShippingID'];
					},
					$this->db->qSelect(
						"
							SELECT
								`ShippingID`
							FROM
								`Listing_Shipping`
							WHERE
								`ListingID` = ?
						",
						'i',
						array(
							$listing_id
						)
					)
				);
				
				if( $attribute_count > 0 ){
					
					if( $stmt_getAttributes = $this->db->prepare("
						SELECT
							`AttributeID`,
							`Type`,
							`Value`
						FROM
							`Listing_Attribute`
						INNER JOIN
							`ListingAttribute` ON `Listing_Attribute`.`AttributeID` = `ListingAttribute`.`ID`
						WHERE
							`ListingID` = ?
					") ){
						
						$stmt_getAttributes->bind_param('i', $listing_id);
						$stmt_getAttributes->execute();
						$stmt_getAttributes->store_result();
						$stmt_getAttributes->bind_result($attribute_id, $attribute_type, $attribute_value);
						
						$i = 1;
						while( $stmt_getAttributes->fetch() ){
							
							if( $attribute_id == LISTING_ATTRIBUTE_FROM_COUNTRY ){
								//$content['ships_from'] = $attribute_value;
								continue;
							} elseif( !isset($content['ships_from']) && $attribute_id == LISTING_ATTRIBUTE_FROM_CONTINENT){
								//$content['ships_from'] = 'cont_'.$attribute_value;
								continue;
							}
							
							$content['attribute-'.$i] = $attribute_id;
							$content['attribute-'.$i.'_content'] = $attribute_value;
							$i++;
						}
						
					} else {
						//die('attributes');
					}
					
				}
				
				if( $bulkprice_count > 0 ){
					
					if( $stmt_getBulkPrice = $this->db->prepare("
						SELECT
							`Quantity`,
							`Price`,
							`CurrencyID`
						FROM
							`Listing_BulkPrice`
						WHERE
							`ListingID` = ?
					") ){
						
						$stmt_getBulkPrice->bind_param('i', $listing_id);
						$stmt_getBulkPrice->execute();
						$stmt_getBulkPrice->store_result();
						$stmt_getBulkPrice->bind_result($bulkprice_quantity, $bulkprice_price, $bulkprice_currency);
						
						$i = 1;
						while( $stmt_getBulkPrice->fetch() ){
							$content['special_pricing-'.$i.'_quantity'] = $bulkprice_quantity;
							$content['special_pricing-'.$i.'_price'] = $bulkprice_price;
							$content['special_pricing-'.$i.'_currency'] = $bulkprice_currency;
							$i++;
						}
						
					} else {
						//die('bulkprice');
					}
					
				}
				
				if( $continent_count > 0 ){
					
					if( $stmt_getContinents = $this->db->prepare("
						SELECT
							`ContinentID`
						FROM
							`Listing_Continent`
						WHERE
							`ListingID` = ?
					") ){
						
						$stmt_getContinents->bind_param('i', $listing_id);
						$stmt_getContinents->execute();
						$stmt_getContinents->store_result();
						$stmt_getContinents->bind_result($continent_id);
						
						while( $stmt_getContinents->fetch() ){
							$content['ships_to_continent'][] = $continent_id;
						}
						
					} else {
						//die('continents');
					}
					
				}
				
				if( $country_count > 0 ){
					
					if( $stmt_getCountries = $this->db->prepare("
						SELECT
							`CountryID`
						FROM
							`Listing_Country`
						WHERE
							`ListingID` = ?
					") ){
						
						$stmt_getCountries->bind_param('i', $listing_id);
						$stmt_getCountries->execute();
						$stmt_getCountries->store_result();
						$stmt_getCountries->bind_result($country_id);
						
						while( $stmt_getCountries->fetch() ){
							$content['ships_to_country'][] = $country_id;
						}
						
					} else {
						//die('countries');
					}
					
				}
				
				if ($image_count > 0){
					$images = $this->db->qSelect(
						"
							SELECT
								`Listing_Image`.`ID`,
								`Listing_Image`.`Primary`,
								CONCAT(
									'/" . UPLOADS_PATH . "',
									`Image`.`Filename`
								) Image
							FROM
								`Listing_Image`
							LEFT JOIN
								`Image` ON
									`Listing_Image`.`ImageID` = `Image`.`ID`
							WHERE
								`Listing_Image`.`ListingID` = ?
							ORDER BY
								`Listing_Image`.`Primary` DESC
						",
						'i',
						array(
							$listing_id
						)
					);
					$images = array_map(
						function($array){
							$array['Image'] = NXS::getPictureVariant($array['Image'], IMAGE_THUMBNAIL_SUFFIX);
							return $array;
						},
						$images
					);
					$content['images'] = $images;
				}
				
				$content['promo_code_ids'] = false;
				if(
					$promoCodes = $this->fetchListingPromoCodes($listing_id)
				)
					foreach($promoCodes as $promoCode){
						$content['promo_code_ids'][] = $promoCode['ID'];
						
						$content['promo_code-' . $promoCode['ID'] . '-code'] = $promoCode['Code'];
						$content['promo_code-' . $promoCode['ID'] . '-discount'] = $promoCode['Discount'];
						$content['promo_code-' . $promoCode['ID'] . '-currency'] = $promoCode['CurrencyID'];
						$content['promo_code-' . $promoCode['ID'] . '-quantity'] = $promoCode['Quantity'];
					}
				
				
				return array(
					'id' => $listing_id,
					'rating_count' => $rating_count,
					'content' => $content
				);
				
			} else {
				return false;
			}
			
			
		} else {
			//die($this->db->error);
		}
		
	}
	
	public function getListingPaymentMethods($listingID = null){
		return $this->db->qSelect(
			"
				SELECT DISTINCT
					`PaymentMethod`.`ID`,
					Cryptocurrency.`Name`,
					(
						? IS NULL OR
						`Listing_PaymentMethod`.`ListingID` IS NOT NULL
					) Enabled
				FROM
					`PaymentMethod`
				INNER JOIN
					`Currency` Cryptocurrency ON
						`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
				LEFT JOIN
					`Listing_PaymentMethod` ON
						`PaymentMethod`.`ID` = `Listing_PaymentMethod`.`PaymentMethodID` AND
						`Listing_PaymentMethod`.`ListingID` = ?
				WHERE
					`PaymentMethod`.`UserID` = ?
			",
			'iii',
			[
				$listingID,
				$listingID,
				$this->User->ID
			]
		)
			?: false;
	}
	
	private function fetchListingPromoCodes($listingID){
		if(
			$promoCodes = $this->db->qSelect(
				"
					SELECT
						`ID`,
						`Code`,
						`Discount`,
						`CurrencyID`,
						`Quantity`
					FROM
						`Listing_PromoCode`
					WHERE
						`ListingID` = ?
					ORDER BY
						`DateTime` ASC,
						`ID` ASC
				",
				'i',
				[$listingID]
			)
		)
			return $promoCodes;
		
		return false;
	}
	
	public function fetchInvites($getClaimed = FALSE, $pageNumber = 1, &$inviteCount = 0){
		if(
			$inviteCounts = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `InviteCode`.`ID`) inviteCount
					FROM
						`InviteCode`
					LEFT JOIN
						`User` ON
							`InviteCode`.`ClaimedID` = `User`.`ID`
					WHERE
						`InviteCode`.`UserID` = ? AND
						" .
						(
							$getClaimed
								? '`ClaimedID` IS NOT NULL'
								: '`ClaimedID` IS NULL'
						) . "
				",
				'i',
				[
					$this->User->ID
				]
			)
		){
			$inviteCount = $inviteCounts[0]['inviteCount'];
			
			$offset = NXS::getOffset(
				$inviteCount,
				INVITES_PER_PAGE,
				$pageNumber
			);
		
			$invites = $this->db->qSelect(
				"
					SELECT
						`InviteCode`.`ID`,
						`InviteCode`.`Code`,
						`User`.`Alias` UserAlias,
						`InviteCode`.`Issued`,
						`InviteCode`.`Comment`
					FROM
						`InviteCode`
					LEFT JOIN
						`User` ON
							`InviteCode`.`ClaimedID` = `User`.`ID`
					WHERE
						`InviteCode`.`UserID` = ? AND
						" .
						(
							$getClaimed
								? '`ClaimedID` IS NOT NULL'
								: '`ClaimedID` IS NULL'
						) . "
					ORDER BY
						`InviteCode`.`Issued` ASC,
						(
							`InviteCode`.`Comment` != '' AND
							`InviteCode`.`Comment` IS NOT NULL
						) DESC
					LIMIT
						?, " . INVITES_PER_PAGE . "
				",
				'ii',
				array(
					$this->User->ID,
					$offset
				)
			);
		
			return $invites;
		}
		
		return FALSE;
	}
	
	public function updateInvites(){
		$updatedInvites = array();
		
		foreach( $_POST['invite_ids'] as $inviteID ){
			$issued = isset($_POST['invite-' . $inviteID . '_issued']) && !isset($_POST['invite-' . $inviteID . '_retract']);
			$comments = htmlspecialchars($_POST['invite-' . $inviteID . '_comments']);
			
			$updatedInvites[] = $this->db->qQuery(
				"
					UPDATE
						`InviteCode`
					SET
						`Issued` = ?,
						`Comment` = ?
					WHERE
						`ID` = ? AND
						`UserID` = ?
				",
				'isii',
				array(
					$issued,
					$comments,
					$inviteID,
					$this->User->ID
				)
			);
		}
		
		return $updatedInvites;
	}
	
	public function _getNumberOfUnissuedInvites($countIssued){
		return $this->db->qSelect(
			"
				SELECT
					COUNT(`ID`) Count
				FROM
					`InviteCode`
				WHERE
					`UserID` = ? AND
					`ClaimedID` IS NULL
					" . (
						$countIssued
							? "AND `Issued` = FALSE"
							: FALSE
					) . "
			",
			'i',
			array(
				$this->User->ID
			)
		)[0]['Count'];
	}
	
	public function topUpInvites($topUpTo = INVITES_VENDORS_TOP_UP_QUANTITY, $countIssued = TRUE){
		$numberOfUnissuedInvites = $this->_getNumberOfUnissuedInvites($countIssued);
		$needsToGenerate = $topUpTo - $numberOfUnissuedInvites;
		
		if($needsToGenerate > 0){
			$inviteCodesGenerated = 0;
			
			for($i = 0; $i < $needsToGenerate; $i++){
				$code = NXS::generateRandomString(10, FALSE);
						
				if(
					$this->db->qQuery(
						"
							INSERT IGNORE INTO
								`InviteCode` (`Code`, `Type`, `UserID`)
							VALUES
								(?, 'buyer', ?)
						",
						'si',
						array(
							$code,
							$this->User->ID
						)
					)
				)
					$inviteCodesGenerated++;
			}
			
			return $inviteCodesGenerated;
		}
		
		return FALSE;
	}
	
	public function fetchListings($sort, $page){
		switch($sort){
			case 'id_desc':
				$order_by = '
					`Listing`.`DateAdded` DESC,
					`Listing`.`ID` DESC
				';
			break;
			case 'name_asc':
				$order_by = '`Listing`.`Name` ASC';
			break;
			case 'name_desc':
				$order_by = '`Listing`.`Name` DESC';
			break;
			case 'price_asc':
				$order_by = '`Listing`.`Price`/`Currency`.`1EUR` ASC, `Listing`.`ID` ASC';
			break;
			case 'price_desc':
				$order_by = '`Listing`.`Price`/`Currency`.`1EUR` DESC, `Listing`.`ID` ASC';
			break;
			case 'stock_asc':
				$order_by = '`Quantity_Left` ASC, `Listing`.`ID` ASC';
			break;
			case 'stock_desc':
				$order_by = '`Quantity_Left` DESC, `Listing`.`ID` ASC';
			break;
			// case 'id_asc':
			default:
				$order_by = '
					`Listing`.`DateAdded` ASC,
					`Listing`.`ID` ASC
				';
		}
		
		// Should We Show "Archived" Listings??
		$hideArchivedListings = $this->User->Attributes['Preferences']['ShowArchivedListings'] == FALSE;
		
		$stmt_countListings = $this->db->prepare("
			SELECT
				COUNT(`ID`),
				(
					SELECT
						COUNT(`ID`)
					FROM
						`Listing`
					WHERE
						`Inactive` = TRUE
					AND	`VendorID` = mainlisting.`VendorID`
					" . (
						$hideArchivedListings
							? "AND `Listing`.`Archived` = FALSE"
							: FALSE
					) . "
				)
			FROM
				`Listing` mainlisting
			WHERE
				`VendorID` = ?
				" . (
					$hideArchivedListings
						? "AND mainlisting.`Archived` = FALSE"
						: FALSE
				) . "
		");
		
		$stmt_getListings = $this->db->prepare("
			SELECT
				`Listing`.`ID`,
				`Listing`.`Name`,
				`Listing`.`Price`,
				`Currency`.`ISO`,
				(
					SELECT	AVG(Listing_Rating.`Rating_Vendor`)
					FROM   `Transaction_Rating` Listing_Rating
					WHERE  Listing_Rating.`ListingID` = `Listing`.`ID`
				),
				`Listing`.`Inactive`,
				`Listing`.`Approved`,
				(
					SELECT	COUNT(`Listing_Question`.`ID`)
					FROM	`Listing_Question`
					WHERE
						`Listing_Question`.`ListingID` = `Listing`.`ID`
					AND	`Listing_Question`.`Content` IS NULL
				),
				`Listing`.`Stealth`,
				IF (
					`ListingGroup`.`SynchronizeStock` IS TRUE AND
					`ListingGroup`.`Stock` IS NOT NULL,
					`ListingGroup`.`Stock`,
					`Quantity_Left`
				),
				`Listing`.`Archived`,
				`Quantity_Minimum`,
				(
					SELECT
						COUNT(`Listing_PromoCode`.`ID`)
					FROM
						`Listing_PromoCode`
					WHERE
						`Listing_PromoCode`.`ListingID` = `Listing`.`ID` AND
						`Listing_PromoCode`.`Quantity` > 0
				) > 0,
				DATEDIFF(NOW(), `Listing`.`DateAdded`) < " . LISTING_CAN_CHANGE_TITLE_INTERVAL_DAYS . ",
				`Listing_Group`.`GroupID`,
				IF (
					`Listing_Group`.`GroupID` IS NOT NULL,
					(
						SELECT
							COUNT(DISTINCT `Listing_Group`.`GroupID`)
						FROM
							`Listing` L2
						INNER JOIN
							`Listing_Group` ON
								`Listing_Group`.`ListingID` = L2.`ID`
						INNER JOIN
							`ListingGroup` LG2 ON
								LG2.`ID` = `Listing_Group`.`GroupID`
						WHERE
							L2.`VendorID` = `Listing`.`VendorID` AND
							(
								LG2.`DateTime` < `ListingGroup`.`DateTime` OR
								(
									LG2.`DateTime` = `ListingGroup`.`DateTime` AND
									LG2.`ID` < `ListingGroup`.`ID`
								)
							)
					),
					FALSE
				),
				`ListingGroup`.`SynchronizeStock`,
				`Unit`.`ID`,
				`Unit`.`DimensionID`
			FROM
				`Listing`
			INNER JOIN
				`Currency` ON `Listing`.`CurrencyID` = `Currency`.`ID`
			LEFT JOIN
				`Listing_Group` ON
					`Listing_Group`.`ListingID` = `Listing`.`ID`
			LEFT JOIN
				`ListingGroup` ON
					`ListingGroup`.`ID` = `Listing_Group`.`GroupID`
			LEFT JOIN
				`Unit` ON
					`ListingGroup`.`SynchronizeStock` = TRUE AND
					`ListingGroup`.`UnitID` = `Unit`.`ID`
			WHERE
				`VendorID` = ?
				" . (
					$hideArchivedListings
						? "AND `Listing`.`Archived` = FALSE"
					    : FALSE
				) . "
			ORDER BY
				`Listing_Group`.`GroupID` IS NULL ASC,
				`ListingGroup`.`DateTime` ASC,
				`ListingGroup`.`ID` ASC,
				" . $order_by . "
			LIMIT ?, " . VENDORS_LISTINGS_PER_PAGE . "
		");
		
		if ( false !== $stmt_countListings && false !== $stmt_getListings){
			$stmt_countListings->bind_param('i', $this->User->ID);
			$stmt_countListings->execute();
			$stmt_countListings->store_result();
			$stmt_countListings->bind_result(
				$listing_count,
				$listing_inactive_count
			);
			$stmt_countListings->fetch();
			
			if($listing_count > 0) {
				$offset = NXS::getOffset(
					$listing_count,
					VENDORS_LISTINGS_PER_PAGE,
					$page
				);
				
				$stmt_getListings->bind_param(
					'ii',
					$this->User->ID,
					$offset
				);
				$stmt_getListings->execute();
				$stmt_getListings->store_result();
				$stmt_getListings->bind_result(
					$listing_id,
					$listing_name,
					$listing_price,
					$listing_currency,
					$listing_rating,
					$listing_inactive,
					$listing_approved,
					$listing_unanswered_question_count,
					$listing_stealth,
					$listing_stock,
					$listing_archived,
					$listing_quantity_minimum,
					$listingHasActivePromos,
					$listingHasEditableTitle,
					$listingGroupID,
					$listingGroupNumber,
					$listingGroupSynchronizeStock,
					$listingUnitID,
					$listingUnitDimensionID
				);
				
				$listings = array();
				while($stmt_getListings->fetch() ){
					$listings[] = array(
						'id'			=> $listing_id, 
						'name'			=> htmlspecialchars($listing_name),
						'price'			=> $listing_currency == 'BTC'
										? number_format($listing_price, BITCOIN_DECIMAL_PLACES)
										: NXS::formatDecimal($listing_price, 2),
						'currency'		=> $listing_currency,
						'rating'		=> $listing_rating,
						'rating_count'		=> 0,
						'inactive'		=> ($listing_inactive == 1),
						'approved'		=> ($listing_approved == 1),
						'rejected'		=> ($listing_approved == -1),
						'unanswered_questions'	=> $listing_unanswered_question_count > 0,
						'stealth'		=> $listing_stealth == 1,
						'stock'			=> NXS::formatDecimal($listing_stock, 2),
						'archived'		=> $listing_archived,
						'Quantity_Minimum'	=> $listing_quantity_minimum,
						'hasActivePromos'	=> $listingHasActivePromos == 1,
						'editableTitle'		=> $listingHasEditableTitle,
						'groupID'		=> $listingGroupID,
						'groupHue'		=>
							$listingGroupNumber !== NULL
								? NXS::partitionNumber($listingGroupNumber)
								: false,
						'synchronizeStock'	=> $listingGroupSynchronizeStock,
						'unitID'		=> $listingUnitID,
						'dimensionID'		=> $listingUnitDimensionID
						
					);
				}
			} else
				$listings = $listing_count = false;
		}
		
		$this->User->setUserNotification(
			USER_NOTIFICATION_TYPEID_LISTING_OUT_OF_STOCK,
			0
		);
		
		return array($listing_count, $listing_inactive_count, $listings);
	}
	
	public function fetchListingIDs(){
		
		if( $stmt_getListingIDs = $this->db->prepare("
			SELECT
				`ID`,
				`Name`
			FROM
				`Listing`
			WHERE
				`VendorID` = ?
		") ){
			
			$stmt_getListingIDs->bind_param('i', $this->User->ID);
			$stmt_getListingIDs->execute();
			$stmt_getListingIDs->store_result();
			
			if( $stmt_getListingIDs->num_rows > 0 ){
				
				$stmt_getListingIDs->bind_result($listing_id, $listing_name);
				
				$listings = array();
				while( $stmt_getListingIDs->fetch() ){
					$listings[ $listing_id ] = array(
						'id'	=>	$listing_id,
						'name'	=>	$listing_name
					);
				}
				
				return $listings;
				
			} else {
				return false;
			}
			
		}
		
	}
	
	public function fetchListingCategories($target_category_id = false){
		$final_categories = array();
		
		if( $stmt_ListingCategories = $this->db->prepare("
			SELECT
				`ID`,
				`ParentID`,
				`Name`
			FROM
				`ListingCategory`
			ORDER BY
				`Sort` ASC,
				`Name` ASC
		") ){
			$stmt_ListingCategories->execute();
			$stmt_ListingCategories->store_result();
			$stmt_ListingCategories->bind_result($id, $parent_id, $name);
			
			$all_categories = array();
			while($stmt_ListingCategories->fetch() ){
				$all_categories[] = array(
					'ID' => $id,
					'Parent'=> empty($parent_id) ? 0 : $parent_id,
					'Name' => $name);
			}
			
			$categories = $original_categories = $this->makeRecursive($all_categories);
			
			if( $allowed_listing_category_id = $this->db->getSiteInfo('ListingCategoryID') ){
				$categories = NXS::reduceCategories($allowed_listing_category_id, $categories, $all_categories);
				//$categories = array($categories);
				$categories = $categories['Children'];
				if( $target_category_id && !NXS::filterCategory($target_category_id, $categories) )
					$categories = $original_categories;
			}
			
		} else {
			die($this->db->error);
		}
		//print_r( $categories ); die;
		return $categories;
	}
	
	public function fetchListingAttributes(){
		if( $stmt_ListingAttributes = $this->db->prepare("
			SELECT
				`ID`,
				`Attribute`,
				`Type`
			FROM
				`ListingAttribute`
			ORDER BY
				`Type` ASC,
				`Attribute` ASC
		") ){
			$stmt_ListingAttributes->execute();
			$stmt_ListingAttributes->store_result();
			$stmt_ListingAttributes->bind_result($id, $attribute, $type);
			
			$attributes = array();
			while( $stmt_ListingAttributes->fetch() ){
				$attributes[$type][] = array(
					'id' => $id,
					'attribute' => $attribute,
					'type' => $type
				);
			}
			
			return $attributes;
			
		}
		
	}
	
	public function fetchListingShippingOptions(){
		
		return array_map(
			function($array){
				return array_merge(
					$array,
					array(
						'EURPrice' => NXS::formatPrice(
							$this->User->Currency,
							$array['EURPrice']
						)
					)
				);
			},
			$this->db->qSelect(
				"
					SELECT
						`ListingShipping`.`ID`,
						`ListingShipping`.`Name`,
						`ListingShipping`.`Price` / `Currency`.`1EUR` EURPrice
					FROM
						`ListingShipping`
					INNER JOIN	`Currency`
						ON	`ListingShipping`.`CurrencyID` = `Currency`.`ID`
					WHERE
						`ListingShipping`.`VendorID` = ?
				",
				'i',
				array(
					$this->User->ID
				)
			)
		);
		
	}
	
	public function new_listing(){
		if( !empty($_POST) ) {
			if( !empty($_POST['delete_pic']) )
				$this->deleteListingPicture($_POST['delete_pic']);
			elseif( !empty($_POST['make_pic_primary']) )
				$this->makeListingPicturePrimary($listing_id, $_POST['make_pic_primary']);
			
			foreach($_POST as $key => $value)
				$_SESSION['listing_post'][$key] =
					is_array($value)
						? array_map(
							'htmlspecialchars',
							$value
						)
						: htmlspecialchars($value);
			$tryAgain = false;
			
			$columns = array('`VendorID`');
			$stmt_types = 'i';
			$stmt_params = array(&$this->User->ID);
			
			if( empty($_POST['shipping_options']) ){
				$_SESSION['temp_notifications'][] = array(
					'Group' => 'Specific',
					'Content' => 'You need to choose at least one shipping option',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					),
				);
				$tryAgain = TRUE;
			}
			
			if( !empty($_POST['name']) ){
				if( strlen($_POST['name']) < 5 || strlen($_POST['name']) > MAX_LENGTH_LISTING_NAME ){
					$_SESSION['listing_feedback']['name'] = 'This must be between 5 - '.MAX_LENGTH_LISTING_NAME.' characters';
					$tryAgain = true;
				}
				
				$name = trim(strip_tags($_POST['name']));
				$columns[] = "`Name`";
				$stmt_types .= 's';
				$stmt_params[] = &$name;
			} else {
				$_SESSION['listing_feedback']['name'] = 'This field is required';
				$tryAgain = true;
			}
			
			if( !empty($_POST['category']) && is_numeric($_POST['category']) && $_POST['category'] > 0 ){
				$columns[] = "`CategoryID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['category'];
			} else
				$tryAgain = true;
			
			if( !empty($_POST['price']) && is_numeric($_POST['price']) && $_POST['price'] > 0 ){
				$listing_price = $_POST['price'];
				
				$columns[] = "`Price`";
				$stmt_types .= 'd';
				$stmt_params[] = &$listing_price;
			} else {
				$_SESSION['listing_feedback']['price'] = 'This is not a valid price.';
				$tryAgain = true;
			}
			
			if( !empty($_POST['currency']) && is_numeric($_POST['currency']) ){
				
				$columns[] = "`CurrencyID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['currency'];
			} else
				$tryAgain = true;
			
			$listingPrice_BTC = NXS::convertCurrencies($this->db, $listing_price * $_POST['quantity_minimum'], $_POST['currency'], 1);
			/*if( NXS::compareFloatNumbers($listingPrice_BTC, LOWEST_PRICE_PROFITABLE_FOR_MARKET, '<') ){
				$_SESSION['listing_feedback']['price'] = 'Price cannot be lower than ' . LOWEST_PRICE_PROFITABLE_FOR_MARKET . ' BTC';
				$tryAgain = true;
			}*/
			
			$excerpt = FALSE;
			
			if( !empty($_POST['summary']) )
				$excerpt = trim(preg_replace('/\s+/', ' ', htmlspecialchars($_POST['summary'])));
			
			if( !empty($_POST['description']) ){
				$description = htmlspecialchars($_POST['description']);
				$columns[] = "`Description`";
				$stmt_types .= 's';
				$stmt_params[] = &$description;
				
				$html = NXS::formatText($_POST['description']);
				$columns[] = "`HTML`";
				$stmt_types .= 's';
				$stmt_params[] = &$html;
				
				if( !$excerpt )
					$excerpt = strip_tags(
						preg_replace(
							'/<p>((?:(?!<\/p).)*)<\/p>.*/s',
							'$1',
							$html,
							1
						)
					);
			}
			
			if($excerpt){
				$excerpt = str_replace('<br>', '', $excerpt);
			
				$columns[] = "`Excerpt`";
				$stmt_types .= 's';
				$stmt_params[] = &$excerpt;
			}
			
			$one = 1;
				
			$columns[] = "`Approved`";
			$stmt_types .= 'i';
			$stmt_params[] = &$one;
			
			if( isset($_POST['listing_active']) ){
				$zero = 0;
				
				$columns[] = "`Inactive`";
				$stmt_types .= 'i';
				$stmt_params[] = &$zero;
				
				if( $_POST['stock'] < 1 )
					$_POST['stock'] = 1;
			} else {
				$one = 1;
				
				$columns[] = "`Inactive`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
			}
			
			if( isset($_POST['listing_visible']) ){
				
				$zero = 0;
				
				$columns[] = "`Stealth`";
				$stmt_types .= 'i';
				$stmt_params[] = &$zero;
				
			} else {
				
				$one = 1;
				
				$columns[] = "`Stealth`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
				
			}
			
			if( !empty($_POST['quantity']) ){
				
				if( !preg_match('/\d{1,4}(?:\.\d{1,2})?/', $_POST['quantity']) || $_POST['quantity'] < 0.01 ){
					$_SESSION['listing_feedback']['quantity'] = 'This is not a valid quantity.';
					$tryAgain = true;
				}
				
				$columns[] = "`Quantity`";
				$stmt_types .= 'd';
				$stmt_params[] = &$_POST['quantity'];
				
			} else {
				$_SESSION['listing_feedback']['quantity'] = 'This cannot be empty.';
				$tryAgain = true;
			}
			
			if( !empty($_POST['unit']) ){
				
				$columns[] = "`UnitID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['unit'];
				
			} else
				$tryAgain = true;
			
			if ($_POST['stock'] < 0 || !$_POST['stock'])
				$_POST['stock'] = 0;
			
			$columns[] = "`Quantity_Left`";
			$stmt_types .= 'i';
			$stmt_params[] = &$_POST['stock'];
			
			if( !empty($_POST['critical_quantity']) ){
				$columns[] = "`Quantity_Critical`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['critical_quantity'];
			} else {
				$null = NULL;
				
				$columns[] = "`Quantity_Critical`";
				$stmt_types .= 'i';
				$stmt_params[] = &$null;
			}
			
			if(
				!empty($_POST['quantity_minimum']) &&
				filter_input(INPUT_POST, "quantity_minimum", FILTER_VALIDATE_INT) &&
				$_POST['quantity_minimum'] > 0
			){
				$columns[] = "`Quantity_Minimum`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['quantity_minimum'];
				
				if(
					$_POST['stock'] > 0 &&
					$_POST['stock'] < $_POST['quantity_minimum']
				)
					$_POST['stock'] = $_POST['quantity_minimum'];
			} else {
				$one = 1;
				
				$columns[] = "`Quantity_Minimum`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
			}
			
			if( isset($_POST['ships_to_continent']) ){
				$continents = $this->fetchContinentsCountries();
						
				foreach($continents as $continent_id => $continent){
					if( isset($continent['countries']) ){
						foreach($continent['countries'] as $country){
							$country_continents[ $country['id'] ] = $continent_id;
						}
					}
				}
				
				$shipping_name = $shipping_description = $shipping_price = $shipping_currency = $shipping_pricing_mode = $shipping_options = array();
				
				for( $i=1; $i <= 3; $i++ ){
					if( !empty($_POST['shipping-'.$i.'_name']) ){
						
						$shipping_name[$i] = strip_tags($_POST['shipping-'.$i.'_name']);
						$shipping_description[$i] = htmlspecialchars($_POST['shipping-'.$i.'_description']);
						$shipping_price[$i] = $_POST['shipping-'.$i.'_price'];
						$shipping_currency[$i] = $_POST['shipping-'.$i.'_currency'];
						$shipping_pricing_mode[$i] = $_POST['shipping-'.$i.'_pricing_mode'];
						
						if( strlen($shipping_name[$i]) < 3 || strlen($shipping_name[$i]) > MAX_LENGTH_LISTING_SHIPPING_NAME ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_name'] = 'This must be between 3 - '.MAX_LENGTH_LISTING_SHIPPING_NAME.' characters.';
							$tryAgain = true;
						} elseif( strlen($shipping_description[$i]) > MAX_LENGTH_LISTING_SHIPPING_DESCRIPTION ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_description'] = 'This must not excees '.MAX_LENGTH_LISTING_SHIPPING_DESCRIPTION.' characters.';
							$tryAgain = true;
						} elseif( !is_numeric($shipping_price[$i]) || $shipping_price[$i] < 0 || !isset($shipping_pricing_mode[$i]) || empty($shipping_currency[$i]) ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_price'] = 'This is not a valid price';
							$tryAgain = true;
						} else {
							
							$shipping_price[$i] = ($shipping_pricing_mode[$i] == 0) ? $shipping_price[$i]*(1+LISTING_FEE) : $shipping_price[$i];
							
							$shipping_options[$i] = array(
								'name' => &$shipping_name[$i],
								'description' => &$shipping_description[$i],
								'price' => &$shipping_price[$i],
								'currency' => &$shipping_currency[$i]
							);
						}	
					}
				}
				
				if( $_POST['ships_from'] !== 0 ){
					$columns[] = "`CountryID`";
					$stmt_types .= 'i';
					$stmt_params[] = &$_POST['ships_from'];
					
					if( substr($_POST['ships_from'], 0, 5 ) == 'cont_' && is_numeric( substr($_POST['ships_from'], 5) ) ){
						$from_continent_id = LISTING_ATTRIBUTE_FROM_CONTINENT;
						$from_continent_value = substr($_POST['ships_from'], 5);
						
						$listing_attributes[] = array(
							'id' => &$from_country_id,
							'value' => &$from_continent_value
						);
					} elseif ( is_numeric($_POST['ships_from']) && isset($country_continents[ $_POST['ships_from'] ]) ) {
						$from_country_id = LISTING_ATTRIBUTE_FROM_COUNTRY;
						$listing_attributes[] = array(
							'id' => &$from_country_id,
							'value' => &$_POST['ships_from']
						);
						
						$from_continent_id = LISTING_ATTRIBUTE_FROM_CONTINENT;
						$listing_attributes[] = array(
							'id' => &$from_continent_id,
							'value' => &$country_continents[ $_POST['ships_from'] ]
						);
					}
				}
			}
			
			if($tryAgain)
				return false;
			
			if( $stmt_insertListing = $this->db->prepare("
				INSERT INTO
					`Listing` (".implode(', ', $columns).", `DateAdded`)
				VALUES
					(?".str_repeat(', ?', (count($columns) - 1 )).", NOW())
			") ){
				
				$stmt_args = array_merge( array($stmt_types), $stmt_params);
				call_user_func_array( array($stmt_insertListing, 'bind_param'), $stmt_args );
				
				if ($stmt_insertListing->execute()){
					$new_id = $stmt_insertListing->insert_id;
					$id = &$new_id;
					
					$paymentMethods = array_filter(
						($_POST['payment_methods'] ?: []),
						function($paymentMethodID){
							return $this->checkUserPaymentMethod($paymentMethodID);
						}
					)
						?: [];
						
					$this->updateListingPaymentMethods(
						$paymentMethods,
						[$id],
						false
					);
					
					if( !empty($listing_attributes) && count($listing_attributes) > 0 ){
						if( $stmt_insertAttributes = $this->db->prepare("
							INSERT INTO
								`Listing_Attribute` (`ListingID`, `AttributeID`, `Value`)
							VALUE
								(?, ?, ?)".str_repeat(', (?, ?, ?)', ( count($listing_attributes) - 1 ))."
						") ){
							$stmt_attributes_types = '';
							$stmt_attributes_params = array();
							
							foreach($listing_attributes as $listing_attribute){
								$stmt_attributes_types .= 'iis';
								$stmt_attributes_params[] = &$id;
								$stmt_attributes_params[] = &$listing_attribute['id'];
								$stmt_attributes_params[] = &$listing_attribute['value'];
							}
							
							$stmt_attributes_args = array_merge( array($stmt_attributes_types), $stmt_attributes_params );
							
							call_user_func_array( array( $stmt_insertAttributes, 'bind_param' ), $stmt_attributes_args );
							
							$stmt_insertAttributes->execute();	
						}
					}
					
					if( isset($_POST['listing-image-ids']) ){
						$this->db->qQuery(
							"
								INSERT INTO
									`Listing_Image` (`ListingID`, `Primary`, `ImageID`)
								SELECT
									?,
									`Primary`,
									`ImageID`
								FROM
									`Listing_Image`
								WHERE
									`ID` IN (" . rtrim(str_repeat('?, ', count($_POST['listing-image-ids'])), ', ') . ")
							",
							'i' . str_repeat('i', count($_POST['listing-image-ids'])),
							array_merge(
								array($id),
								$_POST['listing-image-ids']
							),
							TRUE
						);
					}
					
					if (isset($_POST['uploads']['file'])){
						// Get Image Count
						$listingImageCount = $this->db->qSelect(
							"
								SELECT	COUNT(*) count
								FROM	`Listing_Image`
								WHERE	`ListingID` = ?
							",
							'i',
							array(
								$id
							)
						);
						$listingImageCount = $listingImageCount[0]['count'];
						
						if ($listingImageCount < LISTING_IMAGES_MAX){
							$primary	= $listingImageCount == 0;
							$this->db->qQuery(
								"
									INSERT INTO
										`Listing_Image` (
											`ListingID`,
											`Primary`,
											`ImageID`
										)
									VALUES
										(?, ?, ?)
								",
								'iii',
								[
									$id,
									$primary,
									$_POST['uploads']['file']
								]
							);
						}
					}
					
					if (!empty($_POST['ships_to_continent'])){
						$stmt_continents_types = '';
						$stmt_continents_params = array();
						
						foreach($_POST['ships_to_continent'] as $key => $ship_to_continent){
							
							if( !is_numeric($ship_to_continent) ) continue;
							
							$stmt_continents_types .= 'ii';
							$stmt_continents_params[] = &$id;
							$stmt_continents_params[] = &$_POST['ships_to_continent'][$key];
							
						}
						
						if( count($stmt_continents_params)/2 > 0 ) {
						
							if( $stmt_insertContinents = $this->db->prepare("
								INSERT INTO
									`Listing_Continent` (`ListingID`, `ContinentID`)
								VALUES
									(?, ?)".(str_repeat(', (?, ?)', ( count($stmt_continents_params)/2 - 1 )) )."
							") ){
								
								$stmt_continents_args = array_merge( array($stmt_continents_types), $stmt_continents_params );
								
								call_user_func_array( array($stmt_insertContinents, 'bind_param'), $stmt_continents_args );
								
								$stmt_insertContinents->execute();
								
							}
							
							if ( !empty( $_POST['ships_to_country']) ){
							
								$stmt_countries_types = '';
								$stmt_countries_params = array();
								
								foreach( $_POST['ships_to_country'] as $key => $ship_to_country ){
									
									if( !is_numeric($ship_to_country) ) continue;
									
									if( !in_array( $country_continents[$ship_to_country], $_POST['ships_to_continent']) ) continue;
									
									$stmt_countries_types .= 'ii';
									$stmt_countries_params[] = &$id;
									$stmt_countries_params[] = &$_POST['ships_to_country'][$key];
									
								}
								
								if( count($stmt_countries_params)/2 > 0 ){
									
									if( $stmt_insertCountries = $this->db->prepare("
										INSERT INTO
											`Listing_Country` (`ListingID`, `CountryID`)
										VALUE
											(?, ?)".( str_repeat(', (?, ?)', ( count($stmt_countries_params)/2  - 1 ) ) )."
									") ){
										
										$stmt_countries_args = array_merge( array($stmt_countries_types), $stmt_countries_params );
										
										call_user_func_array( array($stmt_insertCountries, 'bind_param'), $stmt_countries_args );
										
										$stmt_insertCountries->execute();
										
									}
									
								}
								
							}
						
						}
					}
					
					
					if( !empty($_POST['shipping_options']) )
						foreach( $_POST['shipping_options'] as $shipping_option )
							$this->db->qQuery(
								"
									INSERT INTO
										`Listing_Shipping` (`ListingID`, `ShippingID`)
									VALUES
										(?, ?)
								",
								'ii',
								array(
									$id,
									$shipping_option
								)
							);
					
					if(
						isset($_POST['enable_grouping']) &&
						!empty($_POST['group_listings'])
					){
						$syncDescriptions = 
							isset($_POST['group_sync_descriptions']) &&
							(
								!isset($_POST['description_original']) ||
								$_POST['description_original'] == $_POST['description']
							) &&
							(
								!isset($_POST['excerpt_original']) ||
								$_POST['excerpt_original'] == $_POST['summary']
							);
						
						$syncImages =
							isset($_POST['group_sync_images']) &&
							!isset($_POST['delete_pic']) &&
							!isset($_POST['make_pic_primary']) &&
							!isset($_POST['uploads']['file']);
						
						$syncShipping =
							isset($_POST['group_sync_shipping']) &&
							(
								!isset($_POST['original_shipping_options']) ||
								$_POST['original_shipping_options'] == $_POST['shipping_options']
							);
						
						$this->updateListingGroupOptions(
							$id,
							isset($_POST['group_id']) ? $_POST['group_id'] : FALSE,
							$this->_parseListingGroupLabels(),
							[
								LISTING_GROUP_SETTING_SYNCHRONIZE_IMAGES_DB_COLUMN => $syncImages,
								LISTING_GROUP_SETTING_SYNCHRONIZE_DESCRIPTIONS_DB_COLUMN => $syncDescriptions,
								LISTING_GROUP_SETTING_SYNCHRONIZE_SHIPPING_DB_COLUMN => $syncShipping,
								LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN => isset($_POST['group_sync_stock'])
							],
							$_POST['group_label']
						);
					}
					
					if(
						isset($_POST['enable_promos']) &&
						!empty($_POST['promo_code-new-code'])
					){
						$this->updateListingPromoCodes(
							$id,
							$this->parseListingPromoCodes($listingPrice_BTC)
						);
					}
					
					// GREAT SUCCESS
					
					$this->fixVendorPublicKey();
					
					return $id;	
				}	
			}	
		}
	}
	
	private function _parseListingGroupLabels(){
		if( !empty($_POST['group_listings']) ){
			$groupListingIDs = [];
			foreach($_POST['group_listings'] as $groupListingID){
				$label = $_POST['group_listing-' . $groupListingID . '-label'];
				$groupListingIDs[$groupListingID] =
					$label &&
					!in_array(
						$label,
						$groupListingIDs,
						true
					)
						? $label
						: null;
			}
				
			return $groupListingIDs;
		}
		
		return false;
	}
	
	private function _insertListingPromoCode(
		$listingID,
		$code,
		$discount,
		$currencyID,
		$quantity
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Listing_PromoCode` (
						`ListingID`,
						`Code`,
						`Discount`,
						`CurrencyID`,
						`Quantity`
					)
				VALUES (
					?,
					?,
					?,
					?,
					?		
				)
			",
			'isiii',
			[
				$listingID,
				$code,
				$discount,
				$currencyID ?: NULL,
				$quantity
			]
		);
	}
	
	private function _updateListingPromoCode(
		$ID,
		$code,
		$discount,
		$currencyID,
		$quantity
	){
		$this->db->qQuery(
			"
				UPDATE IGNORE
					`Listing_PromoCode`
				SET
					`Code` = ?,
					`Discount` = ?,
					`CurrencyID` = ?,
					`Quantity` = ?
				WHERE
					`ID` = ?
			",
			'siiii',
			[
				$code,
				$discount,
				$currencyID ?: NULL,
				$quantity,
				$ID
			]
		);
		
		return true;
	}
	
	private function _clearListingPromoCodes(
		$listingID,
		$exemptedPromoCodeIDs = false
	){
		$exemptedPromoCodeIDs = $exemptedPromoCodeIDs ?: [];
		return $this->db->qQuery(
			"
				DELETE FROM
					`Listing_PromoCode`
				WHERE
					`ListingID` = ? " . (
						$exemptedPromoCodeIDs
							? "AND `ID` NOT IN (" . rtrim(str_repeat('?, ', count($exemptedPromoCodeIDs)),', ') . ")"
							: false
					) . "
			",
			str_repeat('i', count($exemptedPromoCodeIDs) + 1),
			array_merge(
				[$listingID],
				$exemptedPromoCodeIDs
			)
		);
	}
	
	private function updateListingPromoCodes(
		$listingID,
		$promoCodes
	){
		$promoCodeIDs = [];
		
		if($promoCodes)
			foreach($promoCodes as $promoCode){
				if( isset($promoCode['ID']) ){
					$this->_updateListingPromoCode(
						$promoCode['ID'],
						$promoCode['code'],
						$promoCode['discount'],
						$promoCode['currencyID'],
						$promoCode['quantity']
					);
					$promoCodeIDs[] = $promoCode['ID'];
				} else
					$promoCodeIDs[] = $this->_insertListingPromoCode(
						$listingID,
						$promoCode['code'],
						$promoCode['discount'],
						$promoCode['currencyID'],
						$promoCode['quantity']
					);
			}
		
		return $this->_clearListingPromoCodes(
			$listingID,
			$promoCodeIDs
		);
	}
	
	private function _clearListingGroup($groupID){
		return $this->db->qQuery(
			"
				DELETE FROM
					`Listing_Group`
				WHERE
					`Listing_Group`.`GroupID` = ?
			",
			'i',
			[$groupID]
		);
	}
	
	private function _insertListingsIntoGroup(
		$groupID,
		$listingIDs
	){
		$insertedCount = 0;
		
		foreach($listingIDs as $listingID => $label)
			if(
				$this->db->qQuery(
					"
						INSERT IGNORE INTO
							`Listing_Group` (`ListingID`, `GroupID`, `Label`)
						VALUES
							(?, ?, ?)
					",
					'iis',
					[
						$listingID,
						$groupID,
						$label
					]
				)
			)
				$insertedCount++;
		
		return $insertedCount == count($listingIDs);
	}
	
	private function _insertListingGroup(){
		return $this->db->qQuery(
			"
				INSERT INTO
					`ListingGroup`
				VALUES
					()
			"
		);
	}
	
	private function _updateListingGroupSettings(
		$groupID,
		$groupSettings
	){
		if(count($groupSettings) > 0){
			foreach($groupSettings as $setting => $value){
				$setClause[] = '`' . $setting . '` = ?';
			}
		
			return $this->db->qQuery(
				"
					UPDATE
						`ListingGroup`
					SET
						" . implode(', ', $setClause) . "
					WHERE
						`ID` = ?
				",
				str_repeat('i', count($groupSettings) + 1),
				array_merge(
					$groupSettings,
					[$groupID]
				)
			);
		}
		
		return FALSE;
	}
	
	private function _isMyListingGroup($groupID){
		return $this->db->qSelect(
			"
				SELECT
					COUNT(DISTINCT `Listing_Group`.`GroupID`) isMyListingGroup
				FROM
					`Listing_Group`
				INNER JOIN
					`Listing` ON
						`Listing_Group`.`ListingID` = `Listing`.`ID`
				WHERE
					`Listing`.`VendorID` = ? AND
					`Listing_Group`.`GroupID` = ?
			",
			'ii',
			[
				$this->User->ID,
				$groupID
			]
		)[0]['isMyListingGroup'];
	}
	
	private function updateListingGroupOptions(
		$listingID,
		$groupID,
		$groupListingIDs,
		$groupSettings = FALSE,
		$groupLabel = NULL
	){
		if(
			$groupID &&
			!$this->_isMyListingGroup($groupID)
		)
			return false;
		
		$groupListingIDs[$listingID] =
			$groupLabel &&
			!in_array(
				$groupLabel,
				$groupListingIDs,
				true
			)
				? $groupLabel
				: NULL;
		
		if ($groupID)
			$this->_clearListingGroup($groupID);
		else
			$groupID = $this->_insertListingGroup();
			
		$this->_insertListingsIntoGroup(
			$groupID,
			$groupListingIDs
		);
		
		if (
			isset($groupSettings[LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN]) &&
			$groupSettings[LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN] &&
			!$this->_canSynchronizeStockInListingGroup($groupID)
		){
			$groupSettings[LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN] = false;
			$_SESSION['temp_notifications'][] = array(
				'Group' => 'Specific',
				'Content' => 'For their stock to be synchronized, listings must have interconvertible units.',
				'Design' => array(
					'Color' => 'red',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				),
			);
		}
		
		if($groupSettings)
			$this->_updateListingGroupSettings(
				$groupID,
				$groupSettings
			);
		
		$this->synchronizeListingGroup(
			$groupID,
			$listingID
		);
			
		return FALSE;
	}
	
	private function synchronizeListingGroup(
		$groupID,
		$targetListingID
	){
		if(
			$listingGroup = $this->_getListingGroup($targetListingID)
		){
			$listingIDs = $this->fetchListingGroupMemberIDs($targetListingID, TRUE);
			
			$this->_synchronizeListingCategories(
				$targetListingID,
				$listingIDs
			);
			
			$this->_synchronizeListingShipping(
				$targetListingID,
				$listingIDs,
				$listingGroup[LISTING_GROUP_SETTING_SYNCHRONIZE_SHIPPING_DB_COLUMN]
			);
			
			if ($listingGroup[LISTING_GROUP_SETTING_SYNCHRONIZE_IMAGES_DB_COLUMN])
				$this->_synchronizeListingImages(
					$targetListingID,
					$listingIDs
				);
				
			if ($listingGroup[LISTING_GROUP_SETTING_SYNCHRONIZE_DESCRIPTIONS_DB_COLUMN])
				$this->_synchronizeListingDescriptions(
					$targetListingID,
					$listingIDs
				);
			
			if ($listingGroup[LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN])
				$this->setListingGroupStock(
					$targetListingID,
					(
						isset($_POST['unit']) &&
						!isset($_POST['group_stock'])
							? $_POST['unit']
							: $listingGroup['UnitID']
					),
					(
						isset(
							$_POST['stock'],
							$_POST['quantity']
						) &&
						!isset($_POST['group_stock'])
							? ($_POST['stock'] * $_POST['quantity'])
							: $listingGroup['Stock']
					)
				);
			else
				$this->_clearListingGroupStock($groupID);
			
			return TRUE;
		}
			
		return FALSE;
	}
	
	private function _clearListingGroupStock($groupID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Listing_Group`
					INNER JOIN
						`Listing` ON
							`Listing_Group`.`ListingID` = `Listing`.`ID`
					SET
						`Listing`.`Inactive` = IF(
							`Listing`.`Quantity_Left` < `Listing`.`Quantity_Minimum`,
							TRUE,
							`Listing`.`Inactive`
						),
						`Listing_Group`.`OutOfStock` = FALSE
					WHERE
						`Listing_Group`.`GroupID` = ?
				",
				'i',
				[$groupID]
			);
	}
	
	private function _synchronizeListingCategories( // and shippingOrigin
		$targetListingID,
		$listingIDs
	){
		return $this->db->qQuery(
			"
				UPDATE
					`Listing`
				INNER JOIN
					`Listing` targetListing ON
						targetListing.`ID` = ?
				SET
					`Listing`.`CategoryID` = targetListing.`CategoryID`,
					`Listing`.`CountryID` = targetListing.`CountryID`
				WHERE
					`Listing`.`ID` IN (" . rtrim(str_repeat('?, ', count($listingIDs)),', ') . ")
			",
			str_repeat('i', count($listingIDs) + 1),
			array_merge(
				[$targetListingID],
				$listingIDs
			)
		);
	}
	
	private function _synchronizeListingDescriptions(
		$targetListingID,
		$listingIDs
	){
		return $this->db->qQuery(
			"
				UPDATE
					`Listing`
				INNER JOIN
					`Listing` targetListing ON
						targetListing.`ID` = ?
				SET
					`Listing`.`Description` = targetListing.`Description`,
					`Listing`.`Excerpt` = targetListing.`Excerpt`,
					`Listing`.`HTML` = targetListing.`HTML`
				WHERE
					`Listing`.`ID` IN (" . rtrim(str_repeat('?, ', count($listingIDs)),', ') . ")
			",
			str_repeat('i', count($listingIDs) + 1),
			array_merge(
				[$targetListingID],
				$listingIDs
			)
		);
	}
	
	private function _clearListingShipping(
		$listingIDs,
		$includeOptions
	){
		return $this->db->qQuery(
			"
				DELETE
					`Listing_Continent`,
					`Listing_Country`
					" . (
						$includeOptions
							? ',`Listing_Shipping`'
							: false
					) . "
				FROM
					`Listing`
				LEFT JOIN
					`Listing_Continent` ON
						`Listing`.`ID` = `Listing_Continent`.`ListingID`
				LEFT JOIN
					`Listing_Country` ON
						`Listing`.`ID` = `Listing_Country`.`ListingID`
				" . (
					$includeOptions
						? "
							LEFT JOIN
								`Listing_Shipping` ON
									`Listing`.`ID` = `Listing_Shipping`.`ListingID`
						"
						: false
				) . "
				WHERE
					`Listing`.`ID` IN (" . rtrim(str_repeat('?, ', count($listingIDs)),', ') . ")
			",
			str_repeat('i', count($listingIDs)),
			$listingIDs
		);
	}
	
	private function _synchronizeListingShippingContinents(
		$listingID,
		$targetListingID
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Listing_Continent` (
						`ListingID`,
						`ContinentID`
					)
				SELECT
					?,
					`ContinentID`
				FROM
					`Listing_Continent`
				WHERE
					`ListingID` = ?
			",
			'ii',
			[
				$listingID,
				$targetListingID
			]
		);
	}
	
	private function _synchronizeListingShippingCountries(
		$listingID,
		$targetListingID
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Listing_Country` (
						`ListingID`,
						`CountryID`
					)
				SELECT
					?,
					`CountryID`
				FROM
					`Listing_Country`
				WHERE
					`ListingID` = ?
			",
			'ii',
			[
				$listingID,
				$targetListingID
			]
		);
	}
	
	private function _synchronizeListingShippingOptions(
		$listingID,
		$targetListingID
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Listing_Shipping` (
						`ListingID`,
						`ShippingID`
					)
				SELECT
					?,
					`ShippingID`
				FROM
					`Listing_Shipping`
				WHERE
					`ListingID` = ?
			",
			'ii',
			[
				$listingID,
				$targetListingID
			]
		);
	}
	
	private function _synchronizeListingShipping(
		$targetListingID,
		$listingIDs,
		$synchronizeOptions = true
	){
		$this->_clearListingShipping(
			$listingIDs,
			$synchronizeOptions
		);
		
		foreach($listingIDs as $listingID){
			$this->_synchronizeListingShippingContinents(
				$listingID,
				$targetListingID
			);
			$this->_synchronizeListingShippingCountries(
				$listingID,
				$targetListingID
			);
			
			if($synchronizeOptions)
				$this->_synchronizeListingShippingOptions(
					$listingID,
					$targetListingID
				);
		}
			
		return TRUE;
	}
	
	private function _clearListingImages($listingIDs){
		return $this->db->qQuery(
			"
				DELETE FROM
					`Listing_Image`
				WHERE
					`ListingID` IN (" . rtrim(str_repeat('?, ', count($listingIDs)),', ') . ")
			",
			str_repeat('i', count($listingIDs)),
			$listingIDs
		);
	}
	
	private function _synchronizeListingImages(
		$targetListingID,
		$listingIDs
	){
		$this->_clearListingImages($listingIDs);
		
		foreach($listingIDs as $listingID)
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Listing_Image` (
							`ListingID`,
							`Primary`,
							`ImageID`
						)
					SELECT
						?,
						`Primary`,
						`ImageID`
					FROM
						`Listing_Image`
					WHERE
						`ListingID` = ?
				",
				'ii',
				[
					$listingID,
					$targetListingID
				]
			);
			
		return TRUE;
	}
	
	private function fetchListingGroupMemberIDs(
		$listingID,
		$excludeListing = TRUE
	){
		if(
			$listings = $this->db->qSelect(
				"
					SELECT
						DISTINCT `Listing_Group`.`ListingID`
					FROM
						`Listing_Group`
					INNER JOIN
						`Listing` ON
							`Listing`.`ID` = ?
					WHERE
						`Listing_Group`.`GroupID` = (
							SELECT
								`GroupID`
							FROM
								`Listing_Group`
							WHERE
								`ListingID` = `Listing`.`ID`
						) " . (
							$excludeListing
								? 'AND `Listing_Group`.`ListingID` != `Listing`.`ID`'
								: FALSE
						) . "
				",
				'i',
				[$listingID]
			)
		)
			return array_map(
				function($row){
					return $row['ListingID'];
				},
				$listings
			);
		
		return [];
	}
	
	private function _removeSingularListingGroups(){
		return $this->db->qQuery(
			"
				DELETE FROM
					`ListingGroup`
				WHERE
					(
						SELECT
							COUNT(DISTINCT `Listing_Group`.`ListingID`)
						FROM
							`Listing_Group`
						WHERE
							`Listing_Group`.`GroupID` = `ListingGroup`.`ID`
					) < 2
			"
		);
	}
	
	private function removeListingFromGroup($listingID){
		if(
			$this->db->qQuery(
				"
					DELETE FROM
						`Listing_Group`
					WHERE
						`ListingID` = ?
				",
				'i',
				[$listingID]
			)
		)
			return $this->_removeSingularListingGroups();
		
		return FALSE;
	}
	
	public function edit_listing($listing_id){
		if( !empty($_POST) ) {
			if( !empty($_POST['delete_pic']) )
				$this->deleteListingPicture($_POST['delete_pic']);
			elseif( !empty($_POST['make_pic_primary']) )
				$this->makeListingPicturePrimary($listing_id, $_POST['make_pic_primary']);
			
			$tryAgain = false;
			
			foreach($_POST as $key => $value)
				$_SESSION['listing_post'][$key] = 
					is_array($value)
						? array_map(
							'htmlspecialchars',
							$value
						)
						: htmlspecialchars($value);
			
			if( empty($_POST['shipping_options']) ){
				$_SESSION['temp_notifications'][] = array(
					'Group' => 'Specific',
					'Content' => 'You need to choose at least one shipping option',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					),
				);
				return FALSE;
			}
			
			$update = array();
			$stmt_types = '';
			$stmt_params = array();
			
			$big_ole_zero = 1;
				
			$update[] = "`Approved`";
			$stmt_types .= 'i';
			$stmt_params[] = &$big_ole_zero;
			
			if( !empty($_POST['name']) ){
				
				if( strlen($_POST['name']) < 5 || strlen($_POST['name']) > MAX_LENGTH_LISTING_NAME ){
					$_SESSION['listing_feedback']['name'] = 'This must be between 5 - '.MAX_LENGTH_LISTING_NAME.' characters';
					$tryAgain = true;
				}
				
				$name = trim(strip_tags($_POST['name']));
				$update[] = "`Name`";
				$stmt_types .= 's';
				$stmt_params[] = &$name;
			} else {
				$_SESSION['listing_feedback']['name'] = 'This field is required';
				$tryAgain = true;
			}
			
			if( !empty($_POST['category']) && is_numeric($_POST['category']) && $_POST['category'] > 0 ){
				
				if( isset($_SESSION['edit_listing_allowed_categories']) && !in_array($_POST['category'], $_SESSION['edit_listing_allowed_categories']) ){
					$_SESSION['listing_feedback']['category'] = 'NOPE';
					$tryAgain = true;
				}
				
				$update[] = "`CategoryID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['category'];
				
			} else
				$tryAgain = true;
			
			if( !empty($_POST['price']) && is_numeric($_POST['price']) ){
				$listing_price = $_POST['price'];
				
				$update[] = "`Price`";
				$stmt_types .= 'd';
				$stmt_params[] = &$listing_price;
			} else {
				$_SESSION['listing_feedback']['price'] = 'This is not a valid price.';
				$tryAgain = true;
			}
			
			if( !empty($_POST['currency']) && is_numeric($_POST['currency']) ){
				
				$update[] = "`CurrencyID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['currency'];
			} else
				$tryAgain = true;
			
			$listingPrice_BTC = NXS::convertCurrencies($this->db, $listing_price * $_POST['quantity_minimum'], $_POST['currency'], CURRENCY_ID_BTC);
			/*if( NXS::compareFloatNumbers($listingPrice_BTC, LOWEST_PRICE_PROFITABLE_FOR_MARKET, '<') ){
				$_SESSION['listing_feedback']['price'] = 'Price cannot be lower than ' . LOWEST_PRICE_PROFITABLE_FOR_MARKET . ' BTC';
				$tryAgain = true;
			}*/
			
			$excerpt = FALSE;
			
			if( !empty($_POST['summary']) )
				$excerpt = trim(preg_replace('/\s+/', ' ', htmlspecialchars($_POST['summary'])));
			
			if( !empty($_POST['description']) ){
				$description = htmlspecialchars($_POST['description']);
				$update[] = "`Description`";
				$stmt_types .= 's';
				$stmt_params[] = &$description;
				
				$html = NXS::formatText($_POST['description']);
				$update[] = "`HTML`";
				$stmt_types .= 's';
				$stmt_params[] = &$html;
				
				if( !$excerpt )
					$excerpt = strip_tags(
						preg_replace(
							'/<p>((?:(?!<\/p).)*)<\/p>.*/s',
							'$1',
							$html,
							1
						)
					);
				
			}
			
			if($excerpt){
				$excerpt = str_replace('<br>', '', $excerpt);
			
				$update[] = "`Excerpt`";
				$stmt_types .= 's';
				$stmt_params[] = &$excerpt;
			}
			
			if( isset($_POST['listing_active']) ){
				$zero = 0;
				
				$update[] = "`Inactive`";
				$stmt_types .= 'i';
				$stmt_params[] = &$zero;
				
				if( $_POST['stock'] < 1 )
					$_POST['stock'] = 1;
			} else {
				$one = 1;
				
				$update[] = "`Inactive`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
			}
			
			if( isset($_POST['listing_visible']) ){
				
				$zero = 0;
				
				$update[] = "`Stealth`";
				$stmt_types .= 'i';
				$stmt_params[] = &$zero;
				
			} else {
				
				$one = 1;
				
				$update[] = "`Stealth`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
				
			}
			
			if( !empty($_POST['quantity']) ){
				
				if( !preg_match('/\d{1,4}(?:\.\d{1,2})?/', $_POST['quantity']) || $_POST['quantity'] < 0.01 ){
					$_SESSION['listing_feedback']['quantity'] = 'This is not a valid quantity.';
					$tryAgain = true;
				}
				
				$update[] = "`Quantity`";
				$stmt_types .= 'd';
				$stmt_params[] = &$_POST['quantity'];
				
			} else {
				$_SESSION['listing_feedback']['quantity'] = 'This cannot be empty.';
				$tryAgain = true;
			}
			
			if( !empty($_POST['unit']) ){
				
				$update[] = "`UnitID`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['unit'];
				
			} else
				return false;
			
			if( $_POST['stock'] < 0 )
				$_POST['stock'] = 0;
			
			$update[] = "`Quantity_Left`";
			$stmt_types .= 'i';
			$stmt_params[] = &$_POST['stock'];
			
			if(
				!empty($_POST['quantity_minimum']) &&
				filter_input(INPUT_POST, "quantity_minimum", FILTER_VALIDATE_INT) &&
				$_POST['quantity_minimum'] > 0
			){
				$update[] = "`Quantity_Minimum`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['quantity_minimum'];
				
				if(
					$_POST['stock'] > 0 &&
					$_POST['stock'] < $_POST['quantity_minimum']
				)
					$_POST['stock'] = $_POST['quantity_minimum'];
			} else {
				$one = 1;
				
				$update[] = "`Quantity_Minimum`";
				$stmt_types .= 'i';
				$stmt_params[] = &$one;
			}
			
			if( !empty($_POST['critical_quantity']) ){
				$update[] = "`Quantity_Critical`";
				$stmt_types .= 'i';
				$stmt_params[] = &$_POST['critical_quantity'];
			} else {
				$null = NULL;
				
				$update[] = "`Quantity_Critical`";
				$stmt_types .= 'i';
				$stmt_params[] = &$null;
			}
			
			if( isset($_POST['ships_to_continent']) ){
				$continents = $this->fetchContinentsCountries();
						
				foreach($continents as $continent_id => $continent){
					if( isset($continent['countries']) ){
						foreach($continent['countries'] as $country){
							$country_continents[ $country['id'] ] = $continent_id;
						}
					}
				}
				
				$shipping_name = $shipping_description = $shipping_price = $shipping_currency = $shipping_pricing_mode = $shipping_options = array();
				
				for( $i=1; $i <= 3; $i++ ){
					
					if( !empty($_POST['shipping-'.$i.'_name']) ){
						
						$shipping_name[$i] = strip_tags($_POST['shipping-'.$i.'_name']);
						$shipping_description[$i] = strip_tags($_POST['shipping-'.$i.'_description']);
						$shipping_price[$i] = $_POST['shipping-'.$i.'_price'];
						$shipping_currency[$i] = $_POST['shipping-'.$i.'_currency'];
						$shipping_pricing_mode[$i] = $_POST['shipping-'.$i.'_pricing_mode'];
						
						if( strlen($shipping_name[$i]) < 3 || strlen($shipping_name[$i]) > MAX_LENGTH_LISTING_SHIPPING_NAME ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_name'] = 'This must be between 3 - '.MAX_LENGTH_LISTING_SHIPPING_NAME.' characters.';
							$tryAgain = true;
						} elseif( strlen($shipping_description[$i]) > MAX_LENGTH_LISTING_SHIPPING_DESCRIPTION ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_description'] = 'This must not excees '.MAX_LENGTH_LISTING_SHIPPING_DESCRIPTION.' characters.';
							$tryAgain = true;
						} elseif( !is_numeric($shipping_price[$i]) || $shipping_price[$i] < 0 || !isset($shipping_pricing_mode[$i]) || empty($shipping_currency[$i]) ){
							$_SESSION['listing_feedback']['shipping-'.$i.'_price'] = 'This is not a valid price';
							$tryAgain = true;
						} else {
							
							$shipping_price[$i] = ($shipping_pricing_mode[$i] == 0) ? $shipping_price[$i]*(1+LISTING_FEE) : $shipping_price[$i];
							
							$shipping_options[$i] = array(
								'name' => &$shipping_name[$i],
								'description' => &$shipping_description[$i],
								'price' => &$shipping_price[$i],
								'currency' => &$shipping_currency[$i]
							);	
						}	
					}	
				}
				
				if( $_POST['ships_from'] !== 0 ){
					$update[] = "`CountryID`";
					$stmt_types .= 'i';
					$stmt_params[] = &$_POST['ships_from'];
					
					if( substr($_POST['ships_from'], 0, 5 ) == 'cont_' && is_numeric( substr($_POST['ships_from'], 5) ) ){
						$ships_from_var = substr($_POST['ships_from'], 5);
						
						$listing_attributes[] = array(
							'id' => LISTING_ATTRIBUTE_FROM_CONTINENT,
							'value' => &$ships_from_var
						);
						
					} elseif ( is_numeric($_POST['ships_from']) && isset($country_continents[ $_POST['ships_from'] ]) ) {
						
						$from_country_id = LISTING_ATTRIBUTE_FROM_COUNTRY;
						$listing_attributes[] = array(
							'id' => &$from_country_id,
							'value' => &$_POST['ships_from']
						);
						
						$from_continent_id = LISTING_ATTRIBUTE_FROM_CONTINENT;
						$listing_attributes[] = array(
							'id' => &$from_continent_id,
							'value' => &$country_continents[ $_POST['ships_from'] ]
						);
						
					}
				}
				
			}
			
			if($tryAgain)
				return false;
			
			$stmt_types .= 'ii';
			$stmt_params[] = &$listing_id;
			$stmt_params[] = &$this->User->ID;
			
			if( $stmt_checkListing = $this->db->prepare("
				SELECT
					COUNT(`ID`)
				FROM
					`Listing`
				WHERE
					`ID` = ?
				AND	`VendorID` = ?
			") ){
				
				$stmt_checkListing->bind_param('ii', $listing_id, $this->User->ID);
				$stmt_checkListing->execute();
				$stmt_checkListing->store_result();
				$stmt_checkListing->bind_result($listing_count);
				$stmt_checkListing->fetch();
				
				if( $listing_count < 1 )
					return false;
				
			}
			
			if( $stmt_updateListing = $this->db->prepare("
				UPDATE
					`Listing`
				SET
					".implode(' = ?, ', $update)." = ?
				WHERE
					`ID` = ?
				AND	`VendorID` = ?
			") ){
				
				$stmt_args = array_merge( array($stmt_types), $stmt_params);
				call_user_func_array( array($stmt_updateListing, 'bind_param'), $stmt_args );
				
				if( $stmt_updateListing->execute() ){
					
					$this->updateListingPaymentMethods(
						array_filter(
							$_POST['payment_methods'],
							function($paymentMethodID){
								return $this->checkUserPaymentMethod($paymentMethodID);
							}
						),
						[$listing_id],
						false
					);
					
					// CLEAN UP ATTRIBUTES AND REST
					if( $stmt_cleanUp = $this->db->prepare("
						DELETE
							`Listing_Continent`,
							`Listing_Country`,
							`Listing_Shipping`,
							`Listing_Attribute`
						FROM
							`Listing`
						LEFT JOIN	`Listing_Continent`
							ON `Listing`.`ID` = `Listing_Continent`.`ListingID`
						LEFT JOIN	`Listing_Country`
							ON `Listing`.`ID` = `Listing_Country`.`ListingID`
						LEFT JOIN	`Listing_Shipping`
							ON `Listing`.`ID` = `Listing_Shipping`.`ListingID`
						LEFT JOIN	`Listing_Attribute`
							ON	`Listing`.`ID` = `Listing_Attribute`.`ListingID`
						WHERE
							`Listing`.`ID` = ?
						AND	`Listing`.`VendorID` = ?
					") ){
						
						$stmt_cleanUp->bind_param('ii', $listing_id, $this->User->ID);
						$stmt_cleanUp->execute();
						
					}
					
					$id = &$listing_id;
					
					if( !empty($listing_attributes) && count($listing_attributes) > 0 ){
						
						if( $stmt_insertAttributes = $this->db->prepare("
							INSERT INTO
								`Listing_Attribute` (`ListingID`, `AttributeID`, `Value`)
							VALUE
								(?, ?, ?)".str_repeat(', (?, ?, ?)', ( count($listing_attributes) - 1 ))."
						") ){
							
							$stmt_attributes_types = '';
							$stmt_attributes_params = array();
							
							foreach($listing_attributes as $listing_attribute){
								$stmt_attributes_types .= 'iis';
								$stmt_attributes_params[] = &$id;
								$stmt_attributes_params[] = &$listing_attribute['id'];
								$stmt_attributes_params[] = &$listing_attribute['value'];
							}
							
							$stmt_attributes_args = array_merge( array($stmt_attributes_types), $stmt_attributes_params );
							
							call_user_func_array( array( $stmt_insertAttributes, 'bind_param' ), $stmt_attributes_args );
							
							$stmt_insertAttributes->execute();
							
						}
					
					}
					
					if( !empty( $_POST['ships_to_continent'] ) ){
						
						$stmt_continents_types = '';
						$stmt_continents_params = array();
						
						foreach($_POST['ships_to_continent'] as $key => $ship_to_continent){
							
							if( !is_numeric($ship_to_continent) ) continue;
							
							$stmt_continents_types .= 'ii';
							$stmt_continents_params[] = &$id;
							$stmt_continents_params[] = &$_POST['ships_to_continent'][$key];
							
						}
						
						if( count($stmt_continents_params)/2 > 0 ) {
						
							if( $stmt_insertContinents = $this->db->prepare("
								INSERT INTO
									`Listing_Continent` (`ListingID`, `ContinentID`)
								VALUES
									(?, ?)".(str_repeat(', (?, ?)', ( count($stmt_continents_params)/2 - 1 )) )."
							") ){
								
								$stmt_continents_args = array_merge( array($stmt_continents_types), $stmt_continents_params );
								
								call_user_func_array( array($stmt_insertContinents, 'bind_param'), $stmt_continents_args );
								
								$stmt_insertContinents->execute();
								
							}
							
							if ( !empty( $_POST['ships_to_country']) ){
							
								$stmt_countries_types = '';
								$stmt_countries_params = array();
								
								foreach( $_POST['ships_to_country'] as $key => $ship_to_country ){
									
									if( !is_numeric($ship_to_country) ) continue;
									
									if( !in_array( $country_continents[$ship_to_country], $_POST['ships_to_continent']) ) continue;
									
									$stmt_countries_types .= 'ii';
									$stmt_countries_params[] = &$id;
									$stmt_countries_params[] = &$_POST['ships_to_country'][$key];
									
								}
								
								if( count($stmt_countries_params)/2 > 0 ){
									
									if( $stmt_insertCountries = $this->db->prepare("
										INSERT INTO
											`Listing_Country` (`ListingID`, `CountryID`)
										VALUE
											(?, ?)".( str_repeat(', (?, ?)', ( count($stmt_countries_params)/2  - 1 ) ) )."
									") ){
										
										$stmt_countries_args = array_merge( array($stmt_countries_types), $stmt_countries_params );
										
										call_user_func_array( array($stmt_insertCountries, 'bind_param'), $stmt_countries_args );
										
										$stmt_insertCountries->execute();
										
									}
									
								}
								
							}
						
						}
						
					}
					
					if (isset($_POST['uploads']['file'])){
						// Get Image Count
						$listingImageCount = $this->db->qSelect(
							"
								SELECT	COUNT(*) count
								FROM	`Listing_Image`
								WHERE	`ListingID` = ?
							",
							'i',
							array(
								$listing_id
							)
						);
						$listingImageCount = $listingImageCount[0]['count'];
						
						if ($listingImageCount < LISTING_IMAGES_MAX){
							$primary		= $listingImageCount == 0;
							$this->db->qQuery(
								"
									INSERT INTO
										`Listing_Image` (
											`ListingID`,
											`Primary`,
											`ImageID`
										)
									VALUES
										(?, ?, ?)
								",
								'iii',
								[
									$listing_id,
									$primary,
									$_POST['uploads']['file']
								]
							);
						}
					}
					
					if( !empty($_POST['shipping_options']) )
						foreach( $_POST['shipping_options'] as $shipping_option )
							$this->db->qQuery(
								"
									INSERT INTO
										`Listing_Shipping` (`ListingID`, `ShippingID`)
									VALUES
										(?, ?)
								",
								'ii',
								array(
									$id,
									$shipping_option
								)
							);
					
					if(
						isset($_POST['enable_grouping']) &&
						!empty($_POST['group_listings'])
					)
						$this->updateListingGroupOptions(
							$listing_id,
							isset($_POST['group_id']) ? $_POST['group_id'] : FALSE,
							$this->_parseListingGroupLabels(),
							[
								LISTING_GROUP_SETTING_SYNCHRONIZE_IMAGES_DB_COLUMN => isset($_POST['group_sync_images']),
								LISTING_GROUP_SETTING_SYNCHRONIZE_DESCRIPTIONS_DB_COLUMN => isset($_POST['group_sync_descriptions']),
								LISTING_GROUP_SETTING_SYNCHRONIZE_SHIPPING_DB_COLUMN => isset($_POST['group_sync_shipping']),
								LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN => isset($_POST['group_sync_stock'])
							],
							$_POST['group_label']
						);
					else
						$this->removeListingFromGroup($id);
					
					if(
						isset($_POST['enable_promos']) &&
						(
							!empty($_POST['promo_code-new-code']) ||
							!empty($_POST['promo_code_ids'])
						)
					){
						$this->updateListingPromoCodes(
							$id,
							$this->parseListingPromoCodes($listingPrice_BTC)
						);
					} else
						$this->updateListingPromoCodes(
							$id,
							false
						);
					
					return $id;
				}
			}
		}
	}
	
	private function _canSynchronizeStockInListingGroup($groupID){
		return	$this->db->qSelect(
				"
					SELECT
						`ListingGroup`.`ID`
					FROM
						`ListingGroup`
					INNER JOIN
						`Listing_Group` ON
							`Listing_Group`.`GroupID` = `ListingGroup`.`ID`
					INNER JOIN
						`Listing` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					INNER JOIN
						`Unit` ON
							`Listing`.`UnitID` = `Unit`.`ID`
					WHERE
						`ListingGroup`.`ID` = ?
					GROUP BY
						`ListingGroup`.`ID`
					HAVING
						COUNT(DISTINCT `Unit`.`DimensionID`) = 1
				",
				'i',
				[$groupID]
			);
	}
	
	private function parseListingPromoCodes($listingPrice_BTC){
		$promoCodes = [];
		
		if(
			!empty($_POST['promo_code-new-code']) &&
			!empty($_POST['promo_code-new-discount']) &&
			!empty($_POST['promo_code-new-quantity'])
		)
			$promoCodes[] = [
				'code'		=> $_POST['promo_code-new-code'],
				'discount'	=> $_POST['promo_code-new-currency']
							? $_POST['promo_code-new-discount']
							: min(
								$_POST['promo_code-new-discount'],
								100
							), # cannot be higher than 100 if percentage
				'currencyID'	=> $_POST['promo_code-new-currency'] ?: NULL,
				'quantity'	=> $_POST['promo_code-new-quantity']
			];
					
		if( !empty($_POST['promo_code_ids']) )
			foreach($_POST['promo_code_ids'] as $promoCodeID){
				$promoCodes[] = [
					'ID'		=> $promoCodeID,
					'code'		=> $_POST['promo_code-' . $promoCodeID . '-code'],
					'discount'	=> $_POST['promo_code-' . $promoCodeID . '-currency']
								? $_POST['promo_code-' . $promoCodeID . '-discount']
								: min(
									$_POST['promo_code-' . $promoCodeID . '-discount'],
									100
								), # cannot be higher than 100 if percentage$_POST['promo_code-' . $promoCodeID . '-discount'],
					'currencyID'	=> $_POST['promo_code-' . $promoCodeID . '-currency'] ?: NULL,
					'quantity'	=> $_POST['promo_code-' . $promoCodeID . '-quantity']
				];
			}
			
		foreach($promoCodes as $key => $promoCode){
			if(
				$promoCode['currencyID'] &&
				NXS::compareFloatNumbers(
					NXS::convertCurrencies(
						$this->db,
						$promoCode['discount'],
						$promoCode['currencyID'],
						CURRENCY_ID_BTC
					),
					$listingPrice_BTC,
					'>='
				)
			)
				$promoCodes[$key] = array_merge(
					$promoCode,
					[
						'discount'	=> 100,
						'currencyID'	=> NULL
					]
				);
		}
		
		return $promoCodes;
	}
	
	private function deleteListingPicture($pictureID){
		return $this->db->qQuery(
			"
				DELETE
					`Listing_Image`
				FROM
					`Listing_Image`
				INNER JOIN	`Listing`
					ON	`Listing_Image`.`ListingID` = `Listing`.`ID`
				WHERE
					`Listing_Image`.`ID`		= ?
				AND	`Listing`.`VendorID`		= ?
				AND	`Listing_Image`.`Primary`	= FALSE
			",
			'ii',
			array(
				$pictureID,
				$this->User->ID
			)
		);
	}
	
	private function makeListingPicturePrimary($listingID, $pictureID){
		return $this->db->qQuery(
			"
				UPDATE
					`Listing_Image`
				INNER JOIN	`Listing`
					ON	`Listing_Image`.`ListingID` = `Listing`.`ID`
				SET
					`Listing_Image`.`Primary` = IF(
						`Listing_Image`.`Primary` = TRUE,
						FALSE,
						TRUE
					)
				WHERE
					`Listing`.`VendorID` = ?
				AND	`Listing_Image`.`ListingID` = ?
				AND	(
						`Listing_Image`.`ID` = ? OR
						`Listing_Image`.`Primary` = TRUE
					)
			",
			'iii',
			array(
				$this->User->ID,
				$listingID,
				$pictureID
			),
			TRUE
		);
	}
	
	public function updateListings(){
		foreach ($_POST['listing_ids'] as $listing_id){
			$name			= strip_tags($_POST['listing-' . $listing_id . '_name']);
			$price			= $_POST['listing-' . $listing_id . '_price'];
			$currency		= $_POST['listing-' . $listing_id . '_currency'];
			$inactive		= !isset($_POST['listing-' . $listing_id . '_active']);
			$stealth		= !isset($_POST['listing-' . $listing_id . '_visible']);
			$quantityMinimum	= $_POST['listing-' . $listing_id . '_quantity_minimum'];
			
			$stock = null;
			if ($hasGroupStock = isset($_POST['listing-' . $listing_id . '_stock_unit'])){
				$stockUnitID = $_POST['listing-' . $listing_id . '_stock_unit'];
				$groupStock = $_POST['listing-' . $listing_id . '_stock'];
			} else {
				$stock = $_POST['listing-' . $listing_id . '_stock'];
				if (!$inactive && $stock < 1)
					$stock = 1;
			
				if(
					$stock > 0 &&
					$stock < $quantityMinimum
				)
					$stock = $quantityMinimum;
			}
			
			if (strlen($name) < 5 || strlen($name) > MAX_LENGTH_LISTING_NAME)
				continue;
			
			if( empty($price) || !is_numeric($price) || $price < 0.0001 )
				continue;
			
			if( empty($currency) || !is_numeric($currency) )
				continue;
			
			$this->db->qQuery(
				"
					UPDATE
						`Listing`
					SET
						`Listing`.`Name` 		= ?,
						`Listing`.`Price`		= ?,
						`Listing`.`CurrencyID`		= ?,
						`Listing`.`Quantity_Left`	= IFNULL(?, `Quantity_Left`),
						`Listing`.`Inactive`		= IF(? = TRUE OR ? < 1, TRUE, FALSE),
						`Listing`.`Stealth`		= ?
					
					WHERE
						`Listing`.`ID`		= ? AND
						`Listing`.`VendorID`	= ?
				",
				'sdiiiiiii',
				array(
					$name,
					$price,
					$currency,
					$stock,
					$inactive,
					$stock,
					$stealth,
					$listing_id,
					$this->User->ID
				)
			);
			
			if ($hasGroupStock)
				$this->setListingGroupStock(
					$listing_id,
					$stockUnitID,
					$groupStock
				);
		}
		
		$this->fixListingPromoCodeDiscount();
		
		return true;	
	}
	
	private function setListingGroupStock(
		$listingID,
		$unitID,
		$stock
	){
		return	$this->db->qQuery(
				"
					UPDATE
						`ListingGroup`
					INNER JOIN
						(
							SELECT
								`Listing_Group`.`GroupID`
							FROM
								`Listing_Group`
							INNER JOIN
								`Listing` ON
									`Listing_Group`.`ListingID` = `Listing`.`ID`
							WHERE
								`Listing_Group`.`ListingID` = ? AND
								`Listing`.`VendorID` = ?
						) source ON
							source.`GroupID` = `ListingGroup`.`ID`
					INNER JOIN
						`Unit` ON
							`Unit`.`ID` = ?
					INNER JOIN
						`Listing_Group` ON
							`Listing_Group`.`GroupID` = `ListingGroup`.`ID`
					INNER JOIN
						`Listing` ON
							`Listing_Group`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`Unit` listingUnit ON
							`Listing`.`UnitID` = listingUnit.`ID`
					SET
						`ListingGroup`.`UnitID` = `Unit`.`ID`,
						`ListingGroup`.`Stock` = ?,
						`Listing`.`Quantity_Left` =
							FLOOR(
								(? * `Unit`.`ConversionFactor`) /
								(`Listing`.`Quantity` * listingUnit.`ConversionFactor`)
							),
						`Listing_Group`.`OutOfStock` =
							FLOOR(
								(? * `Unit`.`ConversionFactor`) /
								(`Listing`.`Quantity` * listingUnit.`ConversionFactor`)
							) <
							`Listing`.`Quantity_Minimum`
					WHERE
						`ListingGroup`.`SynchronizeStock` = TRUE
				",
				'iiiddd',
				[
					$listingID,
					$this->User->ID,
					$unitID,
					$stock,
					$stock,
					$stock
				]
			);
	}
	
	private function fixListingPromoCodeDiscount(){
		return $this->db->qQuery(
			"
				UPDATE
					`Listing_PromoCode`
				INNER JOIN
					`Listing` ON
						`Listing_PromoCode`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
				INNER JOIN
					`Currency` Bitcoin ON
						Bitcoin.`ID` = " . CURRENCY_ID_BTC . "
				INNER JOIN
					`Currency` listingCurrency ON
						`Listing`.`CurrencyID` = listingCurrency.`ID`
				INNER JOIN
					`Currency` promoCodeCurrency ON
						`Listing_PromoCode`.`CurrencyID` = promoCodeCurrency.`ID`
				SET
					`Listing_PromoCode`.`Discount` = 100,
					`Listing_PromoCode`.`CurrencyID` = NULL
				WHERE
					Vendor.`ID` = ? AND
					(
						`Listing_PromoCode`.`Discount` /
						promoCodeCurrency.`1EUR` *
						Bitcoin.`1EUR`
					) >=
					(
						`Listing`.`Price` /
						listingCurrency.`1EUR` *
						Bitcoin.`1EUR`
					)
			",
			'i',
			[$this->User->ID]
		);
	}
	
	public function deleteListings(){
		if( $listing_ids = $_POST['listing_select'] )
		
			try{
				$this->db->qQuery(
					"
						DELETE FROM
							`Listing`
						WHERE
							`ID` IN (" . rtrim(str_repeat('?, ', count($listing_ids)), ', ') . ")
						AND	`VendorID` = ?
					",
					str_repeat('i', count($listing_ids) + 1),
					array_merge(
						$listing_ids,
						array($this->User->ID)
					)
				);
			} catch (Exception $e){
				$_SESSION['temp_notifications'][] = array(
					'Group'		=> 'Specific',
					'Content'	=> 'Some listings couldn\'t be deleted &mdash; likely due to ongoing transactions',
					'Design'	=> array(
						'Color'	=> 'red',
						'Icon'	=> Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
			}
		
		return true;
		
	}
	
	public function  toggleListingsActive($active = true){
		if( $listing_ids = $_POST['listing_select'] )
			$this->db->qQuery(
				"
					UPDATE
						`Listing`
					SET
						`Inactive` = " . ($active ? 'FALSE' : 'TRUE') . ",
						`Quantity_Left` = IF(" . (
							$active
								? '1 = 1'
								: '1 = 2'
						) . " AND `Quantity_Left` < 1, 1, `Quantity_Left`)
					WHERE
						`ID` IN (" . rtrim(str_repeat('?, ', count($listing_ids)), ', ') . ")
					AND	`VendorID` = ?
				",
				str_repeat('i', count($listing_ids)) . 'i',
				array_merge(
					$listing_ids,
					array($this->User->ID)
				)
			);
		
		return true;
	}
	
	public function  toggleListingsVisible($visible = true){
		if( $listing_ids = $_POST['listing_select'] )
			$this->db->qQuery(
				"
					UPDATE
						`Listing`
					SET
						`Stealth` = " . ($visible ? 'FALSE' : 'TRUE') . "
					WHERE
						`ID` IN (" . rtrim(str_repeat('?, ', count($listing_ids)), ', ') . ")
					AND	`VendorID` = ?
				",
				str_repeat('i', count($listing_ids)) . 'i',
				array_merge(
					$listing_ids,
					array($this->User->ID)
				)
			);
		
		return true;
	}
	
	public function toggleListingActive($listing_id, $state = 'ACTIVATE') {
		switch($state){
			case 'ACTIVATE':
				$inactive = 'FALSE';
			break;
			case 'DEACTIVATE':
				$inactive = 'TRUE';
			break;
			default:
				return false;
		}
		
		if($listing_id == 'all'){
			$where_id = false;
		} else {
			$where_id = '`Listing`.`ID` = ? AND';
		}
		
		if( $stmt_updateListing = $this->db->prepare("
			UPDATE
				`Listing`
			SET
				`Inactive` = ".$inactive.",
				`Quantity_Left` = IF(
					FALSE = " . $inactive . " AND `Quantity_Left` < 1,
					1,
					`Quantity_Left`
				)
			WHERE
				".$where_id."
				`Archived` = FALSE AND
				`VendorID` = ?
		") ){
			
			if( $listing_id == 'all' ){
				$stmt_updateListing->bind_param('i', $this->User->ID);
			} else {
				$stmt_updateListing->bind_param('ii', $listing_id, $this->User->ID);
			}
			$stmt_updateListing->execute();
			if( $stmt_updateListing->affected_rows > 0 ){
				return true;
			} else {
				return false;
			}	
		}
	}
	
	public function toggleListingArchived($listingID){
		if(
			$this->db->qQuery(
				"
					UPDATE
						`Listing`
					SET
						`Archived` = IF(
							`Archived` IS TRUE,
							FALSE,
							TRUE
						),
						`Inactive` = IF(
							`Archived` IS FALSE,
							`Inactive`,
							TRUE
						)
					WHERE
						`ID` = ?
					AND	`VendorID` = ?
				",
				'ii',
				array(
					$listingID,
					$this->User->ID
				)
			)
		){
			$this->removeListingFromGroup($listingID);
			$this->_clearListingPromoCodes($listingID);
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function countListingReviews($listing_id){
		
		if( $stmt_countListingReviews = $this->db->prepare("
			SELECT
				COUNT(Listing_Rating.`ID`)
			FROM
				`Transaction_Rating` Listing_Rating
			INNER JOIN	`Listing`
				ON	Listing_Rating.`ListingID` = `Listing`.`ID`
			WHERE
				Listing_Rating.`ListingID` = ?
			AND	`Listing`.`VendorID` = ?
		") ){
			
			$stmt_countListingReviews->bind_param('ii', $listing_id, $this->User->ID);
			$stmt_countListingReviews->execute();
			$stmt_countListingReviews->store_result();
			$stmt_countListingReviews->bind_result($rating_count);
			$stmt_countListingReviews->fetch();
			
			return $rating_count;
			
		}
		
	}
	
	public function deleteListing($listing_id){
		
		if( $this->countListingReviews($listing_id) > 0 && !$this->checkAuthentication('Confirm permanently deleting this listing.', 'authorize', URL . 'account/delete_listing/' . $listing_id . '/') ){
			
			header('Location: ' . URL . 'account/listings/#authorize');
			die;
			
		}
		
		if( $stmt_checkActiveTX = $this->db->prepare("
			SELECT
				COUNT(`ID`)
			FROM
				`Transaction`
			WHERE
				`ListingID` = ?
		") ){
			
			$stmt_checkActiveTX->bind_param('i', $listing_id);
			$stmt_checkActiveTX->execute();
			$stmt_checkActiveTX->store_result();
			$stmt_checkActiveTX->bind_result($activetx_count);
			$stmt_checkActiveTX->fetch();
			
			if( $activetx_count > 0 ){
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'This listing still has activate transactions',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
				return false;
			}
			
		}
		
		if( $stmt_deleteListing = $this->db->prepare("
			DELETE
				`Listing`,
				`Listing_Attribute`,
				`Listing_BulkPrice`,
				`Listing_Continent`,
				`Listing_Country`,
				`Listing_Image`,
				`Listing_PromoCode`
			FROM
				`Listing`
			LEFT JOIN
				`Listing_Attribute` ON `Listing`.`ID` = `Listing_Attribute`.`ListingID`
			LEFT JOIN
				`Listing_BulkPrice` ON `Listing`.`ID` = `Listing_BulkPrice`.`ListingID`
			LEFT JOIN
				`Listing_Continent` ON `Listing`.`ID` = `Listing_Continent`.`ListingID`
			LEFT JOIN
				`Listing_Country` ON `Listing`.`ID` = `Listing_Country`.`ListingID`
			LEFT JOIN
				`Listing_Image` ON `Listing`.`ID` = `Listing_Image`.`ListingID`
			LEFT JOIN
				`Listing_PromoCode` ON `Listing`.`ID` = `Listing_PromoCode`.`ListingID`
			WHERE
				`Listing`.`ID` = ?
			AND	`Listing`.`VendorID` = ?
		") ){
			
			$stmt_deleteListing->bind_param('ii', $listing_id, $this->User->ID);
			
			if( $stmt_deleteListing->execute() ){
				
				if( $stmt_deleteListing->affected_rows > 0 ){
					
					$this->fixVendorPublicKey();
					
					return true;
				}
			} else {
				return false;
			}
			
		} else {
			//die('ducks '.$this->db->error);
		}
		
	}
	
	public function toggleStealth($listing_id){
		
		if( $stmt_toggleListingStealth = $this->db->prepare("
		UPDATE
			`Listing`
		SET
			`Stealth` = IF(`Stealth` = TRUE, FALSE, TRUE)
		WHERE
			`Listing`.`ID` = ?
		AND	`Listing`.`VendorID` = ?
		") ){
			
			$stmt_toggleListingStealth->bind_param('ii', $listing_id, $this->User->ID);
			
			if( $stmt_toggleListingStealth->execute() )
				return true;
			
		}
		
	}
	
	public function fetchShippingOptions(){
		
		if($shipping_options = $this->db->qSelect(
			"
				SELECT
					`ID`,
					`Name`,
					`Description`,
					`Price`,
					`CurrencyID`,
					`TransitDays`
				FROM
					`ListingShipping`
				WHERE
					`VendorID` = ?	
			",
			'i',
			array($this->User->ID)
		))
			array_walk(
				$shipping_options,
				function(&$array){
					if( $array['CurrencyID'] !== CURRENCY_ID_BTC )
						$array = array_merge(
							$array,
							array(
								'Price' => number_format($array['Price'], 2)
							)
						);
				}
			);
		
		return $shipping_options;
		
	}
	
	public function updateShippingOptions(){
		
		$shipping_ids = array();
		
		if( isset($_POST['enable_shipping']) ){
				
			foreach($_POST['enable_shipping'] as $shipping_id){
				
				$name = htmlspecialchars($_POST['shipping_option-' . $shipping_id . '_name']);
				
				$description = $_POST['shipping_option-' . $shipping_id . '_description'];
				
				$price = $_POST['shipping_option-' . $shipping_id . '_price'];
				$currency = $_POST['shipping_option-' . $shipping_id . '_currency'];
				
				$transit_days = $_POST['shipping_option-' . $shipping_id . '_transit_days'];
				
				if( empty($name) )
					continue;
				
				if($shipping_id == 'new'){
					$shipping_ids[] = $this->db->qQuery(
						"
							INSERT INTO
								`ListingShipping` (`VendorID`, `Name`, `Description`, `Price`, `CurrencyID`, `TransitDays`)
							VALUES
								(?, ?, ?, ?, ?, ?)
						",
						'issdis',
						array(
							$this->User->ID,
							$name,
							$description,
							$price,
							$currency,
							$transit_days
						)
					);
				} elseif( is_numeric($shipping_id) && $shipping_id > 0 ){
					$shipping_ids[] = $shipping_id;
					$this->db->qQuery(
						"
							UPDATE
								`ListingShipping`
							SET
								`Name`			= ?,
								`Description`	= ?,
								`Price`			= ?,
								`CurrencyID`	= ?,
								`TransitDays`	= ?
							WHERE
								`ID`			= ?
							AND	`VendorID`		= ?
						",
						'ssdisii',
						array (
							$name,
							$description,
							$price,
							$currency,
							$transit_days,
							$shipping_id,
							$this->User->ID
						)
					);
				}
				
			}
			
		}
		
		$this->db->qQuery(
			"
				DELETE FROM
					`ListingShipping`
				WHERE
					" . (!empty($shipping_ids) ? "`ID` NOT IN (" . rtrim(str_repeat('?, ', count($shipping_ids)), ', ') . ") AND " : false) . "
					`VendorID` = ?
			",
			str_repeat('i', count($shipping_ids) + 1),
			array_merge(
				$shipping_ids,
				array($this->User->ID)
			)
		);
		
		return true;
		
	}
	
	public function fetchVendorProfile(){
		$description = $this->User->Info('Description');
		
		if( $stmt_getSections = $this->db->prepare("
			SELECT
				`ID`,
				`Type`,
				`Name`,
				`Content`
			FROM
				`User_Section`
			WHERE
				`VendorID` = ?
			ORDER BY
				`Sort`
		") ){
			
			$stmt_getSections->bind_param('i', $this->User->ID);
			$stmt_getSections->execute();
			$stmt_getSections->store_result();
			
			if( $stmt_getSections->num_rows > 0 ){
				
				$stmt_getSections->bind_result(
					$section_id,
					$section_type,
					$section_name,
					$section_content
				);
				
				$sections = array();
				while( $stmt_getSections->fetch() ){
					$sections[] = array(
						'id' => $section_id,
						'type' => $section_type,
						'name' => $section_name,
						'content' => $section_content
					);
				}
				
			} else {
				$sections = false;
			}
			
		}
		
		return array($description, $sections);
		
	}
	
	public function updateSections(){
		if (!empty($_POST)){
			$stmt_updateDescription = $this->db->prepare("
				UPDATE
					`User`
				SET
					`Description` = ?,
					`HTML` = ?
				WHERE
					`ID` = ?
			");
		
			$stmt_updateSection = $this->db->prepare("
				UPDATE
					`User_Section`
				SET
					`Name` = ?,
					`Content` = ?,
					`HTML` = ?,
					`Sort` = ?
				WHERE
					`ID` = ?
				AND	`VendorID` = ?
			");
		
			$stmt_insertSection = $this->db->prepare("
				INSERT INTO
					`User_Section` (`VendorID`, `Name`, `Content`, `HTML`, `Sort`)
				VALUES
					(?, ?, ?, ?, ?)
			");
		
			if( false !== $stmt_updateDescription && false !== $stmt_updateSection && false !== $stmt_insertSection ){
			
				$description = NXS::formatText($_POST['profile']);
				$stmt_updateDescription->bind_param('ssi', $_POST['profile'], $description, $this->User->ID);
				$stmt_updateDescription->execute();
			
				$section_ids = array();
				if( isset($_POST['enable_section']) ){
				
					foreach($_POST['enable_section'] as $section_id){
					
						$name = htmlspecialchars(
							trim($_POST['section-' . $section_id . '_name'])
						);
						$content = $_POST['section-' . $section_id . '_content'];
						$html = NXS::formatText($content, FALSE, $null, TRUE);
						$sort = $_POST['section-' . $section_id . '_order'];
					
						if( empty($name) || empty($content) )
							continue;
					
						if($section_id == 'new'){
							$stmt_insertSection->bind_param('isssi', $this->User->ID, $name, $content, $html, $sort);
							$stmt_insertSection->execute();
							$section_ids[] = $stmt_insertSection->insert_id;
						} elseif( is_numeric($section_id) && $section_id > 0 ){
							$section_ids[] = $section_id;
							$stmt_updateSection->bind_param('sssiii', $name, $content, $html, $sort, $section_id, $this->User->ID);
							$stmt_updateSection->execute();
						}
					
					}
				
				}
			
				if ($section_ids){
					$stmt_deleteSections = $this->db->prepare("
						DELETE FROM
							`User_Section`
						WHERE
							" . (!empty($section_ids) ? "`ID` NOT IN (" . rtrim(str_repeat('?, ', count($section_ids)), ', ') . ") AND " : false) . "
							`VendorID` = ?
					");
			
					$stmt_deleteSections_types = str_repeat('i', count($section_ids) + 1);
					$stmt_deleteSections_params = array_merge(
						$section_ids,
						array($this->User->ID)
					);
			
					$stmt_deleteSections_args[] = $stmt_deleteSections_types;
					foreach( $stmt_deleteSections_params as $key => $param )
						$stmt_deleteSections_args[] = &$stmt_deleteSections_params[ $key ];
			
					call_user_func_array(
						array($stmt_deleteSections, 'bind_param'),
						$stmt_deleteSections_args
					);
					$stmt_deleteSections->execute();
				}
			
				return true;
			}
		}
	}
	
	public function askQuestion($listing_id){
		$question = htmlspecialchars($_POST['question']);
		
		if( $stmt_addQuestion = $this->db->prepare("
			INSERT INTO
				`Listing_Question` (`ListingID`, `AskerID`, `Title`)
			VALUES
				(?, ?, ?)
		") ){
			
			$stmt_addQuestion->bind_param('iis', $listing_id, $this->User->ID, $question);
			
			if( $stmt_addQuestion->execute() ){
				$_SESSION['temp_notifications'][] = array(
					'Group' => 'Specific',
					'Content' => 'Your question has been submitted',
					'Design' => array(
						'Color' => 'green',
						'Icon' => Icon::getClass('CHECK')
					),
				);
				
				return true;
			}	
		}
	}
	
	private function getListingVendor($listingID){
		return $this->db->qSelect(
			"
				SELECT
					`VendorID`
				FROM
					`Listing`
				WHERE
					`ID` = ?
			",
			'i',
			[$listingID]
		);
	}
	
	public function answerQuestion($question_id){
		if( $stmt_getQuestion = $this->db->prepare("
			SELECT
				`ListingID`,
				`AskerID`,
				`Title`,
				`Listing`.`Name`
			FROM
				`Listing_Question`
			INNER JOIN	`Listing`
				ON	`Listing_Question`.`ListingID` = `Listing`.`ID`
			WHERE
				`Listing_Question`.`ID` = ?
			AND	`Listing`.`VendorID` = ?
		") ){
			$stmt_getQuestion->bind_param('ii', $question_id, $this->User->ID);
			$stmt_getQuestion->execute();
			$stmt_getQuestion->store_result();
			
			if( $stmt_getQuestion->num_rows == 1 ){
				
				$stmt_getQuestion->bind_result(
					$listing_id,
					$asker_id,
					$question_title,
					$listing_name
				);
				$stmt_getQuestion->fetch();
				
				foreach( $_POST as $key => $value )
					$_SESSION['answer_post'][$key] = 
						is_array($value)
							? array_map(
								'htmlspecialchars',
								$value
							)
							: htmlspecialchars($value);
				
				if( !is_numeric($_POST['sort']) || $_POST['sort'] < 1 ){
					$_SESSION['answer_response']['sort'] = 'This is not a valid integer.';
					return array(false, $listing_id);
				}
				
				$question = htmlspecialchars($_POST['question']);
				$answer = htmlspecialchars($_POST['answer']);
				$html = NXS::formatText($_POST['answer']);
				
				if( $stmt_updateQuestion = $this->db->prepare("
					UPDATE
						`Listing_Question`
					SET
						`Title` = ?,
						`Content` = ?,
						`HTML` = ?,
						`Sort` = ?
					WHERE
						`ID` = ?
				") ){
					
					$stmt_updateQuestion->bind_param('sssii', $question, $answer, $html, $_POST['sort'], $question_id);
					
					if( $stmt_updateQuestion->execute() ){
						
						$b36 = NXS::getB36($listing_id);
						
						if (!empty($asker_id))
							$this->User->sendMessage(
								'[b]Your question has been answered[/b]' . PHP_EOL . PHP_EOL . 'This is to let you know, that your question regarding [a=/v/' . $this->User->Alias . '/]' . $this->User->Alias . '[/a]\'' . ( substr(strtolower($this->User->Alias), -1, 1) !== 's' ? 's' : false) . ' listing [a=/i/' . $b36 . '/]' . $listing_name . '[/a], "[i]' . $question_title . '[/i]", was answered by the vendor.' . PHP_EOL . PHP_EOL . 'Read the answer on [a=/i/' . $b36 . '/#questions]the listing\'s FAQ page[/a].' . PHP_EOL . PHP_EOL . 'This is an automatic message. Replies will not be read.',
								$asker_id
							);
						
						unset(
							$_SESSION['answer_response'],
							$_SESSION['answer_post']
						);
						
						return array(true, $listing_id);
					} else {
						return array(false, $listing_id);
					}
					
				}
				
			} else {
				return false;
			}
			
		}
		
	}
	
	public function deleteQuestion($question_id){
		
		if( $stmt_getQuestion = $this->db->prepare("
			SELECT
				`ListingID`
			FROM
				`Listing_Question`
			INNER JOIN	`Listing`
				ON	`Listing_Question`.`ListingID` = `Listing`.`ID`
			WHERE
				`Listing_Question`.`ID` = ?
			AND	`Listing`.`VendorID` = ?
		") ){
			
			$stmt_getQuestion->bind_param('ii', $question_id, $this->User->ID);
			$stmt_getQuestion->execute();
			$stmt_getQuestion->store_result();
			
			if( $stmt_getQuestion->num_rows == 1 ){
				
				$stmt_getQuestion->bind_result($listing_id);
				$stmt_getQuestion->fetch();
				
				if( $stmt_deleteQuestion = $this->db->prepare("
					DELETE FROM
						`Listing_Question`
					WHERE
						`ID` = ?
				") ){
					
					$stmt_deleteQuestion->bind_param('i', $question_id);
					
					if( $stmt_deleteQuestion->execute() ){
						return array(true, $listing_id);
					} else
						return array(false, $listing_id);
				}
			} else 
				return false;
		}
	}
	
	public function fetchUserTransactions($userAlias = FALSE){
		$userAlias = $userAlias ?: $this->User->Alias;
		
		return $this->db->qSelect(
			"
				SELECT
					`Transaction`.`ID`,
					IFNULL(
						`Transaction`.`Identifier`,
						`Transaction`.`ID`
					) Identifier,
					IF(
						My.`ID` = `Transaction`.`BuyerID`,
						Vendor.`Alias`,
						Buyer.`Alias`
					) SubjectAlias,
					CONCAT(
						Cryptocurrency.`Symbol`,
						' ',
						`Transaction`.`Value`
					) Value,
					`Transaction`.`Status`
				FROM
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				INNER JOIN
					`Currency` Cryptocurrency ON
						`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
				INNER JOIN
					`User` My ON
						My.`Alias` = ?
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
				INNER JOIN
					`User` Buyer ON
						`Transaction`.`BuyerID` = Buyer.`ID`
				WHERE
					(
						(
							My.`Vendor` = TRUE AND
							`Listing`.`VendorID` = My.`ID` AND
							`Transaction`.`Status` IN (
								'pending accept',
								'rejected',
								'in transit',
								'expired',
								'in dispute',
								'refunded',
								'pending feedback'
							)
						) OR
						(
							My.`Vendor` = FALSE AND
							`Transaction`.`BuyerID` = My.`ID` AND
							`Transaction`.`Status` IN (
								'pending deposit',
								'pending accept',
								'rejected',
								'in transit',
								'expired',
								'in dispute',
								'refunded',
								'pending feedback'
							)
						)
					) AND
					`Transaction`.`Timeout` > NOW() - INTERVAL " . SUPPORT_TRANSACTION_PANEL_TRANSACTION_MAX_AGE_MONTHS . " MONTH
				ORDER BY
					`Transaction`.`DateTime` DESC,
					`Transaction`.`ID` DESC
			",
			's',
			[
				$userAlias
			]
		);
	}
	
	private function makeRecursive($d, $r = 0, $pk = 'Parent', $k = 'ID', $c = 'Children') {
		$m = array();
		foreach ($d as $e) {
			isset($m[$e[$pk]]) ? false : $m[$e[$pk]] = array();
			isset($m[$e[$k]]) ? false : $m[$e[$k]] = array();
			$m[$e[$pk]][] = array_merge($e, array($c => &$m[$e[$k]]));
		}
		
		return $m[$r]; // remove [0] if there could be more than one root nodes
	}
	
	public function uploadFile (
		$file_field = NULL,
		$check_image = false,
		$random_name = false,
		$width = false,
		$height = false,
		$generateThumbnails = false,
		$definedName = false,
		$addToDatabase = true,
		$originalImageID = null
	) {
		$path = UPLOADS_PATH; //with trailing slash
		$max_size = 1000000;
		$whitelist_ext = [
			'jpg',	'JPG',	'jpeg',	'JPEG',
			'png',	'PNG',
			'gif',	'GIF'
		];

		//Set default file type whitelist
		$whitelist_type = ['image/jpeg', 'image/png','image/gif'];
	
		//The Validation
		// Create an array to hold any output
		$out = array('error'=>NULL);
				   
		if (!$file_field) {
			$out['error'][] = "Please specify a valid form field name";           
		}
	
		if (!$path) {
			$out['error'][] = "Please specify a valid upload path";               
		}
			   
		if (count($out['error']) > 0)
			return $out;

		if(
			!empty($_FILES[$file_field]) &&
			$_FILES[$file_field]['error'] == 0
		) {
			// Get filename
			$file_info = pathinfo($_FILES[$file_field]['name']);
			$name = $file_info['filename'];
			$ext = strtolower($file_info['extension']);
					   
			//Check file has the right extension           
			if (!in_array($ext, $whitelist_ext))
				$out['error'][] = "Invalid file Extension";
					   
			//Check that the file is of the right type
			if (!in_array($_FILES[$file_field]["type"], $whitelist_type))
				$out['error'][] = "Invalid file Type";
					   
			//Check that the file is not too big
			if ($_FILES[$file_field]["size"] > $max_size)
				$out['error'][] = "File is too big";
					   
			//If $check image is set as true
			if (
				$check_image &&
				@!(list($img_width, $img_height) = getimagesize($_FILES[$file_field]['tmp_name']))
			) 
				$out['error'][] = "Uploaded file is not a valid image";
	
			//Create full filename including path
			if ($random_name) {
				$tmp = str_replace(array('.',' '), array('',''), microtime());		   
				if (!$tmp || $tmp == '') 
					$out['error'][] = "File must have a name";
				$newname = $tmp.'.'.$ext;                                
			} elseif ($definedName)
				$newname = $definedName . '.' . $ext;
			else
				$newname = $name . '.' . $ext;
					   
			//Check if file already exists on server
			if (file_exists($path.$newname))
				$out['error'][] = "A file with this name already exists";
	
			if (count($out['error']) > 0)
				return $out;
		
			$image = new Imagick($_FILES[$file_field]['tmp_name']);
		
			// RESIZE
			if(
				(
					$width &&
					$img_width > $width
				) ||
				(
					$height &&
					$img_height > $height
				)
			){
				if(
					!$height ||
					(
						$width &&
						$img_width/$img_height < $width/$height
					)
				)
					$image->thumbnailImage($width, 0);
				else
					$image->thumbnailImage(0, $height);
			}
		
			$image->stripImage();
			$image->writeImage($path.$newname);
			chmod($path.$newname, 0644);
		
			if ($addToDatabase)
				$originalImageID = $this->addImageToDatabase(
					$newname,
					$image->getImageBlob(),
					$originalImageID
				);
		
			if($generateThumbnails)
				foreach($generateThumbnails as $generateThumbnail)
					$thumbnails[] = $this->uploadFile(
						$file_field,
						$check_image,
						FALSE,
						$generateThumbnail['width'],
						$generateThumbnail['height'],
						FALSE,
						explode('.', $newname)[0] . $generateThumbnail['suffix'],
						$addToDatabase,
						$originalImageID
					);
		
			$out['filepath'] = $path;
			$out['filename'] = $newname;
			$out['imageID'] = $originalImageID;
		} else
			$out['error'][] = "No file uploaded";
		
		return $out;
	}
	
	private function addImageToDatabase(
		$path,
		$blob,
		$originalImageID = null
	){
		$stmt = $this->db->prepare(
			"
				INSERT IGNORE INTO
					`Image` (
						`Filename`,
						`File`,
						`OriginalID`
					)
				VALUES (
					?,
					?,
					?
				)
			"
		);
		
		$null = NULL;
		$stmt->bind_param("sbi", $path, $null, $originalImageID);
		$stmt->send_long_data(
			1,
			$blob
		);
		
		if ($stmt->execute())
			return $stmt->insert_id;
			
		return false;
	}
	
	public function getChildrenIDs($reduced_categories){
		
		$ids = array();
		foreach($reduced_categories as $reduced_category){
			$ids[] = $reduced_category['ID'];
			if( array_key_exists('Children', $reduced_category) ){
				$ids = array_merge( $ids, $this->getChildrenIDs($reduced_category['Children']) );
			}
		}
		
		return $ids;
	}
	
	private function getUniqueAlias(){
		
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
		
	}
	
	private function fixVendorPublicKey(){
		
		return;
		
		/*
		
		list($listing_count, $bip32publickey, $btcpublickey) = $this->User->Info('ListingCount', 'BIP32Public', 'BTCPublic');
		
		if( $listing_count > 0 ){
			
			if( $bip32_extended_public = $this->User->Attributes['BIP32ExtendedPublic'][0] && empty($bip32publickey) ){
				
				// ADD BIP32 PUBLIC
				
				if( $stmt_addBIP32PublicKey = $this->db->prepare("
					UPDATE
						`User`
					SET
						`BIP32Public` = ?
					WHERE
						`ID` = ?
				") ){
					
					$stmt_addBIP32PublicKey->bind_param('si', $bip32_extended_public, $this->User->ID);
					$stmt_addBIP32PublicKey->execute();
					
					return true;
					
				}
				
			}
			
			if( $btc_public = $this->User->Attributes['BTCPublic'] && empty($btcpublickey) ){
				
				if( $stmt_addBTCPublicKey = $this->db->prepare("
					UPDATE
						`User`
					SET
						`BTCPublic` = ?
					WHERE
						`ID` = ?
				") ){
					
					$stmt_addBTCPublicKey->bind_param('si', $btc_public, $this->User->ID);
					$stmt_addBTCPublicKey->execute();
					
					return true;
					
				}
				
			}
			
		} elseif ( $listing_count == 0 && ( !empty($bip32publickey) || !empty($btcpublickey) )  ) {
			
			if( $stmt_removeBIP32PublicKey = $this->db->prepare("
				UPDATE
					`User`
				SET
					`BIP32Public` = NULL,
					`BTCPublic` = NULL
				WHERE
					`ID` = ?
			") ){
				
				$stmt_removeBIP32PublicKey->bind_param('i', $this->User->ID);
				$stmt_removeBIP32PublicKey->execute();
				
				return true;
				
			}
			
		}
		
		*/
		
	}
	
	
	
	private function checkAuthentication($title = 'You must authorize to complete this action.', $session_variable_name = 'authorize', $action = false){
		
		if( isset($_SESSION[$session_variable_name]['verified']) && $_SESSION[$session_variable_name]['verified'] > strtotime('- 3 minutes') ){
			return true;
		}
		
		$_SESSION[$session_variable_name]['title'] = $title;
		$_SESSION[$session_variable_name]['action'] = $action;
		
		$_SESSION[$session_variable_name]['username'] = $_POST['authorize_username'];
		$_SESSION[$session_variable_name]['password'] = $_POST['authorize_password'];
		
		$pgp = !empty($_SESSION['pgp']) ? $_SESSION['pgp'] : false;
		
		if( !isset($_POST['authorizing']) ){
			
			if($pgp)
				$this->generatePGPMessage($pgp, $session_variable_name, true);
			
			return false;
			
			
		}
		
		if( empty($_POST['authorize_username']) ){
			$_SESSION[$session_variable_name]['authorize_username'] = 'This field cannot be empty.';
			$tryAgain = true;
		}
		if( empty($_POST['authorize_password']) ){
			$_SESSION[$session_variable_name]['authorize_password'] = 'This field cannot be empty.';
			$tryAgain = true;
		}
		if( $pgp && ( !$this->checkPGP($_POST['authorize_code'], $session_variable_name, $pgp) )  ){
			$_SESSION[$session_variable_name]['authorize_code'] = 'This is not a valid authentication code.';
			$tryAgain = true;
		}
		
		if($pgp)	$this->generatePGPMessage($pgp, $session_variable_name, true);
		
		if( $tryAgain ){
			return false;
		}
		
		$username = isset($_POST['authorize_prehashed']) ? $_POST['authorize_username'] : sha1( strtolower($_POST['authorize_username']) );
			
		$u = sha1(SITEWIDE_USERNAME_SALT . $username);
		
		if($u != $_SESSION['u']){
			$_SESSION[$session_variable_name]['authorize_username'] = FEEDBACK_USERNAME_WRONG;
			return false;
		}
		
		if($stmt_Authenticate = $this->db->prepare("
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
			if ($stmt_Authenticate->num_rows == 1){
				$stmt_Authenticate->bind_result($salt, $db_password);
				$stmt_Authenticate->fetch();
				
				$password = isset($_POST['authorize_prehashed']) ? $_POST['authorize_password'] : hash('sha512', $_POST['authorize_password']);
				$p0 = hash('sha512', $password . $salt);
				$p = hash('sha512', $p0 . $salt);
				
				$uid = Session::get('user_id');
				
				if ($db_password == $p) {
					unset($_SESSION[$session_variable_name]);
					$_SESSION[$session_variable_name]['verified'] = time();
					return true;
				} else {
					$_SESSION[$session_variable_name]['authorize_password'] = FEEDBACK_PASSWORD_WRONG;
					return false;
				}
			} else {
				// No User With Given U
				$_SESSION["feedback_negative"]['general'] = FEEDBACK_UNKNOWN_ERROR;
				
				Session::destroy();
				
				header('Location: ' . URL . '#login');
				die;
				
			}
		}
		
	}
	
	public function checkPrefix($prefix){
		
		if( $stmt_checkPrefix = $this->db->prepare("
			SELECT
				COUNT(`ID`)
			FROM
				`User`
			WHERE
				`ID` != ?
			AND	`SecretPrefix` = ?	
		") ){
			
			$stmt_checkPrefix->bind_param('is', $this->User->ID, $prefix);
			$stmt_checkPrefix->execute();
			$stmt_checkPrefix->store_result();
			$stmt_checkPrefix->bind_result($users_with_prefix);
			$stmt_checkPrefix->fetch();
			
			return $users_with_prefix == 0;
			
		}
		
	}
	
	public function generatePGPMessage($pgp = false, $session_variable_name = 'PGP_authentication_code', $compare_pgp = false){
		$pgp = $pgp ? $pgp : $_SESSION['pgp'];
		
		$public_key_ascii = $pgp;
		
		try {
			$pgp = new PGP($public_key_ascii);
		} catch (Exception $e) {
			return false; // INVALID PGP
		}
		
		$authentication_code = $_SESSION[$session_variable_name]['answer'] = substr(hexdec(hash('sha256', uniqid(openssl_random_pseudo_bytes(16), TRUE))), 2, 10);
		if($compare_pgp) $_SESSION[$session_variable_name]['pgp'] = $public_key_ascii;
		try {
			$message = $pgp->qEncrypt(
				'Your authentication code is: '.$authentication_code . PHP_EOL . PHP_EOL .
				'This message was generated as part of the PGP authentication process on ' . $this->db->site_name . ' for user \'' . $this->User->Alias . '\'.' . PHP_EOL . PHP_EOL .
				'Please ensure that the URL in the address bar is: ' . $this->db->accessDomain,
				TRUE
			);
		} catch (Exception $e) {
			return false;
		}
		
		$_SESSION[$session_variable_name]['message'] = $message;
		
		return true;
	}
	
	private function checkPGP($response, $session_variable_name = 'PGP_authentication_code', $pgp = false){
		
		$session = $_SESSION[$session_variable_name];
		unset($_SESSION[$session_variable_name]);
		
		if ( (isset($session['verified']) && $session['verified'] == $pgp) || ( $response == $session['answer'] && (!$pgp || $pgp == $session['pgp'] ) ) ){
			if($pgp)	$_SESSION[$session_variable_name]['verified'] = $pgp;
			return true;
		}
		
	}
	
	public function generateMessage($type, $public_key){
		
		$authentication_code = hash('sha256', uniqid(openssl_random_pseudo_bytes(16), TRUE));
		
		$derivation_i = !empty($_SESSION['temp_derivation_path']) ? $_SESSION['temp_derivation_path'] : rand(11, 892);
		
		switch($type){
			case 'bip32':
				$address = BitcoinLib::public_key_to_address( BIP32::extract_public_key( BIP32::build_key($public_key, $derivation_i) ), '00');
			break;
			case 'traditional':
				$address = BitcoinLib::public_key_to_address( $public_key, '00' );
			break;
		}
		
		$_SESSION['BTC_authentication_code'] = array(
			'public_key' => $public_key,
			'type' => $type,
			'message' => $authentication_code,
			'i' => $derivation_i,
			'address' => $address
		);
		
	}
	
	public function checkSignature(){
		if( empty($_SESSION['BTC_authentication_code']) ){
			unset($_SESSION['BTC_authentication_code']);
			return false;
		}
		
		if( isset($_SESSION['BTC_authentication_code']['verified']) && $_SESSION['BTC_authentication_code']['verified'] == $_SESSION['BTC_authentication_code']['public_key'] ){
			unset($_SESSION['temp_derivation_path']);
			return true;
		}
		
		$signature = $_POST['btc_signature'];
		$message = $_SESSION['BTC_authentication_code']['message'];
		$derivation_i = $_SESSION['temp_derivation_path'] = $_SESSION['BTC_authentication_code']['i'];
		$address = $_SESSION['BTC_authentication_code']['address'];
		$public_key = $_SESSION['BTC_authentication_code']['public_key'];
		
		unset($_SESSION['BTC_authentication_code']);
		
		if( !empty($signature) && BitcoinMessage::validate_message($address, $signature, $message) ){
			unset($_SESSION['temp_derivation_path']);
			$_SESSION['BTC_authentication_code']['verified'] = $public_key;
			return true;
		}
		
		return false;	
	}
	
	private function checkCaptcha()
	{
		// CHECK IF NEEDS CAPTCHA
		$canSkipCaptcha =
			$this->User->IsVendor ||
			$this->User->Attributes['TotalTransacted'] >= AMOUNT_TRANSACTED_SKIP_CAPTCHA;
		
		if($canSkipCaptcha)
			return TRUE;
		
		$captcha = new Captcha();
		
		if (isset($_POST["captcha"]) && $captcha->check($_POST['captcha']) == true){
			return true;
		}
		
		// default return
		return false;
	}
	
	public function getPendingDepositTXID(){
		if(
			$pendingDepositTX = $this->db->qSelect(
				"
					SELECT
						`ID`
					FROM
						`Transaction`
					WHERE
						`BuyerID` = ? AND
						`Status` = 'pending deposit' AND
						`RedeemScript` IS NOT NULL AND
						`Timeout` > NOW()
					ORDER BY
						`Paid` ASC,
						`Deposited` DESC,
						`DateTime` ASC,
						`ID` ASC
					LIMIT 1
				",
				'i',
				array(
					$this->User->ID
				)
			)
		)
			return $pendingDepositTX[0]['ID'];
		
		return FALSE;
	}
}
