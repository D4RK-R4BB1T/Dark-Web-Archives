<?php

/**
 * CronModel
 */
class CronModel {
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	public function getRunClearance(){
		return	$this->db->qQuery(
				"
					UPDATE
						`Server`
					INNER JOIN
						(
							SELECT
								`Server`.`ID`
							FROM
								`Server`
							WHERE
								`Server`.`ID` = @@server_id AND
								(
									`Server`.`CronRunner` = TRUE OR
									(
										SELECT
											`LastRun` +
											INTERVAL (
												" . CRON_UNRESPONSIVE_CRON_RUNNER_MAX_INTERVAL_MINUTES . " *
												(
													MOD(
														(
															SELECT	COUNT(`ID`)
															FROM	`Server`
														) +
														(
															SELECT
																COUNT(`ID`)
															FROM
																`Server`
															WHERE
																`ID` < @@server_id
														) -
														(
															SELECT
																COUNT(`ID`)
															FROM
																`Server`
															WHERE
																`ID` < (
																	SELECT	`ID`
																	FROM	`Server`
																	WHERE	`CronRunner` = TRUE
																)
														) -
														1,
														(
															SELECT	COUNT(`ID`)
															FROM	`Server`
														)
													)
												)
											) MINUTE
										FROM
											`Server`
										WHERE
											`CronRunner` = TRUE
									) < NOW() - INTERVAL " . CRON_UNRESPONSIVE_CRON_RUNNER_MAX_INTERVAL_MINUTES . " MINUTE
								)
						) S2 ON
							S2.`ID` = `Server`.`ID`
					LEFT JOIN
						`Server` otherServers ON
							`Server`.`ID` IS NOT NULL AND
							otherServers.`ID` != `Server`.`ID`
					SET
						`Server`.`LastRun` = NOW(6),
						`Server`.`Counter` = MOD(`Server`.`Counter` + 1, 256),
						`Server`.`CronRunner` = TRUE,
						otherServers.`CronRunner` = FALSE
				"
			)
				?: die;
	}
	
	public function checkUsersOnline(){
		return	$this->db->qQuery(
				"
					INSERT INTO
						`UsersOnline` (
							`Date`,
							`Total`,
							`Peak`
						)
					VALUES (
						NOW(),
						(
							SELECT
								COUNT(DISTINCT `User`.`ID`)
							FROM
								`User`
							WHERE
								DATE(`User`.`LastSeen`) = DATE(NOW())	
						),
						(
							SELECT
								COUNT(DISTINCT `User`.`ID`)
							FROM
								`User`
							WHERE
								`User`.`LastSeen` > NOW() - INTERVAL " . USER_ONLINE_LAST_SEEN_MINUTES . " MINUTE
						)
					)
					ON DUPLICATE KEY UPDATE
						`Total` = (
							SELECT
								COUNT(DISTINCT `User`.`ID`)
							FROM
								`User`
							WHERE
								DATE(`User`.`LastSeen`) = DATE(NOW())	
						),
						`Peak` = GREATEST(
							(
								SELECT
									COUNT(DISTINCT `User`.`ID`)
								FROM
									`User`
								WHERE
									`User`.`LastSeen` > NOW() - INTERVAL " . USER_ONLINE_LAST_SEEN_MINUTES . " MINUTE
							),
							`Peak`
						)
				"
			);
	}
	
