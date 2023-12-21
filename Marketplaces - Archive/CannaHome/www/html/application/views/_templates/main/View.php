<?php 
class templateView extends View {
	private $User;
	
	function __construct(Database $db, $user){
		parent::__construct('main', $db, $user->IsTester);
		
		$this->db = $db;
		$this->User = $user;
		
		$this->Member = ($this->User->ID !== false);
		
		if ($this->Member){
			$this->NotificationCount = $this->User->countUserNotifications(false);
			
			if( (!isset($_SESSION['last_notification_count']) && $_SESSION['last_notification_count'] = 0) || $_SESSION['last_notification_count'] < $this->NotificationCount ){
				$this->NewNotificationCount = $this->NotificationCount - $_SESSION['last_notification_count'];
			} else {
				$this->NewNotificationCount = false;
			}
			$_SESSION['last_notification_count'] = $this->NotificationCount;
			
			list(
				$this->MessageCount,
				$this->TransactionCount,
				$this->unclaimedInviteCodeCount
			) = [
				$this->User->getUserNotification(USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES),
				$this->User->getUserNotification(USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS),
				0
			];/*$this->User->Info(
				'MessageCount',
				'OngoingTransactionPendingUserActionCount', //OngoingTransactionOfInterestCount
				'FavoriteListingCount',
				'UnclaimedInviteCodeCount',
				'SubscribedForumEntriesCountChange'
			);*/
			
			$this->forumEntryCount = $this->_getForumUnreadCount();
			
			//$this->inviteCount = $this->unclaimedInviteCodeCount + $this->invitedUsersCount;
			
			//if ($this->forumEntryCount)
			//	$this->NotificationCount = $this->NotificationCount - 1;
			
			$this->favoriteCount = $this->User->IsVendor
				? 0
				: $this->User->Info('FavoriteListingCount');
			
			$this->pendingActionCount = $this->MessageCount;
			
			$this->refererAlias = ALIAS_SUPPORT;
		}
		
		// Currencies
		$this->Currencies = $this->getCurrencies($this->cryptocurrencies);
		
		// Locales
		$this->Locales = $this->getLocales();
		
		$this->oneCryptocurrency = 
			$this->User->Cryptocurrency->appendName(1) .
			' = ' .
			NXS::formatPrice($this->User->Currency, 1/$this->User->Cryptocurrency->XEUR);
		
		//$this->RootCategories = $this->fetchRootListingCategories();
		
		$this->isForum = FALSE;
		
		$this->donationAddresses = $this->User->getDonationAddresses();
		
		$this->refreshSeconds = $this->refreshDestination = false;
		
		// Site-specific data
		$this->FooterPages = [];//$this->fetchSitePages();
		
		// Custom User CSS
		/*if ($customCSS = $this->User->Info('UserCSS'))
			$this->inlineStylesheet = $customCSS;
		else
			*/$this->inlineStylesheet = '';
		
		
		// Common Infos
		$this->UserAlias	= empty($this->User->Alias) ? false : $this->User->Alias;
		$this->UserCurrency	= $this->User->Currency;
		$this->UserVendor	= $this->User->IsVendor;
		$this->UserMod		= $this->User->IsMod;
		$this->UserAdmin	= $this->User->IsAdmin;
		$this->UserTester	= $this->User->IsTester;
		
		$this->vendorStores = $this->fetchVendorStores();
		
		$this->breadcrumb = [];
		
		// Handle Universal GET Requests
		$this->doActions();
	}
	
	public function preRender(){
		$this->getChats();
		$this->User->recallibrateUserNotifications();
		$this->getUserDomains();
	}
	
	private function _getForumUnreadCount(){
		if (
			$forumEntryCount = $this->db->qSelect(
				"
					SELECT
						(
							SELECT COUNT(DISTINCT `Discussion_Subscription`.`DiscussionID`)
							FROM `Discussion_Subscription`
							WHERE `Discussion_Subscription`.`SubscriberID` = ? AND
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
							WHERE	`BlogPost_Subscription`.`SubscriberID` = ? AND
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
								`Blog_Subscription`.`SubscriberID` = ? AND
								`BlogPost`.`DateInserted` >
								(
									SELECT	`DateInserted`
									FROM	`BlogPost`
									WHERE	`BlogPost`.`ID` = `Blog_Subscription`.`SeenPostID`
								)
						) forumUnreadCount
				",
				'iii',
				[
					$this->User->ID,
					$this->User->ID,
					$this->User->ID
				]
			)
		)
			return	$forumEntryCount[0]['forumUnreadCount'];
		
		return 0;
	}
	
	private function _fetchUserDomains($maximumDomains){
		if (
			$domains =  $this->db->qSelect(
				"
					SELECT
						`Site_Domain`.`Domain`
					FROM
						`User_Domain`
					INNER JOIN
						`Site_Domain` ON
							`User_Domain`.`DomainID` = `Site_Domain`.`ID`
					WHERE
						`User_Domain`.`UserID` = ?
					ORDER BY
						`Site_Domain`.`Type` = 'vendor' DESC
					LIMIT
						?
				",
				'ii',
				[
					$this->User->ID,
					$maximumDomains
				],
				false,
				true
			)
		)
			return	array_map(
					function($row){
						return $row['Domain'];
					},
					$domains
				);
		
		return false;
	}
	
	private function getUserDomains($maximumDomains = PRIVATE_DOMAINS_MAXIMUM_PER_USER){
		$this->privateDomains = false;
		if (
			($privateDomainsState = $this->User->Info('PrivateDomains')) &&
			$this->privateDomains = $this->_fetchUserDomains($maximumDomains)
		){
			switch (true){
				case ($privateDomainsState == PRIVATE_DOMAINS_STATE_DOMAINS_CHANGED): // URLs have changed
					$this->privateDomainsText = "
						<p><strong class='color-red'>Your private market URLs have changed!</strong></p>
					";
					$this->privateDomainsExpanded = true;
					$this->User->updatePrivateDomainsState(PRIVATE_DOMAINS_STATE_NORMAL_GRANTED);
					break;
				default:
					$this->privateDomainsText =
						$this->UserVendor
							? "
								<p>As a verified vendor at Home, you have been granted priority access to the site.</p>
								<p>If the site comes under attack and the public URL becomes inaccessible, you will continue to have access via these <u>private</u> URLs :</p>
							"
							: "
								<p>As a verified buyer at Home, you have been granted priority, <em>VIP</em> access to the site.</p>
								<p>If the site comes under attack and the public URL becomes inaccessible, you will continue to have access via these <u>private</u> URLs :</p>
							";
					
					$this->privateDomainsExpanded =
						(
							$privateDomainsState == PRIVATE_DOMAINS_STATE_RECENTLY_GRANTED &&
							$this->User->updatePrivateDomainsState(PRIVATE_DOMAINS_STATE_NORMAL_GRANTED)
						) ||
						(
							!$this->User->IsMod &&
							!isset($_SESSION['private_domain_reminded']) &&
							!in_array(
								$this->db->accessDomain,
								$this->privateDomains
							)
						);
			}
			
			if ($this->privateDomainsExpanded)
				$_SESSION['private_domain_reminded'] = true;
		}
			
		return true;
	}
	
