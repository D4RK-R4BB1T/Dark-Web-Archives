<?php
class User {	
	public $Infos;
	private $db;
	
	function __construct(Database $db, $member = false){
		$this->db = $db;
		$this->AccessPrefix = $db->prefix;
		
		if($member){
			$this->ID	= (int) Session::get('user_id');
			$this->Alias	= Session::get('alias');
			$this->pKey	= Session::get('private_key');
			
			$this->Attributes = Session::get('attributes');
			
			list(
				$this->IsVendor,
				$this->IsAdmin,
				$this->IsMod,
				$this->IsTester
			) = $this->Info(
				'Vendor',
				'Admin',
				'Moderator',
				'Tester'
			);
			$this->IsMod = $this->IsMod || $this->IsAdmin;
			
			$this->updateAccount();
			
			$this->Notifications = new Notifications();
			$this->getDefaultNotifications();
			
			if (!$this->db->forum){
				$this->expiredTransactionCount = $this->IsVendor ? 0 : $this->getExpiredTransactionsCount();
				$this->pendingLateDepositTransactionIdentifier = $this->IsVendor ? false : $this->getLateDepositTransactionIdentifier();
			}
			
			$this->Cryptocurrency = $this->getCryptocurrency();
			
			// Update LastSeen
			if (!$this->IsAdmin)
				$this->updateLastSeen();
			
			/*if($stealth==1 && !$this->AccessPrefix)
				$this->AccessPrefix = $access_prefix;*/		
		} else
			$this->ID = $this->Cryptocurrency = $this->IsVendor = $this->IsAdmin = $this->IsMod = FALSE;
	}
	