	public function awardForumRank(){
		return	$this->db->qQuery(
				"
					UPDATE
						`User_Class`
					SET
						`Rank` =
							CASE
								WHEN
									`Rank` IS NOT NULL AND
									`Rank` < 3 AND
									(
										SELECT
											SUM(`Discussion_Vote`.`Vote`)
										FROM
											`Discussion_Comment`
										INNER JOIN
											`Discussion_Vote` ON
											`Discussion_Comment`.`ID` = `Discussion_Vote`.`CommentID`
										WHERE
											`Discussion_Comment`.`PosterID` = `User_Class`.`UserID`
									) +
									(
										SELECT
											COUNT(DISTINCT `BlogPostComment`.`BlogPostID`)
										FROM
											`BlogPostComment`
										WHERE
											`BlogPostComment`.`CommenterID` = `User_Class`.`UserID`
									) >= 250
										THEN 3
								WHEN
									`Rank` IS NOT NULL AND
									`Rank` < 2 AND
									(
										SELECT
											SUM(`Discussion_Vote`.`Vote`)
										FROM
											`Discussion_Comment`
										INNER JOIN
											`Discussion_Vote` ON
											`Discussion_Comment`.`ID` = `Discussion_Vote`.`CommentID`
										WHERE
											`Discussion_Comment`.`PosterID` = `User_Class`.`UserID`
									) +
									(
										SELECT
											COUNT(DISTINCT `BlogPostComment`.`BlogPostID`)
										FROM
											`BlogPostComment`
										WHERE
											`BlogPostComment`.`CommenterID` = `User_Class`.`UserID`
									) >= 100
										THEN 2
								WHEN
									(
										`Rank` IS NULL OR
										`Rank` < 1
									) AND
									(
										SELECT
											SUM(`Discussion_Vote`.`Vote`)
										FROM
											`Discussion_Comment`
										INNER JOIN
											`Discussion_Vote` ON
											`Discussion_Comment`.`ID` = `Discussion_Vote`.`CommentID`
										WHERE
											`Discussion_Comment`.`PosterID` = `User_Class`.`UserID`
									) +
									(
										SELECT
											COUNT(DISTINCT `BlogPostComment`.`BlogPostID`)
										FROM
											`BlogPostComment`
										WHERE
											`BlogPostComment`.`CommenterID` = `User_Class`.`UserID`
									) >= 10
										THEN 1
								ELSE
									`Rank`
							END
					WHERE
						`User_Class`.`ClassID` = 3 AND
						`User_Class`.`Locked` = FALSE
				"
			);
	}
	
	public function appointStarMembers(){
		if (
			$newStarMembers = $this->db->qSelect(
				"
					SELECT
						`User`.ID,
						`User`.Alias,
						(SELECT SUM(IF(`PaymentMethod`.CryptocurrencyID = 1,`Transaction`.Value*`BTC_Rate`.USD_Rate,`Transaction`.Value*`LTC_Rate`.USD_Rate)) Purchases) As AllPurchases,
						COUNT(`Transaction`.Status) As TransactionCount,
						MIN(`Transaction_Event`.Date) As FirstPurchaseDate,
						MIN(`Transaction_Rating`.Rating_Buyer) As MinimumBuyerRating
					FROM
						`User`
					INNER JOIN
						`Transaction` ON
							`User`.ID=`Transaction`.BuyerID
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.ID=`Transaction_Event`.TransactionID
					INNER JOIN
						`Transaction_Rating` ON
							`Transaction`.ID=`Transaction_Rating`.TransactionID
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.PaymentMethodID=`PaymentMethod`.ID
					INNER JOIN
						`BTC_Rate` ON
							`Transaction_Event`.Date=`BTC_Rate`.Date
					INNER JOIN
						`LTC_Rate` ON
							`Transaction_Event`.Date=`LTC_Rate`.Date
					WHERE
						`Transaction`.Status='pending feedback' AND
						`Transaction_Event`.Event='accepted' AND
						NOT EXISTS
							(
								SELECT	`User_Class`.UserID
								FROM	`User_Class`
								WHERE	`User_Class`.UserID=`User`.ID
							)
					GROUP BY
						`User`.ID
					HAVING
						AllPurchases > 250 AND
						TransactionCount > 4 AND
						MinimumBuyerRating != 0 AND
						90 < DATEDIFF(CurDate(),FirstPurchaseDate)
				"
			)
		)
			foreach ($newStarMembers as $starMember){
				$this->db->qQuery(
					"
						INSERT IGNORE INTO
							`User_Class` (
								`UserID`,
								`ClassID`,
								`ForumText`,
								`ForumColor`,
								`ForumIcon`,
								`Public`,
								`Primary`
							)
						VALUES (
							?,
							3,
							NULL,
							NULL,
							NULL,
							1,
							0
						)
					",
					'i',
					[$starMember['ID']]
				) &&
				$this->User->sendMessage(
					STAR_MEMBERS_WELCOME_MESSAGE,
					$starMember['ID']
				);
			}
		
		return true;
	}
	
