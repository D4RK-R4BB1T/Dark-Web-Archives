<?php

/**
 * AdminModel
 */
class AdminModel {
	public $Infos;
		
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
		
		if (!$this->User->IsAdmin)
			NXS::showError();
	}
	
	public function toggleDiscussionSinked($discussionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Discussion`
					SET
						`Sink` = (`Sink` = FALSE)
					WHERE
						`ID` = ?
				",
				'i',
				[$discussionID]
			);
	}
	
	private function _getGenericQuery(
		$identifier,
		$argumentCount
	){
		if (
			(
				$query = $this->db->qSelect(
					"
						SELECT
							`Title`,
							`Type`,
							`Query`,
							`ParameterTypes`
						FROM
							`AdminQuery`
						WHERE
							`Identifier` = ?
					",
					's',
					[$identifier]
				)
			) &&
			strlen($query[0]['ParameterTypes']) == $argumentCount
		)
			return $query[0];
			
		return false;
	}
	
	public function getGenericQueryResults(){
		$arguments = func_get_args();
		$queryIdentifier = array_shift($arguments);
		
		if (
			$query = $this->_getGenericQuery(
				$queryIdentifier,
				count($arguments)
			)
		)
			return [
				$query['Title'],
				call_user_func_array(
					[
						$this->db,
						(
							$query['Type'] == 'Select'
								? 'qSelect'
								: 'qQuery'
						)
					],
					[
						$query['Query'],
						$query['ParameterTypes'],
						$arguments
					]
				)
			];
		
		return false;
	}
	
	public function fetchAnalytics(){
		$aggregateData = [
			/* "[Column Title]" => "[query]", */
			/*"Last Week's Revenues" => "
				SELECT
					CONCAT(
						'" . ENTITY_BITCOIN_SYMBOL . " ',
						(
							FORMAT(
								(
									SELECT
										SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
									FROM
										`Transaction`
									INNER JOIN
										`Listing` ON
											`Transaction`.`ListingID` = `Listing`.`ID`
									INNER JOIN
										`User` Vendor ON
											`Listing`.`VendorID` = Vendor.`ID`
									INNER JOIN
										`Transaction_Event` ON
											`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
									WHERE
										`Transaction_Event`.`Event` = 'accepted' AND
										YEARWEEK(`Transaction_Event`.`Date`, 1) = YEARWEEK(NOW() - INTERVAL 1 WEEK, 1) # preceding week
								),
								4
							)
						),
						' ',
						IF(
							(
								SELECT
									SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
								FROM
									`Transaction`
								INNER JOIN
									`Listing` ON
										`Transaction`.`ListingID` = `Listing`.`ID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
								INNER JOIN
									`Transaction_Event` ON
										`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
								WHERE
									`Transaction_Event`.`Event` = 'accepted' AND
									YEARWEEK(`Transaction_Event`.`Date`, 1) = YEARWEEK(NOW() - INTERVAL 1 WEEK, 1) # preceding week
							) >
							(
								SELECT
									SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
								FROM
									`Transaction`
								INNER JOIN
									`Listing` ON
										`Transaction`.`ListingID` = `Listing`.`ID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
								INNER JOIN
									`Transaction_Event` ON
										`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
								WHERE
									`Transaction_Event`.`Event` = 'accepted' AND
									YEARWEEK(`Transaction_Event`.`Date`, 1) = YEARWEEK(NOW() - INTERVAL 2 WEEK, 1) # before preceding week
							),
							'&nearr;',
							'&searr;'
						),
						'<br>&#36; ',
						(
							SELECT
								FORMAT(
									CAST(
										(
											(
												SELECT
													SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
												FROM
													`Transaction`
												INNER JOIN
													`Listing` ON
														`Transaction`.`ListingID` = `Listing`.`ID`
												INNER JOIN
													`User` Vendor ON
														`Listing`.`VendorID` = Vendor.`ID`
												INNER JOIN
													`Transaction_Event` ON
														`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
												WHERE
													`Transaction_Event`.`Event` = 'accepted' AND
													YEARWEEK(`Transaction_Event`.`Date`, 1) = YEARWEEK(NOW() - INTERVAL 1 WEEK, 1) # preceding week
											) *
											(
												SELECT
													`1EUR`
												FROM
													`Currency`
												WHERE
													`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
											) /
											(
												SELECT
													`1EUR`
												FROM
													`Currency`
												WHERE
													`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
											)
										) AS
										DECIMAL(16,2)
									),
									2
								)
						)
					)
			",*/
			"Number of Active Listings" => "
				SELECT
					COUNT(DISTINCT `Listing`.`ID`)
				FROM
					`Listing`
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
				WHERE
					`Inactive` = FALSE AND
					`Listing`.`Stealth` = FALSE AND
					Vendor.`Stealth` = FALSE
			",
			"Users Online" => "
				SELECT
					CONCAT(
						(
							SELECT
								COUNT(DISTINCT `User`.`ID`)
							FROM
								`User`
							WHERE
								`User`.`LastSeen` > NOW() - INTERVAL " . USER_ONLINE_LAST_SEEN_MINUTES . " MINUTE
						),
						' (',
						(
							SELECT
								GROUP_CONCAT(cnt SEPARATOR ' | ')
							FROM
								(
									SELECT
										COUNT(`User`.`ID`) cnt
									FROM
										`User`
									WHERE
										`User`.`LastSeen` > NOW() - INTERVAL " . USER_ONLINE_LAST_SEEN_MINUTES . " MINUTE AND
										`User`.`LastSeen_ServerID` IS NOT NULL
									GROUP BY
										`User`.`LastSeen_ServerID`
								) a
						),
						')'
					)
			"
		];
		
		$tabularData = [];
		
		if (
			$this->User->Alias == 'Finn' ||
			$this->User->Alias == 'TestAdmin'
		){
			$aggregateData = array_merge(
				$aggregateData,
				[
					"Rolling 7-day Revenues" => "
						SELECT
							CONCAT(
								'" . ENTITY_BITCOIN_SYMBOL . " ',
								(
									FORMAT(
										(
											SELECT
												SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
											FROM
												`Transaction`
											INNER JOIN
												`Listing` ON
													`Transaction`.`ListingID` = `Listing`.`ID`
											INNER JOIN
												`User` Vendor ON
													`Listing`.`VendorID` = Vendor.`ID`
											INNER JOIN
												`Transaction_Event` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											WHERE
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
												`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
												`PaymentMethod`.`CryptocurrencyID` = 1  
										),
										4
									)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 1

									) >
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 2 WEEK AND
											`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK AND
											`PaymentMethod`.`CryptocurrencyID` = 1

									),
									'&nearr;',
									'&searr;'
								),
								'<br> ',
								'" . ENTITY_LITECOIN_SYMBOL . " ',
								(
									FORMAT(
										(
											SELECT
												SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
											FROM
												`Transaction`
											INNER JOIN
												`Listing` ON
													`Transaction`.`ListingID` = `Listing`.`ID`
											INNER JOIN
												`User` Vendor ON
													`Listing`.`VendorID` = Vendor.`ID`
											INNER JOIN
												`Transaction_Event` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											WHERE
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
												`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
												`PaymentMethod`.`CryptocurrencyID` = 7
										),
										4
									)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 7

									) >
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 2 WEEK AND
											`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK AND
											`PaymentMethod`.`CryptocurrencyID` = 7

									),
									'&nearr;',
									'&searr;'
								),
								'<br>&#36; ',
								(
									SELECT
										FORMAT(
											CAST(
												(
													(
														SELECT
															SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
														FROM
															`Transaction`
														INNER JOIN
															`Listing` ON
																`Transaction`.`ListingID` = `Listing`.`ID`
														INNER JOIN
															`User` Vendor ON
																`Listing`.`VendorID` = Vendor.`ID`
														INNER JOIN
															`Transaction_Event` ON
																`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															`Transaction_Event`.`Event` = 'paid' AND
															`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
															`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
															`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
															`PaymentMethod`.`CryptocurrencyID` = 1
													) *
													(
														SELECT
															`BTC_Rate`.`USD_Rate`
														FROM
															`BTC_Rate`
														WHERE
															`BTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 DAY)
													) +
													(
														SELECT
															SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
														FROM
															`Transaction`
														INNER JOIN
															`Listing` ON
																`Transaction`.`ListingID` = `Listing`.`ID`
														INNER JOIN
															`User` Vendor ON
																`Listing`.`VendorID` = Vendor.`ID`
														INNER JOIN
															`Transaction_Event` ON
																`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															`Transaction_Event`.`Event` = 'paid' AND
															`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
															`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
															`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
															`PaymentMethod`.`CryptocurrencyID` = 7
													) *
													(
														SELECT
															`LTC_Rate`.`USD_Rate`
														FROM
															`LTC_Rate`
														WHERE
															`LTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 DAY)
													)
												) AS
												DECIMAL(16,0)
											),
											0
										)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 1
									) *
									(
										SELECT
											`BTC_Rate`.`USD_Rate`
										FROM
											`BTC_Rate`
										WHERE
											`BTC_Rate`.`Date` = (
												SELECT	MAX(`BTC_Rate`.`Date`)
												FROM	`BTC_Rate`
											)
									) +
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 7
									) *
									(
										SELECT
											`LTC_Rate`.`USD_Rate`
										FROM
											`LTC_Rate`
										WHERE
											`LTC_Rate`.`Date` = (
												SELECT	MAX(`LTC_Rate`.`Date`)
												FROM	`LTC_Rate`
											)
									) >
									(
										(
											SELECT
												SUM(
													`Transaction`.`Value` *
													IF(
														Vendor.`Commission` > 0,
														Vendor.`Commission`/1000,
														" . MARKETPLACE_FEE . "
													)
												)
											FROM
												`Transaction_Event`
											INNER JOIN
												`Transaction` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											INNER JOIN
												`User` Vendor ON
													`PaymentMethod`.`UserID` = Vendor.`ID`
											WHERE
												`PaymentMethod`.`CryptocurrencyID` = 1 AND
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` >= NOW() - INTERVAL 2 WEEK AND
												`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK
										) *
										(
											SELECT
												`BTC_Rate`.`USD_Rate`
											FROM
												`BTC_Rate`
											WHERE
												`BTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 WEEK)
										) +
										(
											SELECT
												SUM(
													`Transaction`.`Value` *
													IF(
														Vendor.`Commission` > 0,
														Vendor.`Commission`/1000,
														" . MARKETPLACE_FEE . "
													)
												)
											FROM
												`Transaction_Event`
											INNER JOIN
												`Transaction` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											INNER JOIN
												`User` Vendor ON
													`PaymentMethod`.`UserID` = Vendor.`ID`
											WHERE
												`PaymentMethod`.`CryptocurrencyID` = 7 AND
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` >= NOW() - INTERVAL 2 WEEK AND
												`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK
										) *
										(
											SELECT
												`LTC_Rate`.`USD_Rate`
											FROM
												`LTC_Rate`
											WHERE
												`LTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 WEEK)
										)
									),
									'&nearr;',
									'&searr;'
								)
							)
					",
					"Funds In Escrow" => "
						SELECT
							CONCAT(
								'" . ENTITY_BITCOIN_SYMBOL . " ',
								(
									SELECT
										SUM(`Transaction`.`Value`)
									FROM
										`Transaction`
									INNER JOIN
										`PaymentMethod` ON
											`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
									WHERE
										(
											`Transaction`.`Status` IN (
												'in transit',
												'pending accept',
												'in dispute',
												'expired'
											) OR
											(
												`Transaction`.`Status` IN ('pending feedback', 'rejected', 'refunded') AND
												`Transaction`.`Withdrawn` = FALSE
											) OR
											(
												`Transaction`.`Status` = 'pending deposit' AND
												`Transaction`.`Paid` = TRUE
											)
										) AND
										`PaymentMethod`.`CryptocurrencyID` = 1
								),
								'<br>',
								'" . ENTITY_LITECOIN_SYMBOL . " ',
								(
									SELECT
										SUM(`Transaction`.`Value`)
									FROM
										`Transaction`
									INNER JOIN
										`PaymentMethod` ON
											`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
									WHERE
										(
											`Transaction`.`Status` IN (
												'in transit',
												'pending accept',
												'in dispute',
												'expired'
											) OR
											(
												`Transaction`.`Status` IN ('pending feedback', 'rejected', 'refunded') AND
												`Transaction`.`Withdrawn` = FALSE
											) OR
											(
												`Transaction`.`Status` = 'pending deposit' AND
												`Transaction`.`Paid` = TRUE
											)
										) AND
										`PaymentMethod`.`CryptocurrencyID` = 7
								),

								'<br>&#36; ',
								(
									SELECT
										FORMAT(
											CAST(
												(
													(
														SELECT
															SUM(`Transaction`.`Value`)
														FROM
															`Transaction`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															(
																`Transaction`.`Status` IN (
																	'in transit',
																	'pending accept',
																	'in dispute'
																) OR
																(
																	`Transaction`.`Status` IN ('pending feedback', 'rejected', 'refunded') AND
																	`Transaction`.`Withdrawn` = FALSE
																) OR
																(
																	`Transaction`.`Status` = 'pending deposit' AND
																	`Transaction`.`Paid` = TRUE
																)		
															) AND
															`PaymentMethod`.`CryptocurrencyID` = 1
													) *
													(
														SELECT
															`1EUR`
														FROM
															`Currency`
														WHERE
															`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
													) /
													(
														SELECT
															`1EUR`
														FROM
															`Currency`
														WHERE
															`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
													) +
													(
														SELECT
															SUM(`Transaction`.`Value`)
														FROM
															`Transaction`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															(
																`Transaction`.`Status` IN (
																	'in transit',
																	'pending accept',
																	'in dispute'
																) OR
																(
																	`Transaction`.`Status` IN ('pending feedback', 'rejected', 'refunded') AND
																	`Transaction`.`Withdrawn` = FALSE
																) OR
																(
																	`Transaction`.`Status` = 'pending deposit' AND
																	`Transaction`.`Paid` = TRUE
																)
															) AND
															`PaymentMethod`.`CryptocurrencyID` = 7
													) *
													(
														SELECT
															`1EUR`
														FROM
															`Currency`
														WHERE
															`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
													) /
													(
														SELECT
															`1EUR`
														FROM
															`Currency`
														WHERE
															`Currency`.`ID` = '" . CURRENCY_ID_LTC . "'
													)
												) AS
												DECIMAL(16,0)
											),
											2
										)
								)
						
							)
					",
					"Number of Users<br>" => "
						SELECT
							CONCAT(
								(
									SELECT 
										COUNT(DISTINCT `Transaction`.`BuyerID`)
									FROM
										`Transaction` 
									INNER JOIN
										`Transaction_Event` ON
											`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
									WHERE
										`Transaction_Event`.`Event` = 'Accepted' AND
										`Transaction_Event`.`Date` > NOW() - INTERVAL 90 DAY
								
								),
								' buyers (90 days) ',
								IF(
									(
										SELECT 
											COUNT(DISTINCT `Transaction`.`BuyerID`)
										FROM
											`Transaction` 
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										WHERE
											`Transaction_Event`.`Event` = 'Accepted' AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 90 DAY
								
									) > 
									(
										SELECT 
											COUNT(DISTINCT `Transaction`.`BuyerID`)
										FROM
											`Transaction` 
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										WHERE
											`Transaction_Event`.`Event` = 'Accepted' AND
											`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 90 DAY - INTERVAL 1 WEEK
								
									),
									'&nearr;',
									'&searr;'
								),
								'<br>',
								(
									SELECT
										COUNT(`User`.`ID`)
									FROM
										`User`
									WHERE
										`User`.`LastSeen` > NOW() - INTERVAL 90 DAY
								),
								' active (90 days) ',
								IF(
									(
										SELECT
											COUNT(`User`.`ID`)
										FROM
											`User`
										WHERE
											`User`.`LastSeen` > NOW() - INTERVAL 90 DAY
									) >
									(
										SELECT
											COUNT(`User`.`ID`)
										FROM
											`User`
										WHERE
											`User`.`LastSeen` < NOW() - INTERVAL 1 WEEK AND
											`User`.`LastSeen` > NOW() - INTERVAL 90 DAY - INTERVAL 1 WEEK
									),
									'&nearr;',
									'&searr;'
								),
								'<br>',
								(
									SELECT
										COUNT(`User`.`ID`)
									FROM
										`User`
								),
								' total'
							)
					",
					'Server ID' => "
						SELECT @@server_id
					"
				]
			);
			$tabularData = array_merge(
				$tabularData,
				[
					"Weekly Sales By Vendor" => "
						SELECT
						Vendor.`Alias` 'Vendor',
						COUNT(DISTINCT `Transaction`.`ID`) Orders,
						FORMAT(
							SUM(
								((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 1)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
							)) +
							SUM(
							   ((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 7)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_LTC . "'
							)
							)
							,
							0
						) 'USD',
						FORMAT(
							(SUM(
								((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 1)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
							)) +
							SUM(
								((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 7)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_LTC . "'					
							))
							) /
							COUNT(DISTINCT `Transaction`.`ID`),
							0
						) 'Average Order'
				                FROM
				                        `Transaction`
				                INNER JOIN
				                        `Listing` ON
				                                `Transaction`.`ListingID` = `Listing`.`ID`
				                INNER JOIN
				                        `User` Vendor ON
				                                `Listing`.`VendorID` = Vendor.`ID`
						INNER JOIN
							`Transaction_Event` ON
								`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
						INNER JOIN
							`PaymentMethod` ON
								`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
						WHERE
				                        `Transaction_Event`.`Event` = 'accepted' AND
				                        `Transaction_Event`.`Date` > NOW() - INTERVAL 1 WEEK
				                GROUP BY
				                        Vendor.`Alias`
						ORDER BY 
							(SUM(
								((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 1)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
							)) +
							SUM(
								((`Transaction`.`Value`) * (`PaymentMethod`.`CryptocurrencyID` = 7)) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE
									`Currency`.`ID` = '" . CURRENCY_ID_LTC . "'					
							))
							) DESC
					",
				]
			);
		}
		
		if ($this->User->Alias == 'Rory') {
			$aggregateData = array_merge(
				$aggregateData,
				[
					"Rory's Share" => "
						SELECT
							CONCAT(
								'" . ENTITY_BITCOIN_SYMBOL . " ',
								(
									FORMAT(
										(
											SELECT
												SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . ")) * 0.09
											FROM
												`Transaction`
											INNER JOIN
												`Listing` ON
													`Transaction`.`ListingID` = `Listing`.`ID`
											INNER JOIN
												`User` Vendor ON
													`Listing`.`VendorID` = Vendor.`ID`
											INNER JOIN
												`Transaction_Event` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											WHERE
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
												`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
												`PaymentMethod`.`CryptocurrencyID` = 1  
										),
										4
									)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 1

									) >
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 2 WEEK AND
											`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK AND
											`PaymentMethod`.`CryptocurrencyID` = 1

									),
									'&nearr;',
									'&searr;'
								),
								'<br> ',
								'" . ENTITY_LITECOIN_SYMBOL . " ',
								(
									FORMAT(
										(
											SELECT
												SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . ")) * 0.09
											FROM
												`Transaction`
											INNER JOIN
												`Listing` ON
													`Transaction`.`ListingID` = `Listing`.`ID`
											INNER JOIN
												`User` Vendor ON
													`Listing`.`VendorID` = Vendor.`ID`
											INNER JOIN
												`Transaction_Event` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											WHERE
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
												`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
												`PaymentMethod`.`CryptocurrencyID` = 7
										),
										4
									)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 7

									) >
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 2 WEEK AND
											`Transaction_Event`.`Date` < NOW() - INTERVAL 1 WEEK AND
											`PaymentMethod`.`CryptocurrencyID` = 7

									),
									'&nearr;',
									'&searr;'
								),
								'<br>&#36; ',
								(
									SELECT
										FORMAT(
											CAST(
												(
													(
														SELECT
															SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . ")) * 0.09
														FROM
															`Transaction`
														INNER JOIN
															`Listing` ON
																`Transaction`.`ListingID` = `Listing`.`ID`
														INNER JOIN
															`User` Vendor ON
																`Listing`.`VendorID` = Vendor.`ID`
														INNER JOIN
															`Transaction_Event` ON
																`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															`Transaction_Event`.`Event` = 'paid' AND
															`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
															`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
															`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
															`PaymentMethod`.`CryptocurrencyID` = 1
													) *
													(
														SELECT
															`BTC_Rate`.`USD_Rate`
														FROM
															`BTC_Rate`
														WHERE
															`BTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 DAY)
													) +
													(
														SELECT
															SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . ")) * 0.09
														FROM
															`Transaction`
														INNER JOIN
															`Listing` ON
																`Transaction`.`ListingID` = `Listing`.`ID`
														INNER JOIN
															`User` Vendor ON
																`Listing`.`VendorID` = Vendor.`ID`
														INNER JOIN
															`Transaction_Event` ON
																`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
														INNER JOIN
															`PaymentMethod` ON
																`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
														WHERE
															`Transaction_Event`.`Event` = 'paid' AND
															`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
															`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
															`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
															`PaymentMethod`.`CryptocurrencyID` = 7
													) *
													(
														SELECT
															`LTC_Rate`.`USD_Rate`
														FROM
															`LTC_Rate`
														WHERE
															`LTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 DAY)
													)
												) AS
												DECIMAL(16,0)
											),
											0
										)
								),
								' ',
								IF(
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 1
									) *
									(
										SELECT
											`BTC_Rate`.`USD_Rate`
										FROM
											`BTC_Rate`
										WHERE
											`BTC_Rate`.`Date` = (
												SELECT	MAX(`BTC_Rate`.`Date`)
												FROM	`BTC_Rate`
											)
									) +
									(
										SELECT
											SUM(`Transaction`.`Value` * IF(Vendor.`Commission` > 0, Vendor.`Commission`/1000, " . MARKETPLACE_FEE . "))
										FROM
											`Transaction`
										INNER JOIN
											`Listing` ON
												`Transaction`.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`User` Vendor ON
												`Listing`.`VendorID` = Vendor.`ID`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`PaymentMethod` ON
												`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
										WHERE
											`Transaction_Event`.`Event` = 'paid' AND
											`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL 8 DAY AND
											`Transaction_Event`.`Date` <= NOW() - INTERVAL 1 DAY AND
											`PaymentMethod`.`CryptocurrencyID` = 7
									) *
									(
										SELECT
											`LTC_Rate`.`USD_Rate`
										FROM
											`LTC_Rate`
										WHERE
											`LTC_Rate`.`Date` = (
												SELECT	MAX(`LTC_Rate`.`Date`)
												FROM	`LTC_Rate`
											)
									) >
									(
										(
											SELECT
												SUM(
													`Transaction`.`Value` *
													IF(
														Vendor.`Commission` > 0,
														Vendor.`Commission`/1000,
														" . MARKETPLACE_FEE . "
													)
												)
											FROM
												`Transaction_Event`
											INNER JOIN
												`Transaction` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											INNER JOIN
												`User` Vendor ON
													`PaymentMethod`.`UserID` = Vendor.`ID`
											WHERE
												`PaymentMethod`.`CryptocurrencyID` = 1 AND
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` >= NOW() - INTERVAL 15 DAY AND
												`Transaction_Event`.`Date` < NOW() - INTERVAL 8 DAY
										) *
										(
											SELECT
												`BTC_Rate`.`USD_Rate`
											FROM
												`BTC_Rate`
											WHERE
												`BTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 WEEK)
										) +
										(
											SELECT
												SUM(
													`Transaction`.`Value` *
													IF(
														Vendor.`Commission` > 0,
														Vendor.`Commission`/1000,
														" . MARKETPLACE_FEE . "
													)
												)
											FROM
												`Transaction_Event`
											INNER JOIN
												`Transaction` ON
													`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
											INNER JOIN
												`PaymentMethod` ON
													`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
											INNER JOIN
												`User` Vendor ON
													`PaymentMethod`.`UserID` = Vendor.`ID`
											WHERE
												`PaymentMethod`.`CryptocurrencyID` = 7 AND
												`Transaction_Event`.`Event` = 'paid' AND
												`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
												`Transaction_Event`.`Date` >= NOW() - INTERVAL 15 DAY AND
												`Transaction_Event`.`Date` < NOW() - INTERVAL 8 DAY
										) *
										(
											SELECT
												`LTC_Rate`.`USD_Rate`
											FROM
												`LTC_Rate`
											WHERE
												`LTC_Rate`.`Date` = DATE(NOW() - INTERVAL 1 WEEK)
										)
									),
									'&nearr;',
									'&searr;'
								)
							)
					"
				]
			);
		}
		
		$tabularData = array_merge(
			$tabularData,
			[
				/* "[Table Title]" => "
					SELECT
						`Table`.`Column` 'Column Title',
						...
					FROM
						...
				", */
				"Users Active Within Last " . USER_ONLINE_LAST_SEEN_MINUTES . " Minutes" => "
					SELECT
						`User`.`Alias` 'Username',
						CEIL(
							time_to_sec(
								timediff(
									NOW(),
									`User`.`LastSeen`
								)
							) / 60
						) 'Minutes Since Last Seen',
						`User`.`LastSeen_URL` 'Page Accessed'
					FROM
						`User`
					WHERE
						`User`.`LastSeen` > NOW() - INTERVAL " . USER_ONLINE_LAST_SEEN_MINUTES . " MINUTE
				",
				/* "Weekly Top 20 Buyers" => "
					SELECT
						`User`.`Alias`,
						FORMAT(
							SUM(`Transaction`.`Value`) *
							(
								 SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE   
									`Currency`.`ID` = '" . CURRENCY_ID_USD . "'
							) /
							(
								SELECT
									`1EUR`
								FROM
									`Currency`
								WHERE   
									`Currency`.`ID` = '" . CURRENCY_ID_BTC . "'
							),
							0
						) 'USD'
	 				FROM
	 					`Transaction`
					INNER JOIN
						`User`
					ON
						`Transaction`.`BuyerID`=`User`.`ID`
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID`=`Transaction_Event`.`TransactionID`
					WHERE
						`Transaction_Event`.`Event`='accepted' AND 
						`Transaction_Event`.`Date` > NOW() - INTERVAL 1 WEEK
					GROUP BY
						`User`.`Alias`
					ORDER BY
						SUM(`Transaction`.`Value`) DESC
					LIMIT 20
					",
				"Transactions Currently In Transit" => "
					 SELECT
						`Transaction`.`ID` 'TX ID',
						Vendor.`Alias` 'Vendor',
						Buyer.`Alias` 'Buyer',
						`Listing`.`Name` 'Listing',
						`Transaction`.`Value` 'BTC In Escrow'
					FROM
						`Transaction`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					WHERE
						`Transaction`.`Status` = 'in transit'
					ORDER BY
						`Transaction`.`ID`
					SELECT
		                                Vendor.`Alias` 'Vendor',
		                                SUM(`Transaction`.`Value`) AS 'Value USD'
		                        FROM
		                                `Transaction`
		                        INNER JOIN
		                                `Listing` ON
		                                        `Transaction`.`ListingID` = `Listing`.`ID`
		                        INNER JOIN
		                                `User` Vendor ON
		                                        `Listing`.`VendorID` = Vendor.`ID`
		                        WHERE
		                                `Transaction`.`Status` = 'in transit'
		                        GROUP BY
		                                Vendor.`Alias`
					ORDER BY 
						SUM(`Transaction`.`Value`) DESC
				", */
				"Vendor Invites" => "
					SELECT DISTINCT
						`User`.`Alias` AS 'Vendor',
						(
							SELECT
								COUNT(*)
							FROM
								`InviteCode`
							WHERE
								`UserID` = `User`.`ID` AND
								`ClaimedID` IS NOT NULL
						) AS 'Number of Claimed Invites',
						(
							SELECT
								COUNT(*)
							FROM
								`InviteCode`
							WHERE
								`UserID` = `User`.`ID` AND
								`ClaimedID` IS NULL
						) AS 'Number of Un-claimed Invites'
					FROM
						`User`
					WHERE
						`User`.`Vendor` = TRUE
					AND	`User`.`Stealth` = FALSE
					AND	DATEDIFF(CurDate(), DATE(`User`.`LastSeen`)) < 30
					ORDER BY `Vendor`
					"
				]
				);
		
		array_walk(
			$aggregateData,
			function(&$value, $key){
				$value = '( ' . $value . ' ) "' . $key . '"';
			}
		);
		$aggregateData_SELECT = implode(
			', ',
			$aggregateData
		);
		try{
			$aggregateData_results = $this->db->qSelect(
				"
					SELECT
						" . $aggregateData_SELECT . "
				"
			)[0];
		} catch (Exception $e){
			die('Invalid DB Statements (Aggregate Data): <br><br>' . $this->db->error);
		}
		
		if ($confirmedWalletBalance = ElectrumDaemon::getWalletBalance($unconfirmedWalletBalance))
			$aggregateData_results['Fee Wallet'] = ENTITY_BITCOIN_SYMBOL . ' ' . $confirmedWalletBalance . ($unconfirmedWalletBalance ? ' (+ ' . $unconfirmedWalletBalance . ')' : false);
		
		foreach($tabularData as $datum => $query){
			try{
				$tabularData_results[$datum] = $this->db->qSelect($query);
			} catch (Exception $e){
				die('Invalid DB Statements (Tabular Data): "' . $datum . '": <br><br>' . $this->db->error);
			}
		}
		
		//$aggregateData_results['Users Online'] = count($tabularData_results['Users Active Within Last 15 Minutes']);
		
		return array(
			$aggregateData_results,
			$tabularData_results
		);
	}
	
	private function _fetchSalesByDay(){
		if (
			$salesByDay = $this->db->qSelect(
				"
					SELECT
						WEEK(`Transaction_Event`.`Date`, 3) Week,
						WEEKDAY(`Transaction_Event`.`Date`) weekday,
						(SUM(`Transaction`.`Value` * `BTC_Rate`.`USD_Rate` * (`PaymentMethod`.`CryptocurrencyID` = 1)) +
						SUM(`Transaction`.`Value` * `LTC_Rate`.`USD_Rate` * (`PaymentMethod`.`CryptocurrencyID` = 7))) Sales 
					FROM
						Transaction 
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
					INNER JOIN
						`BTC_Rate` ON
							`Transaction_Event`.`Date` = `BTC_Rate`.`Date`
					INNER JOIN
						`LTC_Rate` ON
							`Transaction_Event`.`Date` = `LTC_Rate`.`Date`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID` 
					INNER JOIN 
						`User` ON
							`Listing`.`VendorID` = `User`.`ID` 
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`Transaction_Event`.`Event` = 'Accepted' AND
						`Transaction_Event`.`Date` > CURDATE() - INTERVAL 51 WEEK - INTERVAL DAYOFWEEK(NOW()) - 1 DAY
					GROUP BY
						`Transaction_Event`.`Date`
					ORDER BY
						`Transaction_Event`.`Date`
				"
			)
		){
			$weekdays = $weeks = [];
			foreach ($salesByDay as $i => $day){
				if ($i == 0)
					$firstWeek = $day['Week'];
				
				$weeks[$day['Week']] = $day['Week'];
				
				$weekdays[$day['weekday']]['w ' . $day['Week']] = (float) $day['Sales'] / 1e3;
			}
			
			foreach ($weekdays as $weekday => $weekdayweeks){
				foreach ($weeks as $week){
					if (
						!array_key_exists(
							'w ' . $week,
							$weekdayweeks
						)
					)
						$weekdays[$weekday]['w ' . $week] = 0;
				}
				uksort(
					$weekdays[$weekday],
					function($a, $b) use ($firstWeek){
						if (
							substr(
								$a,
								2
							) >= $firstWeek &&
							substr(
								$b,
								2
							) < $firstWeek
						)
							$return = -1;
						elseif (
							substr(
								$b,
								2
							) >= $firstWeek &&
							substr(
								$a,
								2
							) < $firstWeek
						)
							$return = 1;
						else
							$return = substr(
								$a,
								2
							) - substr(
								$b,
								2
							);
							
						return $return;
					}
				);
			}
			
			krsort($weekdays);
			
			return $weekdays;
		}
		
		return false;
	}
	
	private function _fetchSalesByWeek(
		$weeks = 52,
		$absolute = false
	){
		$salesByWeek_indexed = [];
		if(
			$salesByWeek = $this->db->qSelect(
				"
					SELECT
						" . (
							$absolute
								? "YEARWEEK(`Transaction_Event`.`Date`)"
								: "WEEK(`Transaction_Event`.`Date`)"
						) . " Week,
						(SUM(`Transaction`.`Value` * `BTC_Rate`.`USD_Rate` * (`PaymentMethod`.`CryptocurrencyID` = 1)) +
						SUM(`Transaction`.`Value` * `LTC_Rate`.`USD_Rate` * (`PaymentMethod`.`CryptocurrencyID` = 7))) Sales 
					FROM
						Transaction 
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
					INNER JOIN
						`BTC_Rate` ON
							`Transaction_Event`.`Date` = `BTC_Rate`.`Date`
					INNER JOIN
						`LTC_Rate` ON
							`Transaction_Event`.`Date` = `LTC_Rate`.`Date`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID` 
					INNER JOIN 
						`User` ON
							`Listing`.`VendorID` = `User`.`ID` 
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`Transaction_Event`.`Event` = 'Accepted' AND
						`Transaction_Event`.`Date` > CURDATE() - INTERVAL ? WEEK
					GROUP BY
						Week 
					ORDER BY
						`Transaction_Event`.`Date`
				",
				'i',
				[$weeks]
			)
		){
			foreach($salesByWeek as $week)
				$salesByWeek_indexed['w ' . $week['Week']] = (float) $week['Sales'];
			
			return $salesByWeek_indexed;
		}
		
		return false;
	}
	
	private function _fetchRevenues($weeks){
		$revenues_indexed = [];
		if(
			$revenues = $this->db->qSelect(
				"
					SELECT
						DATE_FORMAT(
							CURDATE() -
							INTERVAL DATEDIFF(
								CURDATE(),
								`BTC_Rate`.`Date`
							) WEEK,
							'%U'
						) Week,
						(
							SELECT
								SUM(
									`Transaction`.`Value` *
									IF(
										Vendor.`Commission` > 0,
										Vendor.`Commission`/1000,
										" . MARKETPLACE_FEE . "
									)
								)
							FROM
								`Transaction_Event`
							INNER JOIN
								`Transaction` ON
									`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
							INNER JOIN
								`PaymentMethod` ON
									`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
							INNER JOIN
								`User` Vendor ON
									`PaymentMethod`.`UserID` = Vendor.`ID`
							WHERE
								`PaymentMethod`.`CryptocurrencyID` = 1 AND
								`Transaction_Event`.`Event` = 'paid' AND
								`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
								`Transaction_Event`.`Date` >= CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								)+1 WEEK AND
								`Transaction_Event`.`Date` < CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								) WEEK
						) *
						(
							SELECT
								BR2.`USD_Rate`
							FROM
								`BTC_Rate` BR2
							WHERE
								BR2.`Date` = CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								) WEEK
						) +
						(
							SELECT
								SUM(
									`Transaction`.`Value` *
									IF(
										Vendor.`Commission` > 0,
										Vendor.`Commission`/1000,
										" . MARKETPLACE_FEE . "
									)
								)
							FROM
								`Transaction_Event`
							INNER JOIN
								`Transaction` ON
									`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
							INNER JOIN
								`PaymentMethod` ON
									`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
							INNER JOIN
								`User` Vendor ON
									`PaymentMethod`.`UserID` = Vendor.`ID`
							WHERE
								`PaymentMethod`.`CryptocurrencyID` = 7 AND
								`Transaction_Event`.`Event` = 'paid' AND
								`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
								`Transaction_Event`.`Date` >= CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								)+1 WEEK AND
								`Transaction_Event`.`Date` < CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								) WEEK
						) *
						(
							SELECT
								`LTC_Rate`.`USD_Rate`
							FROM
								`LTC_Rate`
							WHERE
								`LTC_Rate`.`Date` = CURDATE() -
								INTERVAL DATEDIFF(
									CURDATE(),
									`BTC_Rate`.`Date`
								) WEEK
						) Revenues
					FROM
						`BTC_Rate`
					WHERE
						`BTC_Rate`.`Date` > CURDATE() - INTERVAL ? DAY
					ORDER BY
						`BTC_Rate`.`Date` ASC
				",
				'i',
				[$weeks]
			)
		){
			foreach($revenues as $day){
				$revenues_indexed[ $day['Week'] ] = (float) $day['Revenues'];
			}
			
			return $revenues_indexed;
		}
		
		return false;
	}
	
	public function renderAllSalesGraph(){
		$graph = new Graph(550,350);
		
		$salesByWeek = $this->_fetchSalesByWeek(420, true);
		
		$graph->addData($salesByWeek);
		$graph->setTitle("All Sales");
		$graph->setBars(false);
		$graph->setLine(true);
		$graph->setXValues(false);
		
		if (
			$this->User->Alias !== 'Finn' &&
			$this->User->Alias !== 'TestAdmin'
		)
			$graph->setYValues(false);
		
		$graph->createGraph();
	}
	
	public function renderStackedGraph(){
		$graph = new GraphStacked(550,350);
		
		$salesByDay = $this->_fetchSalesByDay();
		
		call_user_func_array(
			[
				$graph,
				'addData'
			],
			$salesByDay
		);
		
		$graph->setTitle(
			"Weekly Sales" .
			(
				$this->User->Alias == 'Finn' ||
				$this->User->Alias == 'TestAdmin'
					? ' (thousands of USD)'
					: false
			)
		);
		//$graph->setGradient("lime", "green");
		$graph->setBarOutlineColor("black");
		$graph->setBarColor(
			'#52987E',
			'#F79800',
			'#D332D7',
			'#1EBCBC',
			'#D73272',
			'#E1DF41',
			'#4578F5'
		);
		
		$graph->setTitleLocation('left');
		
		$graph->setLegend(TRUE);
		$graph->setLegendTitle(
			'Sun',
			'Sat',
			'Fri',
			'Thu',
			'Wed',
			'Tue',
			'Mon'
		);
		
		if (
			$this->User->Alias !== 'Finn' &&
			$this->User->Alias !== 'TestAdmin'
		)
			$graph->setYValues(false);
		
		$graph->createGraph();
	}
	
	public function renderGraph(){
		$graph = new Graph(550,350);
		
		$salesByWeek = $this->_fetchSalesByWeek();
		
		$graph->addData($salesByWeek);
		$graph->setTitle("Weekly Sales");
		$graph->setGradient("lime", "green");
		$graph->setBarOutlineColor("black");
		
		if (
			$this->User->Alias !== 'Finn' &&
			$this->User->Alias !== 'TestAdmin'
		)
			$graph->setYValues(false);
		
		$graph->createGraph();
	}
	
	private function _fetchUsersOnline($weeks){
		$peakUsers = $totalUsers = [];
		$usersOnline = $this->db->qSelect(
			"
				SELECT
					`Date`,
					`Total`,
					`Peak`
				FROM
					`UsersOnline`
				WHERE
					`Date` > NOW() - INTERVAL ? WEEK AND
					`Date` < DATE(NOW())
			",
			'i',
			[$weeks]
		);
		
		foreach ($usersOnline as $row){
			$totalUsers[$row['Date']] = $row['Total'];
			$peakUsers[$row['Date']] = $row['Peak'];
		}
		
		return	[
				$totalUsers,
				$peakUsers
			];
	}
	
	public function renderUsersOnlineGraph($weeks = 13){
		$graph = new Graph(550,350); // dimensions should probably be defined constants
		
		list(
			$totalUsers,
			$peakUsers
		) = $this->_fetchUsersOnline($weeks);
		
		$mean = array_sum($totalUsers) / count($totalUsers);
		
		$graph->addData($totalUsers); //, $peakUsers);
		
		$graph->setTitle('Users Online');
		$graph->setBars(false);
		$graph->setLine(true);
		$graph->setDataPoints(true);
		$graph->setDataPointColor('maroon');
		$graph->setLineColor("navy", "maroon");
		$graph->setDataValues(false);
		$graph->setGoalLine($mean, 'red');
		$graph->setLegendTitle('Total', 'Peak');
		$graph->createGraph();
	}
	
	public function renderRevenuesGraph($weeks = 13){
		return true;
		
		$graph = new Graph(550,350); // dimensions should probably be defined constants
		
		$revenues = $this->_fetchRevenues($weeks);
		$mean = array_sum($revenues) / count($revenues);
		
		$graph->addData($revenues);
		$graph->setTitle('Rolling Revenues');
		$graph->setBars(false);
		$graph->setLine(true);
		$graph->setDataPoints(true);
		$graph->setDataPointColor('maroon');
		$graph->setDataValues(false);
		$graph->setDataValueColor('maroon');
		$graph->setGoalLine($mean, 'red');
		$graph->createGraph();
	}
	
	public function fetchCommentReports(){
		return $this->db->qSelect(
			"
				SELECT
					`Discussion_Comment`.`ID` ID,
					Poster.`Alias` alias,
					`Discussion_Comment`.`Content` content,
					COUNT(DISTINCT `Discussion_Comment_Report`.`ID`) reportCount
				FROM
					`Discussion_Comment`
				INNER JOIN	`Discussion_Comment_Report`
					ON `Discussion_Comment`.`ID` = `Discussion_Comment_Report`.`CommentID`
				INNER JOIN	`User` Poster
					ON	`Discussion_Comment`.`PosterID` = Poster.`ID`
			"
		);
	}
	
	public function fetchUserReports(){
		return $this->db->qSelect(
			"
				SELECT
					`User`.`Alias` alias,
					COUNT(DISTINCT `User_Report`.`ID`) reportCount
				FROM
					`User`
				INNER JOIN `User_Report`
					ON `User`.`ID` = `User_Report`.`ReportedID`
			"
		);
	}
	
	public function banUser($userID){
		return $this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`Banned` = TRUE
				WHERE
					`ID` = ?
			",
			'i',
			array($userID)
		);
	}
	
	public function toggleUserBanned($userAlias){
		return $this->db->qQuery(
			"
				UPDATE
					`User`
				SET
					`Banned` = (`Banned` = FALSE)
				WHERE
					`Alias` = ?
			",
			's',
			[$userAlias]
		);
	}
	
	public function fetchVendorApplications(){
		$applications = $this->db->qSelect(
			"
				SELECT
					Vendor.`ID` userID,
					Vendor.`Alias` alias,
					`VendorApplication`.`Application` application,
					`VendorApplication`.`ApplicationAttempts` applicationAttempts,
					`VendorApplication`.`Policy` policy,
					(
						SELECT	COUNT(*)
						FROM	`InviteCode`
						WHERE
							`Type` = 'buyer'
						AND	`ClaimedID` = Vendor.`ID`
					) buyerEndorsements,
					(
						SELECT	COUNT(*)
						FROM	`InviteCode`
						WHERE
							`Type` = 'vendor'
						AND	`ClaimedID` = Vendor.`ID`
					) vendorEndorsements
				FROM
					`VendorApplication`
				INNER JOIN	`User` Vendor
					ON	`VendorApplication`.`UserID` = Vendor.`ID`
				WHERE
					Vendor.`Vendor` = FALSE
				AND	`VendorApplication`.`Blacklist` = FALSE
				AND	`VendorApplication`.`Reviewed` = FALSE
			"
		);
		
		return $applications;
	}
	
	public function respondApplication($userID){
		switch($_POST['action']){
			case 'approve':
				$this->makeVendor($userID);
			break;
			case 'reject':
				$this->rejectApplication($userID);
			break;
			case 'blacklist':
				$this->blacklistApplication($userID);
			break;
		}
	}
	
	private function makeVendor($userID){
		$getApplication = $this->db->qSelect(
			"
				SELECT
					`Policy`
				FROM
					`VendorApplication`
				WHERE
					`UserID` = ?
			",
			'i',
			array($userID)
		);
		$policy = $getApplication[0]['Policy'];
		
		$policyHTML = NXS::formatText($policy);
		
		$this->db->qQuery(
			"
				INSERT INTO
					`User_Section` (`VendorID`, `Type`, `Name`, `Content`, `HTML`)
				VALUES
					(?, 'policy', 'Refund Policy', ?, ?)
			",
			'iss',
			[
				$userID,
				$policy,
				$policyHTML
			]
		);
		
		$this->db->qQuery(
			"
				INSERT IGNORE INTO
					`User_Class` (`UserID`, `ClassID`)
				VALUES
					(?, 7)
			",
			'i',
			[
				$userID
			]
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
			[$userID]
		);
		$this->User->allocateUserDomains(
			PRIVATE_DOMAINS_STATE_RECENTLY_GRANTED,
			$userID
		);
		
		// Notify Vendor
		$this->User->sendMessage(
			VENDOR_APPLICATION_APPROVED_MESSAGE_UNFORMATTED,
			$userID
		);
	}
	
	private function acknowledgeApplication($userID){
		$this->db->qQuery(
			"
				UPDATE
					`VendorApplication`
				SET
					`Reviewed` = TRUE,
					`ApplicationAttempts` = `ApplicationAttempts` + 1
				WHERE
					`UserID` = ?
			",
			'i',
			array($userID)
		);
	}
	
	private function rejectApplication($userID){
		$this->acknowledgeApplication($userID);
		$this->User->sendMessage(
			VENDOR_APPLICATION_UNSUCCESSFUL_UNFORMATTED,
			$userID
		);
	}
	
	private function blacklistApplication($userID){
		$this->db->qQuery(
			"
				UPDATE
					`VendorApplication`
				SET
					`Reviewed` = TRUE,
					`ApplicationAttempts` = `ApplicationAttempts` + 1,
					`Blacklist` = TRUE
				WHERE
					`UserID` = ?
			",
			'i',
			array($userID)
		);
		
		$this->User->sendMessage(
			VENDOR_APPLICATION_UNSUCCESSFUL_UNFORMATTED,
			$userID
		);
	}
	
	public function fetchUnclaimedInvites(){
		
		$invites = $this->db->qSelect(
			"
				SELECT
					`Code`,
					`Type`,
					`Issued`,
					`Comment`
				FROM
					`InviteCode`
				WHERE
					`ClaimedID` IS NULL
				AND
					`Issued`=0
			"
		);
		
		foreach($invites as $invite){
			$invitecodes[ $invite['Type'] ][] = $invite['Code'];
		}
		
		return $invitecodes;
		
	}
	
	public function distributeInviteCodes($quantity){
		$inviteCodesGenerated = 0;
		
		$users = $this->db->qSelect(
			"
				SELECT
					`ID`
				FROM
					`User`
			"
		);
		
		foreach($users as $user){
			for($i = 0; $i < $quantity; $i++){
				$code = $this->generateRandomString(10, TRUE);
				
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
							$user['ID']
						)
					)
				)
					$inviteCodesGenerated++;
			}
		}
		
		return $inviteCodesGenerated;
	}
	
	public function generateInviteCodes(){
		$quantity = $_POST['quantity'];
		
		for( $i = 0; $i < $quantity; $i++ ){
			$code = $this->generateRandomString(10, TRUE);
			
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`InviteCode` (`Code`, `Type`)
					VALUES
						(?, ?)
				",
				'ss',
				array($code, $_POST['type'])
			);
			
		}
		
		return true;
		
	}
	
	public function fetchUnapprovedListings(){
		
		if( $stmt_getUnapproved = $this->db->prepare("
			SELECT
				`Listing`.`ID`,
				`ListingCategory`.`Name`,
				`User`.`ID`,
				`User`.`Alias`,
				`Listing`.`Image`,
				`Listing`.`Name`,
				`Listing`.`Description`
			FROM
				`Listing`
			INNER JOIN
				`ListingCategory` ON `Listing`.`CategoryID` = `ListingCategory`.`ID`
			INNER JOIN
				`User`	ON `Listing`.`VendorID` = `User`.`ID`
			WHERE
				`Listing`.`Approved` = FALSE
			LIMIT 5
		") ){
			
			$stmt_getUnapproved->execute();
			$stmt_getUnapproved->store_result();
			if( $stmt_getUnapproved->num_rows > 0 ){
				
				$stmt_getUnapproved->bind_result(
					$listing_id,
					$listing_category,
					$listing_vendor_id,
					$listing_vendor_alias,
					$listing_image,
					$listing_title,
					$listing_description
				);
				
				$listings = array();
				while( $stmt_getUnapproved->fetch() ){
					$listings[] = array(
						'id' => $listing_id,
						'category' => $listing_category,
						'vendor' => $listing_vendor_alias,
						'vendor_id' => $listing_vendor_id,
						'image' => str_replace('%URL%', URL, $listing_image),
						'title' => $listing_title,
						'description' => strip_tags($listing_description)
					);
				}
				
				return $listings;
				
			} else {
				return false;
			}
			
		}
		
	}
	
	public function fetchDisputedTransactions(){
		
		if ( $stmt_getDisputed = $this->db->prepare("
			SELECT
				`Transaction`.`ID`,
				`Transaction`.`Timeout`,
				`Transaction`.`Value`,
				`Transaction`.`MediatorID`,
				`Listing`.`ID`,
				`Listing`.`Name`,
				`ListingCategory`.`Name`,
				Vendor.`ID`,
				Vendor.`Alias`,
				Vendor.`Reputation`,
				Buyer.`ID`,
				Buyer.`Alias`,
				Buyer.`Reputation`
			FROM
				`Transaction`
			INNER JOIN `Listing`
				ON `Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN `ListingCategory`
				ON `Listing`.`CategoryID` = `ListingCategory`.`ID`
			INNER JOIN `User` Vendor
				ON `Listing`.`VendorID` = Vendor.`ID`
			INNER JOIN `User` Buyer
				ON `Transaction`.`BuyerID` = Buyer.`ID`
			WHERE
				`Transaction`.`Status` = 'in dispute' 
			AND	`Transaction`.`Timeout` < NOW()
			AND	(`Transaction`.`MediatorID` IS NULL OR `Transaction`.`MediatorID` = ?)
			ORDER BY
				`Transaction`.`Timeout` ASC
		") ){
			
			$stmt_getDisputed->bind_param('i', $this->User->ID);
			$stmt_getDisputed->execute();
			$stmt_getDisputed->store_result();
			
			if( $stmt_getDisputed->num_rows > 0 ){
				
				$stmt_getDisputed->bind_result($transaction_id, $transaction_timeout, $transaction_value, $mediator_id, $listing_id, $listing_name, $listing_category, $vendor_id, $vendor_alias, $vendor_reputation, $buyer_id, $buyer_alias, $buyer_reputation);
				
				$disputed_transactions = array();
				while( $stmt_getDisputed->fetch() ){
					$disputed_transactions[] = array(
						'id' => $transaction_id,
						'timeout' => $transaction_timeout,
						'value' => $transaction_value,
						'is_mediator' => $mediator_id == $this->User->ID,
						'listing_id' => $listing_id,
						'listing_name' => $listing_name,
						'listing_category' => $listing_category,
						'vendor_id' => $vendor_id,
						'vendor_alias' => $vendor_alias,
						'vendor_reputation' => $vendor_reputation,
						'buyer_id' => $buyer_id,
						'buyer_alias' => $buyer_alias,
						'buyer_reputation' => $buyer_reputation
					);
				}
				
				return $disputed_transactions;
				
			} else {
				
				return false;
				
			}
			
		}
		
	}
	
	public function startMediation($transaction_id){
		
		if( $stmt_becomeMediator = $this->db->prepare("
			UPDATE
				`Transaction`
			SET
				`MediatorID` = ?
			WHERE
				`ID` = ?
			AND	`Status` = 'in dispute'
		") ){
			
			$stmt_becomeMediator->bind_param('ii', $this->User->ID, $transaction_id);
			
			if( $stmt_becomeMediator->execute() ){
				
				return true;
				
			} else {
				
				return false;
				
			}
			
		}
		
	}
	
	public function notify_user(){
		
		$auto_delete = "NOW() + INTERVAL 30 DAY";
		
		if( $stmt_findRecipient = $this->db->prepare("
			SELECT
				`ID`,
				`PublicKey`,
				`EncryptPGP`,
				`PGP`
			FROM
				`User`
			WHERE
				`ID` = ?
			LIMIT 1
		") ){
			$stmt_findRecipient->bind_param('i', $_POST['user_id']);
			$stmt_findRecipient->execute();
			$stmt_findRecipient->store_result();
			if($stmt_findRecipient->num_rows > 0) {
				$stmt_findRecipient->bind_result($recipient_id, $recipient_pub, $recipient_encrypt_pgp, $recipient_pgp);
				$stmt_findRecipient->fetch();
			} else {
				die('User doesn\'t exist');
			}
		}
		
		if($recipient_encrypt_pgp == 1 && !empty($recipient_pgp) ){
			$pgp = new PGP($recipient_pgp);
			
			$content = '[pgp]' . $pgp->qEncrypt($_POST['message'], true) . '[/pgp]';
		} else {
			
			$content = $_POST['message'];
			
		}
		
		$content = NXS::formatText($content);
		
		$rsa = new RSA();
		
		$content = $rsa->qEncrypt(
			json_encode(
				array(
					'Date' => date('j F Y'),
					'Message' => $content
				)
			),
			$recipient_pub
		);
	
		if($stmt_sendMessage = $this->db->prepare("
			INSERT
				`Message` (`SenderID`, `RecipientID`, `Content`, `AutoDelete`)
			VALUES
				(?, ?, ?, ".$auto_delete.")
		") ){
			$stmt_sendMessage->bind_param('iis', $this->User->ID, $recipient_id, $content);
			if($stmt_sendMessage->execute()){
				return true;
			}
		}
		
	}
	
	public function deleteDiscussion($discussion_id){
		
		if( $stmt_deleteDiscussion = $this->db->prepare("
			DELETE FROM
				`Discussion`
			WHERE
				`ID` = ?
		") ){
			
			$stmt_deleteDiscussion->bind_param('i', $discussion_id);
			
			if( $stmt_deleteDiscussion->execute() ){
				return true;
			}
			
		}
		
	}
	
	public function sinkDiscussion($discussion_id){
		
		if( $stmt_sinkDiscussion = $this->db->prepare("
			UPDATE
				`Discussion`
			SET
				`Sink` = IF(`Sink` = TRUE, FALSE, TRUE)
			WHERE
				`ID` = ?
		") ){
			
			$stmt_sinkDiscussion->bind_param('i', $discussion_id);
			
			if( $stmt_sinkDiscussion->execute() ){
				return true;
			}
			
		}
		
	}
	
	public function closeDiscussion($discussion_id){
		
		if( $stmt_closeDiscussion = $this->db->prepare("
			UPDATE
				`Discussion`
			SET
				`Closed` = IF(`Closed` = TRUE, FALSE, TRUE)
			WHERE
				`ID` = ?
		") ){
			
			$stmt_closeDiscussion->bind_param('i', $discussion_id);
			
			if( $stmt_closeDiscussion->execute() ){
				return true;
			}
			
		}
		
	}
	
	public function announceDiscussion($discussion_id){
		
		if( $stmt_announceDiscussion = $this->db->prepare("
			UPDATE
				`Discussion`
			SET
				`Status` = 'notice'
			WHERE
				`ID` = ?
		") ){
			
			$stmt_announceDiscussion->bind_param('i', $discussion_id);
			
			if( $stmt_announceDiscussion->execute() ){
				return true;
			}
			
		}
		
	}
	
	public function editForumComment($comment_id){
		
		$content = $_POST['content'];
		
		if( $stmt_updateComment = $this->db->prepare("
			UPDATE
				`Discussion_Comment`
			SET
				`Content` = ?,
				`HTML` = ?
			WHERE
				`ID` = ?
		") ) {
			
			$html = NXS::formatText($content, $this->db);
			
			$stmt_updateComment->bind_param('ssi', $content, $html, $comment_id);
			
			if( $stmt_updateComment->execute() ){
				
				return true;
				
			}
			
		}
		
	}
	
	public function deleteComment($comment_id){
		
		$stmt_getComment = $this->db->prepare("
			SELECT
				`DiscussionID`
			FROM
				`Discussion_Comment`
			WHERE
				`ID` = ?
		");
		
		$stmt_deleteComment = $this->db->prepare("
			DELETE FROM
				`Discussion_Comment`
			WHERE
				`ID` = ?
		");
		
		if( $stmt_getComment !== false && $stmt_deleteComment !== false ){
			
			$stmt_getComment->bind_param('i', $comment_id);
			$stmt_getComment->execute();
			$stmt_getComment->store_result();
			
			if( $stmt_getComment->num_rows == 1 ){
				
				$stmt_getComment->bind_result($discussion_id);
				$stmt_getComment->fetch();
				
				$stmt_deleteComment->bind_param('i', $comment_id);
				
				if( $stmt_deleteComment->execute() ){
					return $discussion_id;
				}
				
			}
			
		}
		
		return false;
		
	}
	
	private function formatText($input){
		
		// PGP Blocks
		if( preg_match_all('/\[pgp\]((?:(?!\[\/pgp).)*)\[\/pgp]/is', $input, $comment_pgps) > 0 ){
			
			$comment_pgp_blocks = array();
			
			foreach( $comment_pgps[1] as $comment_pgp ){
				
				$comment_pgp_blocks[] = '</p><pre>' . $comment_pgp . '</pre><p>';
				
			}
			
			$input = preg_replace('/\[pgp\](?:(?!\[\/pgp).)*\[\/pgp]/is', '%%%PGPBLOCK%%%', $input);
			
		}
		
		// Misc Element
		$input = preg_replace('/\[b\]((?:(?!\[\/b).)*)\[\/b]/is', '<strong>$1</strong>', $input);
		$input = preg_replace('/\[i\]((?:(?!\[\/i).)*)\[\/i]/is', '<em>$1</em>', $input);
		
		$link_count = 1;	
		$input = preg_replace_callback(
			'/\[a=?([^\]]+)?\]((?:(?!\[\/i).)*)\[\/a]/i',
			function($matches) use (&$link_count){
				
				if( substr($matches[1], 0, strlen(URL)) == URL ){
					return '<a href="' . $matches[1] . '">' . $matches[2] . '</a>';
				} else {
					$link_id = $link_count++;
					return '<a href="#anchor-' . $link_id . '">' . $matches[2] . '</a>
							<div class="modal" id="anchor-' . $link_id . '">
								<a href="#close"></a>
								<div class="rows-10">
									<a class="close" href="#close">×</a>
									<p class="row">This link points to an external webpage: <em>' . $matches[1] . '</em>.<br>Are you sure wish to continue?</p>
									<div class="row cols-10">
										<div class="col-6"><a target="_blank" href="' . $matches[1] . '" class="btn wide color">Continue</a></div>
										<div class="col-6"><a href="#close" class="btn wide red color">Nevermind</a></div>
									</div>
								</div>
							</div>';
				}
				
			},
			$input
		); // [a] to <a>
		
		// UL LIsts
		if( preg_match_all('/(?:\n?^-+[^\n]+)+/m', $input, $comment_uls) > 0 ){
			
			$comment_ul_lists = array();
			
			$input = preg_replace_callback(
				'/(?:\n?^-+[^\n]+)+/m',
				function($matches) use (&$comment_ul_lists){
				
					$list = preg_replace(
						'/^-\s?([^\n]+)/m',
						'<li>$1</li>',
						$matches[0]
					);
					$comment_ul_lists[] = '</p><ul>' . $list . '</ul><p>';
					
					return '%%%UL-LIST%%%';
					
					
				},
				$input
			);
			
		}
		
		// OL Lists
		if( preg_match_all('/(?:\n?^\d+[^\n]+)+/m', $input, $comment_ols) > 0 ){
			
			$comment_ol_lists = array();
			
			$input = preg_replace_callback(
				'/(?:\n?^\d+[^\n]+)+/m',
				function($matches) use (&$comment_ol_lists){
				
					$list = preg_replace(
						'/^\d+\.?\s?([^\n]+)/m',
						'<li>$1</li>',
						$matches[0]
					);
					$comment_ol_lists[] = '</p><ol>' . $list . '</ol><p>';
					
					return '%%%OL-LIST%%%';
					
					
				},
				$input
			);
			
		}
		
		$input = $this->nl2p($input);
		
		// Adding Quotes
		if( preg_match_all("/\[quote=['\"]?(\d+)['\"]?/i", $input, $comment_quotes) > 0){
			
			if( $stmt_getQuotedComment = $this->db->prepare("
				SELECT
					`User`.`Alias`,
					`User`.`Reputation`
				FROM
					`Discussion_Comment`
				INNER JOIN `User`
					ON	`Discussion_Comment`.`PosterID` = `User`.`ID`
				WHERE
					`Discussion_Comment`.`ID` = ?
			") ){
			
				$quoted_commenter = array();
				
				foreach( $comment_quotes[1] as $quoted_comment_id ){
					
					$stmt_getQuotedComment->bind_param('i', $quoted_comment_id);
					$stmt_getQuotedComment->execute();
					$stmt_getQuotedComment->store_result();
					$stmt_getQuotedComment->bind_result($quoted_commenter_alias, $quoted_commenter_reputation);
					$stmt_getQuotedComment->fetch();
					
					if( $stmt_getQuotedComment->num_rows == 1 ){
					
						$quoted_commenter[$quoted_comment_id] = array(
							'alias' => $quoted_commenter_alias,
							'reputation' => $quoted_commenter_reputation
						);
						
					} else {
						
						$quoted_commenter[$quoted_comment_id] = false;
						
					}
				}
					
				$input = preg_replace_callback(
					"/\[quote=['\"]?(\d+)['\"]? date=['\"]?(\d{4}-(?:1[0-2]|0?[1-9])-(?:[1-3][0-9]|0?[1-9]))['\"]?\](.(?:(?!\[\/quote).)*)\[\/quote\]/is",
					function($matches) use ($quoted_commenter){
						if( $quoted_commenter[ $matches[1] ] ){
							return "</p><div class='quote'><a href='" . URL . 'forum/comment/' . $matches[1] . '/' . "' class='btn'>View</a><p class='quoted'>Posted on <strong>" . date('j F Y', strtotime($matches[2])) . "</strong> by <a href='" . URL . 'usr/' . strtolower($quoted_commenter[ (int) $matches[1] ]['alias']) . '/' . "'>" . $quoted_commenter[ $matches[1] ]['alias'] . " [" . $quoted_commenter[ (int) $matches[1] ]['reputation'] . "]</a> :</p>" . $this->nl2p($matches[3]) . "</div><p>";
						} else {
							return "</p><div class='quote'><p><strong>DELETED COMMENT</strong></p></div><p>";
						}
					}, $input
				);
					
			}
			
		}
		
		if( preg_match_all("/@(\w+)/i", $input, $comment_replies) > 0 ){
			
			if( $stmt_getRepliedCommenter = $this->db->prepare("
				SELECT
					`Alias`,
					`Reputation`
				FROM
					`User`
				WHERE
					`Alias` = ?
			") ){
			
				$replied_commenter = array();
				
				foreach( $comment_replies[1] as $replied_alias ){
					
					$stmt_getRepliedCommenter->bind_param('s', $replied_alias);
					$stmt_getRepliedCommenter->execute();
					$stmt_getRepliedCommenter->store_result();
					$stmt_getRepliedCommenter->bind_result($pretty_replied_alias, $replied_reputation);
					$stmt_getRepliedCommenter->fetch();
					
					if( $stmt_getRepliedCommenter->num_rows == 1 ){
					
						$replied_commenter[$replied_alias] = array(
							'alias' => $pretty_replied_alias,
							'reputation' => $replied_reputation
						);
						
					} else {
						
						$replied_commenter[$replied_alias] = false;
						
					}
					
				}
				
				$input = preg_replace_callback(
					"/@(\w+)/",
					function($matches) use ($replied_commenter){
						if( $replied_commenter[ $matches[1] ] ){
							return "<a class='reply-to' href='" . URL . 'usr/' . strtolower($matches[1]) . '/' . "'>" . $replied_commenter[ $matches[1] ]['alias'] . " [" . $replied_commenter[ $matches[1] ]['reputation'] . "]</a>";
						} else {
							return $matches[0];
						}
					}, $input
				);
					
			}
			
		}
		
		// Re-insert Lists
		if( !empty($comment_ul_lists) ){
			for( $i = 0; $i < count($comment_ul_lists) ; $i++ ){
				$input = preg_replace('/%%%UL-LIST%%%/', $comment_ul_lists[$i], $input, 1);
			}
		}
		if( !empty($comment_ol_lists) ){
			for( $i = 0; $i < count($comment_ol_lists) ; $i++ ){
				$input = preg_replace('/%%%OL-LIST%%%/', $comment_ol_lists[$i], $input, 1);
			}
		}
		
		
		// Re-inserting PGP Blocks
		if( !empty($comment_pgp_blocks) ){
			for( $i = 0; $i < count($comment_pgp_blocks) ; $i++ ){
				
				$input = preg_replace('/%%%PGPBLOCK%%%/', $comment_pgp_blocks[$i], $input, 1);
				
			}
		}
		
		// Removing Empty Paragraphs and Double Linebreaks
		$input = preg_replace('/<p>(?:\s|<br ?\/?>)*<\/p>|(?:<br ?\/?>){2}/i', '', $input);
		$input = preg_replace('/><br ?\/?>/i', '>', $input);
		$input = preg_replace('/(?:<br ?\/?>)\s*<\/p/i', '</p', $input);
		
		return $input;
		
	}
	
	private function nl2p($string){
		return '<p>' . preg_replace('#(<br>[\r\n]+){2}#', '</p><p>', nl2br($string, false)) . '</p>';		
	}

	private function generateRandomString($length = 10, $case_sensitive = FALSE) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		if($case_sensitive) $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}