	private function _getModChats(){
		$this->ongoingChats = $this->fetchOngoingChats($this->hasUnreadChats);
		$this->unansweredChats = $this->fetchChatsWithStatus(CHAT_STATUS_ID_OPEN);
		$this->importantChats = $this->fetchChatsWithStatus(CHAT_STATUS_ID_IMPORTANT);
		
		foreach ($this->ongoingChats as $key => $ongoingChat){
			if (
				(
					$this->importantChats &&
					array_key_exists(
						$key,
						$this->importantChats
					) &&
					$this->importantChats[$key] = $ongoingChat
				) ||
				(
					$this->unansweredChats &&
					array_key_exists(
						$key,
						$this->unansweredChats
					) &&
					$this->unansweredChats[$key] = $ongoingChat
				)
			)
				unset($this->ongoingChats[$key]);
		}
		
		$this->hasChats = $this->ongoingChats || $this->unansweredChats || $this->importantChats;
		$this->unreadMessageCount = 0;
	}
	
	private function _getDisputes(&$hasNoteworthyDisputes = null){
		$hasNoteworthyDisputes = false;
		if (
			$disputes = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`Identifier`,
						CONCAT(
							Vendor.`Alias`,
							' &amp; ',
							Buyer.`Alias`
						) disputeTitle,
						`Transaction`.`MediatorID` = ? isMediator,
						`Transaction`.`MediatorID` = ? AND
						`Transaction`.`MediatorSeenMessageID` IS NOT NULL AND
						(
							SELECT
								`DateTime`
							FROM
								`Transaction_Message`
							WHERE
								`TransactionID` = `Transaction`.`ID`
							ORDER BY
								`DateTime` DESC,
								`ID` DESC
							LIMIT
								1
						) >
						(
							SELECT	`DateTime`
							FROM	`Transaction_Message`
							WHERE	`ID` = `Transaction`.`MediatorSeenMessageID`
						) hasUnreadMessages
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`User` Vendor ON
							`PaymentMethod`.`UserID` = Vendor.`ID`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					WHERE
						`Transaction`.`Status` = 'in dispute' AND
						`Transaction`.`Timeout` < NOW() AND
						(
							`Transaction`.`MediatorID` IS NULL OR
							`Transaction`.`MediatorID` = ?
						)
					ORDER BY
						`Transaction`.`Timeout` ASC
				",
				'iii',
				[
					$this->User->ID,
					$this->User->ID,
					$this->User->ID
				]
			)
		){
			foreach ($disputes as $dispute)
				if (
					$hasNoteworthyDisputes =
						!$dispute['isMediator'] ||
						$dispute['hasUnreadMessages']
				)
					break;
			
			return $disputes;
		}
		