	public function autoVacationVendors($days = VENDOR_INACTIVITY_AUTO_VACATION_DAYS){
		return $this->db->qQuery(
			"
				UPDATE
					`User`
				INNER JOIN
					`Listing` ON
						`Listing`.`VendorID` = `User`.`ID`
				SET
					`Listing`.`Inactive` = TRUE
				WHERE
					`User`.`LastSeen` < NOW() - INTERVAL ? DAY AND
					`User`.`Moderator` = FALSE
			",
			'i',
			[$days]
		);
	}
	
	public function deleteImages(){
		while (
			$this->db->qQuery(
				"
					DELETE	`Image`
					FROM	`Image`
					INNER JOIN (
						SELECT
							`Image`.`ID`
						FROM
							`Image`
						LEFT JOIN
							`User` ON
								`Image`.`ID` = `User`.`ImageID`
						LEFT JOIN
							`Listing_Image` ON
								`Image`.`ID` = `Listing_Image`.`ImageID`
						LEFT JOIN
							`DiscussionComment_Image` ON
								`Image`.`ID` = `DiscussionComment_Image`.`ImageID`
						WHERE
							`User`.`ID` IS NULL AND
							`Listing_Image`.`ID` IS NULL AND
							`DiscussionComment_Image`.`ImageID` IS NULL AND
							`Image`.`OriginalID` IS NULL
						LIMIT 1
					) img ON
						`Image`.`ID` = img.`ID`
				"
			)
		){}
		
		return true;
	}
	
	public function deleteUserContent(){
		while (
			$this->db->qQuery(
				"
					DELETE	`UserContent`
					FROM	`UserContent`
					INNER JOIN
						(
							SELECT
								`UserContent`.`ID`
							FROM
								`UserContent`
							WHERE
								NOT EXISTS (
									SELECT NULL
									FROM `ChatMessage` WHERE
									`UserContent`.`ID` = `ChatMessage`.`ContentID`
								) AND
								NOT EXISTS (
									SELECT NULL
									FROM `Message` WHERE
									`UserContent`.`ID` = `Message`.`ContentID`
								) AND
								NOT EXISTS (
									SELECT NULL
									FROM `Message` WHERE
									`UserContent`.`ID` = `Message`.`ContentID_Sender`
								)
							LIMIT 1
						) UC2 ON
							UC2.`ID` = `UserContent`.`ID`
				"
			)
		){}
		
		return true;
	}
	