	private function getLateDepositTransactionIdentifier(){
		$seenLateDepositOrderIdentifiers =
			isset($_SESSION['seen_late_deposit_order_identifiers'])
				? $_SESSION['seen_late_deposit_order_identifiers']
				: [];
		
		if (
			$lateDepositTransactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`Identifier`
					FROM
						`Transaction`
					WHERE
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`Paid` = FALSE AND
						`Transaction`.`Deposited` = TRUE AND
						NOW() > `Transaction`.`Timeout` AND
						NOW() < `Transaction`.`Timeout` + INTERVAL " . ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES . " MINUTE
						" . (
							$seenLateDepositOrderIdentifiers
								? " AND `Transaction`.`Identifier` NOT IN (" . rtrim(str_repeat('?, ', count($seenLateDepositOrderIdentifiers)), ', ') . ")"
								: false
								
						) . "
					ORDER BY
						`Transaction`.`Timeout` ASC
					LIMIT
						1
				",
				'i' . ($seenLateDepositOrderIdentifiers ? str_repeat('i', count($seenLateDepositOrderIdentifiers)) : false),
				array_merge(
					[$this->ID],
					$seenLateDepositOrderIdentifiers
				)
			)
		)
			return $lateDepositTransactions[0]['Identifier'];
		
		return false;
	}
	
	private function getExpiredTransactionsCount(){
		return	$this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `Transaction`.`ID`) count
					FROM
						`Transaction`
					WHERE
						`BuyerID` = ? AND
						`Status` = 'expired'
				",
				'i',
				[$this->ID]
			)[0]['count'];
	}
	
	public function ascertainUserClass(
		$classID,
		$rank = null,
		&$userRank = null
	){
		if (
			$userClass = $this->db->qSelect(
				"
					SELECT	`UserID`, `Rank`
					FROM	`User_Class`
					WHERE
						`UserID` = ? AND
						`ClassID` = ? AND
						IFNULL(`Rank`, 1) != 0
						" . (
							$rank !== null
								? "AND `Rank` >= ?"
								: false
						) . "
					LIMIT
						1
				",
				(
					$rank === null
						? 'ii'
						: 'iii'
				),
				(
					$rank === null
						? [
							$this->ID,
							$classID
						]
						: [
							$this->ID,
							$classID,
							$rank
						]
				)
			)
		){
			$userRank = $userClass[0]['Rank'];
			return true;
		}
		
		return false;
	}
	
	public function getCryptocurrency($ID = false){
		if(
			isset($this->Cryptocurrency) &&
			(
				!$ID ||
				$ID == $this->Cryptocurrency->ID
			)
		)
			return $this->Cryptocurrency;
		else
			$ID = $ID ?: $this->Attributes['Preferences']['CryptocurrencyID'];
		
		if (
			$cryptocurrencies = $this->db->qSelect(
				"
					SELECT
						`ISO`,
						`Name`,
						`1EUR`,
						`DecimalPlaces`,
						`Prefix_Public`,
						`Prefix_Private`,
						`Prefix_ScriptHash`,
						`Bech32HRP`
					FROM
						`Currency`
					WHERE
						`ID` = ?
				",
				'i',
				[$ID],
				false,
				true
			)
		){
			$cryptocurrency = $cryptocurrencies[0];
			return new Cryptocurrency(
				$ID,
				$cryptocurrency['ISO'],
				$cryptocurrency['Name'],
				$cryptocurrency['1EUR'],
				$cryptocurrency['DecimalPlaces'],
				$cryptocurrency['Prefix_Public'],
				$cryptocurrency['Prefix_Private'],
				$cryptocurrency['Prefix_ScriptHash'],
				$cryptocurrency['Bech32HRP']
			);
		}
			
		return false;
	}
	
	public function insertPlaintextMessage(
		$messageID,
		$HTML,
		$userID = FALSE
	){
		$userID = $userID ?: $this->ID;
		
		if (
			$contentID = $this->db->qQuery(
				"
					INSERT INTO
						`UserContent` (
							`Raw`,
							`Formatted`
						)
					VALUES
						(
							NULL,
							?
						)
				",
				's',
				[
					$HTML
				]
			)
		)
			return $this->db->qQuery(
				"
					UPDATE
						`Message`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					SET
						`ContentID` = IF(
							`Message`.`RecipientID` = thisUser.`ID`,
							?,
							`ContentID`
						),
						`ContentID_Sender` = IF(
							`Message`.`SenderID` = thisUser.`ID`,
							?,
							`ContentID_Sender`
						)
					WHERE
						`Message`.`ID` = ?
				",
				'iiii',
				[
					$userID,
					$contentID,
					$contentID,
					$messageID
				]
			);
			
		return FALSE;
	}
	
	public function sendMessage(
		$contentRaw,
		$recipientID = false,
		$senderID = SYSTEM_MESSAGER_ID,
		$timeoutDays = 99
	){
		$recipientID = $recipientID ?: $this->ID;
		$content = NXS::formatText($contentRaw);
		$array = array(
			'Date'		=> date('j F Y'),
			'Timestamp'	=> time()
		);
		
		$rsa = new RSA();
		
		$recipientMessage = $rsa->qEncrypt(
			json_encode(
				array_merge(
					$array,
					array(
						'Message' => $content
					)
				)
			),
			$this->Info('PublicKey', $recipientID)
		);
		
		if (
			$messageID = $this->db->qQuery(
				"
					INSERT INTO
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
							NULL,
							NOW() + INTERVAL ? DAY,
							NOW()
						)
				",
				'iisi',
				[
					$senderID,
					$recipientID,
					$recipientMessage,
					$timeoutDays
				]
			)
		){
			$this->insertPlaintextMessage(
				$messageID,
				$content,
				$recipientID
			);
			$this->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
				1,
				$recipientID
			);
			$this->db->incrementStatistic('messages', 1);
			$this->refreshConversation(
				$recipientID,
				$senderID
			);
			
			return $messageID;
		}
	}
	
	private function _insertConversation($correspondentIDs){
		return $this->db->qQuery("INSERT INTO `Conversation` () VALUES ()");
	}
	
	private function _insertConversationUser(
		$conversationID,
		$userID
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`Conversation_User` (
						`ConversationID`,
						`UserID`
					)
				VALUES (
					?,
					?
				)
			",
			'ii',
			[
				$conversationID,
				$userID
			]
		);
	}
	
	private function createConversation($correspondentIDs){
		if ($conversationID = $this->_insertConversation($correspondentIDs)){
			foreach($correspondentIDs as $correspondentID)
				$this->_insertConversationUser(
					$conversationID,
					$correspondentID
				);
				
			return $conversationID;
		}
		
		return false;
	}
	
	private function _getConversationIDFromMessageID($messageID){
		if (
			$conversations = $this->db->qSelect(
				"
					SELECT
						`Conversation`.`ID`
					FROM
						`Conversation`
					INNER JOIN
						`Message`
							ON `Message`.`ID` = ?
					INNER JOIN
						`Conversation_User` CU1 ON
							`Conversation`.`ID` = CU1.`ConversationID` AND
							CU1.`UserID` = `Message`.`SenderID`
					INNER JOIN
						`Conversation_User` CU2 ON
							`Conversation`.`ID` = CU2.`ConversationID` AND
							CU2.`UserID` = `Message`.`RecipientID`
				",
				'i',
				[$messageID]
			)
		)
			return $conversations[0]['ID'];
		
		return false;
	}
	
	private function getConversationID(
		$args,
		$createConversation = true,
		&$createdConversation = false
	){
		if (count($args) == 1)
			return $this->_getConversationIDFromMessageID($args[0]);
		else
			$correspondentIDs = $args;
		
		if (
			$conversations = $this->db->qSelect(
				"
					SELECT
						`Conversation`.`ID`
					FROM
						`Conversation`
					INNER JOIN
						`Conversation_User` CU1 ON
							`Conversation`.`ID` = CU1.`ConversationID` AND
							CU1.`UserID` = ?
					INNER JOIN
						`Conversation_User` CU2 ON
							`Conversation`.`ID` = CU2.`ConversationID` AND
							CU2.`UserID` = ?
				",
				'ii',
				[
					$correspondentIDs[0],
					$correspondentIDs[1],
				]
			)
		)
			return $conversations[0]['ID'];
		
		if (
			$createConversation &&
			$createdConversation = $this->createConversation($correspondentIDs)
		)
			return $createdConversation;
			
		return false;
	}
	
	private function _updateConversation($conversationID){
		return $this->db->qQuery(
			"
				UPDATE
					`Conversation`
				SET
					`LatestMessageID` = (
						SELECT
							`Message`.`ID`
						FROM
							`Message`
						INNER JOIN
							`Conversation_User` CU1 ON
								CU1.`ConversationID` = ? AND
								CU1.`UserID` = `Message`.`SenderID`
						INNER JOIN
							`Conversation_User` CU2 ON
								CU2.`ConversationID` = ? AND
								CU2.`UserID` = `Message`.`RecipientID`
						ORDER BY
							`Message`.`Sent` DESC,
							`Message`.`ID` DESC
						LIMIT
							1
					),
					`DateTime` = (
						SELECT
							`Message`.`Sent`
						FROM
							`Message`
						INNER JOIN
							`Conversation_User` CU1 ON
								CU1.`ConversationID` = ? AND
								CU1.`UserID` = `Message`.`SenderID`
						INNER JOIN
							`Conversation_User` CU2 ON
								CU2.`ConversationID` = ? AND
								CU2.`UserID` = `Message`.`RecipientID`
						ORDER BY
							`Message`.`Sent` DESC,
							`Message`.`ID` DESC
						LIMIT
							1
					)
				WHERE
					`ID` = ?
			",
			'iiiii',
			[
				$conversationID,
				$conversationID,
				$conversationID,
				$conversationID,
				$conversationID
			]
		);
	}
	
	private function _checkConversationUser(
		$conversationID,
		$userID
	){
		if (
			$conversationUsers = $this->db->qSelect(
				"
					SELECT
						(
							SELECT
								COUNT(`Message`.`ID`)
							FROM
								`Message`
							INNER JOIN
								`Conversation_User` CU1 ON
									CU1.`UserID` = `Message`.`RecipientID`
							INNER JOIN
								`Conversation_User` CU2 ON
									CU2.`UserID` = `Message`.`SenderID`
							WHERE
								CU1.`ConversationID` = `Conversation_User`.`ConversationID` AND
								CU2.`ConversationID` = `Conversation_User`.`ConversationID` AND
								`Message`.`RecipientID` = `Conversation_User`.`UserID` AND
								`Message`.`Read` = FALSE AND
								`Message`.`ContentID` IS NOT NULL
						)  > 0 Unread,
						(
							SELECT
								COUNT(`Message`.`ID`)
							FROM
								`Message`
							INNER JOIN
								`Conversation_User` CU1 ON
									CU1.`UserID` = `Message`.`RecipientID`
							INNER JOIN
								`Conversation_User` CU2 ON
									CU2.`UserID` = `Message`.`SenderID`
							WHERE
								CU1.`ConversationID` = `Conversation_User`.`ConversationID` AND
								CU2.`ConversationID` = `Conversation_User`.`ConversationID` AND
								(
									(
										`Message`.`RecipientID` = `Conversation_User`.`UserID` AND
										`Message`.`ContentID` IS NOT NULL
									) OR
									(
										`Message`.`SenderID` = `Conversation_User`.`UserID` AND
										`Message`.`ContentID_Sender` IS NOT NULL
									)
								)
						)  = 0 Deleted,
						(
							SELECT
								COUNT(`Message`.`ID`)
							FROM
								`Message`
							INNER JOIN
								`Conversation_User` CU1 ON
									CU1.`UserID` = `Message`.`RecipientID`
							INNER JOIN
								`Conversation_User` CU2 ON
									CU2.`UserID` = `Message`.`SenderID`
							WHERE
								CU1.`ConversationID` = `Conversation_User`.`ConversationID` AND
								CU2.`ConversationID` = `Conversation_User`.`ConversationID` AND
								`Message`.`RecipientID` = `Conversation_User`.`UserID` AND
								`Message`.`Important` = TRUE AND
								`Message`.`ContentID` IS NOT NULL
						) > 0 Important
					FROM
						`Conversation_User`
					WHERE
						`Conversation_User`.`ConversationID` = ? AND
						`Conversation_User`.`UserID` = ?
				",
				'ii',
				[
					$conversationID,
					$userID
				]
			)
		)
			return $conversationUsers[0];
		
		return false;
	}
	
	private function _updateConversationUser(
		$conversationID,
		$userID
	){
		if(
			$conversationUser = $this->_checkConversationUser(
				$conversationID,
				$userID
			)
		)
			return $this->db->qQuery(
				"
					UPDATE
						`Conversation_User`
					SET
						`Unread` = ?,
						`Deleted` = ?,
						`Important` = ?
					WHERE
						`Conversation_User`.`ConversationID` = ? AND
						`Conversation_User`.`UserID` = ?
				",
				'iiiii',
				[
					$conversationUser['Unread'],
					$conversationUser['Deleted'],
					$conversationUser['Important'],
					$conversationID,
					$userID
				]
			);
			
		return false;
	}
	
	public function refreshConversation(){
		$args = func_get_args();
		$conversationID = $this->getConversationID($args);
		
		$this->_updateConversation($conversationID);
		
		if (count($args) == 1)
			$this->_updateConversationUser(
				$conversationID,
				$this->ID
			);
		else
			foreach ($args as $correspondentID)
				$this->_updateConversationUser(
					$conversationID,
					$correspondentID
				);
			
		return true;
	}
	
	private function _getNextUserDomainsCombinationID(){
		if (
			$combinationIDs = $this->db->qSelect(
				"
					SELECT
						`ID`
					FROM
						`Combination`
					ORDER BY
						(
							SELECT	COUNT(DISTINCT `User_Combination`.`UserID`)
							FROM	`User_Combination`
							WHERE	`CombinationID` = `Combination`.`ID`
						) ASC,
						`ID` ASC
					LIMIT	1
				"
			)
		)
			return $combinationIDs[0]['ID'];
		
		return false;
	}
	
	private function _getCombinationElements($combinationID){
		if (
			$combinationElements = $this->db->qSelect(
				"
					SELECT
						`Element`
					FROM
						`CombinationElement`
					WHERE
						`CombinationID` = ?
					ORDER BY
						`Element` ASC
				",
				'i',
				[$combinationID]
			)
		)
			return array_map(
				function($row){
					return $row['Element'];
				},
				$combinationElements
			);
		
		return false;
	}
	
	private function _getUserDomainIDs($combinationElements){
		if (
			$allDomains = $this->db->qSelect(
				"
					SELECT
						`ID`,
						(
							SELECT	COUNT(SD2.`ID`)
							FROM	`Site_Domain` SD2
							WHERE
								`Type` = 'private' AND
								SD2.`ID` < `Site_Domain`.`ID`
						) + 1 domainNumber
					FROM
						`Site_Domain`
					WHERE
						`Type` = 'private'
				"
			)
		){
			$domainIDs = [];
			foreach ($allDomains as $domain)
				if (in_array($domain['domainNumber'], $combinationElements))
					$domainIDs[] = $domain['ID'];
					
			return $domainIDs;
		}
		
		return false;
	}
	
	private function _insertUserCombination(
		$userID,
		$combinationID
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`User_Combination` (
						`UserID`,
						`CombinationID`
					)
				VALUES (
					?,
					?
				)
			",
			'ii',
			[
				$userID,
				$combinationID
			]
		);
	}
	
	private function _insertUserDomains(
		$userID,
		$domainIDs
	){
		foreach ($domainIDs as $domainID)
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`User_Domain` (
							`UserID`,
							`DomainID`
						)
					VALUES (
						?,
						?
					)
				",
				'ii',
				[
					$userID,
					$domainID
				]
			);
		
		return true;
	}
	
	public function updatePrivateDomainsState(
		$newState,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		
		return $this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`PrivateDomains` = ?
				WHERE
					`ID` = ?
			",
			'ii',
			[
				$newState,
				$userID
			]
		);
	}
	
	public function allocateUserDomains(
		$domainsState = PRIVATE_DOMAINS_STATE_RECENTLY_GRANTED,
		$userID = false,
		$requiredPriorState = PRIVATE_DOMAINS_STATE_UNGRANTED
	){
		$userID = $userID ?: $this->ID;
		
		if (
			(
				$requiredPriorState === null ||
				$this->Info($userID, 'PrivateDomains') == $requiredPriorState
			) &&
			($combinationID = $this->_getNextUserDomainsCombinationID()) &&
			$combinationElements = $this->_getCombinationElements($combinationID)
		){
			$domainIDs = $this->_getUserDomainIDs($combinationElements);
			
			$this->_insertUserCombination($userID, $combinationID);
			$this->_insertUserDomains($userID, $domainIDs);
			$this->updatePrivateDomainsState($domainsState, $userID);
			
			return true;
		}
		
		return false;
	}
	
	public function reallocateUserDomains(){
		$users =
			$this->db->qSelect(
				"
					SELECT
					`User`.`ID` UserID,
					`User_Combination`.`CombinationID`
					FROM
					`User`
					LEFT JOIN
					`User_Combination` ON
					`User`.`ID` = `User_Combination`.`UserID`
					LEFT JOIN
					`User_Domain` ON
					`User`.`ID` = `User_Domain`.`UserID`
					WHERE
					`PrivateDomains` = 2 AND
					`User_Domain`.`UserID` IS NULL
				"
			);
		
		foreach ($users as $user){
			$userID = $user['UserID'];
			$combinationID = $user['CombinationID'];
			
			$combinationElements = $this->_getCombinationElements($combinationID);
			$domainIDs = $this->_getUserDomainIDs($combinationElements);
			$this->_insertUserDomains($userID, $domainIDs);
			//$this->updatePrivateDomainsState(PRIVATE_DOMAINS_STATE_DOMAINS_CHANGED, $userID);
		}
		
		return true;
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
	
	public function recallibrateUserNotifications(
		$chunkSize = USER_NOTIFICATION_RECALLIBRATION_CHUNK_SIZE,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		$userNotifications = [
			[
				USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
				'MessageCount'
			],
			[
				USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
				'OngoingTransactionPendingUserActionCount'
			],
			[
				USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE,
				'InDisputeTransactionCount'
			],
			[
				USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS,
				'SubscribedForumEntriesCountChange'
			],
			[
				USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
				'UnwithdrawnRejectedTransactionCount'
			],
			[
				USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
				'UnsuccessfulBroadcastCount'
			]
		];
		
		if ($this->IsVendor){
			$userNotifications = array_merge(
				$userNotifications,
				[
					[
						USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT,
						'PendingTransactionCount'
					],
					[
						USER_NOTIFICATION_TYPEID_LISTING_NEW_QUESTION,
						'UnansweredListingQuestionCount'
					],
					[
						USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL,
						'UnwithdrawnFinalizedTransactionCount'
					]
				]
			);
		} else {
			$userNotifications = array_merge(
				$userNotifications,
				[
					[
						USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK,
						'PendingFeedbackTransactionCount'
					]
				]
			);
		}
		
		$notificationCount = count($userNotifications);
		$dividend = (date('z') + 1);
		
		if(
			!$chunkSize ||
			!isset($this->Attributes['LastRecallibration']) ||
			$this->Attributes['LastRecallibration'] !== $dividend
		){
			for(
				$i = 0;
				$i < ($chunkSize ?: $notificationCount);
				$i++
			){
				$index = (($chunkSize ?: $notificationCount)*($dividend % $notificationCount)) % $notificationCount - $i;
				
				if ($userNotification = $userNotifications[$index < 0 ? $notificationCount + $index : $index])
					$this->setUserNotification(
						$userNotification[0],
						$this->Info(
							$userID,
							$userNotification[1]
						),
						$userID
					);
			}
			
			if($chunkSize)
				$this->updateAttributes(
					[
						'LastRecallibration' => $dividend
					]
				);
				
			return true;
		}
	}
	
	public function updateLastSeen(){
		if (
			!isset($_SESSION['refreshedLastSeen']) ||
			time() - $_SESSION['refreshedLastSeen'] > USER_LAST_SEEN_REFRESH_FREQUENCY_SECONDS
		){
			$_SESSION['refreshedLastSeen'] = time();
			return	$this->db->qQuery(
					"
						UPDATE
							`User`
						SET
							`LastSeen` = NOW(),
							`LastSeen_URL` = ?,
							`LastSeen_ServerID` = @@server_id
						WHERE
							`ID` = ?
					",
					'si',
					[
						$_GET['url'],
						$this->ID
					]
				);
		}
		
		return false;
	}
	
	private function _getUnansweredListingQuestionCount($userID){
		if (
			$notificationCount = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT UnansweredListingQuestion.`ID`) count
					FROM
						`Listing`
					INNER JOIN
						`Listing_Question` UnansweredListingQuestion ON
							`Listing`.`ID` = UnansweredListingQuestion.`ListingID` AND
							UnansweredListingQuestion.`Content` IS NULL
					WHERE
						`Listing`.`VendorID` = ? AND
						`Listing`.`Archived` = FALSE
				",
				'i',
				[$userID]
			)
		)
			return $notificationCount[0]['count'];
		
		return 0;
	}
	
	public function getUserNotification(
		$notificationTypeID,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		
		switch ($notificationTypeID){
			case USER_NOTIFICATION_TYPEID_LISTING_NEW_QUESTION:
				return $this->_getUnansweredListingQuestionCount($userID);
		}
		
		if(
			$notificationValue = $this->db->qSelect(
				"
					SELECT
						`Value`
					FROM
						`User_Notification`
					WHERE
						`UserID` = ? AND
						`TypeID` = ?
				",
				'ii',
				[
					$userID,
					$notificationTypeID
				]
			)
		)
			return $notificationValue[0]['Value'];
			
		return false;
	}
	
	public function incrementUserNotification(
		$notificationTypeID,
		$increment = 1,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		if(
			is_array($notificationTypeID) &&
			$notificationTypeIDs = $notificationTypeID
		)
			return array_walk(
				$notificationTypeIDs,
				function($notificationTypeID) use ($increment, $userID){
					return $this->incrementUserNotification(
						$notificationTypeID,
						$increment,
						$userID
					);
				}
			);
		
		if(
			is_array($userID) &&
			$userIDs = $userID
		)
			return array_walk(
				$userIDs,
				function($userID) use ($notificationTypeID, $increment){
					return $this->incrementUserNotification(
						$notificationTypeID,
						$increment,
						$userID
					);
				}
			);
		
		return $this->db->qQuery(
			"
				INSERT INTO
					`User_Notification` (
						`UserID`,
						`TypeID`,
						`Value`
					)
				VALUES (
					?,
					?,
					GREATEST(
						?,
						0
					)
				)
				ON DUPLICATE KEY UPDATE
					`Value` = GREATEST(
						CAST(`Value` AS SIGNED) + ?,
						0
					)
			",
			"iiii",
			[
				$userID,
				$notificationTypeID,
				$increment,
				$increment
			]
		);
	}
	
	public function setUserNotification(
		$notificationTypeID,
		$value,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		if(
			is_array($notificationTypeID) &&
			$notificationTypeIDs = $notificationTypeID
		)
			return array_walk(
				$notificationTypeIDs,
				function($notificationTypeID) use ($value, $userID){
					return $this->setUserNotification(
						$notificationTypeID,
						$value,
						$userID
					);
				}
			);
		
		if(
			is_array($userID) &&
			$userIDs = $userID
		)
			return array_walk(
				$userIDs,
				function($userID) use ($notificationTypeID, $value){
					return $this->setUserNotification(
						$notificationTypeID,
						$value,
						$userID
					);
				}
			);
		
		return $this->db->qQuery(
			"
				INSERT INTO
					`User_Notification` (
						`UserID`,
						`TypeID`,
						`Value`
					)
				VALUES (
					?,
					?,
					?
				)
				ON DUPLICATE KEY UPDATE
					`Value` = ?
			",
			"iiii",
			[
				$userID,
				$notificationTypeID,
				$value,
				$value
			]
		);
	}
	
	public function getFundsInEscrow(){
		if (
			$this->IsVendor &&
			$cryptocurrencies = $this->db->qSelect(
				"
					SELECT
						`PaymentMethod`.`CryptocurrencyID`,
						SUM(`Transaction`.`Value`) total
					FROM
						`PaymentMethod`
					INNER JOIN
						`Transaction` ON
							`PaymentMethod`.`ID` = `Transaction`.`PaymentMethodID`
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						(
							`Transaction`.`Status` IN (
								'in transit',
								'pending accept',
								'in dispute',
								'expired'
							) OR
							(
								`Transaction`.`Status` = 'pending feedback' AND
								`Transaction`.`Withdrawn` = FALSE
							)
						)
					GROUP BY
						`PaymentMethod`.`CryptocurrencyID`
				",
				'i',
				[$this->ID]
			)
		) {
			$totalValue = 0;
			return [
				'cryptocurrencies' => array_map(
					function($cryptocurrency) use (&$totalValue){
						$my_cryptocurrency = $this->getCryptocurrency($cryptocurrency['CryptocurrencyID']);
						$cryptocurrency['formatted'] = $my_cryptocurrency->formatValue($cryptocurrency['total'], true);
						$totalValue += $cryptocurrency['total'] / $my_cryptocurrency->XEUR;
						
						return $cryptocurrency;
					},
					$cryptocurrencies
				),
				'total' => NXS::formatPrice($this->Currency, $totalValue)
			];
		}
		
		return false;
	}
	
	public function getListingPaymentMethods(
		$listingID = null,
		&$allPaymentMethods = null,
		&$activePaymentMethods = null,
		&$activePaymentMethodIDs = null
	){
		if (
			$paymentMethods = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Currency`.`ID`,
						`Currency`.`ISO`,
						`Currency`.`Name`,
						`Currency`.`Color`,
						IF(
							? IS NOT NULL,
							(
								SELECT
									COUNT(`Listing_PaymentMethod`.`PaymentMethodID`)
								FROM
									`Listing_PaymentMethod`
								INNER JOIN
									`PaymentMethod` ON
										`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID`
								WHERE
									`Listing_PaymentMethod`.`ListingID` = ? AND
									`PaymentMethod`.`CryptocurrencyID` = `Currency`.`ID` AND
									`PaymentMethod`.`Enabled` = TRUE
							),
							FALSE
						) Available
					FROM
						`Currency`
					WHERE
						`Currency`.`Crypto` = TRUE
				",
				'ii',
				[
					$listingID,
					$listingID
				],
				false,
				true
			)
		){
			$allPaymentMethods = array_map(
				function($paymentMethod){
					$paymentMethod['Icon'] = Icon::getClass($paymentMethod['Name']);
					return $paymentMethod;
				},
				$paymentMethods
			);
			
			$activePaymentMethods = array_filter(
				$allPaymentMethods,
				function($paymentMethod){
					return $paymentMethod['Available'];
				}
			);
			$activePaymentMethodIDs = array_map(
				function($paymentMethod){
					return $paymentMethod['ID'];
				},
				$activePaymentMethods
			);
			
			$hasPreferredPaymentMethod = in_array(
				$this->Attributes['Preferences']['CryptocurrencyID'],
				$activePaymentMethodIDs
			);
			
			if (
				$listingID == null ||
				!$hasPreferredPaymentMethod ||
				(
					$this->Attributes['Preferences']['PromptCryptocurrency'] &&
					count($activePaymentMethods) > 1
				)
			)
				return $allPaymentMethods;
		}
		
		return false;
	}
	
	public function Info(){
		$args = func_get_args();
		
		global $argv;
		$isCron = isset($argv);
		
		if ($ignoreCache = $args[0] === 0)
			array_shift($args);
		else
			$ignoreCache = $isCron;
		
		if (is_numeric($args[0]))
			$user_id = array_shift( $args );
		else
			$user_id = $this->ID;
		
		$attributes = array();
		$array = array();
		
		$select = array();
		$join = array();
		$where = array();
		$bind_types = '';
		$bind_vars = array();
		
		$m = $this->db->m;
		if(!$ignoreCache){
			$cachedResults = $m->getMulti(
				array_map(
					function($stat) use ($user_id){
						return MEMCACHED_KEY_PREFIX_USER_INFO . $user_id . $stat;
					},
					$args
				)
			);
		}
		
		foreach($args as $key => $stat){
			/*if (isset($this->Infos[$user_id][$stat])) {
				unset($args[$key]);
				$array[$key] = $this->Infos[$user_id][$stat];
				continue;
			}*/
			if(
				!$ignoreCache &&
				$cachedResults &&
				isset($cachedResults[MEMCACHED_KEY_PREFIX_USER_INFO . $user_id . $stat])
			){
				unset($args[$key]);
				$array[$key] = $cachedResults[MEMCACHED_KEY_PREFIX_USER_INFO . $user_id . $stat];
				continue;
			}
			switch($stat){
				case 'PartiallySignedFeeBumpCount':
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT `Transaction_FeeBump`.`TransactionID`)
							FROM
								`Transaction_FeeBump`
							INNER JOIN
								`Transaction` ON
									`Transaction_FeeBump`.`TransactionID` = `Transaction`.`ID`
							INNER JOIN
								`Listing` ON
									`Transaction`.`ListingID` = `Listing`.`ID`
							WHERE
								`Listing`.`VendorID` = `User`.`ID` AND
								`Transaction_FeeBump`.`Submitted` = FALSE
						) AS "'.$key.'"
					';
				break;
				case 'ReferralCount':
					$attributes['ReferredUser'][] = "COUNT(DISTINCT ReferredUser.`ID`) AS '".$key."'";
				break;
				case 'ReputationChange':
					$attributes['Users'][] = "(`User`.`Reputation` - ?) AS '".$key."'";
					$bind_types .= 'i';
					$bind_vars[] = &$this->Attributes['LastSeen']['Reputation'];
				break;
				case 'AverageTransactionRating':
				case 'AverageUserRating':
					$attributes['Default'][] = '
						(
							SELECT
								IF(
									`User`.`Vendor`,
									AVG(`Transaction_Rating`.`Rating_Vendor`),
									AVG(`Transaction_Rating`.`Rating_Buyer`)
								)
							FROM
								`Transaction`
							INNER JOIN
								`Transaction_Rating` ON
									`Transaction`.`ID` = `Transaction_Rating`.`TransactionID`
							LEFT JOIN
								`Listing` ON
									`Transaction`.`ListingID` = `Listing`.`ID`
							WHERE
								(
									`Listing`.`VendorID` = `User`.`ID` AND
									`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
								) OR
								(
									`Transaction`.`BuyerID` = `User`.`ID` AND
									`Transaction_Rating`.`Rating_Buyer` IS NOT NULL
								)
						) AS "'.$key.'"
					';
					
					/*$attributes['TransactionRatings'][] = '
						IF(
							`User`.`Vendor`,
							AVG(`Transaction_Rating`.`Rating_Vendor`),
							AVG(`Transaction_Rating`.`Rating_Buyer`)
						) AS "'.$key.'"';*/
				break;
				case 'TransactionRatingCount':
				case 'UserRatingCount':
					//$attributes['TransactionRatings'][] = 'COUNT(DISTINCT `Transaction_Rating`.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT `Transaction_Rating`.`ID`)
							FROM
								`Transaction`
							INNER JOIN
								`Transaction_Rating` ON
									`Transaction`.`ID` = `Transaction_Rating`.`TransactionID`
							LEFT JOIN
								`Listing` ON
									`Transaction`.`ListingID` = `Listing`.`ID`
							WHERE
								(
									`Listing`.`VendorID` = `User`.`ID` AND
									`Transaction_Rating`.`Rating_Buyer` IS NOT NULL
								) OR
								`Transaction`.`BuyerID` = `User`.`ID`
						) AS "'.$key.'"
					';
				break;
				case 'LastTransactionRatingID':
				case 'LastUserRatingID':
					$attributes['TransactionRatings'][] = 'MAX(DISTINCT `Transaction_Rating`.`ID`) AS "'.$key.'"';
				break;
				case 'TransactionCommentCount':
				case 'UserCommentCount':
					//$attributes['TransactionComments'][] = 'COUNT(DISTINCT Transaction_Comment.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT Transaction_Comment.`ID`)
							FROM
								`Transaction`
							INNER JOIN
								`Transaction_Rating` Transaction_Comment ON
									Transaction_Comment.`TransactionID` = `Transaction`.`ID`
							INNER JOIN
								`Listing` ON
									`Transaction`.`ListingID` = `Listing`.`ID`
							WHERE
								`Listing`.`VendorID` = `User`.`ID` AND
								Transaction_Comment.`Content` IS NOT NULL
						) AS "'.$key.'"
					';
				break;
				case 'NumberOfDaysOld':
					$attributes['Default'][] = 'DATEDIFF(NOW(), `User`.`JoinDateTime`) AS "'.$key.'"';
				break;
				case 'ListingCount':
					$attributes['Listings'][] = 'COUNT(DISTINCT `Listing`.`ID`) AS "'.$key.'"';
				break;
				case 'UnapprovedListingCount':
					$attributes['UnapprovedListings'][] = 'COUNT(DISTINCT UnapprovedListing.`ID`) AS "'.$key.'"';
				break;
				case 'ActiveListingCount':
					$attributes['ActiveListings'][] = 'COUNT(DISTINCT ActiveListing.`ID`) AS "'.$key.'"';
				break;
				case 'UnclaimedInviteCodeCount':
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(`InviteCode`.`ID`)
							FROM
								`InviteCode`
							WHERE
								`InviteCode`.`UserID` = `User`.`ID` AND
								`InviteCode`.`ClaimedID` IS NULL
						) AS "'.$key.'"
					';
				break;
				case 'InvitedUsersCount':
					$attributes['ClaimedInviteCodes'][] = 'COUNT(DISTINCT ClaimedInviteCode.`ID`) AS "'.$key.'"';
				break;
				case 'FundsInEscrow':
					$attributes['Default'][] = '
						(
							SELECT
								SUM(`Transaction`.`Value`)
							FROM
								`Transaction`
							LEFT JOIN
								`Listing` ON
									`Transaction`.`ListingID` = `Listing`.`ID`
							WHERE
								(
									`Listing`.`VendorID` = `User`.`ID` OR
									`Transaction`.`BuyerID` = `User`.`ID`
								) AND
								(
									`Transaction`.`Status` IN (
										\'in transit\',
										\'pending accept\',
										\'in dispute\'
									) OR
									(
										`Transaction`.`Status` = \'pending feedback\' AND
										`Transaction`.`Withdrawn` = FALSE
									)
								)
						) AS "'.$key.'"
					';
				break;
				case 'FavoriteListingCount':
					//$attributes['User_Listings'][] = 'COUNT(DISTINCT FavoritedListing.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT `User_Listing`.`ListingID`)
							FROM
								`User_Listing`
							INNER JOIN
								`Listing` ON
									`User_Listing`.`ListingID` = `Listing`.`ID`
							WHERE
								`User`.`ID` = `User_Listing`.`UserID` AND
								`Listing`.`Archived` = FALSE /*AND
								`Listing`.`Inactive` = FALSE*/
								
						) AS "'.$key.'"
					';
				break;
				case 'TransactionRatingCountChange':
				case 'UserRatingCountChange':
					//$attributes['UnseenTransactionRatings'][] = 'COUNT(DISTINCT UnseenTransaction_Rating.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnseenTransaction_Rating.`ID`)
							FROM
								`Transaction_Rating` UnseenTransaction_Rating
							WHERE
								(
									(
										`User`.`ID` = UnseenTransaction_Rating.`BuyerID` AND
										UnseenTransaction_Rating.`Rating_Buyer` IS NOT NULL
									) OR
									(
										`User`.`ID` = UnseenTransaction_Rating.`VendorID` AND
										UnseenTransaction_Rating.`Rating_Vendor` IS NOT NULL
									)
								) AND
								UnseenTransaction_Rating.`ID` > ?
								
						) AS "'.$key.'"
					';
					$bind_types .= 'i';
					$bind_vars[] = &$this->Attributes['LastSeen']['TransactionRating_ID'];
				break;
				case 'TransactionCount':
					$attributes['Transactions'][] = 'COUNT(DISTINCT `Transaction`.`ID`) AS "'.$key.'"';
				break;
				case 'LastTransactionID':
					$attributes['Transactions'][] = 'MAX(`Transaction`.`ID`) AS "'.$key.'"';
				break;
				case 'OngoingTransactionCount':
					$attributes['OngoingTransactions'][] = 'COUNT(DISTINCT OngoingTransaction.`ID`) AS "'.$key.'"';
				break;
				case 'OngoingTransactionOfInterestCount':
					$attributes['OngoingTransactionsOfInterest'][] = 'COUNT(DISTINCT OngoingTransactionOfInterest.`ID`) AS "'.$key.'"';
				break;
				case 'OngoingTransactionPendingUserActionCount':
					//$attributes['OngoingTransactionsPendingUserAction'][] = 'COUNT(DISTINCT OngoingTransactionPendingUserAction.`ID`) AS "'.$key.'"';	
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT OngoingTransactionPendingUserAction.`ID`)
							FROM
								`Transaction` OngoingTransactionPendingUserAction
							LEFT JOIN
								`PaymentMethod` ON
									OngoingTransactionPendingUserAction.`PaymentMethodID` = `PaymentMethod`.`ID`
							LEFT JOIN
								`PendingBroadcast` UnsuccessfulBroadcast ON	
									UnsuccessfulBroadcast.`TransactionID` = OngoingTransactionPendingUserAction.`ID` AND
									UnsuccessfulBroadcast.`BroadcastAttempts` >= ' . MAXIMUM_BROADCAST_ATTEMPTS . '
							WHERE
								(	# IF VENDOR:
									`PaymentMethod`.`UserID` = `User`.`ID` AND
									(
										OngoingTransactionPendingUserAction.`Status` IN ("pending accept", "in dispute") OR
										(
											OngoingTransactionPendingUserAction.`Status` = "pending feedback" AND
											(
												OngoingTransactionPendingUserAction.`Withdrawn` = FALSE OR
												UnsuccessfulBroadcast.`UserID` IS NOT NULL
											)
										) OR
										(
											OngoingTransactionPendingUserAction.`Status` IN ("rejected", "refunded") AND
											(
												OngoingTransactionPendingUserAction.`Withdrawn` = FALSE OR
												UnsuccessfulBroadcast.`UserID` IS NOT NULL
											)
										)
									)
								) OR
								(	# IF BUYER :
									`User`.`ID` = OngoingTransactionPendingUserAction.`BuyerID` AND
									OngoingTransactionPendingUserAction.`NotificationIncremented` = TRUE
								)
						) AS "'.$key.'"
					';
				break;
				case 'PendingTransactionCount':
					//$attributes['PendingTransactions'][] = 'COUNT(DISTINCT PendingTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT PendingTransaction.`ID`)
							FROM
								`Listing`
							LEFT JOIN
								`Transaction` PendingTransaction ON
									`Listing`.`ID` = PendingTransaction.`ListingID` AND
									PendingTransaction.`Status` = "pending accept"	
							WHERE
								`Listing`.`VendorID` = `User`.`ID`
								
						) AS "'.$key.'"
					';
				break;
				case 'InTransitTransactionCount':
					$attributes['InTransitTransactions'][] = 'COUNT(DISTINCT InTransitTransaction.`ID`) AS "'.$key.'"';
				break;
				case 'LastInTransitTransactionID':
					$attributes['InTransitTransactions'][] = 'MAX(InTransitTransaction.`ID`) AS "'.$key.'"';
				break;
				case 'InTransitTransactionCountChange':
					//$attributes['UnseenInTransitTransactions'][] = 'COUNT(DISTINCT UnseenInTransitTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnseenInTransitTransaction.`ID`)
							FROM
								`Transaction` UnseenInTransitTransaction
							LEFT JOIN
								`Listing` ON
									`Listing`.`ID` = UnseenInTransitTransaction.`ListingID`
							WHERE
								(
									`User`.`ID` = `Listing`.`VendorID` OR
									UnseenInTransitTransaction.`BuyerID` = `User`.`ID`
								) AND
								UnseenInTransitTransaction.`Status` = "in transit" AND
								UnseenInTransitTransaction.`ID` > ?
								
						) AS "'.$key.'"
					';
					
					$bind_types .= 'i';
					$bind_vars[] = &$this->Attributes['LastSeen']['InTransit_Transaction_ID'];
				break;
				case 'InTransitBuyingTransactionCountChange':
					//$attributes['UnseenInTransitBuyingTransactions'][] = 'COUNT(DISTINCT UnseenInTransitBuyingTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnseenInTransitBuyingTransaction.`ID`)
							FROM
								`Transaction` UnseenInTransitBuyingTransaction
							WHERE
								UnseenInTransitBuyingTransaction.`BuyerID` = `User`.`ID` AND
								(
									UnseenInTransitBuyingTransaction.`Status` = "in transit" OR
									(
										UnseenInTransitBuyingTransaction.`Escrow` = FALSE AND
										UnseenInTransitBuyingTransaction.`Status` = "pending feedback"
									)
								) AND
								UnseenInTransitBuyingTransaction.`ID` > ?
								
						) AS "'.$key.'"
					';
					$bind_types .= 'i';
					$bind_vars[] = &$this->Attributes['LastSeen']['InTransit_Transaction_ID'];
				break;
				case 'PendingFeedbackTransactionCount':
					//$attributes['PendingFeedbackTransactions'][] = 'COUNT(DISTINCT PendingFeedbackTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT PendingFeedbackTransaction.`ID`)
							FROM
								`Transaction` PendingFeedbackTransaction
							WHERE
								PendingFeedbackTransaction.`Timeout` > NOW() AND
								PendingFeedbackTransaction.`Status` = "pending feedback" AND
								PendingFeedbackTransaction.`BuyerID` = `User`.`ID` AND
								PendingFeedbackTransaction.`Feedback_Buyer` = FALSE
						) AS "'.$key.'"
					';
				break;
				case 'InDisputeTransactionCount':
					//$attributes['InDisputeTransactions'][] = 'COUNT(DISTINCT InDisputeTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT InDisputeTransaction.`ID`)
							FROM
								`Transaction` InDisputeTransaction
							INNER JOIN `Listing` ON
								InDisputeTransaction.`ListingID` = `Listing`.`ID`
							WHERE
								(
									`Listing`.`VendorID` = `User`.`ID` OR
									InDisputeTransaction.`BuyerID` = `User`.`ID`
								) AND
								InDisputeTransaction.`Status` = "in dispute"
								
						) AS "'.$key.'"
					';
				break;
				case 'UnwithdrawnRejectedTransactionCount':
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnwithdrawnRejectedTransaction.`ID`)
							FROM
								`Listing`
							LEFT JOIN `Transaction` UnwithdrawnRejectedTransaction ON
								UnwithdrawnRejectedTransaction.`ListingID` = `Listing`.`ID` AND
								UnwithdrawnRejectedTransaction.`Status` IN("rejected", "refunded") AND
								UnwithdrawnRejectedTransaction.`Withdrawn` = FALSE AND
								UnwithdrawnRejectedTransaction.`Unconfirmed` = FALSE AND
								UnwithdrawnRejectedTransaction.`Timeout` > NOW()
							WHERE
								`Listing`.`VendorID` = `User`.`ID`
								
						) AS "'.$key.'"
					';
				break;
				case 'UnwithdrawnFinalizedTransactionCount':
					//$attributes['UnwithdrawnFinalizedTransactions'][] = 'COUNT(DISTINCT UnwithdrawnFinalizedTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnwithdrawnFinalizedTransaction.`ID`)
							FROM
								`PaymentMethod`
							LEFT JOIN
								`Transaction` UnwithdrawnFinalizedTransaction ON
									UnwithdrawnFinalizedTransaction.`PaymentMethodID` = `PaymentMethod`.`ID` AND
									UnwithdrawnFinalizedTransaction.`Status` = "pending feedback" AND
									UnwithdrawnFinalizedTransaction.`Withdrawn` = FALSE
							WHERE
								`PaymentMethod`.`UserID` = `User`.`ID`
								
						) AS "'.$key.'"
					';
				break;
				case 'IncipientTransactionCount':
					//$attributes['UnwithdrawnFinalizedTransactions'][] = 'COUNT(DISTINCT UnwithdrawnFinalizedTransaction.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT IncipientTransaction.`ID`)
							FROM
								`Listing`
							LEFT JOIN
								`Transaction` IncipientTransaction ON
									IncipientTransaction.`ListingID` = `Listing`.`ID` AND
									IncipientTransaction.`Status` = "pending deposit" AND
									IncipientTransaction.`Paid` = TRUE AND
									IncipientTransaction.`Timeout` > NOW()
							WHERE
								`Listing`.`VendorID` = `User`.`ID`
								
						) AS "'.$key.'"
					';
				break;
				case 'ExpiredTransactionCount':
					$attributes['ExpiredTransactions'][] = 'COUNT(DISTINCT ExpiredTransaction.`ID`) AS "'.$key.'"';
				break;
				case 'MessageCount':
				case 'MessageCount_Unread':
					//$attributes['UnreadMessages'][] = 'COUNT(DISTINCT `Message`.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT `Message`.`ID`)
							FROM
								`Message`
							WHERE
								`User`.`ID` = `Message`.`RecipientID` AND
								`Message`.`Read` = FALSE AND
								`Message`.`ContentID` IS NOT NULL AND
								(
									SELECT	`Banned`
									FROM	`User` MessageSender
									WHERE	MessageSender.`ID` = `Message`.`SenderID`
								) = FALSE
						) AS "'.$key.'"
					';
				break;
				case 'OldestUnreadMessageID':
					$attributes['UnreadMessages'][] = 'MIN(DISTINCT `Message`.`ID`) AS "'.$key.'"';
				break;
				case 'SubscribedForumEntriesCountChange':
					list(
						$SubscribedDiscussionCountChange,
						$SubscribedBlogPostCountChange
					) = $this->Info(
						'SubscribedDiscussionCountChange',
						'SubscribedBlogPostCountChange'
					);
					
					$SubscribedForumEntriesCountChange = $SubscribedDiscussionCountChange + $SubscribedBlogPostCountChange;
					
					$attributes['Default'][] = '
						' . $SubscribedForumEntriesCountChange . ' AS "'.$key.'"
					';
				break;
				case 'SubscribedBlogPostCountChange':
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnseenSubscribedBlogPost.`ID`)
							FROM
								`BlogPost` UnseenSubscribedBlogPost
							INNER JOIN
								`Blog` ON
									UnseenSubscribedBlogPost.`BlogID` = `Blog`.`ID`
							INNER JOIN
								`DiscussionCategory` ON
									`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
							INNER JOIN
								`Site` ON
									`DiscussionCategory`.`SiteID` = `Site`.`ForumID`
							INNER JOIN
								`User` thisUser
							LEFT JOIN
								`Blog_Subscription` ON
									`Blog`.`ID` = `Blog_Subscription`.`BlogID` AND
									thisUser.`ID` = `Blog_Subscription`.`SubscriberID`
							LEFT JOIN
								`BlogPost_Subscription` ON
									UnseenSubscribedBlogPost.`ID` = `BlogPost_Subscription`.`BlogPostID` AND
									thisUser.`ID` = `BlogPost_Subscription`.`SubscriberID`
							WHERE
								thisUser.`ID` = `User`.`ID` AND
								`Site`.`ID` = ? AND
								(
									(
										SELECT	`DateInserted`
										FROM	`BlogPost`
										WHERE	`ID` = UnseenSubscribedBlogPost.`ID`
									) > 
									(
										SELECT	`DateInserted`
										FROM	`BlogPost`
										WHERE	`ID` = `Blog_Subscription`.`SeenPostID`
									) OR
									(
										SELECT
											`DateInserted`
										FROM
											`BlogPostComment`
										WHERE
											`BlogPostComment`.`BlogPostID` = UnseenSubscribedBlogPost.`ID`
										ORDER BY
											`DateInserted` DESC,
											`ID` DESC
										LIMIT 1
									) > (
										SELECT	`DateInserted`
										FROM	`BlogPostComment`
										WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
									)
								)
						) AS "'.$key.'"
					';
					
					$bind_types .= 'i';
					$bind_vars[] = &$this->db->site_id;
				break;
				case 'SubscribedDiscussionCountChange':
					//$attributes['UnseenSubscribedDiscussions'][] = 'COUNT(DISTINCT UnseenSubscribedDiscussion.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnseenSubscribedDiscussion.`ID`)
							FROM
								`Discussion_Subscription`
							LEFT JOIN	`Site`
								ON	`Site`.`ID` = ?
							LEFT JOIN	`DiscussionCategory`
								ON	`DiscussionCategory`.`SiteID` = `Site`.`ForumID`
							LEFT JOIN	`Discussion` UnseenSubscribedDiscussion
								ON	`Discussion_Subscription`.`DiscussionID` = UnseenSubscribedDiscussion.`ID`
								AND	`DiscussionCategory`.`ID` = UnseenSubscribedDiscussion.`CategoryID`
								AND	(
										SELECT
											`DateInserted`
										FROM
											`Discussion_Comment`
										WHERE
											`Discussion_Comment`.`DiscussionID` = UnseenSubscribedDiscussion.`ID`
										ORDER BY
											`DateInserted` DESC,
											`ID` DESC
										LIMIT
											1
									) > (
										SELECT	`DateInserted`
										FROM	`Discussion_Comment`
										WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
									)
							WHERE
								`Discussion_Subscription`.`SubscriberID` = `User`.`ID`
								
						) AS "'.$key.'"
					';
					
					$bind_types .= 'i';
					$bind_vars[] = &$this->db->site_id;
				break;
				case 'UnansweredListingQuestionCount':
					//$attributes['UnansweredListingQuestions'][] = 'COUNT(DISTINCT UnansweredListingQuestion.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnansweredListingQuestion.`ID`)
							FROM
								`Listing`
							LEFT JOIN `Listing_Question` UnansweredListingQuestion ON
								`Listing`.`ID` = UnansweredListingQuestion.`ListingID` AND
								UnansweredListingQuestion.`Content` IS NULL
							WHERE
								`Listing`.`VendorID` = `User`.`ID` AND
								`Listing`.`Archived` = FALSE
								
						) AS "'.$key.'"
					';
				break;
				case 'FollowerCount':
					$attributes['Followers'][] = 'COUNT(DISTINCT `User_User`.`FollowerID`) AS "'.$key.'"';
				break;
				case 'UnsuccessfulBroadcastCount':
					//$attributes['UnsuccessfulBroadcasts'][] = 'COUNT(DISTINCT UnsuccessfulBroadcast.`ID`) AS "'.$key.'"';
					
					$attributes['Default'][] = '
						(
							SELECT
								COUNT(DISTINCT UnsuccessfulBroadcast.`ID`)
							FROM
								`PendingBroadcast` UnsuccessfulBroadcast
							INNER JOIN
								`Transaction` ON
									UnsuccessfulBroadcast.`TransactionID` = `Transaction`.`ID`
							LEFT JOIN
								`Transaction_FeeBump` ON
									UnsuccessfulBroadcast.`TransactionID` = `Transaction_FeeBump`.`TransactionID`
							WHERE
								`User`.`ID` = UnsuccessfulBroadcast.`UserID` AND
								UnsuccessfulBroadcast.`BroadcastAttempts` >= ' . MAXIMUM_BROADCAST_ATTEMPTS . ' AND
								(
									`Transaction_FeeBump`.`TransactionID` IS NULL OR
									`Transaction`.`Status` != "pending deposit"
								)
								
						) AS "'.$key.'"
					';
				break;
				default: 
					$attributes['Users'][] = "`User`.`".$stat."` AS '".$key."'";
				break;
			}
		}
		if( !empty($attributes) ){
			foreach($attributes as $table => $columns){
				switch($table){
					// ADD JOINS FOR TABLES OTHER THAN USER
					case 'UnsuccessfulBroadcasts':
						$join['UnsuccessfulBroadcasts'] = '
							LEFT JOIN	`PendingBroadcast` UnsuccessfulBroadcast
								ON	`User`.`ID` = UnsuccessfulBroadcast.`UserID`
							AND	UnsuccessfulBroadcast.`BroadcastAttempts` >= ' . MAXIMUM_BROADCAST_ATTEMPTS . '
						';
					break;
					case 'ReferredUser':
						$join['ReferredUser'] = '
							LEFT JOIN	`User` ReferredUser
								ON	`User`.`ID` = ReferredUser.`ReferrerID`
							AND	ReferredUser.`Reputation` != 0';
					break;
					case 'Listings':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
					break;
					case 'UnapprovedListings':
						$join['UnapprovedListing'] = "LEFT JOIN `Listing` UnapprovedListing ON `User`.`ID` = UnapprovedListing.`VendorID` AND UnapprovedListing.`Approved` = FALSE";
					break;
					case 'ActiveListings':
						$join['ActiveListing'] = "
						LEFT JOIN	`Listing` ActiveListing
							ON	`User`.`ID` = ActiveListing.`VendorID`
							AND	ActiveListing.`Inactive` = FALSE";
					break;
					case 'UnclaimedInviteCodes':
						$join['UnclaimedInviteCode'] = "
							LEFT JOIN
								`InviteCode` UnclaimedInviteCode ON
									`User`.`ID` = UnclaimedInviteCode.`UserID`
						";
					break;
					case 'ClaimedInviteCodes':
						$join['ClaimedInviteCode'] = "
							LEFT JOIN
								`InviteCode` ClaimedInviteCode ON
									`User`.`ID` = ClaimedInviteCode.`UserID` AND
									ClaimedInviteCode.`ClaimedID` IS NOT NULL
						";
					break;
					case 'User_Listings':
						$join['User_Listing'] = "
							LEFT JOIN	`User_Listing`
								ON	`User`.`ID` = `User_Listing`.`UserID`
							LEFT JOIN	`Listing` FavoritedListing
								ON	`User_Listing`.`ListingID` = FavoritedListing.`ID`
								AND	FavoritedListing.`Inactive` = FALSE
						";
					break;
					case 'TransactionRatings':
						$join['Transaction_Rating'] = "
							LEFT JOIN	`Transaction_Rating`
								ON	(
										`User`.`ID` = `Transaction_Rating`.`BuyerID` AND
										`Transaction_Rating`.`Rating_Buyer` IS NOT NULL
									) OR
									(
										`User`.`ID` = `Transaction_Rating`.`VendorID` AND
										`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
									)
						";
					break;
					case 'UnseenTransactionRatings':
						$join['UnseenTransactionRating'] = "
							LEFT JOIN	`Transaction_Rating` UnseenTransaction_Rating
								ON	(
										(
											`User`.`ID` = UnseenTransaction_Rating.`BuyerID` AND
											UnseenTransaction_Rating.`Rating_Buyer` IS NOT NULL
										) OR
										(
											`User`.`ID` = UnseenTransaction_Rating.`VendorID` AND
											UnseenTransaction_Rating.`Rating_Vendor` IS NOT NULL
										)
									) AND
									UnseenTransaction_Rating.`ID` > ?";
						$bind_types .= 'i';
						$bind_vars[] = &$this->Attributes['LastSeen']['TransactionRating_ID'];
					break;
					case 'TransactionComments':
						$join['TransactionComments'] = "
							LEFT JOIN	`Transaction_Rating` Transaction_Comment
								ON	`User`.`ID` = Transaction_Comment.`VendorID`
								AND	Transaction_Comment.`Content` IS NOT NULL";
					break;
					case 'UnseenTransactionComments':
						$join['UnseenUser_Comment'] = "
							LEFT JOIN	`Transaction_Rating` UnseenTransaction_Comment
								ON `User`.`ID` = UnseenTransaction_Comment.`VendorID`
							AND UnseenTransaction_Comment.`ID` > ?
							AND UnseenTransaction_Comment.`Content` IS NOT NULL ";
						$bind_types .= 'i';
						$bind_vars[] = &$this->Attributes['LastSeen']['TransactionRating_ID'];
					break;
					case 'UnreadMessages':
						$join['Message'] = '
							LEFT JOIN	`Message`
								ON	`User`.`ID` = `Message`.`RecipientID`
								AND	`Message`.`Read` = FALSE
								AND `Message`.`ContentID` IS NOT NULL
								AND	(
										SELECT	`Banned`
										FROM	`User` MessageSender
										WHERE	MessageSender.`ID` = `Message`.`SenderID`
									) = FALSE';
					break;
					case 'AllTransactions':
					case 'Transactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['Transaction'] = 'LEFT JOIN `Transaction` ON `Listing`.`ID` = `Transaction`.`ListingID` OR `User`.`ID` = `Transaction`.`BuyerID`';
					break;
					case 'OngoingTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['OngoingTransaction'] = '
							LEFT JOIN	`Transaction` OngoingTransaction
								ON	(
										`Listing`.`ID` = OngoingTransaction.`ListingID` OR
										`User`.`ID` = OngoingTransaction.`BuyerID`
									) AND
									(
										OngoingTransaction.`Status` IN("in dispute") OR
										(
											OngoingTransaction.`Timeout` > NOW() AND
											(
												OngoingTransaction.`Status` IN(
													"pending deposit",
													"in transit",
													"pending accept"
												)
											)
										)
									)';
					break;
					case 'OngoingTransactionsOfInterest':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['OngoingTransactionOfInterest'] = '
							LEFT JOIN	`Transaction` OngoingTransactionOfInterest
								ON	(
										`Listing`.`ID` = OngoingTransactionOfInterest.`ListingID` OR
										`User`.`ID` = OngoingTransactionOfInterest.`BuyerID`
									) AND
									(
										(
											OngoingTransactionOfInterest.`Status` = "pending deposit" AND
											`User`.`ID` = OngoingTransactionOfInterest.`BuyerID` AND
											OngoingTransactionOfInterest.`Timeout` > NOW()
										) OR
										OngoingTransactionOfInterest.`Status` = "in transit" OR
										OngoingTransactionOfInterest.`Status` = "pending accept" OR
										OngoingTransactionOfInterest.`Status` = "in dispute"
									)
						';
					break;
					case 'OngoingTransactionsPendingUserAction':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						
						$join['UnsuccessfulBroadcasts'] = '
							LEFT JOIN	`PendingBroadcast` UnsuccessfulBroadcast
								ON	
									`User`.`ID` = UnsuccessfulBroadcast.`UserID` AND
									UnsuccessfulBroadcast.`BroadcastAttempts` >= ' . MAXIMUM_BROADCAST_ATTEMPTS . '
						';
						$join['OngoingTransactionPendingUserAction'] = '
							LEFT JOIN	`Transaction` OngoingTransactionPendingUserAction
								ON	(	# IF VENDOR:
										`Listing`.`ID` = OngoingTransactionPendingUserAction.`ListingID` AND
										(
											OngoingTransactionPendingUserAction.`Status` IN ("pending accept", "in dispute") OR
											(
												OngoingTransactionPendingUserAction.`Status` = "pending feedback" AND
												(
													OngoingTransactionPendingUserAction.`Withdrawn` = FALSE OR
													UnsuccessfulBroadcast.`TransactionID` = OngoingTransactionPendingUserAction.`ID` OR
													(
														OngoingTransactionPendingUserAction.`Feedback_Vendor` IS FALSE AND
														OngoingTransactionPendingUserAction.`Timeout` > NOW()
													)
												)
											) OR
											(
												OngoingTransactionPendingUserAction.`Status` IN ("rejected", "refunded") AND
												OngoingTransactionPendingUserAction.`Unconfirmed` = FALSE AND
												(
													OngoingTransactionPendingUserAction.`Withdrawn` = FALSE OR
													UnsuccessfulBroadcast.`TransactionID` = OngoingTransactionPendingUserAction.`ID`
												)
											)
										)
									) OR
									(	# IF BUYER :
										`User`.`ID` = OngoingTransactionPendingUserAction.`BuyerID` AND
										(
											OngoingTransactionPendingUserAction.`Status` IN ("in transit", "in dispute", "expired") OR
											(
												OngoingTransactionPendingUserAction.`Timeout` > NOW() AND
												(
													(
														OngoingTransactionPendingUserAction.`Status` = "pending deposit" AND
														OngoingTransactionPendingUserAction.`RedeemScript` IS NOT NULL
													) OR
													(
														OngoingTransactionPendingUserAction.`Status` = "pending feedback" AND
														OngoingTransactionPendingUserAction.`Feedback_Buyer` = FALSE
													)
												)
											)
										)
									)
						';
					break;
					case 'PendingTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['PendingTransaction'] = 'LEFT JOIN `Transaction` PendingTransaction ON `Listing`.`ID` = PendingTransaction.`ListingID` AND PendingTransaction.`Status` = "pending accept"';
					break;
					case 'InTransitTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['InTransitTransaction'] = '
							LEFT JOIN `Transaction` InTransitTransaction
								ON	(
										`Listing`.`ID` = InTransitTransaction.`ListingID` OR
										InTransitTransaction.`BuyerID` = `User`.`ID`
									) AND
									InTransitTransaction.`Status` = "in transit"';
					break;
					case 'UnseenInTransitTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['UnseenInTransitTransaction'] = '
							LEFT JOIN `Transaction` UnseenInTransitTransaction
								ON	(
										`Listing`.`ID` = UnseenInTransitTransaction.`ListingID` OR
										UnseenInTransitTransaction.`BuyerID` = `User`.`ID`
									) AND
									UnseenInTransitTransaction.`Status` = "in transit" AND
									UnseenInTransitTransaction.`ID` > ?';
						$bind_types .= 'i';
						$bind_vars[] = &$this->Attributes['LastSeen']['InTransit_Transaction_ID'];
					break;
					case 'UnseenInTransitBuyingTransactions':
						$join['UnseenInTransitBuyingTransaction'] = '
							LEFT JOIN `Transaction` UnseenInTransitBuyingTransaction
								ON
									UnseenInTransitBuyingTransaction.`BuyerID` = `User`.`ID` AND
									(
										UnseenInTransitBuyingTransaction.`Status` = "in transit" OR
										(
											UnseenInTransitBuyingTransaction.`Escrow` = FALSE AND
											UnseenInTransitBuyingTransaction.`Status` = "pending feedback"
										)
									) AND
									UnseenInTransitBuyingTransaction.`ID` > ?';
						$bind_types .= 'i';
						$bind_vars[] = &$this->Attributes['LastSeen']['InTransit_Transaction_ID'];
					break;
					case 'InDisputeTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['InDisputeTransaction'] = '
							LEFT JOIN `Transaction` InDisputeTransaction
								ON	(
										`Listing`.`ID` = InDisputeTransaction.`ListingID` OR
										InDisputeTransaction.`BuyerID` = `User`.`ID`
									) AND
									InDisputeTransaction.`Status` = "in dispute"';
					break;
					case 'PendingFeedbackTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['PendingFeedbackTransaction'] = '
							LEFT JOIN `Transaction` PendingFeedbackTransaction
								ON	PendingFeedbackTransaction.`Timeout` > NOW() AND
									PendingFeedbackTransaction.`Status` = "pending feedback" AND
									(
										(
											PendingFeedbackTransaction.`BuyerID` = `User`.`ID` AND
											PendingFeedbackTransaction.`Feedback_Buyer` = FALSE
										) OR
										(
											PendingFeedbackTransaction.`ListingID` = `Listing`.`ID` AND
											PendingFeedbackTransaction.`Feedback_Vendor` = FALSE AND
											(
												PendingFeedbackTransaction.`Escrow` = TRUE OR
												PendingFeedbackTransaction.`Shipped` = TRUE
											)
										)
									)';
					break;
					case 'UnwithdrawnFinalizedTransactions':
						$join['Listing'] = 'LEFT JOIN `Listing` ON `User`.`ID` = `Listing`.`VendorID`';
						$join['UnwithdrawnFinalizedTransaction'] = "
							LEFT JOIN `Transaction` UnwithdrawnFinalizedTransaction
								ON	UnwithdrawnFinalizedTransaction.`ListingID` = `Listing`.`ID`
								AND	UnwithdrawnFinalizedTransaction.`Status` = 'pending feedback'
								AND UnwithdrawnFinalizedTransaction.`Withdrawn` = FALSE
						";
					break;
					case 'ExpiredTransactions':
						$join['ExpiredTransaction'] = "
							LEFT JOIN `Transaction` ExpiredTransaction
								ON	ExpiredTransaction.`BuyerID`	= `User`.`ID`
								AND	ExpiredTransaction.`Status`		= 'expired'
						";
					break;
					case 'UnseenSubscribedDiscussions':
						$join['Site'] = "
							LEFT JOIN	`Site`
								ON	`Site`.`ID` = ?
						";
						$bind_types .= 'i';
						$bind_vars[] = &$this->db->site_id;
						$join[] = '
							LEFT JOIN	`DiscussionCategory`
								ON	`DiscussionCategory`.`SiteID` = `Site`.`ForumID`
							LEFT JOIN	`Discussion_Subscription`
								ON	`User`.`ID` = `Discussion_Subscription`.`SubscriberID`
							LEFT JOIN	`Discussion` UnseenSubscribedDiscussion
								ON	`Discussion_Subscription`.`DiscussionID` = UnseenSubscribedDiscussion.`ID`
								AND	`DiscussionCategory`.`ID` = UnseenSubscribedDiscussion.`CategoryID`
								AND	(
										SELECT	MAX(`Discussion_Comment`.`ID`)
										FROM	`Discussion_Comment`
										WHERE	`Discussion_Comment`.`DiscussionID` = UnseenSubscribedDiscussion.`ID`
									) > `Discussion_Subscription`.`SeenCommentID`
								';
					break;
					case 'UnansweredListingQuestions':
						$join[] = 'LEFT JOIN `Listing_Question` UnansweredListingQuestion ON `Listing`.`ID` = UnansweredListingQuestion.`ListingID` AND UnansweredListingQuestion.`Content` IS NULL';
					break;
					case 'Followers':
						$join[] = 'LEFT JOIN `User_User` ON `User`.`ID` = `User_User`.`UserID`';
					break;
				}
				foreach($columns as $column){
					$select[] = $column;
				}
			}
			
			$bind_types .= 'i';
			$bind_vars[] = &$user_id;
			array_unshift($bind_vars, $bind_types);
			
			$query = "
				SELECT
					".implode(', ', $select)."
				FROM
					`User`
					".implode(' ', $join)."
				WHERE
					`User`.`ID` = ?
				LIMIT 1
			";
			
			if( $stmt_UpdateStats = $this->db->prepare($query) ){
				call_user_func_array(array($stmt_UpdateStats, 'bind_param'), $bind_vars);
				$stmt_UpdateStats->execute();
				$stmt_UpdateStats->store_result();
				if($stmt_UpdateStats->num_rows == 1){
					$variables = array();
        				$data = array();
					$meta = $stmt_UpdateStats->result_metadata();
					while($field = $meta->fetch_field()){
						$variables[] = &$data[$field->name];
					}
					
					call_user_func_array(array($stmt_UpdateStats, 'bind_result'), $variables);
					
					while ($stmt_UpdateStats->fetch())
						foreach ($data as $k=>$v)
							$array[$k] = $v;
					
					$cacheData = [];
					foreach($data as $key => $value){
						$cacheData[MEMCACHED_KEY_PREFIX_USER_INFO . $user_id . $args[$key]] = $value;
					}
					
					/*$m->setMulti(
						$cacheData,
						$isCron ? MEMCACHED_CACHE_EXPIRATION_SECONDS_USER_INFO_CRON : MEMCACHED_CACHE_EXPIRATION_SECONDS_USER_INFO
					);*/
				} else
					$this->Notifications->quick('FatalError');
			} else {
				//if($this->IsAdmin)
					//die($this->db->error);
				
				return false;
			}
		} else if( empty($array) )
			return false;
		
		$memcachedIterationCountIndex = MEMCACHED_KEY_PREFIX_USER_INFO . $user_id . MEMCACHED_KEY_SUFFIX_ITERATION_COUNT;
		if (!$isCron)
			$m->set(
				$memcachedIterationCountIndex,
				0,
				MEMCACHED_CACHE_EXPIRATION_SECONDS_USER_INFO
			);
		
		return count($array) == 1 ? $array[0] : $array;
	}
	
	private function _getInviteDispensationQuantity(){
		if (
			$result = $this->db->qSelect(
				"
					SELECT
						GREATEST(
							`Site`.`InviteDispensationQuantity`,
							IFNULL(
								MAX(`UserClass`.`InviteDispensationQuantity`),
								0
							)
						) InviteDispensationQuantity
					FROM
						`Site`
					LEFT JOIN
						`User_Class` ON
							`User_Class`.`UserID` = ?
					LEFT JOIN
						`UserClass` ON
							`UserClass`.`ID` = `User_Class`.`ClassID`
					WHERE
						`Site`.`ID` = ?
				",
				'ii',
				[
					$this->ID,
					$this->db->site_id
				]
			)
		)
			return $result[0]['InviteDispensationQuantity'];
		
		return false;
	}
	
	public function getInvitesEntitlement(&$numberOfInvites = NULL){
		list(
			$dispenseInvites,
			$dispensationFrequency
		) = $this->db->getSiteInfo(
			'DispenseInvites',
			'InviteDispensationFrequency'
		);
		
		if ($dispenseInvites){
			$userNumberOfDaysOld = $this->Info('NumberOfDaysOld');
			
			if($userNumberOfDaysOld < $dispensationFrequency)
				return FALSE;
			
			if ($dispensationQuantity = $this->_getInviteDispensationQuantity()){
				$numberOfInvitesGivenRecently = $this->_getNumberOInvitesGivenWithinInterval($dispensationFrequency);
			
				if ($numberOfInvites = $dispensationQuantity - $numberOfInvitesGivenRecently)
					return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function _getNumberOInvitesGivenWithinInterval($daysAgo){
		# How many invites issued by the user were used to register new accounts in the last $daysAgo days
		if(
			$results = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `User`.`ID`) inviteCount
					FROM
						`InviteCode`
					INNER JOIN
						`User` ON
							`InviteCode`.`ClaimedID` = `User`.`ID`
					WHERE
						`InviteCode`.`UserID` = ? AND
						`User`.`JoinDateTime` > NOW() - INTERVAL ? DAY
				",
				'ii',
				[
					$this->ID,
					$daysAgo
				]
			)
		)
			return $results[0]['inviteCount'];
		
		return FALSE;
	}
	
	public function updatePrefs($new_prefs){
		$new_attributes = array('Preferences' => array_merge($this->Attributes['Preferences'], $new_prefs));
		if($this->updateAttributes($new_attributes))
			return true;
		else
			return false;
	}
	
	public function updateAttributes(array $new_attributes){
		$new_attributes = array_replace_recursive($this->Attributes, $new_attributes);
		
		if ($new_attributes !== $this->Attributes){
			if($this->ID){
				$stmt_UpdatePrefs = $this->db->prepare("
					UPDATE
						`User`
					SET
						`Attributes` = ?
					WHERE
						`ID` = ?
					LIMIT 1
				");
				if( $stmt_UpdatePrefs != FALSE  ){
					$rsa = new RSA();
					$encrypted_attributes = $rsa->qEncrypt(json_encode($new_attributes));
					$stmt_UpdatePrefs->bind_param('si', $encrypted_attributes, $this->ID);
					if(!$stmt_UpdatePrefs->execute()){
						return false;
					} else {
						$_SESSION['attributes'] = $this->Attributes = $new_attributes;
						return true;
					}
				}
			} else {
				$_SESSION['attributes'] = $this->Attributes = $new_attributes;
				return true;
			}
				
		}
		
		return true;
	}
	
	public function toggleListingFavorite($listingID){
		if ($this->ID){
			try {
				
				$listingFavorite = $this->db->qSelect(
					"
						SELECT	COUNT(*) AS Count
						FROM	`User_Listing`
						WHERE
							`UserID`	= ?
						AND	`ListingID`	= ?
					",
					'ii',
					array(
						$this->ID,
						$listingID
					)
				);
				
				switch($listingFavorite[0]['Count']){
					case TRUE:
						return $this->db->qQuery(
							"
								DELETE FROM
									`User_Listing`
								WHERE
									`UserID`	= ?
								AND	`ListingID`	= ?
							",
							'ii',
							array(
								$this->ID,
								$listingID
							)
						);
					break;
					default:
						return $this->db->qQuery(
							"
								INSERT INTO
									`User_Listing`
								VALUES
									(?, ?)
							",
							'ii',
							array(
								$this->ID,
								$listingID
							)
						);
				}
				
			} catch (Exception $e) {
				return false;
			}
		}
	}
	
	public function toggleUserSubscription($vendorAlias){
		if($vendorAlias == $this->Alias)
			return FALSE;
		
		if ($this->ID);
			try {
				$userSubscription = $this->db->qSelect(
					"
						SELECT	COUNT(*) count
						FROM	`User_User`
						WHERE
							`FollowerID` = ?
						AND	`UserID` = (
							SELECT	`ID`
							FROM	`User`
							WHERE	`Alias` = ?
						)
					",
					'is',
					array(
						$this->ID,
						$vendorAlias
					)
				);
				
				switch($userSubscription[0]['count']){
					case TRUE:
						$query = "
							DELETE FROM
								`User_User`
							WHERE
								`FollowerID` = ?
							AND	`UserID` = (
								SELECT	`ID`
								FROM	`User`
								WHERE	`Alias` = ?
							)
						";
						$forumQuery = "
							DELETE IGNORE FROM
								`Blog_Subscription`
							WHERE
								`SubscriberID` = ? AND
								`BlogID` = (
									SELECT
										`Blog`.`ID`
									FROM
										`Blog`
									INNER JOIN
										`User` ON
											`Blog`.`UserID` = `User`.`ID`
									WHERE
										`User`.`Alias` = ?
								)
						";
					break;
					case FALSE:
						$query = "
							INSERT INTO
								`User_User` (`FollowerID`, `UserID`)
							VALUES
								(
									?,
									(
										SELECT	`ID`
										FROM	`User`
										WHERE	`Alias` = ?
									)
								)
						";
						$forumQuery = "
							INSERT IGNORE INTO
								`Blog_Subscription` (
									`BlogID`,
									`SubscriberID`,
									`SeenPostID`
								)
							SELECT
								`Blog`.`ID`,
								?,
								(
									SELECT	MAX(`BlogPost`.`ID`)
									FROM	`BlogPost`
									WHERE	`BlogPost`.`BlogID` = `Blog`.`ID`
								)
							FROM
								`Blog`
							INNER JOIN
								`User` ON
									`Blog`.`UserID` = `User`.`ID`
							WHERE
								`User`.`Alias` = ?
						";
					break;
					default:
						return false;
				}
				
				$this->db->qQuery(
					$query,
					'is',
					[
						$this->ID,
						$vendorAlias
					]
				);
				
				$this->db->qQuery(
					$forumQuery,
					'is',
					[
						$this->ID,
						$vendorAlias
					],
					$this->IsTester
				);
				
				return true;
			} catch (Exception $e) {
				return false;
			}
	}
	
	public function dismissSubscribedDiscussions(){
		return $this->db->qQuery(
			"
				UPDATE
					`User_Notification`
				LEFT JOIN
					`Discussion_Subscription` ON
						`Discussion_Subscription`.`SubscriberID` = `User_Notification`.`UserID`
				LEFT JOIN
					`Blog_Subscription` ON
						`Blog_Subscription`.`SubscriberID` = `User_Notification`.`UserID`
				LEFT JOIN
					`BlogPost_Subscription` ON
						`BlogPost_Subscription`.`SubscriberID` = `User_Notification`.`UserID`
				SET
					`User_Notification`.`Value` = 0,
					`Discussion_Subscription`.`SeenCommentID` = (
						SELECT	MAX(`Discussion_Comment`.`ID`)
						FROM	`Discussion_Comment`
					),
					`Blog_Subscription`.`SeenPostID` = (
						SELECT	MAX(`BlogPost`.`ID`)
						FROM	`BlogPost`
					),
					`BlogPost_Subscription`.`SeenCommentID` = (
						SELECT	MAX(`BlogPostComment`.`ID`)
						FROM	`BlogPostComment`
					)
				WHERE
					`User_Notification`.`UserID` = ? AND
					`User_Notification`.`TypeID` = '" . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "'
			",
			'i',
			[$this->ID]
		);
	}
	
	public function getCryptocurrencyPublicKey(
		$cryptocurrencyID,
		$userID = false
	){
		$userID = $userID ?: $this->ID;
		if (
			$publicKeys = $this->db->qSelect(
				"
					SELECT
						`ExtendedPublicKey`
					FROM
						`PaymentMethod`
					WHERE
						`UserID` = ? AND
						`CryptocurrencyID` = ?
				",
				'ii',
				[
					$userID,
					$cryptocurrencyID
				]
			)
		)
			return $publicKeys[0]['ExtendedPublicKey'];
		
		return false;
	}
	
	public function getCryptocurrencyAddress(
		$cryptocurrencyID = FALSE,
		$userID = FALSE
	){
		$cryptocurrencyID = $cryptocurrencyID ?: $this->Cryptocurrency->ID;
		$userID = $userID ?: $this->ID;
		
		if(
			$addresses = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`RefundAddress`
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`RefundAddress` IS NOT NULL AND
						`PaymentMethod`.`CryptocurrencyID` = ?
					ORDER BY
						`Transaction`.`ID` DESC
					LIMIT 1
				",
				'ii',
				[
					$userID,
					$cryptocurrencyID
				]
			)
		)
			return $addresses[0]['RefundAddress'];
			
		return false;
	}
	
	private function getDefaultNotifications(){
		// GET DB Notifications
		if( $stmt_UrgentNotification = $this->db->prepare("
			SELECT
				`ID`,
				`Type`,
				`Color`,
				`Icon`,
				`Anchor`,
				`Content`,
				`Target`
			FROM
				`Notification`
			WHERE
				`ID` > ? AND
				`Type` IN (
					'General',
					'Urgent'
				)
		") ){
			$stmt_UrgentNotification->bind_param('i', $this->Attributes['LastSeen']['NotificationID']);
			$stmt_UrgentNotification->execute();
			$stmt_UrgentNotification->store_result();
			if ($stmt_UrgentNotification->num_rows > 0) {
				$stmt_UrgentNotification->bind_result(
					$ntf_id,
					$ntf_type,
					$ntf_color,
					$ntf_icon,
					$ntf_anchor,
					$ntf_content,
					$ntf_target
				);
				while($stmt_UrgentNotification->fetch()){
					if( empty($ntf_anchor) )
						$ntf_anchor = false;
					$this->Notifications->custom(
						$ntf_content,
						$ntf_anchor ? $ntf_anchor : false,
						'?do[DismissNotification]='.$ntf_id,
						$ntf_type,
						array(
							'Color' => $ntf_color,
							'Icon' => $ntf_icon
						),
						$ntf_target
					);
					$this->NewestNotificationID = $ntf_id;
				}
			}
		} else {
			die(); // STATEMENT PREPARATION FAILED
		}
		
		// New User Alias Notification
		if(isset($_SESSION['new_user']) && $_SESSION['new_user']){
			$this->Notifications->custom('<strong>Welcome to ' . $this->db->getSiteInfo('SiteName_Short') . '!</strong> You have been assigned a random alias. <strong>Click here</strong> to choose your own', URL.'account/settings/#profile', '?do[DismissNewUserNotification]', 'General', array(
				'Color' => 'blue',
				'Icon' => $this->db->getSiteInfo('Icon')
			));
		}
		
		// Temporary Session Notifications
		if(!empty($_SESSION['temp_notifications']) ){
			foreach( $_SESSION['temp_notifications'] as $notification ){
				$this->Notifications->custom(
					isset($notification['Content']) ? $notification['Content'] : false,
					isset($notification['Anchor']) ? $notification['Anchor'] : false,
					isset($notification['Dismiss']) ? $notification['Dismiss'] : '.',
					isset($notification['Group']) ? $notification['Group'] : 'General',
					isset($notification['Design']) ? $notification['Design'] : array(
						'Color' => 'blue',
						'Icon' => 'default'
					)
				);
			}
			unset($_SESSION['temp_notifications']);
		}
	}
	
	private function updateAccount(){
		$attributes = $this->Attributes;
		
		switch( $attributes['Version'] ){
			case 1:
				// Create BIP32 Master Key
				$bip32_master = BIP32::master_key( bin2hex(openssl_random_pseudo_bytes(16)) );
				$bip32_extended_private = BIP32::build_key($bip32_master[0], $this->ID . "'");
				$bip32_extended_public = BIP32::extended_private_to_public( $bip32_extended_private);
				
				$this->updateAttributes( array(
					'Version' => 2,
					'BIP32Encrypted' => false,
					'BIP32Master' => $bip32_master[0],
					'BIP32ExtendedPrivate' => $bip32_extended_private,
					'BIP32ExtendedPublic' => $bip32_extended_public,
				) );
				
				// Add Extended Public If Vendor
				
				if( $this->Info('ListingCount') > 0 ){
					
					$bip32_extended_public = BIP32::extended_private_to_public( $bip32_extended_private );
					
					if( $stmt_addBIP32PublicKey = $this->db->prepare("
						UPDATE
							`User`
						SET
							`BIP32Public` = ?
						WHERE
							`ID` = ?
					") ){
						
						$stmt_addBIP32PublicKey->bind_param('si', $bip32_extended_public[0], $this->ID);
						$stmt_addBIP32PublicKey->execute();
						
					}
					
				}
				
				// Add Alias if Empty
				if( empty($this->Alias) ){
					
					$new_alias = $this->getUniqueAlias();
					
					if( $stmt_addAlias = $this->db->prepare("
						UPDATE	`User`
						SET		`Alias` = ?
						WHERE	`ID` = ?
					") ){
						
						$stmt_addAlias->bind_param('si', $new_alias, $this->ID);
						$stmt_addAlias->execute();
						
						$_SESSION['alias'] = $this->Alias = $new_alias;
						
					}
					
				}
			case 2:
				// Give Traditional Public Key Parameter
				$this->updateAttributes( array(
					'Version' => 3,
					'BTCPublic' => false
				) );
			case 3:
				// Make Verified Vendor OFF
				$this->updateAttributes( array(
					'Version' => 4,
					'Preferences' => array(
						'CatalogFilter' => array(
							'verified_vendors' => false
						)
					)
				) );
			case 4:
			case 5:
				$this->updateAttributes( array(
					'Version' => 6,
					'TotalTransacted' => 0
				) );	
			case 6:
			case 7:
				$this->updateAttributes( array(
					'Version' => 8,
					'Preferences' => array(
						'CollapsedNav' => FALSE
					)
				) );
			case 8:
				$this->updateAttributes( array(
					'Version' => 9,
					'Preferences' => array(
						'CollapsedNav' => FALSE
					)
				) );
			case 9: // Hide Old Transactions
				$this->updateAttributes( array(
					'Version' => 10,
					'Preferences' => array(
						'ShowExpiredTransactions' => FALSE
					)
				) );
			case 10: // Archived Listings
				$this->updateAttributes( array(
					'Version' => 11,
					'Preferences' => array(
						'ShowArchivedListings' => FALSE
					)
				) );
			case 11: // Locales
				$this->updateAttributes(
					[
						'Version' => 12,
						'Preferences' => [
							'LocaleID' => LOCALE_DEFAULT_ID,
							'CatalogFilter' => [
								'ships_to' => 0,
								'ships_from' => 0
							]
						]
					]
				);
			case 12: // Bitcoin Fee Levels
				$this->updateAttributes(
					[
						'Version' => 13,
						'Preferences' => [
							'LiveUpdate' => true,
							'BitcoinFeeLevel' => BITCOIN_FEE_LEVEL_DEFAULT
						]
					]
				);
			case 13:
			case 14:
			case 15:
			case 16:
			case 17:
			case 18:
			case 19:
			case 20:
			case 21:
			case 22:
			case 23:
			case 24:
			case 25:
			case 26:
			case 27:
			case 28:
			case 29:
			case 30:
			case 31:
			case 32:
			case 33:
			case 34:
			case 35:
			case 36:
			case 37:
			case 38: // New Notification System
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
					0
				);
				
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_LISTING_NEW_QUESTION,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL,
					0
				);
				
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK,
					0
				);
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED,
					0
				);
				
				$this->updateAttributes(
					[
						'Version' => 39
					]
				);
			case 39:
			case 40:
			case 41:
			case 42:
			case 43:
			case 44: // Alternative cryptocurrencies
				$this->updateAttributes(
					[
						'Version' => 45,
						'Preferences' => [
							'CryptocurrencyID' => CRYPTOCURRENCIES_CRYPTOCURRENCY_ID_DEFAULT,
							'PromptCryptocurrency' => true,
							'CryptocurrencyFeeLevel' => CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT,
							'CatalogFilter' => [
								'cryptocurrencies' => false
							]
						]
					]
				);
			case 45:
				$this->setUserNotification(
					USER_NOTIFICATION_TYPEID_LISTING_OUT_OF_STOCK,
					0
				);
				$this->updateAttributes(
					[
						'Version' => 46
					]
				);
			case 46:
				if (
					$this->IsVendor ||
					$this->Attributes['TotalTransacted'] >= PRIVATE_DOMAINS_BUYER_CRITERION_MINIMUM_TRANSACTED_EUR
				)
					$this->allocateUserDomains();
					
				$this->updateAttributes(
					[
						'Version' => 47
					]
				);
			case 47: // REMEMBER TO LEAVE ONE EXTRA SO IT DOESN'T GO TO DEFAULT
			break;
			default:
				$default_attributes = array(
					'Version'		=> 2,
					'BIP32Encrypted'	=> false,
					'BIP32Master'		=> null,
					'BIP32ExtendedPrivate'	=> null,
					'BIP32ExtendedPublic'	=> null,
					'Preferences'			=> array(
						'CurrencyID'	=> 3,
						'CatalogFilter' => array(
							'verified_vendors'	=> FALSE,
							'ships_to'		=> '0',
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
				
				$this->Attributes = array();
				$this->updateAttributes( $default_attributes );
				
				$this->updateAccount();
		}
		
		## Restore Backedup Public Key If Missing For Some Reason
		$this->fixMissingPublicKey();
		
		//$new_db_bip32_public = false;
		
		//list($db_bip32_public, $db_btc_public) = $this->Info('BIP32Public', 'BTCPublic');
		
		/* if( !$this->Attributes['BTCPublic'] && !preg_match('/^xpub.+/', $this->Attributes['BIP32ExtendedPublic'][0]) ){
			
			// FIX BIP32 PUBLIC
			$bip32_master = BIP32::master_key( bin2hex(openssl_random_pseudo_bytes(16)) );
			$bip32_extended_private = BIP32::build_key($bip32_master[0], $this->ID . "'");
			$bip32_extended_public = BIP32::extended_private_to_public( $bip32_extended_private);
			
			$this->updateAttributes( array(
				'BIP32Encrypted' => false,
				'BIP32Master' => $bip32_master[0],
				'BIP32ExtendedPrivate' => $bip32_extended_private,
				'BIP32ExtendedPublic' => $bip32_extended_public,
			) );
			
			if( !empty($db_bip32_public) || !empty($db_btc_public) )
				$new_db_bip32_public = $bip32_extended_public[0];
			
		} */
		
	}
	
	public function countUserNotifications($includeForumNotifications = true){
		$notificationCount = 0;
		if (
			$notificationCounts = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `User_Notification`.`TypeID`) " . (
							$includeForumNotifications
								? "
									+ (
										(
											SELECT COUNT(DISTINCT `Discussion_Subscription`.`DiscussionID`)
											FROM `Discussion_Subscription`
											WHERE `Discussion_Subscription`.`SubscriberID` = `User_Notification`.`UserID` AND
											(
												SELECT
													`DateInserted`
												FROM
													`Discussion_Comment`
												WHERE
													`DiscussionID` = `Discussion_Subscription`.`DiscussionID`
												ORDER BY
													`DateInserted` DESC,
													`ID` DESC
												LIMIT 1
											) >
											IFNULL(
												(
													SELECT	`DateInserted`
													FROM	`Discussion_Comment`
													WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
												),
												'" . MYSQL_DATETIME_RANGE_LOWEST . "'
											)
										) +
										(
											SELECT	COUNT(DISTINCT `BlogPost_Subscription`.`BlogPostID`)
											FROM	`BlogPost_Subscription`
											WHERE	`BlogPost_Subscription`.`SubscriberID` = `User_Notification`.`UserID` AND
											(
												SELECT
													`BlogPostComment`.`DateInserted`
												FROM
													`BlogPostComment`
												WHERE
													`BlogPostComment`.`BlogPostID` = `BlogPost_Subscription`.`BlogPostID`
												ORDER BY
													`BlogPostComment`.`DateInserted` DESC,
													`BlogPostComment`.`ID` DESC
												LIMIT 1
											) >
											IFNULL(
												(
													SELECT	`DateInserted`
													FROM	`BlogPostComment`
													WHERE	`BlogPostComment`.`ID` = `BlogPost_Subscription`.`SeenCommentID`
												),
												'" . MYSQL_DATETIME_RANGE_LOWEST . "'
											)
										) +
										(
											SELECT
												COUNT(DISTINCT `BlogPost`.`ID`)
											FROM
												`Blog_Subscription`
											INNER JOIN
												`BlogPost` ON
													`Blog_Subscription`.`BlogID` = `BlogPost`.`BlogID`
											WHERE
												`Blog_Subscription`.`SeenPostID` IS NOT NULL AND
												`Blog_Subscription`.`SubscriberID` = `User_Notification`.`UserID` AND
												`BlogPost`.`DateInserted` >
												(
													SELECT	`DateInserted`
													FROM	`BlogPost`
													WHERE	`BlogPost`.`ID` = `Blog_Subscription`.`SeenPostID`
												)
										) > 0
									)
								"
								: false
						) . " Count
					FROM
						`User_Notification`
					INNER JOIN
						`NotificationType` ON
							`User_Notification`.`TypeID` = `NotificationType`.`ID`
					WHERE
						`User_Notification`.`UserID` = ? AND
						`User_Notification`.`Value` > 0 AND
						`NotificationType`.`Dashboard` = TRUE
				",
				'i',
				[$this->ID]
			)
		)
			$notificationCount = $notificationCounts[0]['Count'];
		
		$dashboardNotificationCount =
			isset($this->Notifications->all['Dashboard'])
				? count($this->Notifications->all['Dashboard'])
				: 0;
		if ($this->IsVendor){
			$notificationCount += $this->_getUnansweredListingQuestionCount($this->ID) > 0;
		
			if (isset($this->Notifications->all['Vendor']))
				$dashboardNotificationCount += count($this->Notifications->all['Vendor']);
		}
		
		return $notificationCount + $dashboardNotificationCount;
	}
	
	public function getCryptocurrencyIDFromISO($ISO){
		if(
			$cryptocurrencies = $this->db->qSelect(
				"
					SELECT
						`ID`
					FROM
						`Currency`
					WHERE
						`ISO` = ? AND
						`Crypto` = TRUE
				",
				's',
				[$ISO]
			)
		)
			return $cryptocurrencies[0]['ID'];
			
		return false;
	}
	
	public function updateCryptocurrency(
		$ISO,
		$prompt = false
	){
		return $this->updateAttributes(
			[
				'Preferences' => [
					'CryptocurrencyID' => $this->getCryptocurrencyIDFromISO($ISO) ?: $this->Attributes['Preferences']['CryptocurrencyID'],
					'PromptCryptocurrency' => $prompt
				]
			]
		);
	}
	
	public function generateDonationAddress(){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`DonationAddress` (`DonorID`)
				VALUES
					(?)
			",
			'i',
			array(
				$this->ID
			)
		);
	}

	public function getDonationAddresses(){
		if(
			$donationAddressKey = $this->db->qSelect(
				"
					SELECT
						IF(myDonationAddress.`ID` IS NOT NULL, COUNT(`DonationAddress`.`ID`) + 1, NULL) keyIndex
					FROM
						`DonationAddress`
					INNER JOIN
						`DonationAddress` myDonationAddress ON
							myDonationAddress.`DonorID` = ?
					WHERE
						`DonationAddress`.`DateTime` < myDonationAddress.`DateTime` OR
						(
							`DonationAddress`.`DateTime` = myDonationAddress.`DateTime` AND
							`DonationAddress`.`ID` < myDonationAddress.`ID`
						)
				",
				'i',
				[
					$this->ID
				],
				false,
				true
			)[0]['keyIndex']
		){
			$donationAddresses = [
				NXS::getBIP32Address(
					$donationAddressKey,
					DONATIONS_BIP32_EXTENDED_PUBLIC_KEY_NEW,
					CRYPTOCURRENCIES_PREFIX_PUBLIC_BITCOIN
				),
				NXS::getBIP32Address(
					$donationAddressKey,
					DONATIONS_BIP32_EXTENDED_PUBLIC_KEY_NEW,
					CRYPTOCURRENCIES_PREFIX_PUBLIC_LITECOIN,
					CRYPTOCURRENCIES_PREFIX_SCRIPT_HASH_LITECOIN
				)
			];
			
			return $donationAddresses;
		}
		
		return FALSE;
	}

	private function fixMissingPublicKey(){
		$BTCPublic = $this->Info(0,'BTCPublic');
		
		if(
			empty( $BTCPublic ) ||
			!BitcoinLib::validate_public_key( $BTCPublic )
		){
			## CHECK IF ENCRYPTED PUBLIC KEY EXISTS AND IS VALID
			if(
				!empty( $this->Attributes['BTCPublic'] ) &&
				BitcoinLib::validate_public_key( $this->Attributes['BTCPublic'] )
			){
				$newPublicKey = $this->Attributes['BTCPublic'];
			}
		
			## CHECK IF HAS PUBLIC KEY BACKUP
			elseif(
				$publicKey = $this->db->qSelect(
				    "
					    SELECT	`BTCPublicKey`
					    FROM	`User_BTCPublicKey`
					    WHERE
						    `UserID`	= ?
				    ",
				    'i',
				    array(
					    $this->ID
				    )
				)
			){
				## USER HAS PUBLIC KEY BACKUP
				$newPublicKey = $publicKey[0]['BTCPublicKey'];
			} else {
				return TRUE; ## User Doesn't Have Backup; Probably Hasn't Setup PublicKey Yet
			}
		    
			if( $newPublicKey ){
				$this->updateAttributes(
				    array(
					    'BTCPublic' => $newPublicKey
				    )
				);
				
				return $this->db->qQuery(
					"
						UPDATE
							`User`
						SET
							`BTCPublic`	= ?
						WHERE
							`ID`		= ?
					",
					'si',
					array(
						$newPublicKey,
						$this->ID
					)
				);
			}
		} elseif(
			!empty($BTCPublic) &&
			BitcoinLib::validate_public_key( $BTCPublic ) &&
			$this->Attributes['BTCPublic'] !== $BTCPublic
		){
			## DB Puplic Key Is Not Empty, BUT Encrypted Public Key is Different From It
			
			$this->updateAttributes(
			    array(
				    'BTCPublic' => $BTCPublic
			    )
			);
		} else
			return TRUE;
	}
	
	private function getUniqueAlias(){
		
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
		
	}
    
}