		return false;
	}
	
	private function getChats(){
		if ($this->UserMod){
			$this->_getModChats();
			$this->hasDisputes = ($this->modDisputes = $this->_getDisputes($hasNoteworthyDisputes));
			
			switch(TRUE){
				case $this->hasUnreadChats:
					$this->chatButtonColor = CHAT_BUTTON_COLOR_UNREAD;
					break;
				case $this->unansweredChats:
					$this->chatButtonColor = CHAT_BUTTON_COLOR_UNANSWERED;
					break;
				case $this->importantChats:
					$this->chatButtonColor = CHAT_BUTTON_COLOR_IMPORTANT;
					break;
				case $hasNoteworthyDisputes:
					$this->chatButtonColor = CHAT_BUTTON_COLOR_DISPUTES;
					break;
			}
		} else {
			$this->hasChats = TRUE;
			$this->hasDisputes = FALSE;
			
			$this->ongoingTransactions = false; //$this->fetchOngoingTransactions();
			
			$currentPath = explode('/', $this->currentPath);
			if(
				$currentPath[0] !== 'account' ||
				$currentPath[1] !== 'support'
			){
				$this->chatMessageCount = $this->getChatMessageCount($this->unreadMessageCount);
				$this->chatMessages = $this->fetchLatestChatMessages();
				//$this->chatButtonColor = $this->unreadMessageCount > 0 ? CHAT_BUTTON_COLOR_UNREAD : FALSE;
				$this->chatButtonColor = CHAT_BUTTON_COLOR_NON_MODS;
			}
		}
	}
	
	private function fetchOngoingTransactions(){
		return $this->db->qSelect(
			"
				SELECT
					`Transaction`.`Identifier`,
					IF(
						My.`ID` = `Transaction`.`BuyerID`,
						Vendor.`Alias`,
						Buyer.`Alias`
					) SubjectAlias,
					`Transaction`.`Value`,
					`Transaction`.`Status`
				FROM
					`Transaction`
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` My ON
						My.`ID` = ?
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
				INNER JOIN
					`User` Buyer ON
						`Transaction`.`BuyerID` = Buyer.`ID`
				WHERE
					(
						(
							Vendor.`ID` = My.`ID` AND
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
							Buyer.`ID` = My.`ID` AND
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
					(
						`Transaction`.`Timeout` > NOW() OR
						`Transaction`.`Status` = 'in dispute'
					)
				ORDER BY
					`Transaction`.`ID` DESC
			",
			'i',
			[
				$this->User->ID
			]
		);
	}
	
	public function renderPaymentMethodsModal(
		$paymentMethods,
		$listingB36,
		$modalID = false,
		$modalHeader = 'Select Payment Method'
	){
		echo
			'<input id="' . $modalID . '" type="checkbox" hidden>
			<div class="modal">
				<label for="' . $modalID . '"></label>
				<div class="rows-20">
					<h5 class="row band bigger"><span>' . $modalHeader . '</span></h5>
					<form class="row rows-10" action="' . URL . 'transactions/order_with_payment_method/' . $listingB36 . '/" method="post">
						<div class="row cols-5">';
		
		foreach ($paymentMethods as $paymentMethod)
			echo				'<div class="col-6">
								<button ' . ($paymentMethod['Available'] ? 'type="submit" name="currency" value="' . $paymentMethod['ISO'] . '"' : 'type="button"' ) . ' class="btn big wide left-icon ' . ($paymentMethod['Available'] ? $paymentMethod['Color'] : 'disabled' ) . '">
									<i class="' . $paymentMethod['Icon'] . '-m"></i>' . $paymentMethod['Name'] . (
										!$paymentMethod['Available']
											? '<div class="hint below"><span>Not available</span></div>'
											: false
									) . '
								</button>
							</div>';
		
		echo '				</div>
						<div class="row">
							<label class="checkbox label">
								<input type="checkbox" name="remember_choice">
								<i></i>Remember my choice for next time
							</label>
						</div>
					</form>
				</div>
			</div>';
	}
	
	private function getChatMessageCount(&$unreadMessageCount = NULL){
		if(
			$chatMessageCounts = $this->db->qSelect(
				"
					SELECT
						COUNT(DISTINCT `ChatMessage`.`ID`) ChatMessageCount,
						(
							SELECT
								COUNT(DISTINCT UnreadChatMessage.`ID`)
							FROM
								`ChatMessage` UnreadChatMessage
							INNER JOIN
								`ChatSubscription` ON
									UnreadChatMessage.`ChatID` = `ChatSubscription`.`ChatID`
							WHERE
								UnreadChatMessage.`ChatID` = `Chat`.`ID` AND
								UnreadChatMessage.`DateTime` > IFNULL(
									(
										SELECT	`DateTime`
										FROM	`ChatMessage`
										WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
									),
									'" . MYSQL_DATETIME_RANGE_LOWEST . "'
								) AND
								`ChatSubscription`.`UserID` = `Chat`.`SubjectUserID`
						) UnreadChatMessageCount
					FROM
						`Chat`
					INNER JOIN
						`ChatMessage` ON
							`Chat`.`ID` = `ChatMessage`.`ChatID`
					WHERE
						`Chat`.`SubjectUserID` = ?
				",
				'i',
				[
					$this->User->ID
				]
			)
		){
			$unreadMessageCount = $chatMessageCounts[0]['UnreadChatMessageCount'];
			
			return $chatMessageCounts[0]['ChatMessageCount'];
		}
			
		return FALSE;
	}
	
	private function _updateChatSubscription(){
		return $this->db->qQuery(
			"
				UPDATE
					`ChatSubscription`
				SET
					`SeenMessageID` = (
						SELECT
							`ChatMessage`.`ID`
						FROM
							`ChatMessage`
						WHERE
							`ChatMessage`.`ChatID` = `ChatSubscription`.`ChatID`
						ORDER BY
							`DateTime` DESC
						LIMIT
							1
					)
				WHERE
					`ChatSubscription`.`UserID` = ?
			",
			'i',
			[
				$this->User->ID
			]
		);
	}
	
	private function _fetchChatMessages(&$lowestID = null, &$highestID = null){
		if(
			$chatMessages = $this->db->qSelect(
				"
					SELECT
						`ChatMessage`.`ID`,
						Sender.`Alias` SenderAlias,
						UNIX_TIMESTAMP(`ChatMessage`.`DateTime`) Timestamp,
						`ChatMessage`.`Color`,
						`ChatMessage`.`TransactionID`,
						`Transaction`.`Identifier` TransactionIdentifier,
						`UserContent`.`Formatted` HTML,
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
						) Unread
					FROM
						`ChatMessage`
					INNER JOIN
						`Chat` ON
							`ChatMessage`.`ChatID` = `Chat`.`ID`
					INNER JOIN
						`User` Sender ON
							`ChatMessage`.`SenderID` = Sender.`ID`
					INNER JOIN
						`UserContent` ON
							`ChatMessage`.`ContentID` = `UserContent`.`ID`
					LEFT JOIN
						`ChatSubscription` ON
							`ChatMessage`.`ChatID` = `ChatSubscription`.`ChatID` AND
							`ChatSubscription`.`UserID` = `Chat`.`SubjectUserID`
					LEFT JOIN
						`Transaction` ON
							`ChatMessage`.`TransactionID` = `Transaction`.`ID`
					WHERE
						`Chat`.`SubjectUserID` = ?
					ORDER BY
						`ChatMessage`.`ID` DESC
					LIMIT
						" . CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT . "
				",
				'i',
				[
					$this->User->ID		
				]
			)
		){
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
					
					return $chatMessage;
				},
				$chatMessages
			);
			
			$lowestID = array_pop((array_slice($chatMessages, -1)))['ID'];
			$highestID = $chatMessages[0]['ID'];
		
			return $chatMessages;
		}
		
		return FALSE;
	}
	
	
	private function fetchLatestChatMessages(){
		if ($chatMessages = $this->_fetchChatMessages()){
			usort(
				$chatMessages,
				function($a, $b){
					return $a['Timestamp'] - $b['Timestamp'];
				}
			);
			
			if($this->unreadMessageCount > 0)
				$this->_updateChatSubscription();
			
			return $chatMessages;
		}
		
		return FALSE;
	}
	
	private function fetchOngoingChats(&$unreadChats = NULL){
		$ongoingChats = $this->db->qSelect(
			"
				SELECT
					`User`.`Alias` UserAlias,
					(
						SELECT
							COUNT(`ChatMessage`.`ID`)
						FROM
							`ChatMessage`
						WHERE
							`ChatMessage`.`ChatID` = `Chat`.`ID` AND
							`ChatMessage`.`DateTime` > (
								SELECT	`DateTime`
								FROM	`ChatMessage`
								WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
							) AND
							`ChatMessage`.`SenderID` != thisUser.`ID`
					) UnreadMessageCount
				FROM
					`Chat`
				INNER JOIN
					`ChatStatus` ON
						`Chat`.`StatusID` = `ChatStatus`.`ID`
				INNER JOIN
					`User` thisUser ON
						thisUser.`ID` = ?
				INNER JOIN
					`User` ON
						`Chat`.`SubjectUserID` = `User`.`ID`
				INNER JOIN
					`ChatSubscription` ON
						`Chat`.`ID` = `ChatSubscription`.`ChatID` AND
						`ChatSubscription`.`UserID` = thisUser.`ID` AND
						`ChatSubscription`.`Role` = '" . CHAT_ROLE_SUPPORT . "'
				WHERE
					(
						`ChatStatus`.`ID` = " . CHAT_STATUS_ID_OPEN . " OR
						`ChatStatus`.`ParentID` = " . CHAT_STATUS_ID_OPEN . "
					) OR
					(
						SELECT
							COUNT(`ChatMessage`.`ID`)
						FROM
							`ChatMessage`
						WHERE
							`ChatMessage`.`ChatID` = `Chat`.`ID` AND
							`ChatMessage`.`DateTime` > (
								SELECT	`DateTime`
								FROM	`ChatMessage`
								WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
							) AND
							`ChatMessage`.`SenderID` != thisUser.`ID`
					) > 0
				ORDER BY
					UnreadMessageCount > 0 DESC,
					`ChatStatus`.`Priority` DESC,
					(
						SELECT
							`ChatMessage`.`ID`
						FROM
							`ChatMessage`
						WHERE
							`ChatMessage`.`ChatID` = `Chat`.`ID` AND
							`ChatMessage`.`DateTime` > (
								SELECT	`DateTime`
								FROM	`ChatMessage`
								WHERE	`ID` = `ChatSubscription`.`SeenMessageID`
							)
						ORDER BY
							`DateTime` ASC
						LIMIT
							1
					) ASC
			",
			'i',
			[$this->User->ID]
		);
		
		$ongoingChat_associative = [];
		if($ongoingChats){
			foreach($ongoingChats as $ongoingChat){
				if($ongoingChat['UnreadMessageCount'])
					$unreadChats = TRUE;
				$ongoingChat_associative[$ongoingChat['UserAlias']] = $ongoingChat;
			}
		
			return $ongoingChat_associative;
		}
			
		return FALSE;
	}
	
	private function fetchChatsWithStatus($status){
		$chats = $this->db->qSelect(
			"
				SELECT
					`User`.`Alias` UserAlias
				FROM
					`Chat`
				INNER JOIN
					`User` ON
						`Chat`.`SubjectUserID` = `User`.`ID`
				INNER JOIN
					`ChatStatus` ON
						`Chat`.`StatusID` = `ChatStatus`.`ID`
				WHERE
					`Chat`.`StatusID` = ?
			",
			'i',
			[$status]
		);
		
		if($chats){
			$chats_associative = [];
			foreach ($chats as $chat)
				$chats_associative[$chat['UserAlias']] = $chat;
				
			return $chats_associative;
		}
			
		return FALSE;
	}
	
	private function getVendors(){
		if (
			$vendors = $this->db->qSelect(
				"
					SELECT DISTINCT
						Vendor.`ID`,
						Vendor.`Alias`,
						Vendor.`ColorShift`,
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) Image
					FROM
						`User` Vendor
					INNER JOIN
						`PaymentMethod` ON
							`PaymentMethod`.`UserID` = Vendor.`ID`
					INNER JOIN
						`Listing` ON
							Vendor.`ID` = `Listing`.`VendorID`
					INNER JOIN
						`Locale_Country` ON
							`Locale_Country`.`LocaleID` = ?
							" . (
								$this->Locales[0]['Exclusive']
									? 'AND `Locale_Country`.`CountryID` = `Listing`.`CountryID`'
									: false
							) . "
					INNER JOIN
						`Listing_Country` ON
							`Listing_Country`.`ListingID` = `Listing`.`ID` AND
							" . (
								$this->Locales[0]['Exclusive']
									? '`Listing_Country`.`CountryID` = `Listing`.`CountryID`'
									: '`Listing_Country`.`CountryID` = `Locale_Country`.`CountryID`'
							) . "
					LEFT JOIN
						`Image` ON
							Vendor.`ImageID` = `Image`.`ID`
					WHERE
						Vendor.`Vendor` = TRUE AND
						Vendor.`Stealth` = FALSE AND
						`PaymentMethod`.`Enabled` = TRUE AND
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Stealth` = FALSE
					ORDER BY
						Vendor.`Alias` ASC
				",
				'i',
				[
					$this->User->Attributes['Preferences']['LocaleID']
				],
				false,
				true
			)
		)
			return	array_map(
					function ($vendor){
						return	array_merge(
								$vendor,
								[
									'Elements' => $vendor['Image'] ? null : ($this->_fetchVendorLogoElements($vendor['ID']) ?: $this->_generateVendorLogoElements($vendor['Alias'])),
									'Image' => $vendor['Image'] ? NXS::getPictureVariant($vendor['Image'], IMAGE_SMALL_SUFFIX) : false,
									'hueRotateDeg' => !$vendor['Image'] ? NXS::partitionNumber($vendor['ColorShift'] ?? $this->_insertVendorColorShift($vendor['ID'])) : false
								]
							);
					},
					$vendors
				);
		
		return false;
	}
	
	private function _insertVendorColorShift($vendorID){
		$this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`ColorShift` = IFNULL(
						(
							SELECT
								MIN(u2.`ColorShift`) + 1
							FROM
								`User` u2
							WHERE
								NOT EXISTS (
									SELECT	u3.`ID`
									FROM	`User` u3
									WHERE
										u3.`ColorShift` = u2.`ColorShift` + 1
								)
						),
						0
					)
				WHERE
					`ID` = ?
			",
			'i',
			[$vendorID]
		);
		
		return rand(0, 100);
	}
	
	private function _fetchVendorLogoElements($vendorID){
		if(
			$logoElements = $this->db->qSelect(
				"
					SELECT
						`Element`
					FROM
						`User_LogoElement`
					WHERE
						`VendorID` = ?
					ORDER BY
						`Order` ASC
				",
				'i',
				[$vendorID],
				false,
				true
			)
		)
			return	array_map(
					function($logoElement){
						return $logoElement['Element'];
					},
					$logoElements
				);
		
		return false;
	}
	
	private function _parseVendorAliasElements($vendorAlias){
		if(
			preg_match(
				REGEX_MIXED_CASE_STRING,
				$vendorAlias
			) &&
			preg_match_all(
				REGEX_TITLE_CASE_WORDS_CAPTURE_WORDS,
				$vendorAlias,
				$logoElements
			)
		)
			return $logoElements[0];
		
		return [$vendorAlias];
	}
	
	private function _insertVendorLogoElements(
		$vendorAlias,
		$logoElements
	){
		foreach ($logoElements as $logoElement)
			$this->db->qQuery(
				"
					INSERT INTO
						`User_LogoElement` (
							`VendorID`,
							`Order`,
							`Element`
						)
					SELECT
						Vendor.`ID`,
						IFNULL(
							(
								SELECT
									MAX(`User_LogoElement`.`Order`)
								FROM
									`User_LogoElement`
								WHERE
									`User_LogoElement`.`VendorID` = Vendor.`ID`
							) + 1,
							1
						),
						?
					FROM
						`User` Vendor
					WHERE
						Vendor.`Alias` = ?
				",
				'ss',
				[
					$logoElement,
					$vendorAlias
				]
			);
		
		return true;
	}
	
	private function _generateVendorLogoElements($vendorAlias){
		$vendorLogoElements = $this->_parseVendorAliasElements($vendorAlias);
		
		$this->_insertVendorLogoElements(
			$vendorAlias,
			$vendorLogoElements
		);
		
		return $vendorLogoElements;
	}
	
	public function fetchVendorStores(){
		return $this->getVendors();
	}
	
	public function renderMemberButton($href, $title, $style = 'btn big color', $label = false, $login_modal_id = 'login-modal'){
		
		// if($this->Member)
			echo '<' . ($label ? 'label' : 'a') . ' class="' . $style . '" ' . ($label ? 'for' : 'href') . '="' . $href . '">' . $title . '</' . ($label ? 'label' : 'a') . '>';
		// else
		//	echo '<label class="' . $style . '" for="' . $login_modal_id . '">' . $title . '</label>';
		
	}
	
	public function renderRating($rating){
		if($rating > 4.75){
			$fullStars = 5;
			$halfStars = $emptyStars = 0;
		} else {
			$fullStars = floor($rating);
			$halfStars = ($rating - 0.5) >= $fullStars ? 1 : 0;
			$emptyStars = 5 - $fullStars - $halfStars;
		}
		
		echo str_repeat('<i class="full"></i>', $fullStars) . str_repeat('<i class="half"></i>', $halfStars) . str_repeat('<i class="empty"></i>', $emptyStars);
	}
	
	public function renderTransactionSignModal(
		$modalID,
		$postAction,
		$rawTransactions,
		$cryptocurrencyID,
		$signingPublicKey,
		$signedTransactionName = 'signed_transaction',
		$response = false,
		$signTutorialLink = URL . 'p/' . PAGE_TRANSACTION_SIGNING_TUTORIAL . '/'
	){
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		if ($signingPublicKey){
			//$legacyAddress = BitcoinLib::public_key_to_address(
			//	$signingPublicKey,
			//	$cryptocurrency->prefixPublic
			//);
			//$bech32Address = $cryptocurrency->bech32EncodePublicKey($signingPublicKey);
		}
		
		$transactionCount = count($rawTransactions);
		$selectAllMessage = '<p class="note">Use <strong>CTRL-A</strong> or <strong>CMD-A</strong> to select everything.</p>';
		echo '
			<div class="modal wide" id="' . $modalID . '">
				<a href="#"></a>
				<div>
					<a class="close" href="#">&times;</a>
					<form method="post" action="' . $postAction . '">
						<fieldset class="rows-15">
							<label class="label">Verify and sign ' . ( $transactionCount == 1 ? 'this' : 'these' ) . ' <em>raw transaction' . ( $transactionCount == 1 ? FALSE : 's' ) . '</em> :<a class="tooltip inline top" target="_blank" href="' . $signTutorialLink . '">How to sign transactions?</a></label>
							<label class="row ' . ($transactionCount == 1 ? 'pre' : 'textarea') . '">
								' . (
									$transactionCount == 1
										? '<pre contentEditable>' . array_pop($rawTransactions) . '</pre>'
										: (
											'<textarea rows="7" readonly>' . (
												implode(
													PHP_EOL . PHP_EOL,
													$rawTransactions
												)
											) . '</textarea>'
										)
								) . (
									$transactionCount == 1
										? $selectAllMessage
										: false
								) . '
							</label>' . (
								$signingPublicKey
									? '
										<div class="row rows-5">
											<label class="row label">Using the wallet with the following <em>Master Public Key</em> :</label>
											<label class="row text">
												<input readonly value="' . $signingPublicKey . '" type="text">
											</label>
										</div>
									'
									: FALSE
							) . (
								$transactionCount > 1
									? '
										<div class="row rows-5">
											<label class="row label">Sign all transactions in one go using this Electrum console command :</label>
											<label class="row text">
												<input readonly value=\'' . $this->getElectrumConsoleSignCommand(array_values($rawTransactions)) . '\' type="text">
												' . $selectAllMessage . '
											</label>
										</div>
									'
									: false
							) . '
						</fieldset>
						<fieldset class="rows-10">
							<label class="label">Paste the signed transaction' . ( $transactionCount == 1 ? FALSE : 's' ) . ' below :</label>
							<label class="row textarea' . ($response ? ' invalid' : false) . '">
								<textarea required rows="5" name="' . $signedTransactionName . '" placeholder="paste the signed transaction' . ( $transactionCount == 1 ? FALSE : 's' ) . ' here"></textarea>
								' . (
									$response
										? '<p class="note">' . $response . '</p>'
										: (
											$transactionCount > 1
												? '<p class="note">Separate multiple transactions with a comma.</p>'
												: FALSE
										)
								) . '
							</label>
							<div class="row align-right">
								<button class="btn arrow-right" type="submit">Send Transaction' . ( $transactionCount == 1 ? FALSE : 's' ) . '</button>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
		';
	}
	
	private function getElectrumConsoleSignCommand($rawTransactions){
		return 'unsigned = ' . json_encode($rawTransactions) . '; txs = ",\\n\\n".join(list(map(lambda x: signtransaction(x)["hex"], unsigned))); print("\\n\\n" + txs); window.app.clipboard().setText(txs); window.show_message("Your transactions have been signed and copied to clipboard.\nTotal incoming payments: " + str(sum(map(lambda x: x["value"] / 1e8, filter(lambda x: ismine(x["address"]), sum(map(lambda x: deserialize(x)["outputs"], unsigned), []))))))';
	}
	
	private function _getClockDigits(
		$initialDigit,
		$numberOfDigits,
		$displayZero = TRUE
	){
		$digits = '';
		
		for ($i = 0; $i < $numberOfDigits; $i++){
			$number = ($initialDigit + $i) % $numberOfDigits;
			$digits .=
				$displayZero || $number > 0
					? $number
					: ' ';
		}
		
		return strrev($digits);
	}
	
	public function renderCountdownTimer(
		$secondsRemaining,
		$secondsTotal,
		$timerDescription = 'Time Left :'
	){
		$percentageRemaining = number_format(100 * $secondsRemaining / $secondsTotal, 0);
		
		echo '
			<div class="countdown-timer">
				<label class="label">' . $timerDescription . '</label>
				<div class="countdown-bar">
					<div style="width: ' . $percentageRemaining . '%; animation-duration: ' . $secondsRemaining . 's"></div>
				</div>
		';
		$this->renderCountdownClock($secondsRemaining);	
		echo'
			</div>
		';
	}
	
	public function renderCountdownClock(
		$secondsRemaining
	){
		$minutesOnClock = floor($secondsRemaining / 60);
		$secondsOnClock = $secondsRemaining % 60;
		
		$minutesOnClock_tens = floor($minutesOnClock / 10);
		$minutesOnClock_singles = $minutesOnClock % 10;
		
		$secondsOnClock_tens = floor($secondsOnClock / 10);
		$secondsOnClock_singles = $secondsOnClock % 10;
		
		$animationDelay_countdownClock = ($secondsRemaining - 1);
		$animationDelay_minutesTens = $secondsRemaining % (10 * 60);
		$animationDelay_minutesSingles = $secondsRemaining % 60;
		$animationDelay_secondsTens = $secondsRemaining % 10;
		
		$digits_minutesTens = $this->_getClockDigits(
			$minutesOnClock_tens,
			10,
			FALSE
		);
		$digits_minutesSingles = $this->_getClockDigits(
			$minutesOnClock_singles,
			10
		);
		$digits_secondsTens = $this->_getClockDigits(
			$secondsOnClock_tens,
			6
		);
		$digits_secondsSingles = $this->_getClockDigits(
			$secondsOnClock_singles,
			10
		);
		
		echo '
			<label class="countdown-clock label" style="animation-delay: ' . $animationDelay_countdownClock . 's">
				<span class="minutes">
					<span'. ($animationDelay_minutesTens ? ' style="animation-delay: ' . $animationDelay_minutesTens . 's"' : FALSE) . ' class="tens" data-digits="' . $digits_minutesTens . '" >' . ($minutesOnClock_tens ?: '&nbsp;') . '</span><span' . ($animationDelay_minutesSingles ? ' style="animation-delay: ' . $animationDelay_minutesSingles . 's"' : FALSE) . ' class="singles" data-digits="' . $digits_minutesSingles . '">' . $minutesOnClock_singles . '</span>
				</span><span class="seconds">
					<span' . ($animationDelay_secondsTens ? ' style="animation-delay: ' . $animationDelay_secondsTens . 's"' : FALSE) . ' class="tens" data-digits="'. $digits_secondsTens . '">' . $secondsOnClock_tens . '</span><span class="singles" data-digits="' . $digits_secondsSingles . '">' . $secondsOnClock_singles . '</span>
				</span>
			</label>
		';	
	}
	
	public function nl2p($string){
		return '<p>' . preg_replace('#(<br />[\r\n]+){2}#', '</p><p>', nl2br($string)) . '</p>';
	}
	
	public function toUL (
		$arr,
		$pass = 0,
		$activeCategories,
		$currentCategory = false,
		$URLPrefix = FALSE
	) {

		if(!$URLPrefix)
			$URLPrefix = URL . 'listings/';

		$html = '';
	
		foreach ( $arr as $index => $v ) {           
			//$html .= '<li><a href="#">';
			//$html .= $v['name'] . '</a>';
	
			$isActive	= 
				(
					$activeCategories &&
					array_key_exists($v['ID'], $activeCategories)
				) ||
				(
					!$currentCategory &&
					$index == 0
				);
			$isCurrent	= $currentCategory && $currentCategory == $v['ID'];
	
			if ( array_key_exists('Children', $v) && !empty($v['Children']) ) {
				$html .= '<li' . ( $isCurrent ? ' class="active"' : false ) . '>';
				$html.= '
					<input id="cat-'.$v['ID'].'" class="expand" type="checkbox" ' . ( $isActive ? 'checked' : false ) .' />
					<a href="' . $URLPrefix . ($v['Alias'] ? $v['Alias'] : $v['ID']).'/">'. $v['Name'] . ($v['ListingCount'] > 0 ? ' <span>'.number_format($v['ListingCount']).'</span>' : false) . '</a>
					<label for="cat-'.$v['ID'].'"></label>
					<ul class="expandable">
				';
				$html.= $this->toUL($v['Children'], $pass+1, $activeCategories, $currentCategory, $URLPrefix);
				$html.= '</ul>';
			} else {
				$html .= '<li' . ($isActive ? ' class="active"' : false) . '><a href="' . $URLPrefix . ($v['Alias'] ? $v['Alias'] : $v['ID']) .'/">';
				$html .= $v['Name'] . ($v['ListingCount'] > 0 ? ' <span>'.number_format($v['ListingCount']).'</span>' : false) . '</a>';
			}
			
			$html .= '</li>';
	
		}
	
		//$html.= '' . PHP_EOL;
	
		return $html;
	}
	
	public function toSelect($arr, $pass = 0, $active_category = false) {
		
		$html = '';
	
		foreach ( $arr as $v ) {
			
			$isActive = $active_category == $v['id'];
	
			$html .= '<option value="' . $v['id'] . '"' . ($isActive ? ' selected' : false) . '>' . str_repeat('&emsp;', $pass) . $v['Name'] . '</option>';
			if ( array_key_exists('Children', $v) && !empty($v['Children']) )
				$html.= $this->toSelect($v['Children'], $pass+1, $activeCategories);
	
		}
	
		return $html;
		
	}
	
	private function getLocales(){
		$locales = $this->db->qSelect(
			"
				SELECT DISTINCT
					`Locale`.`ID`,
					`Locale`.`Name`,
					`Locale`.`Abbreviation`,
					`Locale`.`Exclusive`
				FROM
					`Locale`
				INNER JOIN
					`Locale_Country` ON
						`Locale`.`ID` = `Locale_Country`.`LocaleID`
				INNER JOIN
					`Listing` ON
						`Locale_Country`.`CountryID` = `Listing`.`CountryID`
				WHERE
					`Listing`.`Inactive` = FALSE
			",
			false,
			false,
			false,
			true
		);
		
		$localesIndexed = [];
		foreach($locales as $locale){
			if($locale['ID'] == $this->User->Attributes['Preferences']['LocaleID'])
				$localesIndexed[0] = $locale;
			else
				$localesIndexed[ $locale['ID'] ] = $locale;
		}
			
		return $localesIndexed;
	}
	
	private function getCurrencies(&$cryptocurrencies = []) {
		if (
			$currenciesResults = $this->db->qSelect(
				"
					SELECT
						`Currency`.`ID`,
						`Currency`.`ISO`,
						`Currency`.`Symbol`,
						`Currency`.`1EUR`,
						`Currency`.`Crypto`,
						`Currency`.`Name`,
						`Locale`.`ID` localeID
					FROM
						`Currency`
					LEFT JOIN
						`Locale` ON
							`Currency`.`ID` = `Locale`.`CurrencyID`
				",
				false,
				false,
				false,
				true,
				DATABASE_MEMCACHED_DEFAULT_EXPIRY,
				DATABASE_MEMCACHED_KEY_CURRENCIES
			)
		){
			$currencies = [];
			foreach ($currenciesResults as $currenciesReult){
				$currencyArray = [
					'ID' => $currenciesReult['ID'],
					'ISO' => $currenciesReult['ISO'],
					'Symbol' => $currenciesReult['Symbol'],
					'XEUR' => $currenciesReult['1EUR']
				];
				
				if ($currenciesReult['localeID'] == $this->User->Attributes['Preferences']['LocaleID'])
					$this->User->Currency = $currencyArray;
				else
					$currencies[] = $currencyArray;
					
				if ($currenciesReult['Crypto']){
					$cryptocurrencyArray = array_merge(
						$currencyArray,
						[
							'Name' => $currenciesReult['Name']
						]
					);
					
					if ($currenciesReult['ID'] == $this->User->Attributes['Preferences']['CryptocurrencyID'])
						$cryptocurrencies[0] = $cryptocurrencyArray;
					else
						$cryptocurrencies[$currenciesReult['ID']] = $cryptocurrencyArray;
				}
			}
			
			$long_list = array_slice($currencies, 3);
			
			usort(
				$long_list,
				function ($a, $b) {
					return strcmp(
						strtolower($a['ISO']),
						strtolower($b['ISO'])
					);
				}
			);
			
			return array_merge(
				array(
					array(
						'ID' => $this->User->Currency['ID'],
						'ISO' => $this->User->Currency['ISO']
					)
				),
				array_slice($currencies, 0, 3, true),
				$long_list
			);
		}
	}
	
	private function fetchContinentsCountries(){
		
		$stmt_Continent = $this->db->prepare("
			SELECT
				`ID`,
				`Name`
			FROM
				`Continent`
			INNER JOIN	`Listing_Continent`
				ON	`Continent`.`ID` = `Listing_Continent`.`ContinentID`
		");
		
		$stmt_Country = $this->db->prepare("
			SELECT
				`ID`,
				`ContinentID`,
				`Name`
			FROM
				`Country`
			INNER JOIN	`Listing_Country`
				ON	`Continent`.`ID` = `Listing_Country`.`CountryID`
			ORDER BY
				`Name`
		");
		
		if( false != $stmt_Continent && false != $stmt_Country ){
			$stmt_Continent->execute();
			$stmt_Continent->store_result();
			$stmt_Continent->bind_result($continent_id, $continent_name);
			$continents = array();
			while($stmt_Continent->fetch() ){
				$continents[$continent_id] = array($continent_name);
			}
			
			$stmt_Country->execute();
			$stmt_Country->store_result();
			$stmt_Country->bind_result($country_id, $country_continent_id, $country_name);
			while($stmt_Country->fetch() ){
				$continents[$country_continent_id][1][] = array($country_id, $country_name);
			}
			
			return $continents;
		}
	}

	private function fetchStoreNames(){
		$storeNames_query = "
			SELECT DISTINCT
				`Alias` as Alias,
			FROM
				`User`
			WHERE
				`Vendor` = TRUE
			AND	`Banned` = FALSE
			AND	`Stealth` = ?
			ORDER BY `Alias`
		";
		
		$storeNames = $this->db->qSelect(
			$storeNames_query,
			'i',
			FALSE
		);
		
		return $storeNames;
	}
		
	private function fetchRootListingCategories(){
		$rootListingCategories_query = "
			SELECT DISTINCT
				LC2.`Alias` as Alias,
				LC2.`Name` as Name,
				LC2.`ImageURL` as Image
			FROM
				`Listing`
			INNER JOIN	`ListingCategory` LC1
				ON	`Listing`.`CategoryID` = LC1.`ID`
			INNER JOIN	`ListingCategory` LC2
				ON	(
						LC1.`ID` = LC2.`ID` OR
						LC1.`ParentID` = LC2.`ID` OR
						LC2.`ID` = (
							SELECT	LC3.`ParentID`
							FROM	`ListingCategory` LC3
							WHERE	LC3.`ID` = LC1.`ParentID`
						)
					)
			INNER JOIN	`Site_ListingCategory`
				ON	LC2.`ID` = `Site_ListingCategory`.`CategoryID`
				AND	`Site_ListingCategory`.`SiteID` = ?
			WHERE
				`Listing`.`Inactive` = FALSE
			AND	`Listing`.`Stealth` = FALSE
			AND	LC2.`ParentID` IS NULL
		";
		
		$rootListingCategories = $this->db->qSelect(
			$rootListingCategories_query,
			'i',
			array($this->db->site_id)
		);
		
		return $rootListingCategories;
	}
	
	private function fetchListingCategories($active_category = false){
		$final_categories = array();
		
		if( $stmt_ListingCategories = $this->db->prepare("
			SELECT
				`ListingCategory`.`ID`,
				`ParentID`,
				`Name`,
				`Alias`,
				(
					SELECT
						COUNT(`Listing`.`ID`)
					FROM
						`Listing`
					INNER JOIN	`User`
						ON	`Listing`.`VendorID` = `User`.`ID`
					WHERE
						`Listing`.`Inactive` = FALSE
					AND	`Listing`.`Approved` = TRUE
					AND	`Listing`.`Stealth` = FALSE
					AND	`Listing`.`CategoryID` = `ListingCategory`.`ID`
					AND	`User`.`Stealth` = FALSE
					AND	`User`.`Reputation` >= ?
				)
			FROM
				`ListingCategory`
			ORDER BY
				`Sort` ASC, `Name` ASC
		") ){
			$minimum_vendor_reputation = $this->db->getSiteInfo('MinimumVendorReputation');
			$minimum_vendor_reputation = $minimum_vendor_reputation ? $minimum_vendor_reputation : 0;
			
			$stmt_ListingCategories->bind_param('i', $minimum_vendor_reputation);
			$stmt_ListingCategories->execute();
			$stmt_ListingCategories->store_result();
			$stmt_ListingCategories->bind_result($id, $parent_id, $name, $alias, $listing_count);
			
			$all_categories = array();
			while($stmt_ListingCategories->fetch() ){
				$all_categories[$id] = array(
					'ID' => $id,
					'ParentID'=> empty($parent_id) ? 0 : $parent_id,
					'Name' => $name,
					'Alias' => !empty($alias) ? $alias : false,
					'ListingCount' => $listing_count
				);
				if( !empty($alias) )
					$alias_categories[$alias] = $id;
			}
			
			$categories = NXS::makeRecursive($all_categories);
			
			if( $allowed_listing_category_id = $this->db->getSiteInfo('ListingCategoryID') ){
				$categories = $full_categories = NXS::reduceCategories($allowed_listing_category_id, $categories, $all_categories);
				$categories = $categories['Children'];
			}
			
			$categories = NXS::tallyCount($categories, 'ListingCount', 'Children');
			
		}
		
		if(
			!$active_category &&
			(
				$allowed_listing_category_id ||
				$default_listing_category_id
			)
		)
			$active_category = $default_listing_category_id ? $default_listing_category_id : $allowed_listing_category_id;
		elseif(
			$active_category &&
			$active_category!=='index' &&
			(
				is_numeric($active_category) ||
				isset($alias_categories[$active_category])
			) 
		){
			$active_category = is_numeric($active_category) ? $active_category : $alias_categories[$active_category];
			$active_category = $allowed_listing_category_id ? NXS::filterCategory($active_category, $categories) : $active_category;
		} else
			$active_category = false;
		
		if( $active_category ){
			
			if( isset($all_categories[$active_category]) ){
				
				if( $active_category !== $allowed_listing_category_id)
					$activeCategories = array(
						$active_category => array(
							$active_category,
							$all_categories[$active_category]['Name']
						)
					);
	
				$arr = $all_categories[$active_category];
				while(
					array_key_exists('ParentID', $arr) &&
					$arr['ParentID'] != 0 &&
					(
						!$allowed_listing_category_id ||
						(
							$arr['ID'] != $allowed_listing_category_id &&
							$arr['ParentID'] != $allowed_listing_category_id
						)
					)
				){
					$activeCategories[ $arr['ParentID'] ] = array(
						$arr['ParentID'],
						$all_categories[$arr['ParentID']]['Name']
					);
					$arr = $all_categories[$arr['ParentID']];
				}
				
				if ( $allowed_listing_category_id == $active_category )
					$visible_categories = $full_categories;
				else
					$visible_categories = NXS::reduceCategories(
						$active_category,
						$categories
					);
				
				$visible_categories = NXS::linearArray($visible_categories);
			}
		} else {
			$activeCategories = $visible_categories = false;
		}
		
		return array($categories, $activeCategories, $visible_categories);
	}
	
	private function doActions() {
		if (!empty($_GET['do'])){
			$url = URL.substr(strtok($_SERVER['REQUEST_URI'], '?'), 1);
			//Session::set('do', $_GET['do']);
			$_SESSION['do'] = $_GET['do'];	
			header('Location: '.$url); die;
		} else {
			if (!empty($_SESSION['do'])){
				$_GET['do'] = $_SESSION['do'];
				unset($_SESSION['do']);
			}
			
			if (isset($_GET['do'])){
				foreach ($_GET['do'] as $action => $value){
					switch ($action) {
						case 'ChangeUserPrefs':
							$new_prefs = array();
							foreach($value as $pref => $value){
								switch($pref){
									case 'AdvancedOrderView':
										$new_prefs['AdvancedViewOrders'] = ($this->User->Attributes['Preferences']['AdvancedViewOrders'] ?: false) == false;
										break;
									case 'AdvancedOrdersPerPage':
										if (in_array($value, ORDER_VIEW_ADVANCED_DEFAULT_ITEMS_PER_PAGE_OPTIONS))
											$new_prefs['AdvancedOrdersPerPage'] = $value;
										break;
									case 'CryptocurrencyID':
										if(
											is_numeric($value) &&
											$value > 0 &&
											in_array(
												$value,
												array_map(
													function($currency){
														return $currency['ID'];
													},
													$this->cryptocurrencies
												)
											)
										){
											$new_prefs[$pref] = $value;
											$new_prefs['PromptCryptocurrency'] = false;
										}
									break;
									case 'LocaleID':
										if(
											is_numeric($value) &&
											$value > 0 &&
											array_key_exists(
												$value,
												$this->Locales
											)
										){
											$new_prefs[$pref] = $value;
											$new_prefs['CatalogFilter'] = [
												'ships_to' => 0,
												'ships_from' => $this->Locales[$value]['Exclusive'] ? 0 : -1
											];
											$_SESSION['recentlyChangedLocale'] = true;
										}
									break;
									case 'CryptocurrencyFeeLevel':
										if(
											is_numeric($value) &&
											$value > 0
										)
											$new_prefs[$pref] = $value;
									break;
									case 'CollapsedNav':
										if( $this->User->Attributes['Preferences']['CollapsedNav'] )
											$new_prefs['CollapsedNav'] = FALSE;
										else
											$new_prefs['CollapsedNav'] = TRUE;
									break;
									case 'ShowExpiredTransactions':
										if( $this->User->Attributes['Preferences']['ShowExpiredTransactions'] )
											$new_prefs['ShowExpiredTransactions'] = FALSE;
										else
											$new_prefs['ShowExpiredTransactions'] = TRUE;
									break;
									case 'ShowArchivedListings':
										if( $this->User->Attributes['Preferences']['ShowArchivedListings'] )
											$new_prefs['ShowArchivedListings'] = FALSE;
										else
											$new_prefs['ShowArchivedListings'] = TRUE;
									break;
								}
							}
							if(
								$new_prefs == $this->User->Attributes['Preferences'] ||
								!$this->User->updatePrefs($new_prefs)
							)
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not change user preferences');
						break;
						case 'DismissForumNotification':
								if (is_numeric($value))
									$this->db->qQuery(
										"
											INSERT IGNORE INTO
												`Notification_User` (
													`NotificationID`,
													`UserID`
												)
											VALUES (
												?,
												?
											)
										",
										'ii',
										[
											$value,
											$this->User->ID
										]
									);
							break;
						case 'DismissNotification':
							if( !(is_numeric($value) && $value <= $this->User->NewestNotificationID && $this->User->updateAttributes(array('LastSeen' => array('NotificationID' => $value))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss notification');
							}
						break;
						case 'DismissReputationChange':
							if( !($this->User->updateAttributes(array('LastSeen' => array('Reputation' => $this->User->Reputation))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss reputation change');
							}
						break;
						case 'DismissUserRatingCountChange':
							if (
								!(
									$this->User->updateAttributes(
										array(
											'LastSeen' => array(
												'UserRating_ID' => $this->User->Info(0, 'LastUserRatingID')
											)
										)
									)
								) 
							) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissListingRatingCountChange':
							if( !($this->User->updateAttributes(array('LastSeen' => array('ListingRating_ID' => $this->User->Info(0, 'LastListingRatingID')))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissTransactionRatingCountChange':
							if (
								!(
									$this->User->updateAttributes(
										array(
											'LastSeen' => array(
												'TransactionRating_ID' => $this->User->Info(0, 'LastTransactionRatingID')
											)
										)
									)
								) 
							) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissInTransitTransactionCountChange':
							if( !($this->User->updateAttributes(array('LastSeen' => array('InTransit_Transaction_ID' => $this->User->Info(0, 'LastTransactionID')))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss transaction updates');
							}
						break;
						case 'DismissSubscribedDiscussionCountChange':
							$this->User->dismissSubscribedDiscussions();
						break;
						case 'DismissNewUserNotification':
							unset($_SESSION['new_user']);
						break;
						case 'DismissTransactionAcceptedCountChange':
							$this->User->setUserNotification(
								USER_NOTIFICATION_TYPEID_TRANSACTION_ACCEPTED,
								0
							);
						break;
						case 'ToggleListingFavorite':
							if (
								!(
									is_numeric($value) &&
									$this->User->toggleListingFavorite($value)
								)
							) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not add to favorites');
							}
						break;
						case 'ToggleUserSubscription':
							if ( !$this->User->toggleUserSubscription($value) ){
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not follow user');
							}
						break;
						case 'DismissJSNotification':
							$_SESSION['js_notice_dismissed'] = TRUE;
						break;
						case 'GenerateDonationAddress':
							if( $this->User->generateDonationAddress() )
								$_SESSION['newly_generated_donation_address'] = TRUE;
						break;
					}
				}
				
				header('Location: '.URL.substr($_SERVER['REQUEST_URI'], 1)); die;
			}
		}
	}
	
	private function fetchSitePages() {
		if( $stmt_getSitePages = $this->db->prepare("
			SELECT
				`Title`,
				`URL`,
				`Target`
			FROM
				`Site_Page`
			WHERE
				`SiteID` = ?
		") ){
			
			$stmt_getSitePages->bind_param('i', $this->db->site_id);
			$stmt_getSitePages->execute();
			$stmt_getSitePages->store_result();
			if( $stmt_getSitePages->num_rows > 0 ){
				
				$stmt_getSitePages->bind_result(
					$page_title,
					$page_url,
					$page_target
				);
				
				$pages = array();
				while( $stmt_getSitePages->fetch() ){
					$pages[] = array(
						'title'		=> $page_title,
						'url'		=> $page_url,
						'target'	=> $page_target
					);
				}
				
				return $pages;
				
			} else {
				return false;
			}
			
		}
		
	}
	
	public function renderNotifications() {
		$args = func_get_args();
		return call_user_func_array(array($this->User->Notifications, 'render'), $args);
	}
	
	public function renderFeedbackMessages()
	{
		// echo out the feedback messages (errors and success messages etc.),
		// they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
		require VIEWS_PATH . '_templates/narrow/feedback.php';
	
		// delete these messages (as they are not needed anymore and we want to avoid to show them twice
		Session::set('feedback_positive', null);
		Session::set('feedback_negative', null);
	}
	
	public function storefront_url($alias){
		return $this->AccessPrefix ? 'http://' . $this->AccessPrefix . '.' . substr(URL, 7) : URL . 'usr/' . strtolower($alias) . '/';
	}
}