	public function deleteTransactionData($olderThanMonths = TRANSACTIONS_OVERVIEW_VIEW_ALL_MAXIMUM_AGE_MONTHS){
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`Order_Vendor` = NULL,
						`Order_Buyer` = NULL,
						`NextTX_Vendor` = NULL,
						`NextTX_Buyer` = NULL,
						`NextTX_Site` = NULL,
						`Policy` = NULL,
						`Identifier` = NULL
					WHERE
						`Timeout` < NOW() - INTERVAL ? MONTH
					LIMIT 1
				",
				'i',
				[$olderThanMonths]
			)
		){}
		
		return true;
	}
	
	private function _updateElectrumServerBlockHeight(
		$electrumServerID,
		$blockHeight,
		$connectionTime
	){
		return $this->db->qQuery(
			"
				UPDATE
					`ElectrumServer`
				SET
					`BlockHeight` = ?,
					`ConnectTime` = ?,
					`Failures` = GREATEST(
						0,
						CAST(`Failures` AS SIGNED) - 1
					)
				WHERE
					`ID` = ?
			",
			'iii',
			[
				$blockHeight,
				$connectionTime,
				$electrumServerID
			]
		);
	}
	
	private function _normalizeElectrumServerFailures(){
		return	$this->db->qQuery(
				"
					UPDATE
						`ElectrumServer`
					INNER JOIN
						(
							SELECT
								`CryptocurrencyID`,
								MIN(`Failures`) minFailures
							FROM
								`ElectrumServer`
							GROUP BY
								`CryptocurrencyID`
						) source ON
							source.`CryptocurrencyID` = `ElectrumServer`.`CryptocurrencyID`
					SET
						`ElectrumServer`.`Failures` = GREATEST(
							0,
							CAST(`Failures` AS SIGNED) - source.minFailures
						)
				"
			);
	}
	
	public function checkElectrumServers(){
		$this->_normalizeElectrumServerFailures();
		if (
			$electrumServers = $this->db->qSelect(
				"
					SELECT
						`ID`,
						CONCAT(
							`Protocol`,
							'://',
							`Host`
						) Host,
						`Port`
					FROM
						`ElectrumServer`
					ORDER BY
						`Failures` ASC
				"
			)
		)
			foreach ($electrumServers as $electrumServer){
				$initialTime = time();
				if (
					$blockHeight = ElectrumServer::getBlockHeight(
						$electrumServer['Host'],
						$electrumServer['Port']
					)
				)
					$this->_updateElectrumServerBlockHeight(
						$electrumServer['ID'],
						$blockHeight,
						time() - $initialTime
					);
			}
		
		return true;
	}
	
	private function _getExchangeRates_blockchainInfo(){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_BLOCKCHAIN)) &&
			$exchangeRateData = json_decode($exchangeRateData_json, TRUE)
		){
			$exchangeRates = [];
			
			if ($EURPerBitcoin = $exchangeRateData['EUR']['15m']){
				$exchangeRates['BTC'] = 1 / $EURPerBitcoin;
				
				foreach($exchangeRateData as $ISO => $data){
					if($ISO == 'EUR')
						continue;
				
					$CurrencyPerBitcoin = $data['15m'];
					$exchangeRates[ $ISO ] = $CurrencyPerBitcoin / $EURPerBitcoin;
				}
			}
			
			if(
				isset(
					$exchangeRates['BTC'],
					$exchangeRates['USD']
				)
			)
				return $exchangeRates;
		}
		
		return [];
	}
	
	private function _getExchangeRates_Bitcoin_sochain(){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_SOCHAIN)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			(
				$exchangeRateData['data']['prices'] = array_filter(
					$exchangeRateData['data']['prices'],
					function($exchangeRateData){
						return is_numeric($exchangeRateData['price']);
					}
				)
			) &&
			(
				$exchangeRates = array_map(
					function ($exchangeRateData){
						return $exchangeRateData['price'];
					},
					$exchangeRateData['data']['prices']
				)
			) &&
			($meanExchangeRate = array_sum($exchangeRates) / count($exchangeRates))
		)
			return $meanExchangeRate;
		
		return false;
	}
	
	private function _getExchangeRates_Bitcoin_cryptocompare(&$additionalExchangeRates){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_CRYPTOCOMPARE)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			($EURPerBitcoin = $exchangeRateData['EUR']) &&
			is_numeric($EURPerBitcoin)
		){
			if (is_numeric($exchangeRateData['USD']))
				$additionalExchangeRates['USD'][] = $EURPerBitcoin / $exchangeRateData['USD'];
			
			return $EURPerBitcoin;
		}
		
		return false;
	}
	
	private function _getExchangeRates_Bitcoin_coinmarketcap(&$additionalExchangeRates){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_COINMARKETCAP)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			($EURPerBitcoin = $exchangeRateData[0]['price_eur']) &&
			is_numeric($EURPerBitcoin)
		){
			if (is_numeric($exchangeRateData[0]['price_usd']))
				$additionalExchangeRates['USD'][] = $EURPerBitcoin / $exchangeRateData[0]['price_usd'];
			
			return (float) $EURPerBitcoin;
		}
		
		return false;
	}
	
	private function _getExchangeRates_Litecoin_sochain(){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_SOCHAIN)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			($EURPerLitecoin = $exchangeRateData['data']['prices'][0]['price']) &&
			is_numeric($EURPerLitecoin)
		)
			return $EURPerLitecoin;
		
		return false;
	}
	
	private function _getExchangeRates_Litecoin_cryptocompare(){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_CRYPTOCOMPARE)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			($EURPerLitecoin = $exchangeRateData['EUR']) &&
			is_numeric($EURPerLitecoin)
		)
			return $EURPerLitecoin;
		
		return false;
	}
	
	private function _getExchangeRates_Litecoin_coinmarketcap(){
		if (
			($exchangeRateData_json = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_COINMARKETCAP)) &&
			($exchangeRateData = json_decode($exchangeRateData_json, TRUE)) &&
			($EURPerLitecoin = $exchangeRateData[0]['price_eur']) &&
			is_numeric($EURPerLitecoin)
		)
			return $EURPerLitecoin;
		
		return false;
	}
	
	private function parseExchangeRates(
		$exchangeRates,
		$ISO
	){
		$exchangeRates = array_filter(
			$exchangeRates,
			function($exchangeRate){
				return	$exchangeRate &&
					is_numeric($exchangeRate);
			}
		);
		
		while (count($exchangeRates) > 1){
			$meanExchangeRate = array_sum($exchangeRates) / count($exchangeRates);
			if (max($exchangeRates) - min($exchangeRates) < CRON_UPDATE_EXCHANGE_RATES_MAXIMUM_RELATIVE_DIFFERENCE * min($exchangeRates))
				return [
					$ISO => (1 / $meanExchangeRate)
				];
			
			$exchangeRateDeviations = array_map(
				function($exchangeRate) use ($meanExchangeRate){
					return abs($exchangeRate - $meanExchangeRate);
				},
				$exchangeRates
			);
				
			unset($exchangeRates[array_keys($exchangeRateDeviations, max($exchangeRateDeviations))[0]]);
		}
		
		return false;
	}
	
	private function _getExchangeRates_Litecoin(){
		$exchangeRates = [
			$this->_getExchangeRates_Litecoin_coinmarketcap(),
			$this->_getExchangeRates_Litecoin_cryptocompare(),
			$this->_getExchangeRates_Litecoin_sochain()
		];
		
		return $this->parseExchangeRates(
			$exchangeRates,
			'LTC'
		);
	}
	
	private function _getExchangeRates_Bitcoin(&$additionalExchangeRates){
		$additionalExchangeRates = [];
		
		$exchangeRates = [
			$this->_getExchangeRates_Bitcoin_coinmarketcap($additionalExchangeRates),
			$this->_getExchangeRates_Bitcoin_cryptocompare($additionalExchangeRates),
			$this->_getExchangeRates_Bitcoin_sochain()
		];
		
		return $this->parseExchangeRates(
			$exchangeRates,
			'BTC'
		);
	}
	
	private function _getExchangeRates_proprietary(){
		if(
			($USDPerEUR = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_PROPRIETARY_USD_PER_EUR)) &&
			$USDPerBitcoin = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_PROPRIETARY_USD_PER_BTC)
		)
			return [
				'USD' => $USDPerEUR,
				'BTC' => (1 / $USDPerBitcoin) * $USDPerEUR
			];
		
		return false;
	}
	
	private function _getExchangeRates_europeanCentralBank(){
		if (
			($centralBankData = NXS::get_data(CRON_UPDATE_EXCHANGE_RATES_API_URL_EUROPEAN_CENTRAL_BANK)) &&
			preg_match_all(
				"/\<Cube currency='([^']+)' rate='([^']+)'\/\>/",
				$centralBankData,
				$matches
			)
		){
			$currencies = [];
			foreach($matches[1] as $key => $ISO)
				$currencies[$ISO] = $matches[2][$key];
				
			return $currencies;
		}
		
		return false;
	}
	
	private function getExchangeRates(){
		$exchangeRates_litecoin = $this->_getExchangeRates_Litecoin() ?: [];
		$exchangeRates_bitcoin = $this->_getExchangeRates_Bitcoin($additionalExchangeRateData) ?: [];
		$exchangeRates_europeanCentralBank = $this->_getExchangeRates_europeanCentralBank() ?: [];
		
		if (
			$additionalExchangeRateData &&
			$additionalExchangeRateData = array_map(
				function ($exchangeRateData, $ISO){
					if (isset($exchangeRates_europeanCentralBank[$ISO]))
						$exchangeRateData[] = 1 / $exchangeRates_europeanCentralBank[$ISO];
					
					return $this->parseExchangeRates(...func_get_args());
				},
				$additionalExchangeRateData,
				array_keys($additionalExchangeRateData)
			)
		){
			$additionalExchangeRates = [];
			foreach ($additionalExchangeRateData as $exchangeRateData){
				$additionalExchangeRates[array_keys($exchangeRateData)[0]] = array_values($exchangeRateData)[0];
			}
		
			$exchangeRates_europeanCentralBank = array_merge(
				$exchangeRates_europeanCentralBank,
				$additionalExchangeRates
			);
		}
		
		return array_merge(
			#$exchangeRates_blockchainInfo,
			$exchangeRates_litecoin,
			$exchangeRates_bitcoin,
			$exchangeRates_europeanCentralBank
		);
	}
	
	public function updateExchangeRates(){
		if ($exchangeRates = $this->getExchangeRates()){
			$updatedCurrencies = 0;
			foreach($exchangeRates as $ISO => $EURPerCurrency){
				$updatedCurrencies += $this->db->qQuery(
					"
						UPDATE
							`Currency`
						SET
							`1EUR` = ?
						WHERE
							`ISO` = ?
					",
					'ds',
					[
						$EURPerCurrency,
						$ISO
					]
				);
			}
			
			$this->db->m->delete(DATABASE_MEMCACHED_KEY_CURRENCIES);
			
			return $updatedCurrencies;
		}
			
		return false;
	}
	
	public function insertDailyBTCRate(){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`BTC_Rate` (
						`Date`,
						`USD_Rate`
					)
				SELECT
					NOW(),
					(
						SELECT	`1EUR`
						FROM	`Currency`
						WHERE	`ID` = " . CURRENCY_ID_USD . "
					) / 
					(
						SELECT	`1EUR`
						FROM	`Currency`
						WHERE	`ID` = " . CURRENCY_ID_BTC . "
					)

			"
		);
	}
	
        public function insertDailyLTCRate(){
                return $this->db->qQuery(
                        "
                                INSERT IGNORE INTO
                                        `LTC_Rate` (
                                                `Date`,
                                                `USD_Rate`
                                        )
                                SELECT
                                        NOW(),
                                        (
                                                SELECT  `1EUR`
                                                FROM    `Currency`
                                                WHERE   `ID` = " . CURRENCY_ID_USD . "
                                        ) / 
                                        (
                                                SELECT  `1EUR`
                                                FROM    `Currency`
                                                WHERE   `ID` = " . CURRENCY_ID_LTC . "
                                        ) 

                        "
                );
        }
	
	private function _getUnreadMessagesPendingDelete($conversation){
		return $this->db->qSelect(
			"
				SELECT
					`Message`.`RecipientID`,
					`Message`.`SenderID`,
					COUNT(DISTINCT `Message`.`ID`) messageCount
				FROM
					`Message`
				WHERE
					(
						(
							`Message`.`SenderID` = ? AND
							`Message`.`RecipientID` = ?
						) OR
						(
							`Message`.`SenderID` = ? AND
							`Message`.`RecipientID` = ?
						)
					) AND
					`Message`.`Read` = FALSE AND
					(
						`Message`.`Sent` < NOW() - INTERVAL " . CRON_AUTO_DELETE_ALL_MESSAGES_OLDER_THAN_DAYS . " DAY OR
						(
							`Message`.`AutoDelete` IS NOT NULL AND
							`Message`.`AutoDelete` < NOW()
						) OR
						(
							`Message`.`ContentID` IS NULL AND
							`Message`.`ContentID_Sender` IS NULL
						) OR
						(
							`Message`.`SenderID` IS NULL AND
							`Message`.`RecipientID` IS NULL
						)
					)
				GROUP BY
					`Message`.`RecipientID`
			",
			'iiii',
			[
				$conversation['userOne'],
				$conversation['userTwo'],
				$conversation['userTwo'],
				$conversation['userOne']
			]
		);
	}
	
	private function _decrementUserMessageNotifications($unreadMessagesPendingDelete){
		foreach ($unreadMessagesPendingDelete as $messageArr)
			$this->User->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES,
				-1*$messageArr['messageCount'],
				$messageArr['RecipientID']
			);
		
		return true;
	}
	
	private function _refreshUserConversations($conversations){
		foreach ($conversations as $conversation)
			$this->User->refreshConversation(
				$conversation['userOne'],
				$conversation['userTwo']
			);
		
		return true;
	}
	
	/* 
	 * Deletes messages
	 */
	public function deleteMessages(){
		if (
			$conversations = $this->db->qSelect(
				"
					SELECT DISTINCT
						LEAST(
							`Message`.`SenderID`,
							`Message`.`RecipientID`
						) userOne,
						GREATEST(
							`Message`.`SenderID`,
							`Message`.`RecipientID`
						) userTwo
					FROM
						`Message`
					WHERE
						`Message`.`Sent` < NOW() - INTERVAL " . CRON_AUTO_DELETE_ALL_MESSAGES_OLDER_THAN_DAYS . " DAY OR
						(
							`Message`.`AutoDelete` IS NOT NULL AND
							`Message`.`AutoDelete` < NOW()
						) OR
						(
							`Message`.`ContentID` IS NULL AND
							`Message`.`ContentID_Sender` IS NULL
						) OR
						(
							`Message`.`SenderID` IS NULL AND
							`Message`.`RecipientID` IS NULL
						)
				"
			)
		){
			foreach ($conversations as $conversation){
				if ($unreadMessagesPendingDelete = $this->_getUnreadMessagesPendingDelete($conversation))
					$this->_decrementUserMessageNotifications($unreadMessagesPendingDelete);
				
				$this->db->qQuery(
					"
						DELETE
							`Message`,
							RecipientContent,
							SenderContent
						FROM
							`Message`
						LEFT JOIN
							`UserContent` RecipientContent ON
								`Message`.`ContentID` = RecipientContent.`ID`
						LEFT JOIN
							`UserContent` SenderContent ON
								`Message`.`ContentID_Sender` = SenderContent.`ID`
						WHERE
							(
								(
									`Message`.`SenderID` = ? AND
									`Message`.`RecipientID` = ?
								) OR
								(
									`Message`.`SenderID` = ? AND
									`Message`.`RecipientID` = ?
								)
							) AND
							(
								`Message`.`Sent` < NOW() - INTERVAL " . CRON_AUTO_DELETE_ALL_MESSAGES_OLDER_THAN_DAYS . " DAY OR
								(
									`Message`.`AutoDelete` IS NOT NULL AND
									`Message`.`AutoDelete` < NOW()
								) OR
								(
									`Message`.`ContentID` IS NULL AND
									`Message`.`ContentID_Sender` IS NULL
								) OR
								(
									`Message`.`SenderID` IS NULL AND
									`Message`.`RecipientID` IS NULL
								)
							)
					",
					'iiii',
					[
						$conversation['userOne'],
						$conversation['userTwo'],
						$conversation['userTwo'],
						$conversation['userOne']
					]
				);
			}
			
			$this->_refreshUserConversations($conversations);
		}
		
		return true;
	}
}
