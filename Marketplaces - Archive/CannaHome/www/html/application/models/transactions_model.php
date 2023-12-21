<?php
class TransactionsModel
{
	/**
	* Constructor, expects a Database connection
	* @param Database $db The Database object
	*/
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	private function _getPendingReferralWalletWithdrawals(){
		return	$this->db->qSelect(
				"
					SELECT
						`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`,
						`ReferralWallet_Cryptocurrency`.`OutputAddress`,
						`ReferralWallet`.`ID`,
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
						) keyIndex
					FROM
						`ReferralWallet_Cryptocurrency`
					INNER JOIN
						`ReferralWallet` ON
							`ReferralWallet_Cryptocurrency`.`ReferralWalletID` = `ReferralWallet`.`ID`
					WHERE
						`ReferralWallet_Cryptocurrency`.`Withdrawn` = FALSE AND
						`ReferralWallet_Cryptocurrency`.`OutputAddress` IS NOT NULL
				"
			);
	}
	
	private function _markReferralWalletCryptocurrencyWithdrawn(
		$referralWalletID,
		$cryptocurrencyID
	){
		return	$this->db->qQuery(
				"
					UPDATE
						`ReferralWallet_Cryptocurrency`
					SET
						`Withdrawn` = TRUE
					WHERE
						`ReferralWalletID` = ? AND
						`CryptocurrencyID` = ?
				",
				'ii',
				[
					$referralWalletID,
					$cryptocurrencyID
				]
			);
	}
	
	public function processReferralWalletWithdrawals(){
		$processedWithdrawals = 0;
		if ($pendingReferralWalletWithdrawals = $this->_getPendingReferralWalletWithdrawals()){
			$cryptocurrencies = [];
			foreach ($pendingReferralWalletWithdrawals as $pendingReferralWalletWithdrawal){
				$cryptocurrencyID = $pendingReferralWalletWithdrawal['CryptocurrencyID'];
				$cryptocurrency = $cryptocurrencies[$cryptocurrencyID] =
					isset($cryptocurrencies[$cryptocurrencyID])
						? $cryptocurrencies[$cryptocurrencyID]
						: $this->User->getCryptocurrency($cryptocurrencyID);
				
				$WIFKey = $this->getBIP32PrivateKeyWIF(
					$pendingReferralWalletWithdrawal['keyIndex'],
					$cryptocurrency->prefixPublic,
					REFERRAL_WALLET_EXTENDED_PRIVATE_KEY
				);
				
				if (
					$unsignedTransaction = $this->createSegwitTransaction(
						$cryptocurrency,
						$WIFKey,
						$pendingReferralWalletWithdrawal['OutputAddress'],
						$inputAddress,
						$inputsExceededMax
					)
				){
					$signedTransaction = $this->signWithCoinbin(
						$cryptocurrency,
						$unsignedTransaction,
						$WIFKey
					);
					
					if (
						$successfulPush =
							(
								$this->pushTX(
									$pendingReferralWalletWithdrawal['CryptocurrencyID'],
									$signedTransaction
								) ||
								$this->_checkEmptyAddress(
									$pendingReferralWalletWithdrawal['CryptocurrencyID'],
									$inputAddress
								)
							) &&
							(
								!$inputsExceededMax &&
								$this->_markReferralWalletCryptocurrencyWithdrawn(
									$pendingReferralWalletWithdrawal['ID'],
									$pendingReferralWalletWithdrawal['CryptocurrencyID']
								)
							)
					)
						$processedWithdrawals++;
				}
			}
		}
		
		return $processedWithdrawals;
	}
	
	public function getRatingAttributes(){
		return
			$this->db->qSelect(
				"
					SELECT
						`RatingAttribute`.`ID`,
						`RatingAttribute`.`Name`,
						`Icon`.`Class` icon
					FROM
						`RatingAttribute`
					INNER JOIN
						`Icon` ON
							`RatingAttribute`.`IconID` = `Icon`.`ID`
				"
			)
				?: false;
	}
	
	private function _getTransactionIDFromIdentifier(
		$transactionIdentifier,
		&$actualIdentifier = null
	){
		if(
			strlen($transactionIdentifier) == TRANSACTION_IDENTIFIER_LENGTH &&
			(
				$transactionIDs = $this->db->qSelect(
					"
						SELECT
							`ID`
						FROM
							`Transaction`
						WHERE
							`Identifier` = ?
					",
					's',
					[$transactionIdentifier]
				)
			) &&
			$actualIdentifier = $transactionIdentifier
		)
			return $transactionIDs[0]['ID'];
		
		return null;
	}
	
	public function getTransactionIdentifier($transactionID){
		$transactionIdentifiers = $this->db->qSelect(
			"
				SELECT
					`Identifier`
				FROM
					`Transaction`
				WHERE
					`ID` = ?
			",
			'i',
			[$transactionID]
		);
		if(
			$transactionIdentifiers &&
			$transactionIdentifiers[0]['Identifier']
		)
			return $transactionIdentifiers[0]['Identifier'];
			
		return $this->insertTransactionIdentifier(
			$transactionID
		);
	}
	
	public function getTransactionID(
		$identifier,
		&$actualIdentifier = null
	){
		$identifier = trim($identifier);
		return
			(
				strlen($identifier) < TRANSACTION_IDENTIFIER_LENGTH &&
				is_numeric($identifier) &&
				$actualIdentifier = $this->getTransactionIdentifier($identifier)
			)
				? $identifier
				: $this->_getTransactionIDFromIdentifier(
					$identifier,
					$actualIdentifier
				);
	}
	
	private function insertTransactionIdentifier(
		$transactionID,
		$iterationCount = 0,
		&$transactionIdentifier = null
	){
		if($iterationCount > 2)
			return false;
		
		$transactionIdentifier = NXS::generateRandomString(
			TRANSACTION_IDENTIFIER_LENGTH,
			FALSE,
			TRUE
		);
		
		return
			$this->db->qQuery(
				"
					UPDATE IGNORE
						`Transaction`
					SET
						`Identifier` = ?
					WHERE
						`ID` = ?
				",
				'si',
				[
					$transactionIdentifier,
					$transactionID
				]
			) ||
			$this->insertTransactionIdentifier(
				$transactionID,
				($iterationCount + 1),
				$transactionIdentifier
			)
				? $transactionIdentifier
				: false;
	}
	
	private function _getUnspentOutputAddress($checkedAddresses){
		return	$this->db->qSelect(
				"
					SELECT
						`CryptocurrencyID`,
						`Address`,
						GROUP_CONCAT(`TXID`, '-', `Index`, '-', `Value` ORDER BY `TXID` ASC, `Index` ASC) unspentOutputs,
						COUNT(DISTINCT `TXID`, `Index`) unspentOutputCount
					FROM
						`UnspentOutput`
					" . ($checkedAddresses ? 'WHERE `Address` NOT IN (' . rtrim(str_repeat('?, ', count($checkedAddresses)), ', ') . ')' : false ) . "
					GROUP BY
						`Address`
					LIMIT
						1
				",
				str_repeat('s', count($checkedAddresses)),
				$checkedAddresses
			);
	}
	
	private function _sortUnspentOutputs(&$inputs){
		foreach ($inputs as $key => $row) {
			$TXID[$key]  = $row['txid'];
			$Index[$key] = $row['vout'];
		}
		array_multisort($TXID, SORT_ASC, $Index, SORT_ASC, $inputs);
	}
	
	public function checkUnspentOutputs($checkedAddresses = []){
		if ($unspentOutputAddresses = $this->_getUnspentOutputAddress($checkedAddresses)){
			$unspentOutputAddress = $unspentOutputAddresses[0];
			$checkedAddresses[] = $unspentOutputAddress['Address'];
			
			if (
				list(
					$inputsValue,
					$inputs
				) = $this->getUnspentOutputs(
					$unspentOutputAddress['CryptocurrencyID'],
					$unspentOutputAddress['Address'],
					REQUIRED_TX_CONFIRMATIONS_BROADCAST,
					FALSE,
					false,
					false,
					50,
					false,
					$previousServerIDs
				)
			){
				$this->_sortUnspentOutputs($inputs);
				$inputsCondensed = implode(
					',',
					array_map(
						function ($input){
							return	$input['txid'] .
								'-' .
								$input['vout'] .
								'-' .
								$input['value'];
						},
						$inputs
					)
				);
			}
			
			if (
				!$inputs ||
				$changedOutputs =
					$unspentOutputAddress['unspentOutputs'] !== $inputsCondensed
			){
				if (
					$hasDifferentUnspentOutputs =
						$changedOutputs &&
						count($inputs) <= $unspentOutputAddress['unspentOutputCount']
				){
					list(
						$inputsValue,
						$newInputs
					) = $this->getUnspentOutputs(
						$unspentOutputAddress['CryptocurrencyID'],
						$unspentOutputAddress['Address'],
						REQUIRED_TX_CONFIRMATIONS_BROADCAST,
						FALSE,
						false,
						false,
						50,
						false,
						$previousServerIDs
					);
					
					if ($newInputs == $inputs)
						$this->_deleteUnspentOutputs($unspentOutputAddress['Address']);
					else
						return $this->checkUnspentOutputs($checkedAddresses);
				}
				
				$this->_updateUnspentOutputs(
					$unspentOutputAddress['CryptocurrencyID'],
					$unspentOutputAddress['Address'],
					$inputs
				);
			}
			
			return $this->checkUnspentOutputs($checkedAddresses);
		}
		
		return true;
	}
	
	private function _ascertainSpentOutputs(
		$cryptocurrencyID,
		$address
	){
		if (
			$addressHistory = $this->getAddressHistory(
				$cryptocurrencyID,
				$address
			)
		){ 
			$inputs = $this->getInputsFromAddressHistory(
				$cryptocurrencyID,
				$addressHistory,
				$address,
				$inputsValue,
				$hadOutgoing
			);
			
			return $hadOutgoing;
		}
			
		return false;
	}
	
	private function _deleteUnspentOutputs($address){
		return	$this->db->qQuery(
				"
					DELETE FROM
						`UnspentOutput`
					WHERE
						`Address` = ?
				",
				's',
				[$address]
			);
	}
	
	private function _updateUnspentOutputs(
		$cryptocurrencyID,
		$address,
		$newOutputs
	){
		if (!$newOutputs)
			return	$this->_ascertainSpentOutputs(
					$cryptocurrencyID,
					$address
				) &&
				$this->_deleteUnspentOutputs($address);
		
		return	$this->insertUnspentOutputs(
				$cryptocurrencyID,
				$address,
				$newOutputs
			);
	}
	
	private function insertUnspentOutputs(
		$cryptocurrencyID,
		$address,
		$outputs
	){
		foreach ($outputs as $output)
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`UnspentOutput` (
							`CryptocurrencyID`,
							`Address`,
							`TXID`,
							`Index`,
							`Value`
						)
					VALUES (
						?,
						?,
						?,
						?,
						?
					)
				",
				'issii',
				[
					$cryptocurrencyID,
					$address,
					$output['txid'],
					$output['vout'],
					$output['value']
				]
			);
			
		return true;
	}
	
	public function countSellingTransactions(){
		if( $stmt_countSellingTransactions = $this->db->prepare("
			SELECT
				COUNT(`Transaction`.`ID`)
			FROM
				`Transaction`
			INNER JOIN
				`Listing` ON `Transaction`.`ListingID` = `Listing`.`ID`
			WHERE
				`Listing`.`VendorID` = ?
		") ){
			$stmt_countSellingTransactions->bind_param('i', $this->User->ID);
			$stmt_countSellingTransactions->execute();
			$stmt_countSellingTransactions->store_result();
			$stmt_countSellingTransactions->bind_result($selling_count);
			$stmt_countSellingTransactions->fetch();
			
			return $selling_count;
		}
	}
	
	public function fetchTransactions(
		$type,
		$sort,
		$page,
		$perPage = TRANSACTIONS_PER_PAGE
	){
		switch($sort){
			case 'id_asc':
				$order = '
					`Transaction`.`DateTime` ASC,
					`Transaction`.`ID` ASC
				';
			break;
			case 'alias_asc':
				$order = '`User`.`Alias` ASC';
			break;
			case 'alias_desc':
				$order = '`User`.`Alias` DESC';
			break;
			case 'value_asc':
				$order = '`Transaction`.`Value` ASC';
			break;
			case 'value_desc':
				$order = '`Transaction`.`Value` DESC';
			break;
			case 'listing_asc':
				$order = '`Listing`.`Name` ASC';
			break;
			case 'listing_desc':
				$order = '`Listing`.`Name` DESC';
			break;
			case 'date_asc':
				$order = '`Transaction_Event`.`Date` ASC';
			break;
			case 'date_desc':
				$order = '`Transaction_Event`.`Date` DESC';
			break;
			// case 'id_desc':
			default:
				$order = 
					$type == 'sell' ?
						"
							(
								`PendingBroadcast`.`ID` IS NOT NULL AND
								`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
							) DESC,
							`Transaction`.`DateTime` DESC,
							`Transaction`.`ID` DESC
						"
						: "
							`Transaction`.`DateTime` DESC,
							`Transaction`.`ID` DESC
						";
		}
		
		// Should We Show "Expired" Transactions??
		$hideExpiredTransactions = $this->User->Attributes['Preferences']['ShowExpiredTransactions'] == FALSE;
		
		switch($type){
			case 'finalized':
				$stmt_countTransaction = $this->db->prepare("
					SELECT
						COUNT(`Transaction`.`ID`)
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID`
					WHERE
						(
							" . (
								$this->User->IsVendor
									? "
										(
											`PaymentMethod`.`UserID` = ? AND
											(
												`Transaction`.`Escrow` = TRUE OR
												`Transaction`.`Shipped` = TRUE
											) AND
											`Feedback_Vendor` = TRUE AND
											`Withdrawn` = TRUE AND
											(
												`PendingBroadcast`.`BroadcastAttempts` IS NULL OR
												`PendingBroadcast`.`BroadcastAttempts` < " . MAXIMUM_BROADCAST_ATTEMPTS . "
											)
										)
									"
									: "
										(
											`Transaction`.`BuyerID` = ? AND
											(
												`Feedback_Buyer` = TRUE OR
												`Transaction`.`Timeout` < NOW()
											)
										)
									"
							) . "
						) AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Timeout` > NOW()
				");
				
				$stmt_fetchTransactions = $this->db->prepare("
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`Identifier`,
						`Transaction`.`Status`,
						`Transaction`.`Value`,
						FLOOR(TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60) AS MinsRemaining,
						`Listing`.`ID`,
						`Listing`.`Name`,
						`User`.`Alias`,
						IF(
							`Transaction`.`RedeemScript` IS NULL,
							FALSE,
							TRUE
						),
						IF(
							`Transaction`.`BuyerID` = ?,
							`Feedback_Buyer`,
							`Feedback_Vendor`
						),
						TRUE,
						IF(
							`PendingBroadcast`.`ID` IS NULL,
							FALSE,
							TRUE
						),
						`Listing`.`Inactive`" . (
							!$this->User->IsVendor
								? " OR
									(
										`Listing_Group`.`GroupID` IS NOT NULL AND
										`Listing_Group`.`OutOfStock` = TRUE
									)"
								: false
						) . ",
						FALSE,
						TRUE,
						`Transaction`.`Escrow`,
						`Transaction_Event`.`Date`,
						TRUE,
						TRUE,
						`Transaction`.`Quantity`,
						`Transaction`.`PromoCodeID` IS NOT NULL,
						FALSE,
						Cryptocurrency.`ISO`,
						FALSE,
						`Transaction_Rating`.`Rating_Vendor`,
						`Transaction_Rating`.`Content`,
						`RatingAttribute`.`Name`,
						FALSE
					FROM
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` ON
							" . (
								$this->User->IsVendor
									? "`Transaction`.`BuyerID` = `User`.`ID`"
									: "`Listing`.`VendorID` = `User`.`ID`"
							) . "
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Currency` Cryptocurrency ON
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID`
					LEFT JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID` AND
							`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "'
					LEFT JOIN
						`Transaction_Rating` ON
							`Transaction`.`ID` = `Transaction_Rating`.`TransactionID`
					LEFT JOIN
						`RatingAttribute` ON
							`Transaction_Rating`.`AttributeID` = `RatingAttribute`.`ID`
					" . (
						!$this->User->IsVendor
							? 'LEFT JOIN `Listing_Group` ON `Listing`.`ID` = `Listing_Group`.`ListingID`'
							: false
					) . "
					WHERE
						(
							" . (
								$this->User->IsVendor
									? "
										(
											`PaymentMethod`.`UserID` = ? AND
											(
												`Transaction`.`Escrow` = TRUE OR
												`Transaction`.`Shipped` = TRUE
											) AND
											`Feedback_Vendor` = TRUE AND
											`Withdrawn` = TRUE AND
											(
												`PendingBroadcast`.`BroadcastAttempts` IS NULL OR
												`PendingBroadcast`.`BroadcastAttempts` < " . MAXIMUM_BROADCAST_ATTEMPTS . "
											)
										)
									"
									: "
										(
											`Transaction`.`BuyerID` = ? AND
											(
												`Feedback_Buyer` = TRUE OR
												`Transaction`.`Timeout` < NOW()
											)
										)
									"
							) . "
						) AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Timeout` > NOW()
					ORDER BY " . $order . "
					LIMIT ?, ?
				");
			break;
			case 'incipient':
				$stmt_countTransaction = $this->db->prepare("
					SELECT
						COUNT(`Transaction`.`ID`)
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`Paid` AND
						`Transaction`.`Timeout` > NOW()
				");
				
				$stmt_fetchTransactions = $this->db->prepare("
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`Identifier`,
						`Transaction`.`Status`,
						`Transaction`.`Value`,
						FLOOR(TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60) AS MinsRemaining,
						`Listing`.`ID`,
						`Listing`.`Name`,
						`User`.`Alias`,
						IF(`Transaction`.`RedeemScript` IS NULL, FALSE, TRUE),
						FALSE,
						FALSE,
						FALSE,
						`Listing`.`Inactive`,
						FALSE,
						FALSE,
						`Transaction`.`Escrow`,
						NULL,
						TRUE,
						TRUE,
						`Transaction`.`Quantity`,
						`Transaction`.`PromoCodeID` IS NOT NULL,
						FALSE,
						Cryptocurrency.`ISO`,
						FALSE,
						FALSE,
						FALSE,
						FALSE,
						FALSE
					FROM
						`Transaction`
					INNER JOIN
						`User` ON `Transaction`.`BuyerID` = `User`.`ID`
					INNER JOIN
						`Listing` ON `Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Currency` Cryptocurrency ON
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`Paid` AND
						`Transaction`.`Timeout` > NOW()
					ORDER BY " . $order . "
					LIMIT ?, ?
				");
				
			break;
			case 'sell':
				$stmt_countTransaction = $this->db->prepare("
					SELECT
						COUNT(`Transaction`.`ID`)
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID`
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` != 'pending deposit' AND
						(
							(
								`Transaction`.`Status` = 'pending feedback' AND
								(
									`Transaction`.`Withdrawn` IS FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							) OR
							(
								`Transaction`.`Feedback_Vendor` = FALSE AND
								`Transaction`.`Status` NOT IN ('rejected', 'refunded')
							) OR
							(
								`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
								`Transaction`.`Escrow` = FALSE AND
								`Transaction`.`Shipped` = FALSE
							) OR
							(
								`Transaction`.`Status` IN ('rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									(
										`PendingBroadcast`.`ID` IS NOT NULL AND
										`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
									)
								)
							)
						) AND
						(
							`Transaction`.`Timeout` > NOW() OR
							`Transaction`.`Status` IN ('in dispute', 'expired') OR
							(
								`Transaction`.`Status` IN ('in transit', 'pending feedback', 'rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							)
						)
				");
				
				$stmt_fetchTransactions = $this->db->prepare("
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`Identifier`,
						`Transaction`.`Status`,
						`Transaction`.`Value`,
						FLOOR(TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60) AS MinsRemaining,
						`Listing`.`ID`,
						`Listing`.`Name`,
						`User`.`Alias`,
						IF(`Transaction`.`RedeemScript` IS NULL, FALSE, TRUE),
						`Feedback_Vendor`,
						`Withdrawn`,
						IF(
							`PendingBroadcast`.`ID` IS NULL,
							FALSE,
							TRUE
						),
						`Listing`.`Inactive`,
						IF(
							`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . ",
							TRUE,
							FALSE
						),
						`Transaction`.`Shipped`,
						`Transaction`.`Escrow`,
						`Transaction_Event`.`Date`,
						TRUE,
						TRUE,
						`Transaction`.`Quantity`,
						`Transaction`.`PromoCodeID` IS NOT NULL,
						FALSE,
						Cryptocurrency.`ISO`,
						FALSE,
						FALSE,
						FALSE,
						FALSE,
						FALSE
					FROM
						`Transaction`
					INNER JOIN
						`User` ON
							`Transaction`.`BuyerID` = `User`.`ID`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Currency` Cryptocurrency ON
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					LEFT JOIN	`PendingBroadcast`
						ON	`Transaction`.`ID` = `PendingBroadcast`.`TransactionID`
					LEFT JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID` AND
							`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "'
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` != 'pending deposit' AND
						(
							(	
								`Transaction`.`Status` = 'pending feedback' AND
								(
									`Transaction`.`Withdrawn` IS FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							) OR
							(
								`Transaction`.`Feedback_Vendor` = FALSE AND
								`Transaction`.`Status` NOT IN ('rejected', 'refunded')
							) OR
							(
								`Transaction`.`Status` NOT IN ('rejected', 'refunded') AND
								`Transaction`.`Escrow` = FALSE AND
								`Transaction`.`Shipped` = FALSE
							) OR
							(
								`Transaction`.`Status` IN ('expired', 'in transit', 'rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									(
										`PendingBroadcast`.`ID` IS NOT NULL AND
										`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
									)
								)
							)
						) AND
						(
							`Transaction`.`Timeout` > NOW() OR
							`Transaction`.`Status` IN ('in dispute', 'expired') OR
							(
								`Transaction`.`Status` IN ('in transit', 'pending feedback', 'rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							)
						)
					ORDER BY " . $order . "
					LIMIT ?, ?
				");
				
			break;
			//case 'buy':
			default:
				$stmt_countTransaction = $this->db->prepare("
					SELECT
						COUNT(`Transaction`.`ID`)
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID` AND
							`PendingBroadcast`.`UserID` != `PaymentMethod`.`UserID`
					WHERE
						`Transaction`.`BuyerID` = ? AND
						(
							(
								`Feedback_Buyer` = FALSE AND
								(
									`Transaction`.`Timeout` > NOW() OR
									`Transaction`.`Status` != 'pending feedback'
								)
							) OR
							(
								`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . " AND
								`Transaction`.`Status` IN ('rejected', 'refunded')
							) OR
							`Transaction`.`StatusChanged` = TRUE
						) AND
						(
							`Transaction`.`Timeout` > NOW() - INTERVAL " . TRANSACTIONS_BUYER_VIEW_PAST_TIMEOUT_DAYS . " DAY OR
							`Transaction`.`Status` IN ('in dispute', 'expired') OR
							(
								`Transaction`.`Status` = 'pending deposit' AND
								`Transaction`.`Deposited` = TRUE
							) OR
							(
								`Transaction`.`Timeout` > NOW() - INTERVAL " . UNWITHDRAWN_REFUND_TIMEOUT_TOLERANCE_DAYS . " DAY AND
								`Transaction`.`Status` IN ('rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							)
						)
				");
			
				$stmt_fetchTransactions = $this->db->prepare("
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`Identifier`,
						`Transaction`.`Status`,
						`Transaction`.`Value`,
						FLOOR(TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60) as MinsRemaining,
						`Listing`.`ID`,
						`Listing`.`Name`,
						`User`.`Alias`,
						IF(`Transaction`.`RedeemScript` IS NULL, FALSE, TRUE),
						`Feedback_Buyer`,
						`Withdrawn`,
						IF(
							`PendingBroadcast`.`ID` IS NULL,
							FALSE,
							TRUE
						),
						`Listing`.`Inactive`" . (
							!$this->User->IsVendor
								? " OR
									(
										`Listing_Group`.`GroupID` IS NOT NULL AND
										`Listing_Group`.`OutOfStock` = TRUE
									)"
								: false
						) . ",
						IF(
							`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . ",
							TRUE,
							FALSE
						),
						`Transaction`.`Shipped`,
						`Transaction`.`Escrow`,
						`Transaction_Event`.`Date`,
						`Transaction`.`Paid`,
						`Transaction`.`Deposited`,
						`Transaction`.`Quantity`,
						`Transaction`.`PromoCodeID` IS NOT NULL,
						FALSE,
						Cryptocurrency.`ISO`,
						`Transaction`.`StatusChanged`,
						FALSE,
						FALSE,
						FALSE,
						(
							`Transaction`.`Status` = 'pending deposit' AND
							`Transaction`.`Paid` = FALSE AND
							NOW() > `Transaction`.`Timeout` AND
							NOW() < `Transaction`.`Timeout` + INTERVAL " . ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES . " MINUTE
						) canExtendPaymentWindow
					FROM
						`Transaction`
					INNER JOIN
						`Listing` ON `Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`User` ON
							`PaymentMethod`.`UserID` = `User`.`ID`
					INNER JOIN
						`Currency` Cryptocurrency ON
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID` AND
							`PendingBroadcast`.`UserID` != `PaymentMethod`.`UserID`
					LEFT JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID` AND
							`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "'
					" . (
						!$this->User->IsVendor
							? 'LEFT JOIN `Listing_Group` ON `Listing`.`ID` = `Listing_Group`.`ListingID`'
							: false
					) . "
					WHERE
						`Transaction`.`BuyerID` = ? AND
						(
							(
								`Feedback_Buyer` = FALSE AND
								(
									`Transaction`.`Timeout` > NOW() OR
									`Transaction`.`Status` != 'pending feedback'
								)
							) OR
							(
								`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . " AND
								`Transaction`.`Status` IN ('rejected', 'refunded')
							) OR
							`Transaction`.`StatusChanged` = TRUE
						) AND (
							`Transaction`.`Timeout` > NOW() - INTERVAL " . TRANSACTIONS_BUYER_VIEW_PAST_TIMEOUT_DAYS . " DAY OR
							`Transaction`.`Status` IN ('in dispute', 'expired') OR
							(
								`Transaction`.`Status` = 'pending deposit' AND
								`Transaction`.`Deposited` = TRUE
							) OR
							(
								`Transaction`.`Timeout` > NOW() - INTERVAL " . UNWITHDRAWN_REFUND_TIMEOUT_TOLERANCE_DAYS . " DAY AND
								`Transaction`.`Status` IN ('rejected', 'refunded') AND
								(
									`Transaction`.`Withdrawn` = FALSE OR
									`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
								)
							)
						)
					ORDER BY ".$order."
					LIMIT ?, ?
				");
			
			break;
		}
		
		if (false !== $stmt_countTransaction && false !== $stmt_fetchTransactions){
			
			if( $type == 'finalized' ){
				$stmt_countTransaction->bind_param('i', $this->User->ID);
			} else {
				$stmt_countTransaction->bind_param('i', $this->User->ID);
			}
			$stmt_countTransaction->execute();
			$stmt_countTransaction->store_result();
			$stmt_countTransaction->bind_result($transaction_count);
			$stmt_countTransaction->fetch();
			
			if ($transaction_count > 0) {
				$offset = NXS::getOffset(
					$transaction_count,
					$perPage,
					$page
				);
				
				if ($type == 'finalized')
					$stmt_fetchTransactions->bind_param('iiii', $this->User->ID, $this->User->ID, $offset, $perPage);
				else 
					$stmt_fetchTransactions->bind_param('iii', $this->User->ID, $offset, $perPage);
					
				$stmt_fetchTransactions->execute();
				$stmt_fetchTransactions->store_result();
				$stmt_fetchTransactions->bind_result(
					$tx_id,
					$txIdentifier,
					$tx_status,
					$tx_value,
					$tx_min_remaining,
					$tx_listing_id,
					$tx_listing_name,
					$tx_alias,
					$redeemscript_not_empty,
					$feedback_given,
					$withdrawn,
					$processing,
					$listing_inactive,
					$listing_failed_broadcast,
					$shipped,
					$transaction_escrow,
					$datePaid,
					$hasPaid,
					$hasDeposited,
					$orderQuantity,
					$hasPromo,
					$hasPendingCPFP,
					$cryptocurrencyISO,
					$statusChanged,
					$vendorRating,
					$ratingComments,
					$attributeName,
					$canExtendPaymentWindow
				);
				
				$transactions = array();
				while ($stmt_fetchTransactions->fetch()){
					$time =
						$tx_min_remaining <= 0
							? FALSE
							: NXS::parseMinutes($tx_min_remaining);
					
					if ($statusChanged)
						$this->clearChangedTransactionStatus($tx_id);
					
					$transactions[] = array(
						'id'			=> $tx_id,
						'identifier'		=> $txIdentifier,
						'status'		=> ucwords($tx_status),
						'value'			=> $tx_value . ' ' . $cryptocurrencyISO,
						'timeout'		=> $time,
						'listing_id'		=> $tx_listing_id,
						'listing_name'		=> $tx_listing_name,
						'alias'			=> $tx_alias,
						'confirmed'		=> $redeemscript_not_empty == 1,
						'finished' 		=> !empty($feedback_given),
						'withdrawn'		=> $withdrawn,
						'processing'		=> $processing,
						'listing_inactive'	=> $listing_inactive,
						'failedBroadcast'	=> $listing_failed_broadcast,
						'shipped'		=> $shipped,
						'escrow'		=> $transaction_escrow,
						'datePaid'		=> $datePaid,
						'hasPaid'		=> $hasPaid,
						'hasDeposited'		=> $hasDeposited,
						'quantity'		=> $orderQuantity,
						'minsRemaining'		=> $tx_min_remaining,
						'hasPromo'		=> $hasPromo,
						'hasPendingCPFP'	=> $hasPendingCPFP,
						'statusChanged'		=> $statusChanged,
						'vendorRating'		=> $vendorRating,
						'ratingComments'	=> $ratingComments,
						'attributeName'		=> $attributeName,
						'canExtendPaymentWindow' => $canExtendPaymentWindow
					);
				}
				
			} else
				$transactions = false;	
			
		}
		
		return array($transaction_count, $transactions);
		
	}
	
	public function getExpiredTransactions(){
		if (!empty($_POST))
			$this->processExpiredTransactions();
		
		$expiredTransactions = $this->db->qSelect(
			"
				SELECT
					IFNULL(`Transaction`.`Identifier`, `Transaction`.`ID`) as ID,
					`Listing`.`Name` as name,
					`Transaction`.`Value` as value,
					`Transaction`.`Extended` as extended
				FROM
					`Transaction`
				INNER JOIN	`Listing`
					ON	`Transaction`.`ListingID` = `Listing`.`ID`
				WHERE
					`Transaction`.`BuyerID` = ?
				AND	`Transaction`.`Status` = 'expired'
			",
			'i',
			array($this->User->ID)
		);
		
		return $expiredTransactions;
	}
	
	private function processExpiredTransactions(){
		if (!empty($_POST['txIDs'])){
			$errors = FALSE;
			
			foreach ($_POST['txIDs'] as $txID){
				if (empty($_POST['action-' . $txID]))
					continue;
				
				$action = $_POST['action-' . $txID];
				$txID = $this->getTransactionID($txID);
				switch ($action){
					case 'extend':
						$this->extendTransaction($txID, EXPIRED_TRANSACTION_EXTENSION_DAYS);
						break;
					case 'dispute':
						$this->startTransactionDispute($txID);
						break;
					case 'finalize':
						$this->finalizeTransaction($txID);
						break;
				}
			}
		}
	}
	
	private function extendTransaction($transactionID, $extendDays = EXPIRED_TRANSACTION_EXTENSION_DAYS){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Status`	= 'in transit',
					`Timeout`	= NOW() + INTERVAL ? DAY,
					`Extended`	= TRUE
				WHERE
					`ID`		= ? AND
					`Status`	= 'expired' AND
					`BuyerID`	= ? AND
					`Extended`	= FALSE
			",
			'iii',
			[
				$extendDays,
				$transactionID,
				$this->User->ID
			]
		);
	}
	
	private function getTransactionBuyerVendorIDs($transactionID){
		if(
			$transaction = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`BuyerID`,
						`Listing`.`VendorID`
					FROM
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					WHERE
						`Transaction`.`ID` = ?
				",
				'i',
				[$transactionID]
			)
		)
			return [
				$transaction[0]['BuyerID'],
				$transaction[0]['VendorID'],
			];
		
		return false;
	}
	
	private function startTransactionDispute($transactionID){
		if(
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`Status`	= 'in dispute',
						`Timeout`	= NOW() + INTERVAL " . IN_DISPUTE_TIMEOUT_DAYS . " DAY
					WHERE
						`ID`		= ?
					AND	`Status`	= 'expired'
					AND	`BuyerID`	= ?
				",
				'ii',
				array(
					$transactionID,
					$this->User->ID
				)
			)
		){
			list(
				$buyerID,
				$vendorID
			) = $this->getTransactionBuyerVendorIDs($transactionID);
			$this->User->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE,
				1,
				[
					$buyerID,
					$vendorID
				]
			);
			$this->User->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
				1,
				$vendorID
			);
		}
	}
	
	public function _getEncryptedTXDetails($txID, $JSON_decode = TRUE){
		$tx = $this->db->qSelect(
			"
				SELECT
					`NextTX_Site`
				FROM
					`Transaction`
				WHERE
					`ID` = ?
			",
			'i',
			array(
				$txID
			)
		);
		$encryptedData = $tx[0]['NextTX_Site'];
		
		$rsa = new RSA(SITE_RSA_PRIVATE_KEY);
		
		$decryptedData = $rsa->qDecrypt(
			$encryptedData
		);
		
		if($JSON_decode)
			$decryptedData = json_decode(
				$decryptedData,
				TRUE
			);
		
		return $decryptedData;
	}
	
	private function getTransactionRefundAddress($transactionID){
		if(
			$transactions = $this->db->qSelect(
				"
					SELECT
						`RefundAddress`
					FROM
						`Transaction`
					WHERE
						`ID` = ?
				",
				'i',
				[$transactionID]
			)
		)
			return $transactions[0]['RefundAddress'];
		
		return FALSE;
	}
	
	private function _testTransactionWithdrawalPreparation(
		$commision,
		$cryptocurrencyID,
		$multisigAddress,
		$redeemScript,
		$transactionID,
		$transactionValue,
		$signeeCount
	){
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		if ($commision)
			$marketplaceFee = $commision/1000;
		else
			$marketplaceFee = MARKETPLACE_FEE;
		
		if (
			list(
				$inputsValue,
				$inputs
			) = $this->getUnspentOutputs(
				$cryptocurrency->ID,
				$multisigAddress,
				REQUIRED_TX_CONFIRMATIONS_BROADCAST,
				FALSE,
				$redeemScript,
				false
			)
		){
			$vendorAddress['Address'] = $this->getVendorBIP32AddressForTransaction($transactionID);
			$marketplaceAddress = $this->getMarketplaceAddressForTransaction($transactionID);
			$minimumMarketOutput = $minimumMarketOutput ?: $this->_calculateMinimumMarketOutput($cryptocurrency);
			
			$provisionalOutputs = [$vendorAddress['Address']];
			
			if (
				$isSpendableInput = NXS::compareFloatNumbers(
					$inputsValue,
					$minimumMarketOutput,
					'>'
				)
			){
				$provisionalOutputs[] = $marketplaceAddress;
				
				if (
					$hasExcessFunds =
						NXS::compareFloatNumbers(
							$inputsValue,
							$transactionValue + $minimumMarketOutput,
							'>'
						) &&
						$returnAddress = $this->getTransactionRefundAddress($transactionID)
				)
					$provisionalOutputs[] = $returnAddress;
				
				if (
					$hasReferralWallet =
						NXS::compareFloatNumbers(
							$inputsValue,
							$minimumMarketOutput*2,
							'>'
						) &&
						$referralAddress = $this->getTransactionReferralAddress($transactionID)
				)
					$provisionalOutputs[] = $referralAddress;
			}
			
			$minerFee = $this->estimateDynamicFee(
				$cryptocurrency,
				$inputs,
				$provisionalOutputs,
				$inputsValue,
				$redeemScript,
				($signeeCount - 1),
				$this->getCryptocurrencyFeePerKilobyte(
					CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT,
					$cryptocurrency->ID
				)
			);
			
			if(
				NXS::compareFloatNumbers(
					$inputsValue,
					$minerFee,
					'<='
				)
			){
				$hadTooLowToProcess = TRUE;
				die('too low to process');
			}
			
			$value_affiliate = $value_marketplace = $value_buyer = 0;
			if (
				$isSpendableInputs = NXS::compareFloatNumbers(
					$inputsValue,
					($minerFee + $minimumMarketOutput),
					'>='
				)
			){
				$value_marketplace =
					max(
						$minimumMarketOutput,
						$cryptocurrency->parseValue(
							($hasExcessFunds ? $transactionValue : $inputsValue) *
							$marketplaceFee
						)
					);
				
				if (
					$hasReferralWallet &&
					NXS::compareFloatNumbers(
						$inputsValue,
						($minerFee + $value_marketplace + 2*$minimumMarketOutput),
						'>='
					)
				)
					$value_affiliate = 
						max(
							2*$minimumMarketOutput,
							$cryptocurrency->parseValue(
								(
									$transactionValue *
									REFERRAL_COMMISION
								),
								true
							)
						);
				
				if (
					$hasExcessFunds &&
					NXS::compareFloatNumbers(
						$inputsValue,
						($minerFee + $value_marketplace + $value_affiliate + $minimumMarketOutput),
						'>='
					)
				)
					$value_buyer = $cryptocurrency->parseValue(
						($inputsValue - $transactionValue),
						true
					);
					
			}
			
			$value_vendor = $cryptocurrency->parseValue(
				($inputsValue - $minerFee - $value_marketplace - $value_affiliate - $value_buyer),
				true
			);
			
			$outputs = [];
			
			if (
				$value_marketplace &&
				NXS::compareFloatNumbers(
					$value_marketplace,
					$cryptocurrency->smallestIncrement,
					'>='
				)
			)
				$outputs[$marketplaceAddress] = $value_marketplace;
		
			if (
				$value_vendor &&
				NXS::compareFloatNumbers(
					$value_vendor,
					$cryptocurrency->smallestIncrement,
					'>='
				)
			)
				$outputs[$vendorAddress['Address']] = $value_vendor;
								
			if (
				$value_buyer &&
				NXS::compareFloatNumbers(
					$value_buyer,
					$cryptocurrency->smallestIncrement,
					'>='
				)
			)
				$outputs[$returnAddress] = $value_buyer;
				
			if (
				$value_affiliate &&
				NXS::compareFloatNumbers(
					$value_affiliate,
					$cryptocurrency->smallestIncrement,
					'>='
				)
			)
				$outputs[$referralAddress] = $value_affiliate;
			
			if (empty($outputs)){
				$hadTooLowToProcess = TRUE;
				die('too low to process');
			}
		
			$privateKey_wif_site = $this->getBIP32PrivateKeyWIF(
				$transactionID,
				$cryptocurrency->prefixPublic
			);
		
			$rawTransaction = $unsignedTX = $this->createTXWithCoinbin(
				$cryptocurrency,
				$inputs,
				$outputs,
				$redeemScript
			);
		
			$signedTransaction = array(
				'hex' => $this->signWithCoinbin(
					$cryptocurrency,
					$rawTransaction,
					$privateKey_wif_site
				)
			);
			
			var_dump(
				$cryptocurrency,
				$inputs,
				$outputs,
				$redeemScript,
				$rawTransaction,
				$signedTransaction
			);
		}
	}
	
	public function _playground(){
		set_time_limit(0);
		
		var_dump(
			$this->getUnspentOutputs(
				1,
				'3FsykuuGcy48sxSwqFB7fKUmNnaD8zNGfH',
				REQUIRED_TX_CONFIRMATIONS_BROADCAST,
				FALSE,
				false,
				false,
				50,
				false
			),
			$this->getUnspentOutputs(
				1,
				'3FsykuuGcy48sxSwqFB7fKUmNnaD8zNGfH'
			)
		);
		die;
		
		if (
			(
				(
					$addressHistory = $this->getAddressHistory(
						1,
						'33cXarU6kYzK19kMFu1cRurXfDsQL9JjUP'
					)
				) &&
				$inputs = $this->getInputsFromAddressHistory(
					1,
					$addressHistory,
					'33cXarU6kYzK19kMFu1cRurXfDsQL9JjUP',
					$inputsValue
				)
			)
		)
			var_dump($inputs, $inputsValue);
		
		die;
	}
	
	public function fetchTransaction($id, $hide_timeout = false){
		if(
			$this->User->IsTester &&
			isset($_GET['debug']) 
		)
			return $this->_playground();
		
		$stmt_getTransaction = $this->db->prepare("
			SELECT
				`Transaction`.`Status`,
				IF(
					`Transaction`.`BuyerID` = ?,
					`Transaction`.`Order_Buyer`,
					`Transaction`.`Order_Vendor`
				),
				IF(
					`Transaction`.`BuyerID` = ?,
					`Transaction`.`NextTX_Buyer`,
					`Transaction`.`NextTX_Vendor`
				),
				`Transaction`.`MultiSigAddress`,
				`Transaction`.`RedeemScript`,
				`Transaction`.`Value`,
				`Transaction`.`Timeout`,
				TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) / 60 as MinsRemaining,
				`Listing`.`ID`,
				`Listing`.`Name`,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					`Image`.`Filename`
				) Image,
				Buyer.`ID`,
				Buyer.`Alias`,
				Buyer.`Reputation`,
				AVG(Buyer_Rating.`Rating_Buyer`),
				COUNT(Buyer_Rating.`ID`),
				0,
				Buyer.`BuyCount`,
				Vendor.`ID`,
				Vendor.`Alias`,
				Vendor.`Reputation`,
				`Transaction`.`MediatorID`,
				IF( `Transaction`.`BuyerID` = ?, `Feedback_Buyer`, `Feedback_Vendor`),
				`Transaction`.`Escrow`,
				`Listing`.`Quantity`,
				`Unit`.`Name_Singular`,
				`Unit`.`Name_Plural`,
				`Unit`.`Abbreviation`,
				Vendor.`Commission`,
				`Transaction`.`SigneeCount`,
				`Transaction`.`Shipped`,
				(`Listing`.`AllowFE` AND Vendor.`AllowFE`),
				`Transaction`.`Withdrawn`,
				NOW() > `Transaction`.`Timeout`,
				TIME_TO_SEC( TIMEDIFF(`Transaction`.`Timeout`, NOW() ) ) as SecondsRemaining,
				`Transaction`.`Deposited` hasDeposited,
				`Transaction`.`Paid` hasPaid,
				`Transaction`.`FeeBumped` hadFeeBump,
				`Transaction_Event`.`Date` acceptDate,
				`PaymentMethod`.`ID` paymentMethodID,
				`PaymentMethod`.`CryptocurrencyID`,
				Cryptocurrency.`Color` cryptocurrencyColor,
				(
					`Transaction`.`Status` = 'pending deposit' AND
					`Transaction`.`Paid` = FALSE AND
					NOW() > `Transaction`.`Timeout` AND
					NOW() < `Transaction`.`Timeout` + INTERVAL " . ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES . " MINUTE
				) canExtendPaymentWindow,
				`Transaction`.`BuyerExtendedPublicKey`,
				`Transaction`.`VendorExtendedPublicKey`,
				`Transaction`.`Segwit`
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
			INNER JOIN
				`Unit` ON
					`Listing`.`UnitID` = `Unit`.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			INNER JOIN
				`Currency` Cryptocurrency ON
					`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
			LEFT JOIN
				`Transaction_Rating` Buyer_Rating ON
					Buyer.`ID`	= Buyer_Rating.`BuyerID` AND
					Buyer_Rating.`Rating_Buyer` IS NOT NULL
			LEFT JOIN
				`Transaction_Event` ON
					`Transaction`.`ID` = `Transaction_Event`.`TransactionID` AND
					`Transaction_Event`.`Event` = 'paid'
			LEFT JOIN
				`Listing_Image` ON
					`Listing_Image`.`ListingID` = `Listing`.`ID` AND
					`Listing_Image`.`Primary` = TRUE
			LEFT JOIN
				`Image` ON
					`Listing_Image`.`ImageID` = `Image`.`ID`
			WHERE
				`Transaction`.`ID` = ? AND
				(
					Buyer.`ID` = ? OR
					Vendor.`ID` = ? OR
					`Transaction`.`MediatorID` = ?
				)
			" . (
				$hide_timeout
					? 'AND	`Transaction`.`Timeout` > NOW()'
					: false
			) . "
			LIMIT	1
		");
		
		if( false !== $stmt_getTransaction ){
			$stmt_getTransaction->bind_param(
				'iiiiiii',
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$id,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID
			);
			$stmt_getTransaction->execute();
			$stmt_getTransaction->store_result();
			
			if( $stmt_getTransaction->num_rows == 1 ){
				$stmt_getTransaction->bind_result(
					$status, $order,
					$next_tx_encrypted,
					$multisig,
					$redeem_script,
					$value,
					$timeout,
					$mins_remaining,
					$listingID,
					$listing_name,
					$listing_image,
					$buyer_id,
					$buyer_alias,
					$buyer_reputation,
					$buyerRating,
					$buyerRatingCount,
					$buyerCommentCount,
					$buyerPurchases,
					$vendor_id,
					$vendor_alias,
					$vendor_reputation,
					$mediator_id,
					$given_feedback,
					$escrow_enabled,
					$listing_quantity,
					$unit_singular,
					$unit_plural,
					$unit_abbreviation,
					$vendor_custom_commission,
					$signee_count,
					$shipped,
					$AllowFE,
					$withdrawn,
					$isTimedout,
					$secondsRemaining,
					$hasDeposited,
					$hasPaid,
					$hadFeeBump,
					$acceptDate,
					$paymentMethodID,
					$cryptocurrencyID,
					$cryptocurrencyColor,
					$canExtendPaymentWindow,
					$buyerExtendedPublicKey,
					$vendorExtendedPublicKey,
					$isSegwit
				);
				$stmt_getTransaction->fetch();
				
				if($status == NULL)
					return FALSE;
				
				$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
				
				$rsa = new RSA();
				
				$order = json_decode( $rsa->qDecrypt($order), true );
				
				$next_tx = empty($next_tx_encrypted) ? false : json_decode( $rsa->qDecrypt($next_tx_encrypted), true);
				
				if ($mins_remaining < 0)
					$time_remaining = '0 minutes';
				elseif ($mins_remaining > 1440 )
					$time_remaining = floor($mins_remaining/1440).' day'.(floor($mins_remaining/1440) == 1 ? false : 's');
				elseif ($mins_remaining > 60)
					$time_remaining = floor($mins_remaining/60).' hour'.(floor($mins_remaining/60) == 1 ? false : 's');
				else
					$time_remaining = $mins_remaining.' minute'.($mins_remaining == 1 ? false : 's');
				
				$unshipAllowed = false;
				
				if ($this->User->IsVendor){
					$rawPrice = $order['Price']['raw_price'] + ($order['Price']['price_shipping'] ?: 0);
					$minerFee = $cryptocurrency->parseValue(
						$this->getCryptocurrencyFeePerKilobyte(
							$this->User->Attributes['Preferences']['CryptocurrencyFeeLevel'],
							$cryptocurrency->ID
						) *
						BITCOIN_TRANSACTION_AVERAGE_SIZE_KB /
						1e8
					);
					$order['Price']['transaction_fees'] = $cryptocurrency->formatValue($minerFee);
					
					$minimumMarketOutput = $this->_calculateMinimumMarketOutput($cryptocurrency);
					
					if ($hasReferralWallet = $this->getTransactionReferralAddress($id)){
						$vendor_custom_commission += REFERRAL_COMMISION*1e3;
						$minimumMarketOutput += $minimumMarketOutput;
					}
					
					$marketplaceFee = max(
						$cryptocurrency->parseValue(($order['Price']['raw_price'] + $order['Price']['price_shipping'])*($vendor_custom_commission/1e3)),
						$minimumMarketOutput
					);
					
					if (
						NXS::compareFloatNumbers(
							$rawPrice,
							$marketplaceFee + $minerFee,
							'<'
						)
					)
						$marketplaceFee = 0;
					
					$order['Price']['marketplace_fee'] = $cryptocurrency->formatValue($marketplaceFee);
					
					$finalPrice =
						$rawPrice -
						$marketplaceFee -
						$minerFee;
					
					$order['Price']['final_price'] = 
						NXS::compareFloatNumbers(
							$finalPrice,
							$cryptocurrency->smallestIncrement,
							'>='
						)
							? $cryptocurrency->formatValue($finalPrice)
							: 0;
							
					if ($shipped){
						$m = $this->db->m;
						$mKey = 'recentAction-' . $this->User->ID . '-toggleShipped-' . $id;
						$unshipAllowed = $m->get($mKey);
					}
				}
				$priceCurrency =
					'&#x7e; ' .
					NXS::formatPrice(
						$this->User->Currency,
						$order['Price']['final_price']/$cryptocurrency->XEUR
					);
				
				if($this->User->IsVendor)
					$buyerTransacted = NXS::formatPrice($this->User->Currency, $next_tx['BuyerTransacted'], 1);
				else
					$buyerTransacted = FALSE;
				
				$priceBreakdown = [
					'raw'		=> $cryptocurrency->formatValue($order['Price']['raw_price'], true),
					'shipping'	=>
						(
							$order['Price']['price_shipping'] > 0
								? '+ '
								: false
						) .
						$cryptocurrency->formatValue(
							$order['Price']['price_shipping'],
							true,
							ZERO_PRICE_TEXTUAL_REPLACEMENT
						),
					'final'		=> $cryptocurrency->formatValue($order['Price']['final_price'], true),
					'currency'	=> $priceCurrency
				];
				
				// QUANTITY
				
				if( !empty($listing_quantity) ){
					$text_quantity = $listing_quantity * $order['Quantity'] . ' ' . ($listing_quantity * $order['Quantity'] == 1 ? $unit_singular : ($unit_abbreviation ? $unit_abbreviation : $unit_plural));
				} else
					$text_quantity = false;
				
				return array(
					'status'		=> $status,
					'order'			=> $order,
					'next_tx'		=> $next_tx,
					'price_currency'	=> $priceCurrency,
					'multisig_address'	=> $multisig,
					'redeem_script'		=> $redeem_script,
					'value'			=> $value,
					'timeout'		=> $timeout,
					'time_remaining'	=> $time_remaining,
					'listing_id'		=> $listingID,
					'listing_name'		=> $listing_name,
					'listing_image'		=> NXS::getPictureVariant($listing_image, IMAGE_THUMBNAIL_SUFFIX),
					'buyer_id'		=> $buyer_id,
					'buyer_alias'		=> $buyer_alias,
					'buyer_reputation'	=> $buyer_reputation,
					'buyerRating'		=> $buyerRating,
					'buyerRatingCount'	=> $buyerRatingCount,
					'buyerCommentCount'	=> $buyerCommentCount,
					'buyerPurchases'	=> $buyerPurchases,
					'vendor_id'		=> $vendor_id,
					'vendor_alias'		=> $vendor_alias,
					'vendor_reputation'	=> $vendor_reputation,
					'mediator_id'		=> $mediator_id,
					'feedback_given'	=> !empty($given_feedback),
					'in_transit_timeout'	=> $order['EscrowTimeout'],
					'escrow_enabled'	=> $escrow_enabled == 1,
					'text_quantity'		=> $text_quantity,
					'vendor_commission'	=> $vendor_custom_commission ?: FALSE,
					'buyerTransacted'	=> $buyerTransacted,
					'signee_count'		=> $signee_count,
					'shipped'		=> $shipped,
					'siteAllowsEscrow'	=> $this->db->getSiteInfo('AllowMultisig'),
					'AllowFE'		=> $AllowFE,
					'withdrawn'		=> $withdrawn,
					'timedOut'		=> $isTimedout,
					'secondsRemaining'	=> $secondsRemaining,
					'hasDeposited'		=> $hasDeposited,
					'hasPaid'		=> $hasPaid,
					'hadFeeBump'		=> $hadFeeBump,
					'acceptDate'		=> date('j M Y', strtotime($acceptDate)),
					'paymentMethod'	=> [
						'ID' => $paymentMethodID,
						'cryptocurrency' => $cryptocurrency,
						'color' => $cryptocurrencyColor
					],
					'priceBreakdown'	=> $priceBreakdown,
					'unshipAllowed'		=> $unshipAllowed,
					'canExtendPaymentWindow' => $canExtendPaymentWindow,
					'buyerExtendedPublicKey'	=> $buyerExtendedPublicKey,
					'vendorExtendedPublicKey'	=> $vendorExtendedPublicKey,
					'isSegwit'		=> $isSegwit
				);
			} else
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'No transactions found',
					'Group' => 'Transactions',
					'Anchor' => false,
					'Design' => array(
						'Color' => 'yellow',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_TRIANGLE')
					)
				);
		}
		
		return false;
	}
	
	public function fetchListing(
		$listingID,
		$hideInactive = FALSE,
		$cryptocurrencyID = FALSE
	){
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		
		if (
			$listings = $this->db->qSelect(
				"
					SELECT
						`User`.`ID` AS vendorID,
						`User`.`Alias` AS vendorAlias,
						`User`.`SellCount` AS vendorSales,
						(
							SELECT	AVG(`Transaction_Rating`.`Rating_Vendor`)
							FROM	`Transaction_Rating`
							WHERE
								`Transaction_Rating`.`VendorID` = `User`.`ID` AND
								`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
						) vendorRating,
						LEAST(
							(
								SELECT	COUNT(DISTINCT `Transaction_Rating`.`ID`)
								FROM	`Transaction_Rating`
								WHERE
									`Transaction_Rating`.`VendorID` = `User`.`ID` AND
									`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
							),
							IFNULL(
								`User`.`MaxVisibleRatings`,
								" . MAX_VISIBLE_RATINGS_DEFAULT . "
							)
						) vendorRatingCount,
						(
							SELECT	COUNT(DISTINCT Transaction_Comment.`ID`)
							FROM	`Transaction_Rating` Transaction_Comment
							WHERE
								`User`.`ID` = Transaction_Comment.`VendorID` AND
								Transaction_Comment.`Content` IS NOT NULL
						) AS vendorCommentCount,
						`Listing`.`Name` AS name,
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) image,
						`Listing`.`Price`/`Currency`.`1EUR` AS price,
						(
							SELECT
								AVG(Listing_Rating.`Rating_Vendor`)
							FROM
								`Transaction_Rating` Listing_Rating
							LEFT JOIN
								`Listing_Group` LG2 ON
									Listing_Rating.`ListingID` = LG2.`ListingID`
							WHERE
								Listing_Rating.`Rating_Vendor` IS NOT NULL AND
								(
									Listing_Rating.`ListingID` = `Listing`.`ID` OR
									LG2.`GroupID` = `Listing_Group`.`GroupID`
								)
						) AS rating,
						LEAST(
							(
								SELECT
									COUNT(DISTINCT Listing_Rating.`ID`)
								FROM
									`Transaction_Rating` Listing_Rating
								LEFT JOIN
									`Listing_Group` LG2 ON
										Listing_Rating.`ListingID` = LG2.`ListingID`
								WHERE
									Listing_Rating.`Rating_Vendor` IS NOT NULL AND
									(
										Listing_Rating.`ListingID` = `Listing`.`ID` OR
										LG2.`GroupID` = `Listing_Group`.`GroupID`
									)
							),
							IFNULL(
								`User`.`MaxVisibleRatings`,
								" . MAX_VISIBLE_RATINGS_DEFAULT . "
							)
						) AS ratingCount,
						`Unit`.`Name_Singular` AS unit_singular,
						`Unit`.`Name_Plural` AS unit_plural,
						`Unit`.`Abbreviation` AS unit_abbreviation,
						`Listing`.`Quantity` AS quantity,
						`Listing`.`Quantity_Left` AS stock,
						`Listing`.`Quantity_Minimum`
					FROM
						`Listing`
					INNER JOIN	`User`
						ON `Listing`.`VendorID` = `User`.`ID`
					LEFT JOIN
						`Listing_Image` ON
							`Listing_Image`.`ListingID` = `Listing`.`ID` AND
							`Listing_Image`.`Primary` = TRUE
					LEFT JOIN
						`Image` ON
							`Listing_Image`.`ImageID` = `Image`.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					INNER JOIN	`Currency`
						ON `Listing`.`CurrencyID` = `Currency`.`ID`
					INNER JOIN	`Unit`
						ON	`Listing`.`UnitID` = `Unit`.`ID`
					WHERE
						`Listing`.`ID` = ?
					" . (
						$hideInactive
							? '	AND `Listing`.`Inactive` = FALSE
								AND	`Listing`.`Approved` = TRUE	'
							:	FALSE
					) . "
					LIMIT 1
				",
				'i',
				[$listingID]
			)
		)
			return array_map(
				function($array) use ($cryptocurrency){
					if ($array['exceededMaximumVisibleRatings_vendor'] = $array['vendorRatingCount'] > MAX_VISIBLE_INDIVIDUAL_RATINGS)
						$array['vendorRatingCount'] = floor($array['vendorRatingCount'] / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
						
					if ($array['exceededMaximumVisibleRatings_listing'] = $array['ratingCount'] > MAX_VISIBLE_INDIVIDUAL_RATINGS)
						$array['ratingCount'] = floor($array['ratingCount'] / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
					
					return array_merge(
						$array,
						[
							'price'		=> NXS::formatPrice($this->User->Currency, $array['price']),
							'price_crypto'	=> $cryptocurrency->formatPrice($array['price']),
							'price_unit'	=> $array['quantity'] > 1 ? ENTITY_TILDE . ' ' . $cryptocurrency->formatPrice($array['price'] / $array['quantity']) . ' / ' . '<strong>'.$array['unit_singular'].'</strong>' : false,
							'image'		=> NXS::getPictureVariant($array['image'], IMAGE_THUMBNAIL_SUFFIX)
						]
					);
				},
				$listings
			)[0];
		
		return false;
	}
	
	public function fetchShippingInfo($listingID, $cryptocurrencyID) {
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		
		list(
			$countryCount,
			$shippingDestinations
		) = array_map(
			function($array){
				return array(
					$array['one'],
					$array['two']
				);
			},
			$this->db->qSelect(
				"
					SELECT
						(
							SELECT	COUNT(`Country`.`ID`)
							FROM	`Country`
						) AS one,
						(
							SELECT	COUNT(`Listing_Country`.`CountryID`)
							FROM	`Listing_Country`
							WHERE	`ListingID` = ?
						) AS two
					LIMIT	1
				",
				'i',
				array($listingID)
			)
		)[0];
		
		if(
			$onlyShippingCountries = $this->db->qSelect(
				"
					SELECT
						`Country`.`Name` AS name
					FROM
						`Country`
					WHERE
						`ID` IN (
							SELECT	`CountryID`
							FROM	`Listing_Country`
							WHERE	`ListingID` = ?
						)
					ORDER BY
						`Name` ASC
				",
				'i',
				array($listingID)
			)
		)
			$onlyShippingCountries = array_map(
				function($array){
					return $array['name'];
				},
				$onlyShippingCountries
			);
		
		if(
			$noShippingCountries = $this->db->qSelect(
				"
					SELECT
						`Country`.`Name` AS name
					FROM
						`Country`
					WHERE
						`ID` NOT IN (
							SELECT	`CountryID`
							FROM	`Listing_Country`
							WHERE	`ListingID` = ?
						)
					ORDER BY
						`Name` ASC
				",
				'i',
				array($listingID)
			)
		)
			$noShippingCountries = array_map(
				function($array){
					return $array['name'];
				},
				$noShippingCountries
			);
		
		$shippingOptions = array_map(
			function($array) use ($cryptocurrency){
				return array_merge(
					$array,
					array(
						'price'		=> NXS::formatPrice($this->User->Currency, $array['price']),
						'price_crypto'	=> $cryptocurrency->formatPrice($array['price'])
					)
				);
			},
			$this->db->qSelect(
				"
					SELECT
						`ListingShipping`.`ID` as ID,
						`ListingShipping`.`Name` as name,
						`ListingShipping`.`Price`/`Currency`.`1EUR` as price
					FROM
						`Listing_Shipping`
					INNER JOIN	`ListingShipping`
						ON	`Listing_Shipping`.`ShippingID` = `ListingShipping`.`ID`
					INNER JOIN
						`Currency` ON `ListingShipping`.`CurrencyID` = `Currency`.`ID`
					WHERE
						`Listing_Shipping`.`ListingID` = ?
				",
				'i',
				array($listingID)
			)
		);
		
		return [
			'countryCount'		=> $countryCount,
			'shippingDestinations'	=> $shippingDestinations,
			'onlyShippingCountries'	=> $onlyShippingCountries,
			'noShippingCountries'	=> $noShippingCountries,
			'shippingOptions'	=> $shippingOptions
		];
	}
	
	public function fetchVendor($vendorID, $transactionID = FALSE){
		return $this->db->qSelect(
			"
				SELECT
					IF(
						? != FALSE,
						(
							SELECT
								`Policy`
							FROM
								`Transaction`
							WHERE
								`ID` = ?
						),
						(
							SELECT
								`User_Section`.`HTML`
							FROM
								`User_Section`
							WHERE
								`User_Section`.`VendorID` = ?
							AND	`User_Section`.`Type` = 'policy'
						)
					) as policy
			",
			'iii',
			array(
				$transactionID,
				$transactionID,
				$vendorID

			)
		)[0];
	}
	
	public function fetchListingPaymentMethods($listingID, $cryptocurrencyID){
		if (
			$cryptocurrencies = $this->db->qSelect(
				"
					SELECT
						`Currency`.`ID`,
						`Currency`.`ISO`,
						`Currency`.`Name`,
						`Currency`.`Icon`
					FROM
						`Listing_PaymentMethod`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Currency` ON
							`PaymentMethod`.`CryptocurrencyID` = `Currency`.`ID`
					WHERE
						`Listing_PaymentMethod`.`ListingID` = ? AND
						`PaymentMethod`.`Enabled` = TRUE
				",
				'i',
				[$listingID]
			)
		)
			return array_map(
				function($cryptocurrency) use ($cryptocurrencyID){
					$cryptocurrency['selected'] = $cryptocurrencyID == $cryptocurrency['ID'];
					
					return $cryptocurrency;
				},
				$cryptocurrencies
			);
		
		return false;
	}
	
	private function _checkListing(
		$listingID,
		$cryptocurrencyISO = false,
		$shippingID = false,
		$quantity = false
	){
		$cryptocurrencyISO = $cryptocurrencyISO ?: (!empty($_POST['payment_method']) ? $_POST['payment_method'] : null);
		$shippingID = $shippingID ?: $_POST['shipping'];
		$quantity = $quantity ?: $_POST['quantity'];
		
		if (
			$listings = $this->db->qSelect(
				"
					SELECT
						`Listing`.`ID`,
						`Listing`.`Price`/`Currency`.`1EUR` price,
						`User`.`ID` vendorID,
						`Listing`.`Quantity_Left`,
						`Listing`.`Quantity`,
						`Listing`.`Escrow`,
						`User`.`Commission` vendorCommission,
						`ListingShipping`.`Price` / listingShippingCurrency.`1EUR` shippingPrice,
						`ListingShipping`.`Name` shippingName,
						`ListingShipping`.`TransitDays` shippingTransit,
						`PaymentMethod`.`ID` paymentMethodID,
						`PaymentMethod`.`CryptocurrencyID`
					FROM
						`Listing`
					INNER JOIN
						`User` ON
							`Listing`.`VendorID` = `User`.`ID`
					INNER JOIN
						`Currency` ON
							`Listing`.`CurrencyID` = `Currency`.`ID`
					INNER JOIN
						`Listing_PaymentMethod` ON
							`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
							(
								? IS NULL OR	
								`PaymentMethod`.`CryptocurrencyID` = (
									SELECT
										`ID`
									FROM
										`Currency`
									WHERE
										`ISO` = ?
								)
							)
					INNER JOIN
						`Listing_Shipping` ON
							`Listing`.`ID` = `Listing_Shipping`.`ListingID` AND
							`Listing_Shipping`.`ShippingID` = ?
					INNER JOIN
						`ListingShipping` ON
							`Listing_Shipping`.`ShippingID` = `ListingShipping`.`ID`
					INNER JOIN
						`Currency` listingShippingCurrency ON
							`ListingShipping`.`CurrencyID` = listingShippingCurrency.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`Listing`.`ID` = ? AND
						`Listing`.`VendorID` != ? AND
						`Listing`.`Quantity_Left` >= ? AND
						`Listing`.`Quantity_Minimum` <= ? AND
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`PaymentMethod`.`Enabled` = TRUE AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						)
					LIMIT
						1
				",
				'ssiiiii',
				[
					$cryptocurrencyISO,
					$cryptocurrencyISO,
					$shippingID,
					$listingID,
					$this->User->ID,
					$quantity,
					$quantity
				]
			)
		)
			return $listings[0];
		
		$_SESSION['temp_notifications'][] = [
			'Content' => 'This listing does not have sufficient stock',
			'Design' => [
				'Color' => 'red',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			]
		];
		return false;
	}
	
	private function _validateOrderForm(
		&$sessionPost,
		&$sessionFeedback
	){
		if (isset($_POST)){
			if (
				empty($_POST['quantity_specify']) &&
				!preg_match('/^[\d\.]+$/', $_POST['quantity_select'])
			)
				$sessionFeedback['quantity_select'] = "This is not a valid option.";
			
			if (
				!empty($_POST['quantity_specify']) &&
				!preg_match('/^[\d\.]+$/', $_POST['quantity_specify'])
			)
				$sessionFeedback['quantity_specify'] = "This must be a number.";
			
			if (
				!empty($_POST['shipping']) &&
				!preg_match('/^\d+$/', $_POST['shipping'])
			)
				$sessionFeedback['shipping'] = "This is not a valid option.";
			
			$_POST['quantity'] =
				empty($_POST['quantity_specify'])
					? $_POST['quantity_select']
					: ceil($_POST['quantity_specify'] / $_POST['quantity_per_unit']);
			
			foreach($_POST as $key => $value)
				$sessionPost[$key] = htmlspecialchars($value);
			
			return !isset($sessionFeedback);
		}
		
		return false;
	}
	
	private function _parseOrderForm(
		$listing,
		$quantity = false,
		$shippingID = false,
		$address = false
	){
		$quantity = $quantity ?: $_POST['quantity'];
		$shippingID = $shippingID ?: $_POST['shipping'];
		$address = $address ?: $_POST['address'];
		
		$priceBreakdown = $this->_calculatePriceBreakdown(
			$listing['CryptocurrencyID'],
			$quantity,
			$listing['price'],
			$listing['shippingPrice']
		);
		
		$order = [
			'Version'	=> 2,
			'ListingID'	=> $listing['ID'],
			'Quantity'	=> $quantity,
			'Price'		=> $priceBreakdown,
			'PromoID'	=> false,
			'PromoCode'	=> null,
			'Shipping'	=> $listing['shippingName'],
			'ShippingID'	=> $shippingID,
			'EscrowTimeout'	=> $listing['shippingTransit'],
			'Address'	=> htmlspecialchars($address),
			'Escrow'	=> $listing['Escrow'],
			'RenewedPaymentWindow' => false
		];
		
		list (
			$orderBuyer,
			$orderVendor
		) = $this->_encryptOrderDetails(
			$order,
			$address,
			$listing['vendorID']
		);
		
		return	[
				$order,
				$orderBuyer,
				$orderVendor
			];
	}
	
	private function _calculatePriceBreakdown(
		$cryptocurrencyID,
		$orderQuantity,
		$listingPrice,
		$shippingPrice
	){
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		
		$subtotal = $cryptocurrency->convertPrice($orderQuantity * $listingPrice);
		$priceBreakdown = [
			'unit_price' => $cryptocurrency->convertPrice($listingPrice),
			'raw_price' => $subtotal
		];
		
		if ($shippingPrice){
			$priceBreakdown['price_shipping'] = $cryptocurrency->convertPrice($shippingPrice);
			$subtotal += $priceBreakdown['price_shipping'];
		}
		
		$priceBreakdown['final_price'] = $cryptocurrency->formatValue($subtotal);
		
		return $priceBreakdown;
	}
	
	private function _encryptOrderDetails(
		$order,
		$address,
		$vendorID
	){
		$alreadyEncrypted = preg_match(REGEX_PGP_ENCRYPTED_MESSAGE, $address);
		if(
			$alreadyEncrypted == FALSE &&
			(
				list(
					$vendorPGP,
					$invalidPGP
				) = $this->User->Info(
					0,
					$vendorID,
					'PGP',
					'InvalidPGP'
				)
			) &&
			$vendorPGP &&
			$invalidPGP == FALSE
		){
			$vendorPGP = new PGP($vendorPGP);
			$encryptedAddress = $vendorPGP->qEncrypt(
				$_POST['address'],
				true,
				[
					'Comment' => 'AUTO-ENCRYPTED'
				]
			);
			$orderVendor = array_merge(
				$order,
				[
					'Address' => $encryptedAddress
				]
			);
		} else
			$orderVendor = $order;
		
		$rsa = new RSA();
		
		return [
			$rsa->qEncrypt(json_encode($order)),
			$rsa->qEncrypt(
				json_encode($orderVendor),
				$this->User->Info(0, $vendorID, 'PublicKey')
			)
		];
	}
	
	public function createTransaction($listingID) {
		if (
			$this->_validateOrderForm(
				$_SESSION['order_post'],
				$_SESSION['order_response']
			) &&
			$listing = $this->_checkListing($listingID)
		){
			list(
				$order,
				$orderBuyer,
				$orderVendor
			) = $this->_parseOrderForm($listing);
			
			if (
				$transactionID = $this->db->qQuery(
					"
						INSERT INTO
							`Transaction` (
								`Status`,
								`ListingID`,
								`BuyerID`,
								`Order_Vendor`,
								`Order_Buyer`,
								`PaymentMethodID`,
								`Value`,
								`Timeout`
							)
						VALUES (
							'pending deposit',
							?,
							?,
							?,
							?,
							?,
							?,
							NOW() + INTERVAL " . PENDING_CONFIRMATION_TIMEOUT_MINUTES . " MINUTE
						)
					",
					'iissid',
					[
						$listingID,
						$this->User->ID,
						$orderVendor,
						$orderBuyer,
						$listing['paymentMethodID'],
						$order['Price']['final_price']
					]
				)
			){
				unset(
					$_SESSION['order_post'],
					$_SESSION['order_response']
				);
				$this->insertTransactionIdentifier($transactionID);
				
				return $transactionID;
			} else {
				$this->User->Notifications->quick('FatalError', 'Transaction could not be started');
				return false;
			}
		}
		
		return false;
	}
	
	private function getListingPromoDiscount(
		$listingID,
		$code,
		$cryptocurrencyID
	){
		if(
			$promoCodes = $this->db->qSelect(
				"
					SELECT
						`Listing_PromoCode`.`ID`,
						`Currency`.`ID` IS NULL isPercentage,
						IF(
							`Currency`.`ID` IS NULL,
							`Listing_PromoCode`.`Discount`,
							`Listing_PromoCode`.`Discount` /
							`Currency`.`1EUR` *
							Cryptocurrency.`1EUR`
						) Discount
					FROM
						`Listing_PromoCode`
					LEFT JOIN
						`Currency` ON
							`Listing_PromoCode`.`CurrencyID` = `Currency`.`ID`
					LEFT JOIN
						`Transaction` ON
							`Transaction`.`BuyerID` = ? AND
							`Transaction`.`PromoCodeID` = `Listing_PromoCode`.`ID`
					LEFT JOIN
						`Currency` Cryptocurrency ON
							`Listing_PromoCode`.`CurrencyID` IS NOT NULL AND
							Cryptocurrency.`ID` = ?
					WHERE
						`Listing_PromoCode`.`ListingID` = ? AND
						`Listing_PromoCode`.`Code` = ? AND
						`Listing_PromoCode`.`Quantity` > 0 AND
						`Transaction`.`ID` IS NULL
				",
				'iiis',
				[
					$this->User->ID,
					$cryptocurrencyID,
					$listingID,
					$code
				]
			)
		)
			return $promoCodes[0];
		
		return false;
	}
	
	public function applyPromoCodeTransaction(
		$transactionID,
		$promoCode = false
	){
		if ($promoCode = $promoCode ?: $_POST['promo_code']){
			if (strlen($promoCode) > LISTING_PROMOTIONAL_CODE_LENGTH_MAX){
				$_SESSION['promoFeedback'] = TRANSACTION_APPLY_PROMOTIONAL_CODE_FEEDBACK_CODE_INVALID;
				return false;
			}
			
			$promoDiscount = false;
			if ($transaction = $this->fetchTransaction($transactionID)){
				$cryptocurrency = $transaction['paymentMethod']['cryptocurrency'];
				$promoDiscount = $this->getListingPromoDiscount(
					$transaction['listing_id'],
					$promoCode,
					$cryptocurrency->ID
				);
			}
				
			if ($promoDiscount){
				$order = $transaction['order'];
				
				$rawPrice = $finalPrice = $order['Price']['raw_price'];
				if (isset($order['Price']['price_shipping']))
					$finalPrice = $finalPrice + $order['Price']['price_shipping'];
					
				$isFree = false;	
				if ($promoDiscount['isPercentage']){
					$finalPrice = $finalPrice * (1 - $promoDiscount['Discount']/100);;
					$isFree = $promoDiscount['Discount'] == 100;
				} else
					$finalPrice = max(
						$finalPrice - $promoDiscount['Discount'],
						$cryptocurrency->smallestIncrement
					);
				
				
				$order['Price']['final_price'] = $cryptocurrency->formatValue($finalPrice);
				$order['PromoID'] = $promoDiscount['ID'];
				$order['PromoCode'] = strtoupper($promoCode);
				$order['Discount'] =
					$promoDiscount['isPercentage']
						? number_format($promoDiscount['Discount']) . ' %'
						: $cryptocurrency->formatValue(
							$promoDiscount['Discount'],
							true
						);
				
				if ($isFree){
					if ($transaction['order']['Quantity'] == 1)
						$this->makeTransactionFree($transactionID);
					else {
						$_SESSION['promoFeedback'] = TRANSACTION_APPLY_PROMOTIONAL_CODE_FEEDBACK_CODE_INVALID;
						return false;
					}	
				}
				
				return	$this->updateTransactionOrder(
						$transactionID,
						$cryptocurrency,
						$transaction['vendor_id'],
						$order
					) &&
					$this->decrementPromoQuantity($promoDiscount['ID']);
			}
		}
		
		$_SESSION['promoFeedback'] = TRANSACTION_APPLY_PROMOTIONAL_CODE_FEEDBACK_CODE_INVALID;
		return false;
	}
	
	public function updateTransaction($transactionIdentifier){
		if (!empty($_POST)){
			$transactionID = $this->getTransactionID($transactionIdentifier);
			
			if (
				$_POST['transaction_options-' . $transactionIdentifier] &&
				is_array($_POST['transaction_options-' . $transactionIdentifier])
			)
				foreach ($_POST['transaction_options-' . $transactionIdentifier] as $transactionOption){
					switch ($transactionOption){
						case 'respond':
							switch ($_POST['respond_transaction-' . $transactionIdentifier]){
								case 'accept':
									return $this->acceptOrder($transactionID);
								case 'reject':
									return $this->rejectOrder($transactionID);
							}
							break;
						case 'mark_shipped':
							if (
								(
									isset($_POST['mark_shipped-' . $transactionIdentifier]) &&
									!isset($_POST['mark_shipped-' . $transactionIdentifier . '-unship'])
								) ||
								(
									isset($_POST['mark_shipped-' . $transactionIdentifier . '-unship']) &&
									!isset($_POST['mark_shipped-' . $transactionIdentifier])
								)
							)
								return $this->toggleTransactionShipped($transactionID);
						case 'rate_transaction':
							switch ($_POST['rate_transaction-' . $transactionIdentifier]){
								case 'negative':
									$_POST['overall'] = 0;
									break;
								case 'positive':
									$_POST['overall'] = 5;
									break;
								default:
									break 2;
							}
						
							return $this->rateTransaction($transactionID);
					}
				}
		}
		
		return false;
	}
	
	private function makeTransactionFree($transactionID){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Deposited` = TRUE,
					`Paid` = TRUE,
					`Withdrawn` = TRUE,
					`Feedback_Buyer` = TRUE,
					`Feedback_Vendor` = TRUE
				WHERE
					`ID` = ?
			",
			'i',
			[$transactionID]
		);
	}
	
	private function getVendorCommission($transactionID){
		if(
			$vendors = $this->db->qSelect(
				"
					SELECT
						Vendor.`Commission`
					FROM
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					WHERE
						`Transaction`.`ID` = ?
				",
				'i',
				[$transactionID]
			)
		)
			return $vendors[0]['Commission'] / 1000;
		
		return MARKETPLACE_FEE;
	}
	
	private function updateTransactionOrder(
		$transactionID,
		$cryptocurrency,
		$vendorID,
		$order
	){
		$marketplaceFee = $this->getVendorCommission($transactionID);
		
		$finalPrice = $order['Price']['final_price'];
		$orderVendor = array_merge(
			$order,
			[
				'Price' => array_merge(
					$order['Price'],
					[
						'marketplace_fee' => $cryptocurrency->formatValue($finalPrice*$marketplaceFee),
						'transaction_fees' => $cryptocurrency->formatValue(MINER_FEE),
						'final_price' => $cryptocurrency->formatValue($finalPrice*(1 - $marketplaceFee) - MINER_FEE)
					]
				)
			]
		);
		
		$alreadyEncrypted = preg_match(REGEX_PGP_ENCRYPTED_MESSAGE, $order['Address']);
		list(
			$vendorPGP,
			$invalidPGP
		) = $this->User->Info(
			0,
			$vendorID,
			'PGP',
			'InvalidPGP'
		);
		if(
			$alreadyEncrypted == FALSE &&
			$vendorPGP &&
			$invalidPGP == FALSE
		){
			$vendorPGP = new PGP( $vendorPGP );
			$orderVendor = array_merge(
				$orderVendor,
				[
					'Address' => $vendorPGP->qEncrypt($order['Address'])
				]
			);
		}
		
		$rsa = new RSA();
		
		$order_encrypted_buyer = $rsa->qEncrypt( json_encode( $order ) );
		
		$order_encrypted_vendor = $rsa->qEncrypt( json_encode( $orderVendor ), $this->User->Info(0, $vendorID, 'PublicKey') );
		
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`Order_Vendor` = ?,
						`Order_Buyer` = ?,
						`Value` = ?,
						`PromoCodeID` = ?
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`BuyerID` = ?
				",
				'ssdiii',
				[
					$order_encrypted_vendor,
					$order_encrypted_buyer,
					$finalPrice,
					$order['PromoID'] ?: NULL,
					$transactionID,
					$this->User->ID
				]
			);
	}
	
	public function editTransaction($transactionID) {
		if (
			$this->_validateOrderForm(
				$_SESSION['order_post'],
				$_SESSION['order_response']
			) &&
			$listing = $this->_checkListing($_POST['listing_id'])
		){
			list(
				$order,
				$orderBuyer,
				$orderVendor
			) = $this->_parseOrderForm($listing);
			
			if (
				$rowsAffected = $this->db->qQuery(
					"
						UPDATE
							`Transaction`
						SET
							`Order_Vendor` = ?,
							`Order_Buyer` = ?,
							`PaymentMethodID` = ?,
							`Value` = ?,
							`Timeout` = NOW() + INTERVAL " . PENDING_CONFIRMATION_TIMEOUT_MINUTES . " MINUTE,
							`PromoCodeID` = NULL
						WHERE
							`Transaction`.`ID` = ? AND
							`Transaction`.`BuyerID` = ?
					",
					'ssidii',
					[
						$orderVendor,
						$orderBuyer,
						$listing['paymentMethodID'],
						$order['Price']['final_price'],
						$transactionID,
						$this->User->ID
					]
				)
			){
				unset(
					$_SESSION['order_post'],
					$_SESSION['order_response']
				);
				
				return true;
			} else {
				$this->User->Notifications->quick('FatalError', 'Transaction could not be edited');
				return false;
			}
		}
		
		return false;
	}
	
	/*public function deleteTransaction($transactionID){
		if( $transaction = $this->fetchTransaction($transactionID) ){
			
			$deposit_balance = isset($transaction['order']['DepositAddress']) ? $this->getAddressBalance($transaction['order']['DepositAddress'], 0) : 0;
			
			if( !$this->User->Attributes['BTCPublic'] && NXS::compareFloatNumbers($deposit_balance, 0, '>') ){
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'The deposit address has funds in it. Please retrieve the funds manually.',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
				return false;
			}
			
			if( $stmt_deleteTransaction = $this->db->prepare("
				DELETE
					`Transaction`.*
				FROM
					`Transaction`
				INNER JOIN	`Listing`
					ON	`Transaction`.`ListingID` = `Listing`.`ID`
				WHERE
					`Transaction`.`ID` = ?
				AND	`Transaction`.`BuyerID` = ?
				AND	`Transaction`.`RedeemScript` IS NULL
			") ){
				
				$stmt_deleteTransaction->bind_param('ii', $transactionID, $this->User->ID);
				
				return $stmt_deleteTransaction->execute();
				
			}
			
		}
		
	}*/
	
	private function _validateConfirmForm($cryptocurrency){
		// VALIDATE PUBLIC KEY
		if( $_POST['escrow_option'] !== 'on' )
			$_POST['signing_public_key'] = FALSE;
		
		if (
			!empty($_POST['signing_public_key']) &&
			//!NXS::validateCryptocurrencyPublicKey($_POST['signing_public_key'])
			!$this->validateXPUB($_POST['signing_public_key'])
		){
			$_SESSION['confirm_response']['failedStep'] = 1;
			$_SESSION['confirm_response']['signing_public_key'] = 'This does not appear to be a valid public key.';
			return false;
		}
		
		if (
			$_POST['escrow_option'] == 'on' &&
			empty($_POST['signing_public_key'])
		){
			$_SESSION['confirm_response']['failedStep'] = 1;
			$_SESSION['confirm_response']['signing_public_key'] = 'You cannot have escrow without a public key.';
			return false;
		}
		
		if(
			!isset($_POST['skip_return_address_validation']) &&
			(
				empty($_POST['return_address']) ||
				!$cryptocurrency->validateAddress($_POST['return_address'])
			)
		){
			$_SESSION['confirm_response']['failedStep'] = 2;
			$_SESSION['confirm_response']['return_address'] = 'Please enter a valid <strong>' . $cryptocurrency->name . '</strong> address.';
			return false;
		}
		
		return true;
	}
	
	private function _addPublicKeyIfEmpty(
		$cryptocurrencyID,
		$publicKey
	){
		return	$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`PaymentMethod` (
							`Enabled`,
							`UserID`,
							`CryptocurrencyID`,
							`PublicKey`
						)
					VALUES (
						TRUE,
						?,
						?,
						?
					)
				",
				'iis',
				[
					$this->User->ID,
					$cryptocurrencyID,
					$publicKey
				]
			);
	}
	
	private function clearExistingBuyerTransactionKeyIndices($buyerID, $cryptocurrencyID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					SET
						`BuyerKey` = NULL
					WHERE
						`Transaction`.`BuyerID` = ? AND
						`PaymentMethod`.`CryptocurrencyID` = ?
				",
				'ii',
				[
					$buyerID,
					$cryptocurrencyID
				]
			);
	}
	
	private function updateBuyerExtendedPublicKey($buyerID, $cryptocurrencyID, $extendedPublicKey){
		return	$this->db->qQuery(
				"
					INSERT INTO
						`PaymentMethod` (
							`Enabled`,
							`UserID`,
							`CryptocurrencyID`,
							`ExtendedPublicKey`,
							`Index`
						)
					VALUES (
						TRUE,
						?,
						?,
						?,
						0
					)
					ON DUPLICATE KEY
						UPDATE
							`ExtendedPublicKey` = ?
				",
				'iiss',
				[
					$buyerID,
					$cryptocurrencyID,
					$extendedPublicKey,
					$extendedPublicKey
				]
			);
			
		//$this->clearExistingBuyerTransactionKeyIndices($buyerID, $cryptocurrencyID);
		
		//return true;
	}
	
	private function updateTransactionSigningKeyIndices(
		$transactionID,
		$buyerID,
		$cryptocurrencyID,
		$buyerExtendedPublicKey
	){
		if ($buyerExtendedPublicKey)
			$this->updateBuyerExtendedPublicKey($buyerID, $cryptocurrencyID, $buyerExtendedPublicKey);
		
		if (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					SET
						`BuyerExtendedPublicKey` = IFNULL(
							?,
							`Transaction`.`BuyerExtendedPublicKey`
						),
						`VendorExtendedPublicKey` = `PaymentMethod`.`ExtendedPublicKey`,
						`BuyerKey` = IF(
							IFNULL(
								?,
								`Transaction`.`BuyerExtendedPublicKey`
							) IS NULL,
							NULL,
							IFNULL(
								(
									SELECT
										MIN(t2.`BuyerKey`) + 1
									FROM
										`Transaction` t2
									INNER JOIN
										`PaymentMethod` pm2 ON
											t2.`PaymentMethodID` = pm2.`ID`
									WHERE
										t2.`ID` != `Transaction`.`ID` AND
										t2.`BuyerID` = `Transaction`.`BuyerID` AND
										pm2.`CryptocurrencyID` = `PaymentMethod`.`CryptocurrencyID` AND
										t2.`BuyerExtendedPublicKey` = IFNULL(
											?,
											`Transaction`.`BuyerExtendedPublicKey`
										) AND
										NOT EXISTS (
											SELECT
												`Transaction`.`ID`
											FROM
												`Transaction` t3
											INNER JOIN
												`PaymentMethod` pm3 ON
													t3.`PaymentMethodID` = pm3.`ID`
											WHERE
												t3.`ID` != `Transaction`.`ID` AND
												t3.`BuyerID` = `Transaction`.`BuyerID` AND
												pm3.`CryptocurrencyID` = `PaymentMethod`.`CryptocurrencyID` AND
												t3.`BuyerExtendedPublicKey` = IFNULL(
													?,
													`Transaction`.`BuyerExtendedPublicKey`
												) AND
												t3.`BuyerKey` = t2.`BuyerKey` + 1
										)
								),
								0
							)
						),
						`VendorKey` = IFNULL(
							`VendorKey`,
							IFNULL(
								(
									SELECT
										MIN(t2.`VendorKey`) + 1
									FROM
										`Transaction` t2
									WHERE
										t2.`ID` != `Transaction`.`ID` AND
										t2.`PaymentMethodID` = `PaymentMethod`.`ID` AND
										t2.`VendorExtendedPublicKey` = `PaymentMethod`.`ExtendedPublicKey` AND
										NOT EXISTS (
											SELECT
												`ID`
											FROM
												`Transaction` t3
											WHERE
												t3.`ID` != `Transaction`.`ID` AND
												t3.`PaymentMethodID` = `PaymentMethod`.`ID` AND
												t3.`VendorExtendedPublicKey` = `PaymentMethod`.`ExtendedPublicKey` AND
												t3.`VendorKey` = t2.`VendorKey` + 1
										)
								),
								0
							)
						)
					WHERE
						`Transaction`.`ID` = ?
				",
				'ssssi',
				[
					$buyerExtendedPublicKey,
					$buyerExtendedPublicKey,
					$buyerExtendedPublicKey,
					$buyerExtendedPublicKey,
					$transactionID
				]
			)
		)
			return	true;	
					
		throw new Exception('92dj912dj9d');
	}
	
	private function getTransactionSigningKeys(
		$transactionID,
		$buyerID,
		$cryptocurrencyID,
		$buyerExtendedPublicKey = null
	){
		$buyerExtendedPublicKey = $buyerExtendedPublicKey ?: null;
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						`BuyerKey`,
						`VendorKey`,
						`VendorExtendedPublicKey`
					FROM
						`Transaction`
					WHERE
						`ID` = ? AND
						`VendorKey` IS NOT NULL AND
						`VendorExtendedPublicKey` IS NOT NULL AND
						(
							(
								? IS NULL AND
								`BuyerExtendedPublicKey` IS NULL
							) OR
							`BuyerExtendedPublicKey` = ? 
						) AND
						(
							`BuyerExtendedPublicKey` IS NULL OR
							`BuyerKey` IS NOT NULL
						)
				",
				'iss',
				[
					$transactionID,
					$buyerExtendedPublicKey,
					$buyerExtendedPublicKey
				]
			)
		){
			$transaction = $transactions[0];
			
			return [
				$transaction['BuyerKey'],
				$transaction['VendorKey'],
				$buyerExtendedPublicKey,
				$transaction['VendorExtendedPublicKey'],
			];
		}
		
		if (
			$this->updateTransactionSigningKeyIndices(
				$transactionID,
				$buyerID,
				$cryptocurrencyID,
				$buyerExtendedPublicKey
			)
		)
			return $this->getTransactionSigningKeys(...func_get_args());
		
		throw new Exception('xas09a');
	}
	
	public function confirmTransaction($transactionID){
		foreach ($_POST as $key => $value)
			$_SESSION['confirm_post'][$key] = htmlspecialchars($value);
		
		if (
			$stmt_getTransaction = $this->db->prepare("
				SELECT
					`Transaction`.`Order_Buyer`,
					`PaymentMethod`.`UserID`,
					`Transaction`.`Value`,
					`PaymentMethod`.`CryptocurrencyID`
				FROM
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				WHERE
					`Transaction`.`ID` = ? AND
					`Transaction`.`BuyerID` = ? AND
					`Transaction`.`Timeout` > NOW()
			")
		){
			$stmt_getTransaction->bind_param('ii', $transactionID, $this->User->ID);
			$stmt_getTransaction->execute();
			$stmt_getTransaction->store_result();
			
			if ($stmt_getTransaction->num_rows == 1){
				$stmt_getTransaction->bind_result(
					$encrypted_order,
					$vendor_id,
					$total_value,
					$cryptocurrencyID
				);
				$stmt_getTransaction->fetch();
				
				$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
				
				if (!$this->_validateConfirmForm($cryptocurrency))
					return false;
					
				list(
					$buyerKey,
					$vendorKey,
					$buyerExtendedPublicKey,
					$vendorExtendedPublicKey
				) = $this->getTransactionSigningKeys(
					$transactionID,
					$this->User->ID,
					$cryptocurrencyID,
					$_POST['signing_public_key']
				);
				
				if (
					empty($vendorExtendedPublicKey) ||
					!$this->validateXPUB($vendorExtendedPublicKey)
				){
					
					$_SESSION['temp_notifications'][] = array(
						'Content'	=> 	'Contact support. Vendor payment address is not available',
						'Design'	=> 	array(
							'Color'	=> 	'red',
							'Icon'	=> 	Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						),
						'Group'		=>	'Transactions',
					);
					return false;
				}

				$rsa = new RSA();
				
				$order = json_decode( $rsa->qDecrypt($encrypted_order), TRUE );
				
				$bip32key_marketplace = BIP32::build_key(
					BIP32::extended_private_to_public(
						array(
							SITE_BIP32_EXTENDED_PRIVATE_KEY,
							'm/0'
						)
					),
					$transactionID
				);
				$publicKey_marketplace = BIP32::extract_public_key($bip32key_marketplace);
				
				$publicKey_vendor = NXS::deriveBIP32PublicKey(
					$vendorKey,
					$vendorExtendedPublicKey,
					SIGNING_ACCOUNT_INDEX . '/'
				);
				
				$publicKey_buyer = !empty($_POST['signing_public_key'])
					? NXS::deriveBIP32PublicKey(
						$buyerKey,
						$_POST['signing_public_key'],
						SIGNING_ACCOUNT_INDEX . '/'
					)
					: false;
				
				$escrow_enabled = $_POST['escrow_option'] == 'on' && $publicKey_buyer;
				
				$refundAddress = $_POST['return_address'];
				
				$vendorRSA = $this->User->Info(0, $vendor_id, 'PublicKey');

				$new_order = $rsa->qEncrypt( json_encode( $order ) );
				$signed_transactions_encrypted_vendor = $rsa->qEncrypt( json_encode($next_tx),  $vendorRSA);
				$signed_transactions_encrypted_buyer = $rsa->qEncrypt( json_encode($next_tx) );
				$signed_transactions_encrypted_site = $rsa->qEncrypt( json_encode($next_tx), SITE_RSA_PUBLIC_KEY );
				
				if ($escrow_enabled){
					// CREATE MULTISIG ADDRESS
					$publicKeys = [
						$publicKey_vendor,
						$publicKey_marketplace,
						$publicKey_buyer
					];
					
					//$multisig = RawTransaction::create_multisig(2, $publicKeys);
					$multisig = $cryptocurrency->createMultisigAddress(
						2,
						$publicKeys,
						true
					);
					
					$deposit_address = $multisig['address'];
					$next_tx = [
						'PublicKey_Buyer'	=> $publicKey_buyer,
						'PublicKey_Vendor'	=> $publicKey_vendor,
						'PublicKey_Marketplace'	=> $publicKey_marketplace,
						'BuyerTransacted'	=> $this->User->Attributes['TotalTransacted'],
						'return_address'	=> $refundAddress
					];
					
					$vendorRSA = $this->User->Info(0, $vendor_id, 'PublicKey');
					
					$order['DepositAddress'] = $deposit_address;
					$new_order = $rsa->qEncrypt( json_encode( $order ) );
					
					$signed_transactions_encrypted_vendor = $rsa->qEncrypt( json_encode($next_tx),  $vendorRSA);
					$signed_transactions_encrypted_buyer = $rsa->qEncrypt( json_encode($next_tx) );
					$signed_transactions_encrypted_site = $rsa->qEncrypt( json_encode($next_tx), SITE_RSA_PUBLIC_KEY );
					
					$this->db->qQuery(
						"
							UPDATE
								`Transaction`
							INNER JOIN	`Listing`
								ON	`Transaction`.`ListingID` = `Listing`.`ID`
							INNER JOIN	`User` Vendor
								ON	`Listing`.`VendorID` = Vendor.`ID`
							SET
								`Timeout`		= NOW() + INTERVAL " . PENDING_DEPOSIT_TIMEOUT_MINUTES . " MINUTE,
								`Order_Buyer`		= ?,
								`MultiSigAddress`	= ?,
								`RedeemScript`		= ?,
								`NextTX_Vendor`		= ?,
								`NextTX_Buyer`		= ?,
								`NextTX_Site`		= ?,
								`SigneeCount`		= '3',
								`Transaction`.`Escrow`	= TRUE,
								`Transaction`.`Segwit`	= TRUE,
								`Policy`		= (
									SELECT	`User_Section`.`Content`
									FROM	`User_Section`
									WHERE
										`User_Section`.`VendorID` = `Listing`.`VendorID`
									AND	`User_Section`.`Type` = 'policy'
								),
								`Transaction`.`RefundAddress`	= ?,
								`Transaction`.`Quantity` = ?
							WHERE
								`Transaction`.`ID` = ?
							AND	`Transaction`.`BuyerID` = ?
						",
						'sssssssiii',
						array(
							$new_order,
							$multisig['address'],
							// $deposit_address,
							$multisig['redeemScript'],
							$signed_transactions_encrypted_vendor,
							$signed_transactions_encrypted_buyer,
							$signed_transactions_encrypted_site,
							$refundAddress,
							$order['Quantity'],
							$transactionID,
							$this->User->ID
						)
					);
					/*$this->_addPublicKeyIfEmpty(
						$cryptocurrencyID,
						$publicKey_buyer
					);*/
				} else {
					### Direct-Pay Transaction
					
					$publicKeys = array(
						$publicKey_vendor,
						$publicKey_marketplace
					);
					$multisig = $cryptocurrency->createMultisigAddress(1, $publicKeys, true);
					$next_tx = [
						'PublicKey_Vendor'	=> $publicKey_vendor,
						'PublicKey_Marketplace'	=> $publicKey_marketplace,
						'BuyerTransacted'	=> $this->User->Attributes['TotalTransacted'],
						'return_address'	=> $refundAddress
					];
					$signeeCount = 2;
					
					$deposit_address = $multisig['address'];
					
					$next_tx['DepositAddress'] = $deposit_address;
					
					$vendorRSA = $this->User->Info(0, $vendor_id, 'PublicKey');
					
					$order['DepositAddress'] = $deposit_address;
					$new_order = $rsa->qEncrypt( json_encode( $order ) );
					
					$signed_transactions_encrypted_vendor = $rsa->qEncrypt( json_encode($next_tx),  $vendorRSA);
					$signed_transactions_encrypted_buyer = $rsa->qEncrypt( json_encode($next_tx) );
					$signed_transactions_encrypted_site = $rsa->qEncrypt( json_encode($next_tx), SITE_RSA_PUBLIC_KEY );
					
					$this->db->qQuery(
						"
							UPDATE
								`Transaction`
							INNER JOIN	`Listing`
								ON	`Transaction`.`ListingID` = `Listing`.`ID`
							INNER JOIN	`User` Vendor
								ON	`Listing`.`VendorID` = Vendor.`ID`
							SET
								`Timeout`		= NOW() + INTERVAL " . PENDING_DEPOSIT_TIMEOUT_MINUTES . " MINUTE,
								`Order_Buyer`		= ?,
								`MultiSigAddress`	= ?,
								`RedeemScript`		= ?,
								`NextTX_Vendor`		= ?,
								`NextTX_Buyer`		= ?,
								`NextTX_Site`		= ?,
								`SigneeCount`		= '2',
								`Transaction`.`Escrow`	= FALSE,
								`Transaction`.`Segwit`	= TRUE,
								`Policy`		= (
									SELECT	`User_Section`.`Content`
									FROM	`User_Section`
									WHERE
										`User_Section`.`VendorID` = `Listing`.`VendorID`
									AND	`User_Section`.`Type` = 'policy'
								),
								`Transaction`.`RefundAddress`	= ?,
								`Transaction`.`Quantity`	= ?
							WHERE
								`Transaction`.`ID`	= ?
							AND	`Transaction`.`BuyerID`	= ?
						",
						'sssssssiii',
						array(
							$new_order,
							$deposit_address,
							$multisig['redeemScript'],
							//$deposit_address,
							$signed_transactions_encrypted_vendor,
							$signed_transactions_encrypted_buyer,
							$signed_transactions_encrypted_site,
							$refundAddress,
							$order['Quantity'],
							$transactionID,
							$this->User->ID
						)
					);
				}
				
				//$this->decrementQuantityLeft($transactionID, $order['Quantity']);
				
				unset($_SESSION['confirm_post'], $_SESSION['confirm_response']);
				return true;
			} else
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'Transaction could not be found',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
		}
		
		return false;
	}
	
	/*public function _validatePayment($TXID, $address, $amount){
		$TXHEX = $this->getTXHEXfromID($TXID);
		
		if( empty($TXHEX) )
			return false;
		
		try {
			$tx = RawTransaction::decode($TXHEX);
		} catch (Exception $e) {
			return false;
		}
		
		// Amount in Satoshis
		$amount = $amount * 100000000;
		
		foreach ($tx['vout'] as $vout){
			if (
				$vout['scriptPubKey']['addresses'][0] == $address &&
				NXS::compareFloatNumbers($vout['value'], $amount, '>=')
			)
				return true;
		}
		
		return false;
	}*/
	
	private function rejectOrder($transactionID){
		if (
			$affected_rows = $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							incrementedNotification.`UserID` IN(
								`Listing`.`VendorID`,
								`Transaction`.`BuyerID`
							) AND
							incrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . "'
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `Listing`.`VendorID` AND
							decrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT . "'
					SET
						`Transaction`.`Status` = 'rejected',
						`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						`Listing`.`Quantity_Left` = `Listing`.`Quantity_Left` + `Transaction`.`Quantity`,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Transaction`.`ID` = ? AND
						`Listing`.`VendorID` = ? AND
						`Status` = 'pending accept'
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			)
		){
			$this->db->incrementStatistic('rejects', 1);
			$this->insertTransactionEvent(
				$transactionID,
				TRANSACTION_EVENTS_FLAG_REJECTED
			);
			
			list(
				$buyerID,
				$vendorID
			) = $this->getTransactionBuyerVendorIDs($transactionID);
			$this->toggleTransactionStatusChanged(
				$transactionID,
				$buyerID
			);
			
			return true;
		}
		
		return false;
	}
	
	private function acceptOrder(
		$transactionID,
		$transaction = false
	){
		$transaction = $transaction ?: $this->fetchTransaction($transactionID);
		$cryptocurrency = $transaction['paymentMethod']['cryptocurrency'];
		
		$rsa = new RSA();
		if (
			$transaction['escrow_enabled'] &&
			NXS::compareFloatNumbers($transaction['value'], 0, '>')
		){
			// Autofinalize TX Creation
			if (
				list(
					$inputsValue,
					$inputs
				) = $this->getUnspentOutputs(
					$cryptocurrency->ID,
					$transaction['multisig_address'],
					ADVISED_TX_CONFIRMATIONS_ACCEPT,
					FALSE,
					$transaction['redeem_script']
				)
			){
				$address = $this->getVendorBIP32AddressForTransaction($transactionID);
				
				$minerFee = $this->estimateDynamicFee(
					$cryptocurrency,
					$inputs,
					[$address],
					$inputsValue,
					$transaction['redeem_script'],
					($transaction['escrow_enabled'] ? 2 : 1),
					$this->getCryptocurrencyFeePerKilobyte(
						CRYPTOCURRENCIES_FEE_LEVEL_FASTEST,
						$cryptocurrency->ID
					)
				);
				
				if (NXS::compareFloatNumbers($inputsValue, $transaction['value'], '<')){
					$_SESSION['temp_notifications'][] = array(
						'Content' => 'The funds in this transaction have not yet been confirmed. Please wait for ' . NXS::formatNumber(ADVISED_TX_CONFIRMATIONS_ACCEPT) . ' confirmation' . (ADVISED_TX_CONFIRMATIONS_ACCEPT == 1 ? FALSE : 's') . ' and try again',
						'Design' => array(
							'Color' => 'red',
							'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						),
						'Group'	=> 'Transactions'
					);
					return false;
				}
			
				$value_vendor = $cryptocurrency->parseValue($inputsValue - $minerFee, true);
			
				$outputs = [
					$address => $value_vendor,
				];
			
				$timeLock = strtotime("+" . AUTO_FINALIZE_VENDOR_DAYS . ' days');
			
				$privateKey_wif_site = $this->getBIP32PrivateKeyWIF(
					$transactionID,
					$cryptocurrency->prefixPublic
				);
				
				$rawTransaction = new ElectrumTransaction(
					$cryptocurrency,
					$transaction['redeem_script'],
					$inputs,
					$outputs,
					$transaction['isSegwit']
				);
				
				if ($extendedPublicKey = $this->getTransactionExtendedPublicKey($transactionID))
					$rawTransaction->addExtendedPublicKey(...$extendedPublicKey);
				
				$signedTransaction = ['hex' => $rawTransaction->addLocktime($timeLock)->sign($privateKey_wif_site)];
				
				$next_tx = array_merge(
					$transaction['next_tx'],
					[
						'AutoFinalize' => $signedTransaction
					]
				);
				$next_tx = $rsa->qEncrypt(json_encode($next_tx));
			} else {
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'UTXO retrieval failed. Please try again shortly.',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
			
				return false;			
			}
		} else
			$next_tx = $rsa->qEncrypt(json_encode($transaction['next_tx']));
		
		if (
			$affected_rows = $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					SET
						`Transaction`.`Status` = IF(`Transaction`.`Escrow` = TRUE, 'in transit', 'pending feedback'),
						`Timeout` = IF(`Transaction`.`Escrow` = TRUE, NOW() + INTERVAL ? DAY, NOW() + INTERVAL " . PENDING_FEEDBACK_DAYS . " DAY),
						`NextTX_Vendor` = ?,
						Buyer.`BuyCount` = IF(
							`Transaction`.`Escrow` = FALSE,
							Buyer.`BuyCount` + 1,
							Buyer.`BuyCount`
						),
						Vendor.`SellCount` = IF(
							`Transaction`.`Escrow` = FALSE,
							Vendor.`SellCount` + 1,
							Vendor.`SellCount`
						)
					WHERE
						`Transaction`.`ID` = ?
					AND	`Listing`.`VendorID` = ?
					AND	`Status` = 'pending accept'
				",
				'isii',
				array(
					$transaction['order']['EscrowTimeout'],
					$next_tx,
					$transactionID,
					$this->User->ID
				)
			)
		){
			$decrementNotifications = [USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT];
			if ($transaction['escrow_enabled'])
				$decrementNotifications[] = USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS;
			
			$this->User->incrementUserNotification(
				$decrementNotifications,
				-1
			);
			
			if (!$transaction['escrow_enabled']){
				$this->db->incrementStatistic('finalizations', 1);
				$this->User->incrementUserNotification(
					USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK,
					1,
					$transaction['buyer_id']
				);
				
				if (!$transaction['withdrawn'])
					$this->User->incrementUserNotification(
						USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL,
						1,
						$transaction['vendor_id']
					);
			}
		
			$this->insertTransactionEvent(
				$transactionID,
				TRANSACTION_EVENTS_FLAG_ACCEPTED
			);
			
			$this->toggleTransactionStatusChanged(
				$transactionID,
				$transaction['buyer_id']
			);
			
			return true;
		}
		
		return false;
	}
	
	public function respondTransaction(
		$transactionID,
		&$isEscrow = FALSE
	){
		$transaction = $this->fetchTransaction($transactionID);
		$isEscrow = $transaction['escrow_enabled'];
		
		switch($_POST['action']){
			case 'reject_order':
			case 'reject_order_later':
				$this->rejectOrder($transactionID);
				return 'rejected';
			break;
			case 'accept_order':
				$this->acceptOrder($transactionID);
				return 'fulfill';
			break;
		}
	}
	
	private function decrementPromoQuantity($promoID){
		if($promoID)
			return $this->db->qQuery(
				"
					UPDATE
						`Listing_PromoCode`
					SET
						`Quantity` = GREATEST(
							" . LISTING_PROMOTIONAL_CODE_QUANTITY_MIN . ",
							CAST(`Listing_PromoCode`.`Quantity` AS SIGNED) - 1
						)
					WHERE
						`ID` = ?
				",
				'i',
				[$promoID]
			);
			
		return false;
	}
	
	private function addReturnAddress($transactionID, $returnAddress, &$isEscrow = FALSE){
		if(
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`NextTX_Buyer`,
						`Listing`.`VendorID`,
						`Transaction`.`Escrow`
					FROM
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`BuyerID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			)
		){
			$transaction = $transactions[0];
			$isEscrow = $transaction['Escrow'];
			
			$rsa = new RSA();	
			
			$nextTX = json_decode(
				$rsa->qDecrypt($transaction['NextTX_Buyer']),
				TRUE
			);
			$nextTX['return_address'] = $returnAddress;
			
			$nextTX_encrypted_buyer = $rsa->qEncrypt(
				json_encode($nextTX)
			);
			$nextTX_encrypted_vendor = $rsa->qEncrypt(
				json_encode($nextTX),
				$this->User->Info(0, $transaction['VendorID'], 'PublicKey')
			);
			$nextTX_encrypted_site = $rsa->qEncrypt(
				json_encode($signed_transactions),
				SITE_RSA_PUBLIC_KEY
			);
			
			return $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`NextTX_Buyer` = ?,
						`NextTX_Vendor` = ?,
						`NextTX_Site` = ?
					WHERE
						`ID` = ?
				",
				'sssi',
				[
					$nextTX_encrypted_buyer,
					$nextTX_encrypted_vendor,
					$nextTX_encrypted_site,
					$transactionID
				]
			);
		}
		
		return FALSE;
	}
	
	public function refundTransaction($transactionID){
		foreach($_POST as $key => $value)
			$_SESSION['respond_post'][ $key ] = htmlspecialchars($value);
		
		if(
			$affected_rows = $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							`Transaction`.`Status` = 'in dispute' AND
							(
								(
									decrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE . "' AND
									decrementedNotification.`UserID` IN(
										`Listing`.`VendorID`,
										`Transaction`.`BuyerID`
									)
								) OR
								(
									decrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "' AND
									decrementedNotification.`UserID` = `Listing`.`VendorID`
								)
							)
					SET
						`Transaction`.`Status` = 'refunded',
						`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Transaction`.`ID` = ? AND
						(
							`Listing`.`VendorID` = thisUser.`ID` OR
							thisUser.`ID` = `Transaction`.`MediatorID`
						) AND
						`Transaction`.`Status` IN(
							'in transit',
							'in dispute'
						)
				",
				'ii',
				[
					$this->User->ID,
					$transactionID
				]
			)
		){
			$this->db->incrementStatistic('refunds', 1);
			$this->insertTransactionEvent(
				$transactionID,
				TRANSACTION_EVENTS_FLAG_REFUNDED
			);
		
			list(
				$buyerID,
				$vendorID
			) = $this->getTransactionBuyerVendorIDs($transactionID);
			$this->User->incrementUserNotification(
				[
					USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
					USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL
				],
				1,
				$vendorID
			);
			$this->User->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
				1,
				$buyerID
			);
			
			return true;
		}
		
		return false;
	}
	
	private function _insertMarketplaceAddressKeyIndex($transactionID){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Transaction_MarketplaceAddressKeyIndex` (
						`TransactionID`,
						`ExtendedPublicKeyID`
					)
				VALUES (
					?,
					(
						SELECT
							MAX(`BIP32`.`ID`)
						FROM
							`BIP32`
						WHERE
							`BIP32`.`CryptocurrencyID` = (
								SELECT
									`PaymentMethod`.`CryptocurrencyID`
								FROM
									`Transaction`
								INNER JOIN
									`PaymentMethod` ON
										`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
								WHERE
									`Transaction`.`ID` = ?
							)
					)
				)
			",
			'ii',
			[
				$transactionID,
				$transactionID
			]
		);
	}
	
	public function finalizeTransaction($transactionID){
		$transaction = $this->fetchTransaction($transactionID);
		
		$affected_rows = $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				INNER JOIN
					`User` thisUser ON
						thisUser.`ID` = ?
				INNER JOIN
					`User` Buyer ON
						`Transaction`.`BuyerID` = Buyer.`ID`
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
				LEFT JOIN
					`User_Notification` decrementedNotification ON
						`Transaction`.`Status` = 'in dispute' AND
						(
							(
								decrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE . "' AND
								decrementedNotification.`UserID` IN(
									Vendor.`ID`,
									Buyer.`ID`
								)
							) OR
							(
								decrementedNotification.`TypeID` = '" . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "' AND
								decrementedNotification.`UserID` = Vendor.`ID`
							)
						)
				SET
					`Status` = 'pending feedback',
					Buyer.`BuyCount` = Buyer.`BuyCount` + 1,
					Vendor.`SellCount` = Vendor.`SellCount` + 1,
					`Timeout` = NOW() + INTERVAL " . PENDING_FEEDBACK_DAYS . " DAY,
					decrementedNotification.`Value` = GREATEST(
						0,
						CAST(decrementedNotification.`Value` AS SIGNED) - 1
					)
				WHERE
					`Transaction`.`ID` = ? AND
					(
						`Transaction`.`BuyerID` = thisUser.`ID` OR
						thisUser.`Moderator` = TRUE
					) AND
					`Transaction`.`Status` IN ('in transit', 'expired', 'in dispute')
			",
			'ii',
			[
				$this->User->ID,
				$transactionID
			]
		);
		
		if ($affected_rows){
			$this->db->incrementStatistic('finalizations', 1);
		
			$this->insertTransactionEvent(
				$transactionID,
				TRANSACTION_EVENTS_FLAG_FINALIZED
			);
		
			list(
				$buyerID,
				$vendorID
			) = $this->getTransactionBuyerVendorIDs($transactionID);
			$this->User->incrementUserNotification(
				USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK,
				1,
				$buyerID
			);
			$this->User->incrementUserNotification(
				[
					USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL,
					USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS
				],
				1,
				$vendorID
			);
		}
		
		return true;
	}
	
	public function _calculateMinimumMarketOutput(
		$cryptocurrency,
		$feeLevel = CRYPTOCURRENCIES_FEE_LEVEL_LOWEST,
		$txSize = BITCOIN_TRANSACTION_MEDIAN_P2PKH_SIZE_KB*.5
	){
		return
			$cryptocurrency->parseValue(
				$this->getCryptocurrencyFeePerKilobyte(
					$feeLevel,
					$cryptocurrency->ID
				) *
				$txSize /
				1e8
			);
	}
	
	private function _getCryptocurrencyNetworkFeeLevels(){
		return $this->db->qSelect(
			"
				SELECT
					`CryptocurrencyID`,
					`Level`
				FROM
					`CryptocurrencyNetworkFee`
				WHERE
					`Freeze` = 0
			"
		);
	}
	
	private function _getCryptocurrencyNetworkFeeEstimate($feeLevel){
		$feeLevelEstimate = false;
		$feeLevelEstimates = [];
		while (
			$electrumServer = $this->_getElectrumServer(
				$feeLevel['CryptocurrencyID'],
				$connectionAttempts,
				$previousServerIDs,
				$feeLevelEstimate === false
			)
		){
			$feeLevelEstimate = ElectrumServer::estimateFee(
				$electrumServer['Host'],
				$electrumServer['Port'],
				$feeLevel['Level']
			);
			if (
				$feeLevelEstimate &&
				is_numeric($feeLevelEstimate) &&
				$feeLevelEstimate > 0
			)
				$feeLevelEstimates[] = $feeLevelEstimate * 1e8;
		}
		
		if ($feeLevelEstimates)
			return max(
				NXS::getFilteredAverage($feeLevelEstimates),
				2000
			);
			
		return false;
	}
	
	private function _setCryptocurrencyNetworkFeeEstimate(
		$feeLevel,
		$satoshisPerKiloByte
	){
		return $this->db->qQuery(
			"
				UPDATE
					`CryptocurrencyNetworkFee`
				SET
					`Satoshis` = ?
				WHERE
					`CryptocurrencyID` = ? AND
					`Level` = ?
			",
			'iii',
			[
				$satoshisPerKiloByte,
				$feeLevel['CryptocurrencyID'],
				$feeLevel['Level']
			]
		);
	}
	
	public function updateCryptocurrencyNetworkFeeEstimates(){
		$feeEstimatesUpdated = 0;
		if ($feeLevels = $this->_getCryptocurrencyNetworkFeeLevels())
			foreach ($feeLevels as $feeLevel)
				if ($satoshisPerKiloByte = $this->_getCryptocurrencyNetworkFeeEstimate($feeLevel))
					$feeEstimatesUpdated +=
						$this->_setCryptocurrencyNetworkFeeEstimate(
							$feeLevel,
							$satoshisPerKiloByte
						)
							?: 0;
			
		return $feeEstimatesUpdated;
	}
	
	private function _removePendingBroadcast($transactionID){
		return $this->db->qQuery(
			"
				DELETE FROM
					`PendingBroadcast`
				WHERE
					`TransactionID` = ?
			",
			'i',
			[$transactionID]
		);
	}
	
	public function fetchWithdrawableTransactionIDs($cryptocurrencyID = CRYPTOCURRENCIES_CRYPTOCURRENCY_ID_DEFAULT){
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`ID`
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`PendingBroadcast` ON
							`Transaction`.`ID` = `PendingBroadcast`.`TransactionID`
					WHERE
						(
							`Withdrawn` = FALSE OR
							`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
						) AND
						(
							(
								`PaymentMethod`.`UserID` = ? AND
								`Status` IN ('pending feedback', 'rejected', 'refunded')
							)
						) AND
						`PaymentMethod`.`CryptocurrencyID` = ?
				",
				'ii',
				[
					$this->User->ID,
					$cryptocurrencyID
				]
			)
		)
			return array_map(
				function($transaction){
					return $transaction['ID'];
				},
				$transactions
			);
		
		return false;
	}
	
	private function _queuePartiallySignedTransaction(
		$redeemScript,
		$partiallySignedTransaction
	){
		$_SESSION['pending_transactions']['transactions'][strtolower($redeemScript)] = $partiallySignedTransaction;
	}
	
	public function _checkEmptyAddress(
		$cryptocurrencyID,
		$address,
		$doubleCheck = true,
		$previousElectrumServerIDs = []
	){
		$addressBalance = $this->getAddressBalance(
			$cryptocurrencyID,
			$address,
			0,
			TRUE,
			FALSE,
			$balanceError,
			$previousElectrumServerIDs
		);
		
		return	!$balanceError &&
			$addressBalance !== false &&
			is_numeric($addressBalance) &&
			NXS::compareFloatNumbers(
				$addressBalance,
				0,
				'='
			) &&
			(
				!$doubleCheck ||
				$this->_checkEmptyAddress(
					$cryptocurrencyID,
					$address,
					false,
					$previousElectrumServerIDs
				)
			);
	}
	
	private function getTransactionExtendedPublicKey($transactionID, $userID = false){
		$userID = $userID ?: $this->User->ID;
		
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						IF (
							? = `Transaction`.`BuyerID`,
							`Transaction`.`BuyerExtendedPublicKey`,
							`Transaction`.`VendorExtendedPublicKey`
						) extendedPublicKey,
						IF (
							? = `Transaction`.`BuyerID`,
							`Transaction`.`BuyerKey`,
							`Transaction`.`VendorKey`
						) extendedPublicKeyIndex
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						? IN (`Transaction`.`BuyerID`, `PaymentMethod`.`UserID`) AND
						`Transaction`.`ID` = ?
				",
				'iiii',
				[
					$userID,
					$userID,
					$userID,
					$transactionID
				]
			)
		)
			return [
				$transactions[0]['extendedPublicKey'],
				$transactions[0]['extendedPublicKeyIndex'],
				SIGNING_ACCOUNT_INDEX
			];
		
		return false;
	}
	
	public function prepareTransaction($transaction){
		$errors = false;
		
		$cryptocurrency = $this->User->getCryptocurrency($transaction['CryptocurrencyID']);
		
		if ($transaction['Status'] !== 'pending feedback'){
			$unconfirmedBalance = $this->getAddressBalance(
				$cryptocurrency->ID,
				$transaction['MultiSigAddress'],
				0,
				FALSE,
				FALSE,
				$balanceError
			);
			$hasUnconfirmedFunds = NXS::compareFloatNumbers(
				$unconfirmedBalance,
				0,
				'!='
			);
		
			if ($balanceError)
				$errors = TRUE;
		}
		
		if (!$errors){
			switch($transaction['Status']){
				case 'rejected':
				case 'refunded':
					$returnAddress = $this->getTransactionRefundAddress($transaction['ID']);
					if (empty($returnAddress))
						break;
				
					if (
						(
							$hasUnconfirmedFunds &&
							(
								$addressHistory = $this->getAddressHistory(
									$cryptocurrency->ID,
									$transaction['MultiSigAddress']
								)
							) &&
							$inputs = $this->getInputsFromAddressHistory(
								$cryptocurrency->ID,
								$addressHistory,
								$transaction['MultiSigAddress'],
								$inputsValue
							)
						) ||
						(
							!$hasUnconfirmedFunds &&
							list(
								$inputsValue,
								$inputs
							) = $this->getUnspentOutputs(
								$cryptocurrency->ID,
								$transaction['MultiSigAddress'],
								REQUIRED_TX_CONFIRMATIONS_BROADCAST,
								FALSE,
								$transaction['RedeemScript'],
								false
							)
						)
					){
						$minerFee = $this->estimateDynamicFee(
							$cryptocurrency,
							$inputs,
							[$returnAddress],
							$inputsValue,
							$transaction['RedeemScript'],
							($transaction['SigneeCount'] - 1),
							$this->getCryptocurrencyFeePerKilobyte(
								CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT,
								$cryptocurrency->ID
							)
						);
					
						$outputValue = $cryptocurrency->parseValue($inputsValue - $minerFee, true);
						if (
							NXS::compareFloatNumbers(
								$outputValue,
								$cryptocurrency->smallestIncrement,
								'<'
							)
						){
							$minerFee = $this->estimateDynamicFee(
								$cryptocurrency,
								$inputs,
								[$returnAddress],
								$inputsValue,
								$transaction['RedeemScript'],
								($transaction['SigneeCount'] - 1),
								$this->getCryptocurrencyFeePerKilobyte(
									CRYPTOCURRENCIES_FEE_LEVEL_LOWEST,
									$cryptocurrency->ID
								)
							);
						
							$outputValue = $cryptocurrency->parseValue($inputsValue - $minerFee, true);
							if (
								NXS::compareFloatNumbers(
									$outputValue,
									$cryptocurrency->smallestIncrement,
									'<'
								)
							){
								if(
									(
										$transaction['broadcasterID'] &&
										$this->_removePendingBroadcast($transaction['ID'])
									) ||
									$this->markTransactionWithdrawn($transaction['ID'])
								){
									$notificationTypeIDs_vendor = [USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS];
									if ($transaction['broadcasterID'])
										$this->User->incrementUserNotification(
											USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
											-1,
											$transaction['broadcasterID']
										);
									else {
										$notificationTypeIDs_vendor[] = USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL;
										$this->User->incrementUserNotification(
											[
												USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
												USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS
											],
											-1,
											$transaction['BuyerID']
										);		
									}
			
									$this->User->incrementUserNotification(
										$notificationTypeIDs_vendor,
										-1,
										$transaction['VendorID']
									);
								}
							
								return true;
							}
						}
	
						$outputs_return = [
							$returnAddress => $outputValue
						];
					
						$privateKey_wif_site = $this->getBIP32PrivateKeyWIF(
							$transaction['ID'],
							$cryptocurrency->prefixPublic
						);
						
						$rawTransaction = new ElectrumTransaction(
							$cryptocurrency,
							$transaction['RedeemScript'],
							$inputs,
							$outputs_return,
							$transaction['isSegwit']
						);
						
						if ($transaction['SigneeCount'] == 3 && $extendedPublicKey = $this->getTransactionExtendedPublicKey($transaction['ID'])){
							$rawTransaction->addExtendedPublicKey(...$extendedPublicKey);
						}
						
						$signedTransaction_return = ['hex' => $rawTransaction->sign($privateKey_wif_site)];
					
						$signedTransaction_return['JSON'] = $JSONInputs;
						$signedTransaction_return['txID'] = $transaction['ID'];
					
						switch($transaction['SigneeCount']){
							case 2:
								$previouslyBroadcast =
									$transaction['broadcasterID'] &&
									$this->_removePendingBroadcast($transaction['ID']);
							
								$this->queueTX(
									$transaction['ID'],
									$signedTransaction_return['hex'],
									TRUE,
									$this->User->ID
								);
							
								if(
									$previouslyBroadcast ||
									$this->markTransactionWithdrawn($transaction['ID'])
								){
									$notificationTypeIDs_vendor = [USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS];
									if ($transaction['broadcasterID'])
										$this->User->incrementUserNotification(
											USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
											-1,
											$transaction['broadcasterID']
										);
									else {
										$notificationTypeIDs_vendor[] = USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL;
				
										$this->User->incrementUserNotification(
											[
												USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
												USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS
											],
											-1,
											$transaction['BuyerID']
										);		
									}
			
									$this->User->incrementUserNotification(
										$notificationTypeIDs_vendor,
										-1,
										$transaction['VendorID']
									);
								}
								break;
							case 3:
								$this->_setPrimarySigningPublicKey(
									$cryptocurrency,
									$extendedPublicKey[0]
								);
								
								$this->_queuePartiallySignedTransaction(
									$transaction['RedeemScript'],
									$signedTransaction_return
								);
								break;
						}
						
						return true;
					} elseif (
						//$transaction['broadcasterID'] &&
						$this->_checkEmptyAddress(
							$cryptocurrency->ID,
							$transaction['MultiSigAddress']
						)
					){
						$this->_removePendingBroadcast($transaction['ID']);
						$this->markTransactionWithdrawn($transaction['ID']);
						$notificationTypeIDs_vendor = [USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS];
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
							-1,
							$transaction['broadcasterID']
						);

						$this->User->incrementUserNotification(
							$notificationTypeIDs_vendor,
							-1,
							$transaction['VendorID']
						);
						
						return true;
					}
					
					break;
				case 'pending feedback':
					if($transaction['Commission'])
						$marketplaceFee = $transaction['Commission']/1000;
					else
						$marketplaceFee = MARKETPLACE_FEE;
					
					if (
						list(
							$inputsValue,
							$inputs
						) = $this->getUnspentOutputs(
							$cryptocurrency->ID,
							$transaction['MultiSigAddress'],
							REQUIRED_TX_CONFIRMATIONS_BROADCAST,
							FALSE,
							$transaction['RedeemScript'],
							false
						)
					){
						$vendorAddress['Address'] = $this->getVendorBIP32AddressForTransaction($transaction['ID']);
						$marketplaceAddress = $this->getMarketplaceAddressForTransaction($transaction['ID']);
						$minimumMarketOutput = $minimumMarketOutput ?: $this->_calculateMinimumMarketOutput($cryptocurrency);
						
						$provisionalOutputs = [$vendorAddress['Address']];
						
						if (
							$isSpendableInput = NXS::compareFloatNumbers(
								$inputsValue,
								$minimumMarketOutput,
								'>'
							)
						){
							$provisionalOutputs[] = $marketplaceAddress;
							
							if (
								$hasExcessFunds =
									NXS::compareFloatNumbers(
										$inputsValue,
										$transaction['Value'] + $minimumMarketOutput,
										'>'
									) &&
									$returnAddress = $this->getTransactionRefundAddress($transaction['ID'])
							)
								$provisionalOutputs[] = $returnAddress;
							
							if (
								$hasReferralWallet =
									NXS::compareFloatNumbers(
										$inputsValue,
										$minimumMarketOutput*2,
										'>'
									) &&
									$referralAddress = $this->getTransactionReferralAddress($transaction['ID'])
							)
								$provisionalOutputs[] = $referralAddress;
						}
						
						$minerFee = $this->estimateDynamicFee(
							$cryptocurrency,
							$inputs,
							$provisionalOutputs,
							$inputsValue,
							$transaction['RedeemScript'],
							($transaction['SigneeCount'] - 1),
							$this->getCryptocurrencyFeePerKilobyte(
								$this->User->Attributes['Preferences']['CryptocurrencyFeeLevel'],
								$cryptocurrency->ID
							)
						);
					
						if(
							NXS::compareFloatNumbers(
								$inputsValue,
								$minerFee,
								'<='
							)
						){
							$hadTooLowToProcess = TRUE;
							break;
						}
						
						$value_affiliate = $value_marketplace = $value_buyer = 0;
						if (
							$isSpendableInputs = NXS::compareFloatNumbers(
								$inputsValue,
								($minerFee + $minimumMarketOutput),
								'>='
							)
						){
							$value_marketplace =
								max(
									$minimumMarketOutput,
									$cryptocurrency->parseValue(
										($hasExcessFunds ? $transaction['Value'] : $inputsValue) *
										$marketplaceFee
									)
								);
							
							if (
								$hasReferralWallet &&
								NXS::compareFloatNumbers(
									$inputsValue,
									($minerFee + $value_marketplace + 2*$minimumMarketOutput),
									'>='
								)
							)
								$value_affiliate = 
									max(
										2*$minimumMarketOutput,
										$cryptocurrency->parseValue(
											(
												$transaction['Value'] *
												REFERRAL_COMMISION
											),
											true
										)
									);
							
							if (
								$hasExcessFunds &&
								NXS::compareFloatNumbers(
									$inputsValue,
									($minerFee + $value_marketplace + $value_affiliate + $minimumMarketOutput),
									'>='
								)
							)
								$value_buyer = $cryptocurrency->parseValue(
									($inputsValue - $transaction['Value']),
									true
								);
								
						}
						
						$value_vendor = $cryptocurrency->parseValue(
							($inputsValue - $minerFee - $value_marketplace - $value_affiliate - $value_buyer),
							true
						);
						
						$outputs = [];
						
						if (
							$value_marketplace &&
							NXS::compareFloatNumbers(
								$value_marketplace,
								$cryptocurrency->smallestIncrement,
								'>='
							)
						)
							$outputs[$marketplaceAddress] = $value_marketplace;
					
						if (
							$value_vendor &&
							NXS::compareFloatNumbers(
								$value_vendor,
								$cryptocurrency->smallestIncrement,
								'>='
							)
						)
							$outputs[$vendorAddress['Address']] = $value_vendor;
											
						if (
							$value_buyer &&
							NXS::compareFloatNumbers(
								$value_buyer,
								$cryptocurrency->smallestIncrement,
								'>='
							)
						)
							$outputs[$returnAddress] = $value_buyer;
							
						if (
							$value_affiliate &&
							NXS::compareFloatNumbers(
								$value_affiliate,
								$cryptocurrency->smallestIncrement,
								'>='
							)
						)
							$outputs[$referralAddress] = $value_affiliate;
						
						if (empty($outputs)){
							$hadTooLowToProcess = TRUE;
							break;
						}
					
						$privateKey_wif_site = $this->getBIP32PrivateKeyWIF(
							$transaction['ID'],
							$cryptocurrency->prefixPublic
						);
						
						$rawTransaction = new ElectrumTransaction(
							$cryptocurrency,
							$transaction['RedeemScript'],
							$inputs,
							$outputs,
							$transaction['isSegwit']
						);
						
						if ($extendedPublicKey = $this->getTransactionExtendedPublicKey($transaction['ID']))
							$rawTransaction->addExtendedPublicKey(...$extendedPublicKey);

						$signedTransaction = ['hex' => $rawTransaction->sign($privateKey_wif_site)];
					
						$signedTransaction['JSON'] = $JSONInputs;
						$signedTransaction['txID'] = $transaction['ID'];
					
						switch($transaction['SigneeCount']){
							case 2:
								$previouslyBroadcast =
									$transaction['broadcasterID'] &&
									$this->_removePendingBroadcast($transaction['ID']);
							
								if(
									$this->queueTX(
										$transaction['ID'],
										$signedTransaction['hex'],
										TRUE,
										$this->User->ID
									) &&
									(
										$previouslyBroadcast ||
										$this->markTransactionWithdrawn($transaction['ID'])
									)
								){
									$notificationTypeIDs = [USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS];
									$notificationTypeIDs[] =
										$transaction['broadcasterID']
											? USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL
											: USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL;
							
									$this->User->incrementUserNotification(
										$notificationTypeIDs,
										-1
									);
								}
								break;
							case 3:
								$this->_setPrimarySigningPublicKey(
									$cryptocurrency,
									$extendedPublicKey[0]
								);
							
								$this->_queuePartiallySignedTransaction(
									$transaction['RedeemScript'],
									$signedTransaction
								);
								break;
						}
						
						return true;
					} elseif (
						//$transaction['broadcasterID'] &&
						$this->_checkEmptyAddress(
							$cryptocurrency->ID,
							$transaction['MultiSigAddress']
						)
					) {
						$this->_removePendingBroadcast($transaction['ID']);
						$this->markTransactionWithdrawn($transaction['ID']);
						$notificationTypeIDs = [
							USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
							USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL
						];
				
						$this->User->incrementUserNotification(
							$notificationTypeIDs,
							-1
						);
						
						return true;
					}
					
					break;
			}
		}
		
		if ($hadTooLowToProcess)
			$_SESSION['temp_notifications']['tooLowToProcess'] = array(
				'Content'	=> 'One or more deposit addresses had insufficient funds. Try to lower your <em>transaction priority</em>.',
				'Design'	=> [
							'Color'	=> 'red',
							'Icon'	=> Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						],
				'Group'		=> 'Transactions'
			);
		else
			$_SESSION['temp_notifications']['transactionPreparationError'] = array(
				'Content'	=> 'One or more transactions could not be processed. Please try again later, or contact support.',
				'Design'	=> [
							'Color'	=> 'red',
							'Icon'	=> Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						],
				'Group'		=> 'Transactions'
			);
		
		return false;
	}
	
	public function getTransactionReferralAddress($transactionID){
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						`ReferralWallet`.`ID` IS NOT NULL hasReferralWallet,
						Affiliate.`ID` affiliateID,
						IF (
							`ReferralWallet`.`ID` IS NOT NULL,
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
							),
							FALSE
						) keyIndex,
						Cryptocurrency.`Prefix_Public`,
						Cryptocurrency.`Prefix_ScriptHash`
					FROM
						`Transaction`
					INNER JOIN
						`InviteCode` ON
							`Transaction`.`BuyerID` = `InviteCode`.`ClaimedID`
					INNER JOIN
						`User` Affiliate ON
							`InviteCode`.`UserID` = Affiliate.`ID`
					LEFT JOIN
						`User_Class` ON
							`User_Class`.`UserID` = Affiliate.`ID` AND
							`User_Class`.`ClassID` = 3
					LEFT JOIN
						`ReferralWallet` ON
							`Transaction`.`ReferralWalletID` = `ReferralWallet`.`ID` AND
							LAST_DAY(`ReferralWallet`.`DateTime`) >= DATE(NOW()) AND
							(
								SELECT
									`ReferralWallet_Cryptocurrency`.`CryptocurrencyID`
								FROM
									`ReferralWallet_Cryptocurrency`
								WHERE
									`ReferralWalletID` = `ReferralWallet`.`ID` AND
									`ReferralWallet_Cryptocurrency`.`OutputAddress` IS NOT NULL
								LIMIT 1
							) IS NULL
					LEFT JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`Currency` Cryptocurrency ON
							`ReferralWallet`.`ID` IS NOT NULL AND
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					WHERE
						`Transaction`.`ID` = ? AND
						(
							`ReferralWallet`.`ID` IS NOT NULL OR
							(
								`Transaction`.`Withdrawn` = FALSE AND
								Affiliate.`ID` != `PaymentMethod`.`UserID` AND
								(
									Affiliate.`Vendor` = TRUE OR
									`User_Class`.`UserID` IS NOT NULL
								)
							)
						)
				",
				'i',
				[$transactionID]
			)
		){
			$transaction = $transactions[0];
			
			if ($transaction['hasReferralWallet'])
				return NXS::getBIP32Address(
					$transaction['keyIndex'],
					REFERRAL_WALLET_EXTENDED_PRIVATE_KEY,
					$transaction['Prefix_Public'],
					$transaction['Prefix_ScriptHash'],
					true,
					''
				);
			
			if (
				$this->_assignTransactionReferralWallet(
					$transactionID,
					$transaction['affiliateID']
				)
			)
				return $this->getTransactionReferralAddress($transactionID);
		}
		
		return false;
	}
	
	private function _assignTransactionReferralWallet(
		$transactionID,
		$affiliateID
	){
		if ($referralWalletID = $this->_getLatestReferralWallet($affiliateID))
			return $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`ReferralWalletID` = ?
					WHERE
						`ID` = ?
				",
				'ii',
				[
					$referralWalletID,
					$transactionID
				]
			);
			
		return false;
	}
	
	private function _getLatestReferralWallet($affiliateID){
		if (
			$latestReferralWallets = $this->db->qSelect(
				"
					SELECT
						`ID`
					FROM
						`ReferralWallet`
					WHERE
						`UserID` = ? AND
						LAST_DAY(`DateTime`) >= DATE(NOW())
					ORDER BY
						`DateTime` DESC
					LIMIT 1
				",
				'i',
				[$affiliateID]
			)
		)
			return $latestReferralWallets[0]['ID'];
		
		return	$this->_insertReferralWallet($affiliateID)
			?: false;
			
	}
	
	private function _insertReferralWallet($affiliateID){
		return	$this->db->qQuery(
			"
				INSERT INTO
					`ReferralWallet` (`UserID`)
				VALUES
					(?)
			",
			'i',
			[$affiliateID]
		);
	}
	
	private function _setPrimarySigningPublicKey(
		$cryptocurrency,
		$signingPublicKey
	){
		if ($signingPublicKey){
			if (
				$_SESSION['pending_transactions']['signingPublicKey'] === TRUE ||
				$_SESSION['pending_transactions']['signingPublicKey'] === $cryptocurrency->name . '-' . $cryptocurrency->prefixPublic . '-' . $signingPublicKey
			){
				$_SESSION['pending_transactions']['signingPublicKey'] = $cryptocurrency->name . '-' . $cryptocurrency->prefixPublic . '-' . $signingPublicKey;
				$_SESSION['pending_transactions']['cryptocurrency'] = $cryptocurrency->ID;
			} else
				$_SESSION['pending_transactions']['signingPublicKey'] = $_SESSION['pending_transactions']['cryptocurrency'] = FALSE;
		}
	}
	
	public function _getWithdrawableTransactionIDs($option){
		switch($option){
			case 'all':
				break;
			default:
				if ($singleTransactionID = $this->getTransactionID($option))
					return [$singleTransactionID];
				
				if (
					is_array($_POST['transaction_select']) &&
					$transactionIDs = array_map(
						function($transactionIdentifier){
							return $this->getTransactionID($transactionIdentifier);
						},
						$_POST['transaction_select']
					)
				)
					return $transactionIDs;
		}
		
		return false;
	}
	
	public function getWithdrawableTransactions($option){
		$transactionIDs = $this->_getWithdrawableTransactionIDs($option);
		
		$rsa = new RSA();
		$stmt_transactions_query = "
			SELECT
				`Transaction`.`ID`,
				`Transaction`.`Status`,
				IF(
					`Transaction`.`BuyerID` = `User`.`ID`,
					`Order_Buyer`,
					`Order_Vendor`
				) AS MyOrder,
				IF(
					`Transaction`.`BuyerID` = `User`.`ID`,
					`NextTX_Buyer`,
					`NextTX_Vendor`
				) AS NextTX,
				`Transaction`.`MultiSigAddress`,
				`Transaction`.`RedeemScript`,
				`Transaction`.`Escrow`,
				`Transaction`.`SigneeCount`,
				`Transaction`.`Value`,
				IFNULL(`PendingBroadcast`.`ID`, FALSE) as BroadcastAttemptID,
				`User`.`Commission`,
				`Transaction_FeeBump`.`Hex_Partial` partiallySignedCPFP,
				`Listing`.`VendorID`,
				`Transaction`.`BuyerID`,
				`PendingBroadcast`.`UserID` broadcasterID,
				`PaymentMethod`.`CryptocurrencyID`,
				`Transaction`.`Segwit` isSegwit
			FROM
				`Transaction`
			INNER JOIN
				`User` ON
					`User`.`ID` = ?
			INNER JOIN
				`Listing` ON
					`Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			LEFT JOIN
				`PendingBroadcast` ON
					`Transaction`.`ID` = `PendingBroadcast`.`TransactionID` AND
					`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
			LEFT JOIN
				`Transaction_FeeBump` ON
					`Transaction_FeeBump`.`TransactionID` = `Transaction`.`ID`
			WHERE
				(
					`Withdrawn` = FALSE OR
					`PendingBroadcast`.`UserID` IS NOT NULL
				) AND
				(
					(
						`Transaction`.`BuyerID` = `User`.`ID` AND
						`Status` IN ('rejected', 'refunded')
					) OR
					(
						`PaymentMethod`.`UserID` = `User`.`ID` AND
						`Status` IN ('pending feedback', 'rejected', 'refunded')
					)  OR
					(
						`Status` = 'pending deposit' AND
						`Transaction_FeeBump`.`TransactionID` IS NOT NULL AND
						(
							`Transaction_FeeBump`.`Submitted` = FALSE OR
							`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
						)
					)
				)
				" . (
					$transactionIDs
						? 'AND `Transaction`.`ID` IN (' . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ')'
						: FALSE
				) . "
			ORDER BY
				`Escrow` ASC
		";
		$stmt_transactions_types = 'i';
		$stmt_transactions_params = array($this->User->ID);
		
		if ($transactionIDs){
			$stmt_transactions_types .= str_repeat('i', count($transactionIDs));
			$stmt_transactions_params = array_merge(
				$stmt_transactions_params,
				$transactionIDs
			);
		}
		
		if (
			$transactions = $this->db->qSelect(
				$stmt_transactions_query,
				$stmt_transactions_types,
				$stmt_transactions_params
			)
		)
			return	array_map(
					function($array) use ($rsa){
						return [array_merge(
							$array,
							array(
								'MyOrder'	=> json_decode( $rsa->qDecrypt($array['MyOrder']), TRUE ),
								'NextTX'	=> json_decode( $rsa->qDecrypt($array['NextTX']), TRUE)
							)
						)];
					},
					$transactions
				);
		
		return FALSE;
	}
	
	public function refundUnacceptedTransactions(){
		$transactionsAutoRejected = 0;
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						(
							SELECT
								MIN(`Transaction`.`ID`) ID
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Status` IN ('pending accept') AND
								`Transaction`.`Timeout` < NOW()
						) source ON
							source.`ID` = `Transaction`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							(
								incrementedNotification.`UserID` IN(
									`PaymentMethod`.`UserID`,
									`Transaction`.`BuyerID`
								) AND
								incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . "
							)
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `PaymentMethod`.`UserID` AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT . "
					SET
						`Transaction`.`Status` = 'rejected',
						`Transaction`.`Withdrawn` = FALSE,
						`Transaction`.`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
				"
			)
		)
			$transactionsAutoRejected++;
		
		return $transactionsAutoRejected;
	}
	
	public function autofinalizeTransactions(){
		$transactionsAutofinalized = 0;
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						(
							SELECT
								MIN(`Transaction`.`ID`) ID
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Status` = 'expired' AND
								`Transaction`.`Timeout` < NOW()
						) source ON
							source.`ID` = `Transaction`.`ID`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`User` Vendor ON
							`PaymentMethod`.`UserID` = Vendor.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							(
								incrementedNotification.`UserID` = Vendor.`ID` AND
								incrementedNotification.`TypeID` IN(
									" . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . ",
									" . USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL . "
								)
							) OR
							(
								incrementedNotification.`UserID` = Buyer.`ID` AND
								incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK . "
							)
					SET
						`Transaction`.`Status` = 'pending feedback',
						Buyer.`BuyCount` = Buyer.`BuyCount` + 1,
						Vendor.`SellCount` = Vendor.`SellCount` + 1,
						`Transaction`.`Timeout` = NOW() + INTERVAL " . PENDING_FEEDBACK_DAYS . " DAY,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
				"
			)
		)
			$transactionsAutofinalized++;
		
		return $transactionsAutofinalized;
	}
	
	public function setTransactionsExpired(){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Status` = 'expired',
					`Timeout` = NOW() + INTERVAL " . EXPIRED_TRANSACTION_TIMEOUT_DAYS . " DAY
				WHERE
					`Status` = 'in transit' AND
					`Timeout` < NOW()
			"
		);
	}
	
	public function incrementBuyerNotification($transactionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							`Transaction`.`NotificationIncremented` = FALSE AND
							incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
					SET
						`Transaction`.`NotificationIncremented` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`NotificationIncremented` = FALSE
				",
				'i',
				[$transactionID]
			);
	}
	
	public function clearChangedTransactionStatus($transactionID){
		return
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
					SET
						`Transaction`.`StatusChanged` = FALSE,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`StatusChanged` = TRUE
				",
				'i',
				[$transactionID]
			);
	}
	
	public function toggleTransactionStatusChanged(
		$transactionID,
		$buyerID
	){
		return
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
					SET
						`Transaction`.`StatusChanged` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`StatusChanged` = FALSE
				",
				'i',
				[$transactionID]
			);
	}
	
	public function decrementBuyerNotifications(){
		$buyerNotificationsDecremented = 0;
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						(
							SELECT
								MIN(`Transaction`.`ID`) ID
							FROM
								`Transaction`
							WHERE
								`Transaction`.`NotificationIncremented` = TRUE AND
								`Transaction`.`Status` = 'pending feedback' AND
								`Transaction`.`Timeout` < NOW()
						) source ON
							source.`ID` = `Transaction`.`ID`
					INNER JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							(
								decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . " OR
								(
									`Transaction`.`Feedback_Buyer` = FALSE AND
									decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK . "
								) OR
								(
									`Transaction`.`StatusChanged` = TRUE AND
									decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
								)
							)
					SET
						`Transaction`.`NotificationIncremented` = 0,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						),
						`Transaction`.`StatusChanged` = FALSE
				"
			)
		)
			$buyerNotificationsDecremented++;
		
		return $buyerNotificationsDecremented;
	}
	
	public function _getTransactionPendingPayment(
		$includeDeposited,
		&$checkedTransactionIDs
	){
		$checkedTransactionIDs = $checkedTransactionIDs ?: [];
		
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`MultiSigAddress`,
						`Transaction`.`Value`,
						`Transaction`.`Deposited`,
						`Transaction`.`BuyerID`,
						`Transaction`.`Timeout` < NOW() + INTERVAL " . PENDING_DEPOSIT_TIMEOUT_REMAINING_MINUTES_DOUBLE_CHECK_BALANCE . " MINUTE isCritical,
						`PaymentMethod`.`CryptocurrencyID`
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						" . (
							$includeDeposited
								? "`Transaction`.`Paid` = FALSE"
								: "`Transaction`.`Deposited` = FALSE"
						) . " AND
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`RefundAddress` IS NOT NULL AND
						`Transaction`.`Timeout` > NOW() AND
						`Transaction`.`Value` > 0 " . (
							$checkedTransactionIDs
								? " AND `Transaction`.`ID` NOT IN (" . rtrim(str_repeat('?, ', count($checkedTransactionIDs)), ', ') . ")"
								: false
						) . "
					ORDER BY
						`Transaction`.`Timeout` ASC
					LIMIT 1
				",
				$checkedTransactionIDs ? str_repeat('i', count($checkedTransactionIDs)) : false,
				$checkedTransactionIDs
			)
		){
			$transaction = $transactions[0];
			$checkedTransactionIDs[] = $transaction['ID'];
			
			return $transaction;
		}
		
		return false;
	}
	
	private function _setTransactionsPaid($transactionIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Paid` = TRUE,
					`Deposited` = TRUE,
					`Timeout` = NOW() + INTERVAL " . PENDING_DEPOSIT_CONFIRMATION_TIMEOUT_DAYS . " DAY
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
			",
			str_repeat('i', count($transactionIDs)),
			$transactionIDs
		);
	}
	
	private function _setTransactionsDeposited($transactionIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Deposited` = TRUE
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
			",
			str_repeat('i', count($transactionIDs)),
			$transactionIDs
		);
	}
	
	private function _updateFailedPayment($timeoutToleranceMinutes){
		$transactionsFailedPayment = 0;
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						(
							SELECT
								MIN(`Transaction`.`ID`) ID
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Paid` = FALSE AND
								`Transaction`.`Deposited` = TRUE AND
								`Transaction`.`Status` = 'pending deposit' AND
								`Transaction`.`RefundAddress` IS NOT NULL AND
								`Transaction`.`Timeout` < NOW() - INTERVAL ? MINUTE
						) source ON
							source.`ID` = `Transaction`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							(
								incrementedNotification.`UserID` = `PaymentMethod`.`UserID` AND
								incrementedNotification.`TypeID` IN (
									" . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . ",
									" . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
								)
							) OR
							(
								incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
								(
									incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . " OR
									(
										`Transaction`.`NotificationIncremented` = FALSE AND
										incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
									)
								)
							)
					SET
						`Transaction`.`Status` = 'refunded',
						`Transaction`.`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						`Transaction`.`Unconfirmed` = TRUE,
						`Transaction`.`NotificationIncremented` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
				",
				'i',
				[$timeoutToleranceMinutes]
			)
		)
			$transactionsFailedPayment++;
		
		return $transactionsFailedPayment;
	}
	
	private function _reincrementPromoQuantities(){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				INNER JOIN
					`Listing_PromoCode` ON
						`Transaction`.`PromoCodeID` = `Listing_PromoCode`.`ID`
				SET
					`Transaction`.`PromoCodeID` = NULL,
					`Listing_PromoCode`.`Quantity` = LEAST(
						" . LISTING_PROMOTIONAL_CODE_QUANTITY_MAX . ",
						CAST(`Listing_PromoCode`.`Quantity` AS SIGNED) + 1
					)
				WHERE
					`Paid` = FALSE AND
					`Timeout` < NOW()
			"
		);
	}
	
	private function _deductListingInventories($transactionIDs){
		if(
			$transactions = $this->db->qSelect(
				"
					SELECT
						`ListingID`,
						`Quantity`
					FROM
						`Transaction`
					WHERE
						`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
				",
				str_repeat('i', count($transactionIDs)),
				$transactionIDs
			)
		){
			foreach($transactions as $transaction)
				$this->decrementQuantityLeft(
					$transaction['ListingID'],
					$transaction['Quantity']
				);
				
			return true;
		}
		
		return FALSE;
	}
	
	public function checkTransactionDeposits($includeDeposited){
		while (
			$transaction = $this->_getTransactionPendingPayment(
				$includeDeposited,
				$checkedTransactionIDs
			)
		){
			$cryptocurrency = $this->User->getCryptocurrency($transaction['CryptocurrencyID']);
			$minimumBalance = $this->_calculateMinimumMarketOutput(
				$cryptocurrency,
				CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT,
				BITCOIN_TRANSACTION_AVERAGE_SIZE_KB
			);
			
			$unconfirmedBalance = $this->getAddressBalance( 
				$cryptocurrency->ID,
				$transaction['MultiSigAddress'],
				0,
				TRUE,
				FALSE,
				$errors
			);
		
			$deposited =
				$unconfirmedBalance != 0 &&
				NXS::compareFloatNumbers(
					$unconfirmedBalance,
					$minimumBalance,
					'>='
				);
			$paid = NXS::compareFloatNumbers(
				$unconfirmedBalance,
				$transaction['Value'],
				'>='
			);	
		
			if ($paid)
				$this->markTransactionPaid($transaction['ID']);
			elseif(
				$deposited &&
				$transaction['Deposited'] == FALSE
			)
				$this->markTransactionDeposited(
					$transaction['ID'],
					false
				);
				
			if(
				$paid ||
				(
					$deposited &&
					$transaction['Deposited'] == FALSE
				)
			)
				$this->incrementBuyerNotification($transaction['ID']);
		}
		
		$this->_updateFailedPayment(ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES);
		$this->_reincrementPromoQuantities();
		
		return TRUE;
	}
	
	private function _clearTransactionPromo($transactionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing_PromoCode` ON
							`Transaction`.`PromoCodeID` = `Listing_PromoCode`.`ID`
					SET
						`Transaction`.`PromoCodeID` = NULL,
						`Listing_PromoCode`.`Quantity` = LEAST(
							" . LISTING_PROMOTIONAL_CODE_QUANTITY_MAX . ",
							CAST(`Listing_PromoCode`.`Quantity` AS SIGNED) + 1
						)
					WHERE
						`Transaction`.`ID` = ?
				",
				'i',
				[$transactionID]
			);
	}
	
	private function _extendOrderPaymentWindow($transactionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`Timeout` = NOW() + INTERVAL " . PENDING_DEPOSIT_TIMEOUT_MINUTES_RENEWAL . " MINUTE
					WHERE
						`ID` = ? AND
						`BuyerID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			);
	}
	
	public function renewOrderPaymentWindow($transactionID){
		$transaction = $this->fetchTransaction($transactionID);
		
		if (!$transaction['canExtendPaymentWindow'])
			return false;
		
		$cryptocurrency = $transaction['paymentMethod']['cryptocurrency'];
		
		if (
			$listing = $this->_checkListing(
				$transaction['listing_id'],
				$cryptocurrency->ISO,
				$transaction['order']['ShippingID'],
				$transaction['order']['Quantity']
			)
		){
			$this->_clearTransactionPromo($transactionID);
			
			$listing['price'] =
				max(
					$listing['price'] * $cryptocurrency->XEUR,
					$transaction['order']['Price']['unit_price']
				) / $cryptocurrency->XEUR;
			
			$listing['shippingPrice'] =
				max(
					$listing['shippingPrice'] * $cryptocurrency->XEUR,
					$transaction['order']['Price']['price_shipping']
				) / $cryptocurrency->XEUR;
			
			list(
				$order,
				$orderBuyer,
				$orderVendor
			) = $this->_parseOrderForm(
				$listing,
				$transaction['order']['Quantity'],
				$transaction['order']['ShippingID'],
				$transaction['order']['Address']
			);
			
			$order['DepositAddress'] = $transaction['multisig_address'];
			$order['RenewedPaymentWindow'] = true;
			
			$this->updateTransactionOrder(
				$transactionID,
				$cryptocurrency,
				$transaction['vendor_id'],
				$order
			);
			
			if ($transaction['order']['PromoID'])
				$this->applyPromoCodeTransaction(
					$transactionID,
					$transaction['order']['PromoCode']
				);
				
			return $this->_extendOrderPaymentWindow($transactionID);
		}
		
		return false;
	}
	
	public function claimRefundLateTransactionDeposit($transactionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							(
								incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
								incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . "
							) OR
							(
								incrementedNotification.`UserID` = `Listing`.`VendorID` AND
								incrementedNotification.`TypeID` IN (
									" . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . ",
									" . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
								)
							)
					SET
						`Transaction`.`Status` = 'refunded',
						`Transaction`.`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						`Transaction`.`Withdrawn` = FALSE,
						`Transaction`.`Unconfirmed` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`Paid` = FALSE AND
						`Transaction`.`Deposited` = TRUE AND
						NOW() > `Transaction`.`Timeout` AND
						NOW() < `Transaction`.`Timeout` + INTERVAL " . ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES . " MINUTE
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			);
	}
	
	private function _getFailedTransactionPendingDeposit(
		$timeoutToleranceMinutes,
		&$checkedTransactionIDs
	){
		$checkedTransactionIDs = $checkedTransactionIDs ?: [];
		
		if (
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`MultiSigAddress`,
						`Transaction`.`BuyerID`,
						`PaymentMethod`.`CryptocurrencyID`
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`Transaction`.`Deposited` = FALSE AND	
						`Transaction`.`Status` = 'pending deposit' AND
						`Transaction`.`RefundAddress` IS NOT NULL AND
						`Transaction`.`Timeout` < NOW() AND
						`Transaction`.`Timeout` > NOW() - INTERVAL ? MINUTE
						" . (
							$checkedTransactionIDs
								? " AND `Transaction`.`ID` NOT IN (" . rtrim(str_repeat('?, ', count($checkedTransactionIDs)), ', ') . ")"
								: false
						) . "
					LIMIT 1
				",
				'i' . ($checkedTransactionIDs ? str_repeat('i', count($checkedTransactionIDs)) : false),
				array_merge(
					[$timeoutToleranceMinutes],
					$checkedTransactionIDs
				)
			)
		){
			$transaction = $transactions[0];
			$checkedTransactionIDs[] = $transaction['ID'];
			
			return $transaction;
		}
		
		return false;
	}
	
	public function ascertainFailedDeposits($timeoutToleranceMinutes){
		while(
			$transaction = $this->_getFailedTransactionPendingDeposit(
				$timeoutToleranceMinutes,
				$checkedTransactionIDs
			)
		){
			$unconfirmedBalance = $this->getAddressBalance(
				$transaction['CryptocurrencyID'],
				$transaction['MultiSigAddress'],
				0,
				TRUE,
				FALSE,
				$errors
			);
			
			if (
				!$errors &&
				$unconfirmedBalance != 0
			)
				$this->markTransactionDeposited(
					$transaction['ID'],
					false
				) &&
				$this->incrementBuyerNotification($transaction['ID']);
		}
		
		return TRUE;
	}
	
	private function _getTransactionsPendingPaymentConfirmation(){
		return $this->db->qSelect(
			"
				SELECT
					`Transaction`.`ID`,
					`Transaction`.`MultiSigAddress`,
					`Transaction`.`RedeemScript`,
					`Transaction`.`Value`,
					`Transaction`.`FeeBump`,
					IF(
						`Transaction`.`Escrow`,
						2,
						1
					) RequiredSignatures,
					`Transaction`.`Timeout` < NOW() + INTERVAL " . PENDING_CONFIRMATION_TIMEOUT_REMAINING_MINUTES_DOUBLE_CHECK_BALANCE . " MINUTE isCritical,
					`PaymentMethod`.`CryptocurrencyID`
				FROM
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				WHERE
					`Transaction`.`Status` = 'pending deposit' AND
					`Transaction`.`Paid` = TRUE AND
					`Transaction`.`Timeout` > NOW()
			"
		);
	}
	
	private function _updateFailedPaymentConfirmations(){
		$transactionsFailedPaymentConfirmation = 0;
		while (
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						(
							SELECT
								MIN(`Transaction`.`ID`) ID
							FROM
								`Transaction`
							WHERE
								`Transaction`.`Paid` = TRUE AND
								`Transaction`.`Status` = 'pending deposit' AND
								`Transaction`.`RefundAddress` IS NOT NULL AND
								`Transaction`.`Timeout` < NOW()
						) source ON
							source.`ID` = `Transaction`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							(
								incrementedNotification.`UserID` IN(
									`PaymentMethod`.`UserID`,
									`Transaction`.`BuyerID`
								) AND
								incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL . "
							)
					SET
						`Transaction`.`Status` = 'rejected',
						`Transaction`.`Timeout` = NOW() + INTERVAL " . REJECTED_TIMEOUT_DAYS . " DAY,
						`Transaction`.`Unconfirmed` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
				"
			)
		)
			$transactionsFailedPaymentConfirmation++;
		
		return $transactionsFailedPaymentConfirmation;
	}
	
	private function _getTransaction($ID, &$RSA = NULL){
		if(
			$transactions = $this->db->qSelect(
				"
					SELECT
						`Transaction`.`Escrow`,
						`Transaction`.`Segwit` isSegwit,
						`Transaction`.`MultiSigAddress`,
						`Transaction`.`RefundAddress`,
						`Transaction`.`RedeemScript`,
						`Transaction`.`NextTX_Site`,
						`Transaction`.`BuyerID`,
						`Transaction`.`Quantity`,
						`Buyer`.`PublicKey` buyerPublicKey,
						`Listing`.`VendorID`,
						`Transaction`.`Status`,
						`PaymentMethod`.`CryptocurrencyID`,
						`Transaction`.`Value`
					FROM
						`Transaction`
					INNER JOIN
						`User` Buyer ON
							`Transaction`.`BuyerID` = Buyer.`ID`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`Transaction`.`ID` = ?
				",
				'i',
				[$ID]
			)
		){
			$transaction = $transactions[0];
			$RSA = new RSA(SITE_RSA_PRIVATE_KEY);
			
			return array_merge(
				$transaction,
				[
					'nextTX' => json_decode(
						$RSA->qDecrypt($transaction['NextTX_Site']),
						TRUE
					),
					'cryptocurrency' => $this->User->getCryptocurrency($transaction['CryptocurrencyID'])
				]
			);
		}
		
		return false;
	}
	
	public function _setTransactionsPlaced($transactionIDs){
		foreach ($transactionIDs as $transactionID){
			$transaction = $this->_getTransaction(
				$transactionID,
				$RSA
			);
			$cryptocurrency = $this->User->getCryptocurrency($transaction['CryptocurrencyID']);
			
			if (
				!$transaction ||
				$transaction['Status'] == 'pending accept'
			)
				continue;
			
			$nextTX = NULL;
			
			$inputs = false;
			if (
				NXS::compareFloatNumbers(
					$transaction['Value'],
					0,
					'='
				) ||
				list(
					$inputsValue,
					$inputs
				) = $this->getUnspentOutputs(
					$cryptocurrency->ID,
					$transaction['MultiSigAddress'],
					REQUIRED_TX_CONFIRMATIONS_ORDER,
					FALSE,
					$transaction['RedeemScript'],
					false
				)
			){
				if ($inputs)
					$this->insertUnspentOutputs(
						$cryptocurrency->ID,
						$transaction['MultiSigAddress'],
						$inputs
					);
					
				if ($transaction['Escrow']){
					$minerFee = $this->estimateDynamicFee(
						$cryptocurrency,
						$inputs,
						[$transaction['RefundAddress']],
						$inputsValue,
						$transaction['RedeemScript'],
						2,
						$this->getCryptocurrencyFeePerKilobyte(
							CRYPTOCURRENCIES_FEE_LEVEL_FASTEST,
							$cryptocurrency->ID
						)
					);
				
					$valueBuyer = $cryptocurrency->parseValue($inputsValue - $minerFee, 1);
						    
					$outputs = [
						$transaction['RefundAddress'] => $valueBuyer
					];

					$timeLock = strtotime("+" . AUTO_FINALIZE_BUYER_DAYS . ' DAYS');

					$privateKey_WIF_site = $this->getBIP32PrivateKeyWIF(
						$transactionID,
						$cryptocurrency->prefixPublic
					);
					
					$rawTransaction = new ElectrumTransaction(
						$cryptocurrency,
						$transaction['RedeemScript'],
						$inputs,
						$outputs,
						$transaction['isSegwit']
					);
					
					if ($extendedPublicKey = $this->getTransactionExtendedPublicKey($transactionID))
						$rawTransaction->addExtendedPublicKey(...$extendedPublicKey);
					
					$signedTransaction = ['hex' => $rawTransaction->addLocktime($timeLock)->sign($privateKey_WIF_site)];
					
					$nextTX = $RSA->qEncrypt(
						json_encode(
							array_merge(
								$transaction['nextTX'],
								[
									'AutoFinalize' => $signedTransaction
								]
							)
						),
						$transaction['buyerPublicKey']
					);
				}
				
				if (
					$stmt_updateTX = $this->db->qQuery(
						"
							UPDATE
								`Transaction`
							SET
								`Transaction`.`Status` = 'pending accept',
								`NextTX_Buyer` = IFNULL(
									?,
									`NextTX_Buyer`
								),
								`Timeout` = NOW() + INTERVAL " . PENDING_ACCEPT_TIMEOUT_DAYS . " DAY
							WHERE
								`Transaction`.`ID` = ? AND
								`Transaction`.`Status` = 'pending deposit'
					
						",
						'si',
						[
							$nextTX,
							$transactionID
						]
					)
				){
					//$this->db->incrementStatistic('orders', 1);
					$this->User->incrementUserNotification(
						[
							USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
							USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT
						],
						1,
						$transaction['VendorID']
					);
					$this->insertTransactionEvent(
						$transactionID,
						TRANSACTION_EVENTS_FLAG_PAID
					);
				}
			}
		}
	}
	
	public function _setTransactionsConfirmationFailed($transactionIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				LEFT JOIN
					`User_Notification` decrementedNotification ON
						`Transaction`.`NotificationIncremented` = TRUE AND
						decrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
						decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
				SET
					`Status` = 'pending deposit',
					`Timeout` = NOW() - INTERVAL " . ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES . " MINUTE,
					`Paid` = FALSE,
					`Deposited` = FALSE,
					`Unconfirmed` = FALSE,
					`Transaction`.`NotificationIncremented` = IF(
						decrementedNotification.`UserID` IS NOT NULL,
						0,
						`NotificationIncremented`
					),
					decrementedNotification.`Value` = GREATEST(
						0,
						CAST(decrementedNotification.`Value` AS SIGNED) - 1
					)
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
			",
			str_repeat('i', count($transactionIDs)),
			$transactionIDs
		);
	}
	
	private function _getRejectedTransactionsPendingConfirmation(){
		return $this->db->qSelect(
			"
				SELECT
					`Transaction`.`ID`,
					`Transaction`.`MultiSigAddress`,
					`Listing`.`VendorID`,
					`PaymentMethod`.`CryptocurrencyID`
				FROM
					`Transaction`
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				WHERE
					`Status` = 'rejected' AND
					`Unconfirmed` = TRUE
			"
		);
	}
	
	public function _setTransactionsConfirmed($transactionIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Unconfirmed` = FALSE
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
			",
			str_repeat('i', count($transactionIDs)),
			$transactionIDs
		);
	}
	
	public function checkUnconfirmedRejectedTransactions(){
		if( $transactions = $this->_getRejectedTransactionsPendingConfirmation() ){
			$transactionsConfirmed = $transactionsConfirmationFailed = [];
			foreach ($transactions as $transaction){
				$unconfirmedBalance = $this->getAddressBalance(
					$transaction['CryptocurrencyID'],
					$transaction['MultiSigAddress'],
					0
				);
				
				if (
					$unconfirmedBalance !== false &&
					$unconfirmedBalance == 0
				)
					$transactionsConfirmationFailed[] = $transaction['ID'];
				elseif(
					NXS::compareFloatNumbers(
						$this->getAddressBalance(
							$transaction['CryptocurrencyID'],
							$transaction['MultiSigAddress'],
							REQUIRED_TX_CONFIRMATIONS_ORDER
						),
						$unconfirmedBalance,
						'='
					)
				)
					$this->_setTransactionsConfirmed([$transaction['ID']]);
			}
			
			if ($transactionsConfirmationFailed)
				$this->_setTransactionsConfirmationFailed($transactionsConfirmationFailed);
		}
	}
	
	public function checkTransactionDepositConfirmations(){
		$this->_updateFailedPaymentConfirmations();
		
		if ($transactions = $this->_getTransactionsPendingPaymentConfirmation()){
			$transactionsConfirmed = $transactionsConfirmationFailed = $transactionsCanBumpFee = [];
		
			foreach ($transactions as $transaction){
				$confirmedBalance = $this->getAddressBalance(
					$transaction['CryptocurrencyID'],
					$transaction['MultiSigAddress'],
					REQUIRED_TX_CONFIRMATIONS_ORDER,
					TRUE,
					FALSE,
					$errors
				);
			
				if(
					$confirmed = NXS::compareFloatNumbers(
						$confirmedBalance,
						$transaction['Value'],
						'>='
					)
				){
					//$transactionsConfirmed[] = $transaction['ID'];
					$this->_setTransactionsPlaced([$transaction['ID']]);
					continue;
				}
				
				$unconfirmedBalance = $this->getAddressBalance(
					$transaction['CryptocurrencyID'],
					$transaction['MultiSigAddress'],
					0
				);
				
				/*if ($transaction['FeeBump'] === null)
					$transaction['FeeBump'] = $this->getTransactionFeeBumpRequirement(
						$transaction['ID'],
						$transaction['MultiSigAddress'],
						$transaction['RedeemScript'],
						$transaction['RequiredSignatures']
					);	
				
				if(
					$transaction['FeeBump'] &&
					$canFeeBump = NXS::compareFloatNumbers(
						$unconfirmedBalance,
						$transaction['Value'] + $transaction['FeeBump'],
						'>='
					)
				)
					$transactionsCanBumpFee[] = $transaction['ID'];
				else */ #not yet implemented
				
				if (
					$unconfirmedBalance !== false &&
					NXS::compareFloatNumbers(
						$unconfirmedBalance,
						0,
						'='
					)
				)
					$this->_setTransactionsConfirmationFailed([$transaction['ID']]);
			}
			
			/*if ($transactionsCanBumpFee)
				$this->bumpTransactionsBitcoinFee($transactionsCanBumpFee);*/
		}
		
		return TRUE;
	}
	
	public function signTransactions($cryptocurrencyID){
		if (empty($_POST['signed_transactions'])){
			$_SESSION['sign_response']['signed_transactions'] = 'This cannot be empty';
			return FALSE;
		} else
			$signedTransactions = array_map(
				'trim',
				explode(
					',',
					preg_replace('/\s/', '', $_POST['signed_transactions'])
				)
			);
		
		$rsa = new RSA();
		
		$pendingTransactions = $_SESSION['pending_transactions'];
		$withdrawnTransactionIDs = $usedAddresses = array();
		$errors = false;
		
		$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
		
		foreach($signedTransactions as $signedTransaction){
			try{
				$deserializedTransaction = ElectrumTransaction::deserialize($cryptocurrency, $signedTransaction);
			} catch (Exception $e) {
				$errors = TRUE;
				continue;
			}
			
			$redeemScript = $deserializedTransaction->getRedeemScript();
			
			$pendingTransaction = $pendingTransactions['transactions'][$redeemScript];
			$transactionID = $pendingTransaction['txID'];
			
			if (
				$transactionID &&
				$signedTransaction != $pendingTransaction['hex'] &&
				$deserializedTransaction->isSigned() &&
				$transaction = $this->db->qSelect(
					"
						SELECT
							`RedeemScript`,
							`MultiSigAddress`,
							`Listing`.`VendorID`,
							`Transaction`.`Status` IN ('rejected', 'refunded') isRefund,
							`Transaction`.`BuyerID`,
							`PendingBroadcast`.`UserID` broadcasterID
						FROM
							`Transaction`
						INNER JOIN
							`User` ON
								`User`.`ID` = ?
						INNER JOIN
							`Listing` ON
								`Transaction`.`ListingID` = `Listing`.`ID`
						LEFT JOIN
							`PendingBroadcast` ON
								`Transaction`.`ID` = `PendingBroadcast`.`TransactionID` AND
								`PendingBroadcast`.`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . "
						WHERE
							`Transaction`.`ID` = ? AND
							(
								`User`.`ID` = `Transaction`.`BuyerID`  OR
								`User`.`ID` = `Listing`.`VendorID`
							) AND
							(
								`Transaction`.`Withdrawn` = FALSE OR
								`PendingBroadcast`.`UserID` IS NOT NULL
							)
					",
					'ii',
					[
						$this->User->ID,
						$transactionID
					]
				)
			){
				$transaction = $transaction[0];
				$partiallySignedTransaction = $signedTransaction;
				$previouslyBroadcast =
					$transaction['broadcasterID'] &&
					$this->_removePendingBroadcast($transactionID);
				
				if (
					$transactionQueued = $this->queueTX(
						$transactionID,
						$signedTransaction,
						TRUE,
						$this->User->ID
					)
				)
					unset($_SESSION['pending_transactions']['transactions'][$redeemScript]);
					
				if (
					$transactionQueued &&
					(
						$previouslyBroadcast ||
						$this->markTransactionWithdrawn($transactionID)
					)
				){
					$notificationTypeIDs_vendor = [USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS];
					if ($previouslyBroadcast)
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
							-1,
							$transaction['broadcasterID']
						);
					else {
						$notificationTypeIDs_vendor[] =
							$transaction['isRefund']
								? USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL
								: USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL;
					
						if ($transaction['isRefund'])
							$this->User->incrementUserNotification(
								[
									USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL,
									USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS
								],
								-1,
								$transaction['BuyerID']
							);		
					}
				
					$this->User->incrementUserNotification(
						$notificationTypeIDs_vendor,
						-1,
						$transaction['VendorID']
					);
					
					continue;
				}
			}
			
			$errors = TRUE;
		}
		
		if ($errors){
			$_SESSION['sign_response']['signed_transactions'] = 'One or more transactions were not correctly signed.';
			return false;
		}
		
		return empty($_SESSION['pending_transactions']['transactions']);
	}
	
	public function markTransactionWithdrawn($transactionID){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				INNER JOIN
					`User` ON
						`User`.`ID` = ?
				LEFT JOIN
					`User_Notification` decrementedNotification ON
						`Status` IN ('rejected', 'refunded') AND
						decrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
						(
							(
								`Transaction`.`NotificationIncremented` = TRUE AND
								decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
							) OR
							(
								`Transaction`.`StatusChanged` = TRUE AND
								decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
							)
						)
				SET
					`Transaction`.`Withdrawn` = TRUE,
					`Transaction`.`NotificationIncremented` = IF(
						decrementedNotification.`UserID` IS NOT NULL,
						0,
						`NotificationIncremented`
					),
					decrementedNotification.`Value` = GREATEST(
						0,
						CAST(decrementedNotification.`Value` AS SIGNED) - 1
					)
				WHERE
					`Transaction`.`Withdrawn` = FALSE AND
					`Transaction`.`ID` = ? AND
					(
						`User`.`ID` = `PaymentMethod`.`UserID` OR
						`User`.`ID` = `Transaction`.`BuyerID`
					) AND
					`Transaction`.`Status` IN ('pending feedback', 'rejected', 'refunded')
					
			",
			'ii',
			[
				$this->User->ID,
				$transactionID
			]
		);
	}
	
	public function rateAllTransactionsPositively(){
		return
			$this->db->qQuery(
				"
					INSERT INTO `Transaction_Rating` (
						`TransactionID`,
						`ListingID`,
						`VendorID`,
						`BuyerID`,
						`Rating_Vendor`,
						`Rating_Buyer`,
						`Date`,
						`Content`,
						`ValueTransacted`,
						`AttributeID`
					)
					SELECT
						`Transaction`.`ID`,
						`Transaction`.`ListingID`,
						`PaymentMethod`.`UserID`,
						`Transaction`.`BuyerID`,
						NULL,
						5,
						NOW(),
						NULL,
						0,
						NULL
					FROM
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Feedback_Vendor` = FALSE AND
						`Transaction`.`Timeout` > NOW() AND
						(
							`Transaction`.`Shipped` = TRUE OR
							`Transaction`.`Escrow` = TRUE
						)
					ON DUPLICATE KEY
						UPDATE
							`Rating_Buyer` = 5
				",
				'i',
				[$this->User->ID]
			) +
			$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					SET
						`Transaction`.`Feedback_Vendor` = TRUE
					WHERE
						`PaymentMethod`.`UserID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction`.`Feedback_Vendor` = FALSE AND
						`Transaction`.`Timeout` > NOW() AND
						(
							`Transaction`.`Shipped` = TRUE OR
							`Transaction`.`Escrow` = TRUE
						)
				",
				'i',
				[$this->User->ID]
			);
	}
	
	public function getFeedbackSubscribeToggleState(
		$buyerID,
		$vendorID
	){
		if (
			$states = $this->db->qSelect(
				"
					SELECT
						IFNULL(
							(
								SELECT
									`User_User`.`FollowerID`
								FROM
									`User_User`
								WHERE
									`User_User`.`UserID` = ?
								AND	`User_User`.`FollowerID` = ?
							),
							IF(
								(
									SELECT
										`Transaction`.`ID`
									FROM
										`Transaction`
									INNER JOIN
										`PaymentMethod` ON
											`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
									LEFT JOIN
										`User_User` ON
											`Transaction`.`BuyerID` = `User_User`.`FollowerID` AND
											`PaymentMethod`.`UserID` = `User_User`.`UserID`
									WHERE
										`Transaction`.`ID` > 134914 AND
										`PaymentMethod`.`UserID` = ? AND
										`Transaction`.`BuyerID` = ? AND
										`Transaction`.`Feedback_Buyer` = TRUE AND
										`User_User`.`UserID` IS NULL
									LIMIT 1
								) IS NOT NULL,
								FALSE,
								NULL
							)
						) state
				",
				'iiii',
				[
					$vendorID,
					$buyerID,
					$vendorID,
					$buyerID
				]
			)
		)
			return $states[0]['state'];
			
		return true;
	}
	
	public function rateTransaction($transactionID){
		foreach($_POST as $key => $value)
			$_SESSION['feedback_post'][ $key ] = htmlspecialchars($value);
		
		//$_POST['transaction_comments'] = htmlspecialchars($_POST['transaction_comments']);
		
		if ( !$this->User->IsVendor && !is_numeric($_POST['transaction_rating']) )
			$_SESSION['feedback_response']['transaction_rating'] = 'This is not a valid rating';
		
		if( isset($_SESSION['feedback_response']) )
			return false;
		
		switch($_POST['overall']){
			case 0:
			case 5:
			break;
			default:
				$_POST['overall'] = 5;
		}
		switch($_POST['transaction_rating']){
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
			break;
			default:	
				$_POST['transaction_rating'] = $_POST['overall'];
		}
		
		$attributeID = null;
		if( $this->User->IsVendor ){
			$rating_Vendor = $transactionComments = NULL;
			$rating_Buyer = $_POST['overall'];
			$transactionComments = NULL;
		} else {
			// IS BUYER
			$rating_Buyer = NULL;
			$rating_Vendor = $_POST['transaction_rating'];
			$transactionComments = trim(htmlspecialchars($_POST['transaction_comments']));
			
			if (isset($_POST['rating_attribute']))
				$attributeID = $_POST['rating_attribute'];
				
			if(
				empty($transactionComments) ||
				!preg_match('/[\w]/', $transactionComments) // CONTAINS NO ALPHANUMERIC CHARACTERS; PROBABLY EMPTY
			)
				$transactionComments = NULL;
		}	
		
		$transaction = $this->db->qSelect(
			"
				SELECT
					`Transaction`.`ListingID` AS listingID,
					`Listing`.`VendorID` AS vendorID,
					Vendor.`Alias` vendorAlias,
					`Transaction`.`BuyerID` AS buyerID,
					`Transaction`.`Value` / Cryptocurrency.`1EUR` value
				FROM
					`Transaction`
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` ON
						`User`.`ID` = ?
				INNER JOIN
					`User` Vendor ON
						Vendor.`ID` = `Listing`.`VendorID`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				INNER JOIN
					`Currency` Cryptocurrency ON
						`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
				WHERE
					`Transaction`.`ID` = ? AND
					(
						`Transaction`.`BuyerID` = `User`.`ID` OR
						`Listing`.`VendorID` = `User`.`ID`
					) AND
					`Transaction`.`Status` = 'pending feedback' AND
					`Transaction`.`Value` > 0
			",
			'ii',
			array(
				$this->User->ID,
				$transactionID
			)
		)[0];
		
		if (!$transaction)
			return FALSE;
		
		if (
			!$this->User->IsVendor &&
			(
				(
					isset($_POST['follow_vendor']) &&
					!isset($_POST['is_following_vendor'])
				) ||
				(
					!isset($_POST['follow_vendor']) &&
					isset($_POST['is_following_vendor'])
				)
			)
		)
			$this->User->toggleUserSubscription($transaction['vendorAlias']);
		
		$this->db->qQuery(
			"
				INSERT INTO
					`Transaction_Rating`
						(
							`TransactionID`,
							`ListingID`,
							`VendorID`,
							`BuyerID`,
							`Rating_Vendor`,
							`Rating_Buyer`,
							`Date`,
							`Content`,
							`ValueTransacted`,
							`AttributeID`
						)
				VALUES
					(
						?,
						?,
						?,
						?,
						?,
						?,
						NOW(),
						?,
						?,
						?
					)
				ON DUPLICATE KEY
					UPDATE
						`Rating_Vendor`	= IFNULL(?, `Rating_Vendor`),
						`Rating_Buyer`	= IFNULL(?, `Rating_Buyer`),
						`Content`	= " . ($this->User->IsVendor ? 'IFNULL(?, `Content`)' : '?') . ",
						`AttributeID`	= IF(? = 0, NULL, IFNULL(?, `AttributeID`)),
						`ValueTransacted` = ?
						
			",
			'iiiiiisdiiisiid',
			[
				$transactionID,
				$transaction['listingID'],
				$transaction['vendorID'],
				$transaction['buyerID'],
				$rating_Vendor,
				$rating_Buyer,
				$transactionComments,
				$transaction['value'],
				$attributeID,
				$rating_Vendor,
				$rating_Buyer,
				$transactionComments,
				$attributeID,
				$attributeID,
				$transaction['value']
			]
		);
		
		$isFinalized = $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				INNER JOIN
					`User` ON
						`User`.`ID` = ?
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				SET
					`Feedback_Buyer` = IF(
						`User`.`ID` = `Transaction`.`BuyerID`,
						TRUE,
						`Feedback_Buyer`
					),
					`Feedback_Vendor` = IF(
						`User`.`ID` = `Transaction`.`BuyerID`,
						`Feedback_Vendor`,
						TRUE
					),
					`NotificationIncremented` = IF(
						`User`.`ID` = `Transaction`.`BuyerID`,
						0,
						`NotificationIncremented`
					)
				WHERE
					`Transaction`.`ID` = ? AND
					(
						(
							`Transaction`.`BuyerID`	= `User`.`ID` AND
							`Feedback_Buyer` IS FALSE
						) OR
						(
							`Listing`.`VendorID`	= `User`.`ID` AND
							`Feedback_Vendor` IS FALSE
						)
					)
			",
			'ii',
			[
				$this->User->ID,
				$transactionID
			]
		);
		
		if ($isFinalized){
			// Increase amount transacted
			$previouslyTransacted = $this->User->Attributes['TotalTransacted'];
			$totalTransacted = $this->User->Attributes['TotalTransacted'] + $transaction['value'];
			$this->User->updateAttributes(
				['TotalTransacted' => $totalTransacted]
			);
			if (
				!$this->User->IsVendor &&
				$totalTransacted >= PRIVATE_DOMAINS_BUYER_CRITERION_MINIMUM_TRANSACTED_EUR
			)
				$this->User->allocateUserDomains();
			
			if ($this->User->IsVendor)
				$this->db->incrementStatistic(
					'transacted',
					floor($transaction['value'])
				);
			else
				$this->User->incrementUserNotification(
					[
						USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK,
						USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS
					],
					-1
				);
		}
		
		unset(
			$_SESSION['feedback_post'],
			$_SESSION['feedback_response']
		);
		
		return TRUE;
	}
	
	public function getRatings($transactionID){
		if (
			$rating = $this->db->qSelect(
				"
					SELECT
						`Rating_Vendor` rating,
						`Content` comments,
						`AttributeID`
					FROM
						`Transaction_Rating`
					WHERE
						`Transaction_Rating`.`TransactionID` = ? AND
						`Transaction_Rating`.`BuyerID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			)[0]
		)
			return [
				'transactionRating' => $rating['rating'],
				'comments' => $rating['comments'],
				'AttributeID' => $rating['AttributeID']
			];
		
		return false;
	}
	
	private function markTransactionDisputeRead($transactionID){
		return	$this->db->qSelect(
				"
					UPDATE
						`Transaction`
					SET
						`MediatorSeenMessageID` = (
							SELECT
								`ID`
							FROM
								`Transaction_Message`
							WHERE
								`TransactionID` = `Transaction`.`ID`
							ORDER BY
								`DateTime` DESC,
								`ID` DESC
							LIMIT
								1
						)
					WHERE
						`ID` = ? AND
						`MediatorID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			);
	}
	
	public function getDisputeMessages($transactionID, $page){
		$stmt_countDisputeMessages = $this->db->prepare("
			SELECT
				COUNT(DISTINCT `Transaction_Message`.`ID`),
				`PaymentMethod`.`CryptocurrencyID`
			FROM
				`Transaction_Message`
			INNER JOIN
				`Transaction` ON
					`Transaction_Message`.`TransactionID` = `Transaction`.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			WHERE
				`Transaction_Message`.`TransactionID` = ? AND
				(
					`Transaction`.`BuyerID` = ? OR
					`PaymentMethod`.`UserID` = ? OR
					`Transaction`.`MediatorID` = ?
				) AND
				(
					`Transaction_Message`.`Encrypted` = FALSE OR
					CASE ?
						WHEN `Transaction`.`BuyerID` THEN `Transaction_Message`.`Message_Buyer`
						WHEN `PaymentMethod`.`UserID` THEN `Transaction_Message`.`Message_Vendor`
						WHEN `Transaction`.`MediatorID` THEN `Transaction_Message`.`Message_Mediator`
					END IS NOT NULL
				)
		");
		
		$stmt_getDisputeMessages = $this->db->prepare("
			SELECT
				`Transaction_Message`.`ID`,
				`User`.`Alias`,
				`User`.`Reputation`,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					`Image`.`Filename`
				) Image,
				IF(`Transaction_Message`.`SenderID` = ?, TRUE, FALSE),
				IFNULL(
					`Transaction_Message`.`Message`,
					CASE ?
						WHEN `Transaction`.`BuyerID` THEN `Transaction_Message`.`Message_Buyer`
						WHEN `Listing`.`VendorID` THEN `Transaction_Message`.`Message_Vendor`
						WHEN `Transaction`.`MediatorID` THEN `Transaction_Message`.`Message_Mediator`
					END
				) AS MyMessage,
				`Transaction_Message`.`Encrypted`
			FROM
				`Transaction_Message`
			INNER JOIN	`Transaction`
				ON `Transaction_Message`.`TransactionID` = `Transaction`.`ID`
			INNER JOIN	`Listing`
				ON `Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN	`User`
				ON `Transaction_Message`.`SenderID` = `User`.`ID`
			LEFT JOIN
				`Image` ON
					`User`.`ImageID` = `Image`.`ID`
			WHERE
				`Transaction_Message`.`TransactionID` = ? AND
				(
					`Transaction`.`BuyerID` = ? OR
					`Listing`.`VendorID` = ? OR
					`Transaction`.`MediatorID` = ?
				) AND
				(
					`Transaction_Message`.`Encrypted` = FALSE OR
					CASE ?
						WHEN `Transaction`.`BuyerID` THEN `Transaction_Message`.`Message_Buyer`
						WHEN `Listing`.`VendorID` THEN `Transaction_Message`.`Message_Vendor`
						WHEN `Transaction`.`MediatorID` THEN `Transaction_Message`.`Message_Mediator`
					END IS NOT NULL
				)
			ORDER BY
				`Transaction_Message`.`DateTime` ASC,
				`Transaction_Message`.`ID` ASC
			LIMIT
				?, " . DISPUTE_MESSAGES_PER_PAGE . "
		");
		
		if (false !== $stmt_countDisputeMessages && false !== $stmt_getDisputeMessages){
			$stmt_countDisputeMessages->bind_param('iiiii', $transactionID, $this->User->ID, $this->User->ID, $this->User->ID, $this->User->ID);
			$stmt_countDisputeMessages->execute();
			$stmt_countDisputeMessages->store_result();
			$stmt_countDisputeMessages->bind_result($dispute_message_count, $cryptocurrencyID);
			$stmt_countDisputeMessages->fetch();
			
			if ($dispute_message_count > 0){
				if (!$page)
					$page = ceil($dispute_message_count/DISPUTE_MESSAGES_PER_PAGE);
				
				if (
					$this->User->IsMod &&
					$page == ceil($dispute_message_count/DISPUTE_MESSAGES_PER_PAGE) // is last page
				)
					$this->markTransactionDisputeRead($transactionID);
				
				if (ceil($dispute_message_count/DISPUTE_MESSAGES_PER_PAGE) < $page){
					$offset = 0;
					$this->User->Notifications->quick('FatalError', 'Invalid Page');
				} else
					$offset = DISPUTE_MESSAGES_PER_PAGE*($page - 1);
				
				$stmt_getDisputeMessages->bind_param('iiiiiiii', $this->User->ID, $this->User->ID, $transactionID, $this->User->ID, $this->User->ID, $this->User->ID, $this->User->ID, $offset);
				$stmt_getDisputeMessages->execute();
				$stmt_getDisputeMessages->store_result();
				$stmt_getDisputeMessages->bind_result(
					$message_id,
					$sender_alias,
					$sender_reputation,
					$sender_image,
					$is_sender,
					$my_message,
					$isEncrypted
				);
				
				$rsa = new RSA();
				
				$dispute_messages = $verdicts = [];
				$i = 0;
				while ($stmt_getDisputeMessages->fetch()){
					$my_message = json_decode($isEncrypted ? $rsa->qDecrypt($my_message) : $my_message, true);
					$is_sender = !empty($is_sender);
					
					if (
						$isVerdict =
							$my_message['Type'] == 'proposal' &&
							$my_message['Proposal']['Type'] == 'refund' &&
							$my_message['Proposal']['Content']['isVerdict']
					)
						$verdicts[] = $i;
					
					$dispute_messages[$i++] = array(
						'id' => $message_id,
						'sender_alias' => $sender_alias,
						'sender_reputation' => $sender_reputation,
						'sender_image' => NXS::getPictureVariant($sender_image, IMAGE_THUMBNAIL_SUFFIX),
						'is_sender' => $is_sender,
						'my_message' => $my_message
					);
				}
				
				if ($lastVerdict = array_pop($verdicts)){
					$verdict = &$dispute_messages[$lastVerdict];
					
					$deserializedTransaction = ElectrumTransaction::deserialize(
						$this->User->getCryptocurrency($cryptocurrencyID),
						$verdict['my_message']['Proposal']['Content']['hex']
					);
					
					if ($extendedPublicKey = $this->getTransactionExtendedPublicKey($transactionID)){
						$deserializedTransaction->addExtendedPublicKey(...$extendedPublicKey);
						$verdict['my_message']['Proposal']['Content']['hex'] = $deserializedTransaction->serialize();
					}
					
					foreach($verdicts as $verdictIndex)
						unset($dispute_messages[$verdictIndex]);
				}
				
				return array($page, $dispute_message_count, $dispute_messages);
			} else
				return false;	
		}
	}
	
	public function sendMessage($transactionID){
		foreach($_POST as $key => $value)
			$_SESSION['dispute_post'][ $key ] = htmlspecialchars($value);
		
		$_POST['message'] = htmlspecialchars($_POST['message']);
		
		// VALIDATE MESSAGE
		if (
			!empty($_POST['proposal_type']) &&
			(
				empty($_POST['percentage']) ||
				!is_numeric($_POST['percentage']) ||
				$_POST['percentage'] < 0 ||
				$_POST['percentage'] > 100
			)
		)
			$_SESSION['dispute_response']['percentage'] = 'Must be between 0 and 100.';
		
		if (isset($_SESSION['dispute_response']))
			return false;
		
		$stmt_checkTransaction = $this->db->prepare("
			SELECT
				`Transaction`.`Value`,
				`Transaction`.`RedeemScript`,
				`Transaction`.`MultiSigAddress`,
				`Transaction`.`BuyerID`,
				`Listing`.`VendorID`,
				`Transaction`.`MediatorID`,
				CASE ?
					WHEN `Transaction`.`BuyerID` THEN `Transaction`.`NextTX_Buyer`
					WHEN `Listing`.`VendorID` THEN `Transaction`.`NextTX_Vendor`
				END AS NextTX,
				Vendor.`Commission`,
				`PaymentMethod`.`CryptocurrencyID`,
				`Transaction`.`Segwit`
			FROM
				`Transaction`
			INNER JOIN
				`Listing` ON
					`Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN
				`User` Vendor ON
					`Listing`.`VendorID` = Vendor.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			WHERE
				`Transaction`.`ID` = ? AND
				(
					`Transaction`.`BuyerID` = ? OR
					`Listing`.`VendorID` = ? OR
					`Transaction`.`MediatorID` = ?
				)
		");
		
		$stmt_insertDisputeMessage = $stmt_insertDisputeProposal = $this->db->prepare("
			INSERT INTO
				`Transaction_Message` (
					`TransactionID`,
					`SenderID`,
					`Message_Buyer`,
					`Message_Vendor`,
					`Message_Mediator`,
					`Encrypted`,
					`Message`
				)
			VALUES
				(
					?,
					?,
					NULL,
					NULL,
					NULL,
					FALSE,
					?
				)
		");
		
		if (
			false !== $stmt_checkTransaction &&
			false !== $stmt_insertDisputeMessage
		){
			$stmt_checkTransaction->bind_param(
				'iiiii',
				$this->User->ID,
				$transactionID,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID
			);
			$stmt_checkTransaction->execute();
			$stmt_checkTransaction->store_result();
			
			if ($stmt_checkTransaction->num_rows == 1){
				$stmt_checkTransaction->bind_result(
					$tx_value,
					$redeem_script,
					$multisig_address,
					$buyer_id,
					$vendor_id,
					$mediator_id,
					$next_tx,
					$vendor_commission,
					$cryptocurrencyID,
					$isSegwit
				);
				$stmt_checkTransaction->fetch();
				
				$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
				
				if ($vendor_commission)
					$marketplaceFee = $vendor_commission/1000;
				else
					$marketplaceFee = MARKETPLACE_FEE;
				
				$proposal_id = false;
				
				if (!empty($_POST['message'])) {
					$message_buyer = $message_vendor = $message_mediator = $_POST['message'];
					
					$message_array = $message_array_vendor = $message_array_buyer = $message_array_mediator = array(
						'Type' => 'message',
						'Time' => date('j M Y H:i'),
						'Message' => $_POST['message']
					);
					
					/*
					
					// APPLY RSA (and PGP) ENCRYPTION
					list(
						$buyer_rsa,
						$buyer_pgp,
						$buyer_encrypt_pgp
					) = $this->User->Info(
						0,
						$buyer_id,
						'PublicKey',
						'PGP',
						'EncryptPGP'
					);
					if($buyer_encrypt_pgp){
						$pgp_buyer = new PGP($buyer_pgp);
						$message_buyer = $pgp_buyer->qEncrypt($_POST['message']);
						$message_array_buyer = array_merge( $message_array, array(
							'Message' => $message_buyer
						));
					}
					
					list($vendor_rsa, $vendor_pgp, $vendor_encrypt_pgp) = $this->User->Info(0, $vendor_id, 'PublicKey', 'PGP', 'EncryptPGP');
					if($vendor_encrypt_pgp){
						$pgp_vendor = new PGP($vendor_pgp);
						$message_vendor = $pgp_vendor->qEncrypt($_POST['message']);
						$message_array_vendor = array_merge( $message_array, array(
							'Message' => $message_vendor
						));
					}
					
					if( !empty($mediator_id) ){
						$message_array_mediator = $message_array;
						list($mediator_rsa, $mediator_pgp, $mediator_encrypt_pgp) = $this->User->Info(0, $mediator_id, 'PublicKey', 'PGP', 'EncryptPGP');
						if($mediator_encrypt_pgp){
							$pgp_mediator = new PGP($mediator_pgp);
							$message_mediator = $pgp_mediator->qEncrypt($_POST['message']);
							$message_array_mediator = array_merge( $message_array, array(
								'Message' => $message_mediator
							));
						}
					} else {
						$message_encrypted_mediator = false;
					}
					
					$rsa = new RSA();
					$message_encrypted_buyer = $rsa->qEncrypt( json_encode( $message_array_buyer ), $buyer_rsa );
					$message_encrypted_vendor = $rsa->qEncrypt( json_encode( $message_array_vendor ), $vendor_rsa );
					$message_encrypted_mediator = empty($mediator_id) ? false : $rsa->qEncrypt( json_encode( $message_array_mediator ), $mediator_rsa );
					*/
					
					
					$stmt_insertDisputeMessage->bind_param(
						'iis',
						$transactionID,
						$this->User->ID,
						json_encode($message_array_buyer)
					);
					$stmt_insertDisputeMessage->execute();
				}
				
				$new_message_id = $proposal_type = false;
				
				if (isset($_POST['proposal_type'])){
					switch ($_POST['proposal_type']){
						case 'reship':
							$proposal_type = $_POST['proposal_type'];
							$proposal_content = false;
						break;
						case 'refund':
							$proposal_type = $_POST['proposal_type'];
							
							$rsa = isset($rsa) ? $rsa : new RSA();
							
							$transactions = json_decode( $rsa->qDecrypt($next_tx), TRUE );
							
							if (
								list(
									$inputsValue,
									$inputs
								) = $this->getUnspentOutputs(
									$cryptocurrency->ID,
									$multisig_address,
									REQUIRED_TX_CONFIRMATIONS_BROADCAST,
									FALSE,
									$redeem_script
								)
							){
								$vendorAddress = $this->getVendorBIP32AddressForTransaction($transactionID);
								$buyerAddress = $returnAddress = $this->getTransactionRefundAddress($transactionID);
								$marketplaceAddress = $this->getMarketplaceAddressForTransaction($transactionID);
								$minimumMarketOutput = $this->_calculateMinimumMarketOutput($cryptocurrency);
								
								$minerFee = $this->estimateDynamicFee(
									$cryptocurrency,
									$inputs,
									[
										$vendorAddress,
										$buyerAddress,
										$marketplaceAddress
									],
									$inputsValue,
									$redeem_script,
									2,
									$this->getCryptocurrencyFeePerKilobyte(
										CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT,
										$cryptocurrency->ID
									)
								);
								
								if( $_POST['percentage'] == '100' ){
									$outputs = [
										$buyerAddress => $cryptocurrency->parseValue($inputsValue - $minerFee, true)
									];
								} else {
									if (!$vendorAddress)
										return TRUE; 
									
									if( $_POST['percentage'] == '0' ){
										if( NXS::compareFloatNumbers($inputsValue, $tx_value, '>') ){
											// Return Excess to Buyer
											$value_marketplace	= max($minimumMarketOutput, $cryptocurrency->parseValue($tx_value * $marketplaceFee));
											$value_vendor		= $cryptocurrency->parseValue($tx_value - $value_marketplace - $minerFee, true);
											$value_buyer		= $cryptocurrency->parseValue($inputsValue - $value_marketplace - $value_vendor - $minerFee, true);
										} else {
											$value_marketplace	= max($minimumMarketOutput, $cryptocurrency->parseValue($inputsValue * $marketplaceFee));
											$value_vendor		= $cryptocurrency->parseValue($inputsValue - $value_marketplace - $minerFee, true);
											$value_buyer		= 0;
										}
									} else {
										$fraction = $_POST['percentage']/100;
										
										$value_vendor		= $cryptocurrency->parseValue(($tx_value * (1 - $marketplaceFee) - $minerFee) * (1 - $fraction), true);
										$value_marketplace	= max($minimumMarketOutput, $cryptocurrency->parseValue($marketplaceFee * $tx_value));
										$value_buyer		= $cryptocurrency->parseValue($inputsValue - $value_vendor - $value_marketplace - $minerFee, true);
									}
									
									$outputs = [
										$marketplaceAddress => $value_marketplace
									];
									
									if ( NXS::compareFloatNumbers($value_buyer, 0, '>') )
										$outputs[$buyerAddress] = $value_buyer;
										
									if ( NXS::compareFloatNumbers($value_vendor, 0, '>') )
										$outputs[$vendorAddress] = $value_vendor;
								}
								
								$rawTransaction = new ElectrumTransaction(
									$cryptocurrency,
									$redeem_script,
									$inputs,
									$outputs,
									$isSegwit
								);
								
								if (!$this->User->IsMod && $extendedPublicKey = $this->getTransactionExtendedPublicKey($transactionID))
									$rawTransaction->addExtendedPublicKey(...$extendedPublicKey);
								
								$proposal_content = [
									'hex'		=> $rawTransaction->serialize(),
									'isVerdict'	=> $this->User->IsMod
								];
							} else 
								return false;
						break;
						default:
							$proposal_type = false;
					}
					
					if ($proposal_type){
						$rsa = isset($rsa) ? $rsa : new RSA();
						
						$proposal_array = array(
							'Type' => 'proposal',
							'Time' => date('j M Y H:i'),
							'Proposal' => array(
								'Type' => $proposal_type,
								'Value' => $_POST['percentage'],
								'Content' => $proposal_content
							)
						);
						
						/*
						$buyer_rsa = isset($buyer_rsa) ? $buyer_rsa : $this->User->Info(0, $buyer_id, 'PublicKey');
						$proposal_encrypted_buyer = $rsa->qEncrypt( json_encode($proposal_array), $buyer_rsa );
						
						$vendor_rsa = isset($vendor_rsa) ? $vendor_rsa : $this->User->Info(0, $vendor_id, 'PublicKey');
						$proposal_encrypted_vendor = $rsa->qEncrypt( json_encode($proposal_array), $vendor_rsa );
						
						if ($mediator_id){
							$mediator_rsa = isset($mediator_rsa) ? $mediator_rsa : $this->User->Info(0, $mediator_id, 'PublicKey');
							$proposal_encrypted_mediator = $rsa->qEncrypt( json_encode($proposal_array), $mediator_rsa );
						} else {
							$proposal_encrypted_mediator = false;
						}
						*/
						
						$stmt_insertDisputeProposal->bind_param(
							'iis',
							$transactionID,
							$this->User->ID,
							json_encode($proposal_array)
						);
						$stmt_insertDisputeProposal->execute();
						
						$proposal_id = $stmt_insertDisputeProposal->insert_id;	
					}	
				}
				
				unset(
					$_SESSION['dispute_post'],
					$_SESSION['dispute_response']
				);
				
				return array(
					'id' => $proposal_id,
					'type' => $proposal_type
				);	
			}	
		}	
	}
	
	public function callMediator($transactionID) {
		if( $stmt_updateTransaction = $this->db->prepare("
			UPDATE
				`Transaction`
			INNER JOIN
				`Listing` ON `Transaction`.`ListingID` = `Listing`.`ID`
			SET
				`Timeout` = NOW()
			WHERE
				`Transaction`.`ID` = ?
			AND (`Transaction`.`BuyerID` = ? OR `Listing`.`VendorID` = ?)
			AND	`Timeout` < NOW() + INTERVAL " . (IN_DISPUTE_TIMEOUT_DAYS - CALL_MEDIATOR_DAYS) . " DAY
		") ){
			
			$stmt_updateTransaction->bind_param('iii', $transactionID, $this->User->ID, $this->User->ID);
			
			if( $stmt_updateTransaction->execute() ){
				
				return true;
				
			} else {
				
				return false;
				
			}
			
		}
		
	}
	
	public function withdrawProposal($proposal_id){
		
		$stmt_checkProposal = $this->db->prepare("
			SELECT
				`TransactionID`
			FROM
				`Transaction_Message`
			WHERE
				`ID` = ?
			AND	`SenderID` = ?
			LIMIT	1
		");
		
		$stmt_deleteProposal = $this->db->prepare("
			DELETE FROM
				`Transaction_Message`
			WHERE
				`Transaction_Message`.`ID` = ?
			AND	`SenderID` = ?
		");
		
		if( false !== $stmt_checkProposal && false !== $stmt_deleteProposal ){
			
			$stmt_checkProposal->bind_param('ii', $proposal_id, $this->User->ID);
			$stmt_checkProposal->execute();
			$stmt_checkProposal->store_result();
			
			if( $stmt_checkProposal->num_rows == 1 ){
				
				$stmt_checkProposal->bind_result($transactionID);
				$stmt_checkProposal->fetch();
				
				$stmt_deleteProposal->bind_param('ii', $proposal_id, $this->User->ID);
				
				if( $stmt_deleteProposal->execute() ){
					
					return $transactionID;
					
				}
				
			} else {
				
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'Proposal could not be found',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
				return false;
				
			}
			
		}
	}
	
	public function signProposal($proposal_id){
		if ($stmt_getProposal = $this->db->prepare("
			SELECT
				`Transaction`.`ID`,
				`Transaction`.`RedeemScript`,
				`Transaction`.`MultiSigAddress`,
				IFNULL(
					`Transaction_Message`.`Message`,
					CASE ?
						WHEN `Transaction`.`BuyerID` THEN `Transaction_Message`.`Message_Buyer`
						WHEN `Listing`.`VendorID` THEN `Transaction_Message`.`Message_Vendor`
						WHEN `Transaction`.`MediatorID` THEN `Transaction_Message`.`Message_Mediator`
					END
				) AS MyMessage,
				CASE ?
					WHEN `Transaction`.`BuyerID` THEN `Transaction`.`NextTX_Buyer`
					WHEN `Listing`.`VendorID` THEN `Transaction`.`NextTX_Vendor`
					WHEN `Transaction`.`MediatorID` THEN `Transaction`.`NextTX_Site`
				END AS MyTransaction,
				`Listing`.`Name`,
				Vendor.`ID`,
				Vendor.`Alias`,
				Buyer.`ID`,
				Buyer.`Alias`,
				`Transaction`.`MediatorID`,
				`PaymentMethod`.`CryptocurrencyID`,
				`Transaction_Message`.`Encrypted`
			FROM
				`Transaction_Message`
			INNER JOIN
				`Transaction` ON
					`Transaction_Message`.`TransactionID` = `Transaction`.`ID`
			INNER JOIN
				`Listing` ON
					`Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN
				`User` Vendor ON
					`Listing`.`VendorID` = Vendor.`ID`
			INNER JOIN
				`User` Buyer ON
					`Transaction`.`BuyerID` = Buyer.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			WHERE
				`Transaction_Message`.`ID` = ? AND
				(
					`Transaction`.`BuyerID` = ? OR
					`Listing`.`VendorID` = ? OR
					`Transaction`.`MediatorID` = ?
				)
			LIMIT	1
		") ){
			$stmt_getProposal->bind_param('iiiiii', $this->User->ID, $this->User->ID, $proposal_id, $this->User->ID, $this->User->ID, $this->User->ID);
			$stmt_getProposal->execute();
			$stmt_getProposal->store_result();
			
			if ($stmt_getProposal->num_rows == 1){
				$stmt_getProposal->bind_result(
					$transactionID,
					$redeem_script,
					$multisig_address,
					$my_message,
					$my_transaction,
					$listing_name,
					$vendor_id,
					$vendor_alias,
					$buyer_id,
					$buyer_alias,
					$mediator_id,
					$cryptocurrencyID,
					$encrypted
				);
				$stmt_getProposal->fetch();
				
				$rsa = new RSA();
				
				$rsa_tx = $mediator_id == $this->User->ID ? new RSA(SITE_RSA_PRIVATE_KEY) : $rsa;
				
				$message = json_decode($encrypted ? $rsa->qDecrypt($my_message) : $my_message, true);
				
				if ($message['Type'] == 'proposal'){
					$proposal = $message['Proposal'];
					
					$transactions = json_decode( $rsa_tx->qDecrypt($my_transaction), true);
					
					$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
					
					try {
						$deserializedTransaction = ElectrumTransaction::deserialize($cryptocurrency, trim($_POST['signed_transaction']));
					} catch (Exception $e) {
						$_SESSION['dispute_response']['signed_transaction'] = 'This transaction does not appear to have been signed correctly.';
						return array(false, $transactionID, false);
					}

					if (
						list(
							$inputsValue,
							$inputs
						) = $this->getUnspentOutputs(
							$cryptocurrencyID,
							$multisig_address,
							REQUIRED_TX_CONFIRMATIONS_BROADCAST,
							FALSE,
							$redeem_script
						)
					){
						if(
							!empty($_POST['signed_transaction']) &&
							$deserializedTransaction->isSigned(1)
						){
							if ($message['Proposal']['Content']['isVerdict']) {
								if (
									$this->_signAndPushDisputeRefundTransaction(
										$transactionID,
										$this->User->getCryptocurrency($cryptocurrencyID),
										trim($_POST['signed_transaction'])
									)
								)
									return array(true, $transactionID, 'feedback');
							} elseif (
								$stmt_updateProposal = $this->db->prepare("
									UPDATE
										`Transaction_Message`
									INNER JOIN
										`Transaction` ON
											`Transaction_Message`.`TransactionID` = `Transaction`.`ID`
									INNER JOIN
										`Listing` ON
											`Transaction`.`ListingID` = `Listing`.`ID`
									SET
										`Transaction_Message`.`Message` = ?
									WHERE
										`Transaction_Message`.`ID` = ? AND
										(
											`Transaction`.`BuyerID` = ? OR
											`Transaction`.`MediatorID` = ? OR
											`Listing`.`VendorID` = ?
										)
								")
							){
								$proposal_array = array_merge(
									$message,
									[
										'Proposal' => array_merge(
											$message['Proposal'],
											[
												'Content' => [
													'hex' => $_POST['signed_transaction'],
													'complete' => true	
												]
											]
										)
									]
								);
								
								/*
								$buyer_rsa = isset($buyer_rsa) ? $buyer_rsa : $this->User->Info(0, $buyer_id, 'PublicKey');
								$proposal_encrypted_buyer = $rsa->qEncrypt( json_encode($proposal_array), $buyer_rsa );
								
								$vendor_rsa = isset($vendor_rsa) ? $vendor_rsa : $this->User->Info(0, $vendor_id, 'PublicKey');
								$proposal_encrypted_vendor = $rsa->qEncrypt( json_encode($proposal_array), $vendor_rsa );
								
								if( $mediator_id ){
									$mediator_rsa = isset($mediator_rsa) ? $mediator_rsa : $this->User->Info(0, $mediator_id, 'PublicKey');
									$proposal_encrypted_mediator = $rsa->qEncrypt( json_encode($proposal_array), $mediator_rsa );
								} else {
									$proposal_encrypted_mediator = false;
								}
								*/
								
								$stmt_updateProposal->bind_param(
									'siiii',
									json_encode($proposal_array),
									$proposal_id,
									$this->User->ID,
									$this->User->ID,
									$this->User->ID
								);
								
								if ($stmt_updateProposal->execute())
									return array(true, $transactionID, 'dispute');
							}
						}
						
						$_SESSION['dispute_response']['signed_transaction'] = 'This transaction does not appear to have been signed correctly.';
						return array(false, $transactionID, false);
					}
				} else	
					$_SESSION['temp_notifications'][] = array(
						'Content' => 'This is not a valid proposal',
						'Design' => array(
							'Color' => 'red',
							'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						)
					);
				
				return array(false, $transactionID, false);
			} else
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'Proposal could not be found',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
		}
		
		return false;	
	}
	
	private function _signAndPushDisputeRefundTransaction(
		$transactionID,
		$cryptocurrency,
		$hex
	){
		$privateKey_wif_site = $this->getBIP32PrivateKeyWIF(
			$transactionID,
			$cryptocurrency->prefixPublic
		);
		
		$signedTransaction = ElectrumTransaction::signTransaction(
			$cryptocurrency,
			$hex,
			$privateKey_wif_site
		);
		
		return	$this->pushTX(
				$cryptocurrency->ID,
				$signedTransaction
			) &&
			$this->db->qQuery(
				"
					UPDATE
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
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							(
								decrementedNotification.`UserID` IN (Vendor.`ID`, Buyer.`ID`) AND
								decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE . "
							) OR
							(
								decrementedNotification.`UserID` = Vendor.`ID` AND
								decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS . "
							)
					SET
						`Status` = 'pending feedback',
						Buyer.`BuyCount` = Buyer.`BuyCount` + 1,
						Vendor.`SellCount` = Vendor.`SellCount` + 1,
						`Withdrawn` = TRUE,
						`Timeout` = NOW() + INTERVAL " . PENDING_FEEDBACK_DAYS . " DAY,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Transaction`.`ID` = ? AND
						(
							`Transaction`.`BuyerID` = ? OR
							`Transaction`.`MediatorID` = ? OR
							`Listing`.`VendorID` = ?
						)
				",
				'iiii',
				[
					$transactionID,
					$this->User->ID,
					$this->User->ID,
					$this->User->ID
				]
			);
	}
	
	public function acceptProposal($proposal_id){
		if( $stmt_getProposal = $this->db->prepare("
			SELECT
				`Transaction`.`ID`,
				`Transaction`.`RedeemScript`,
				`Transaction`.`MultiSigAddress`,
				IFNULL(
					`Transaction_Message`.`Message`,
					CASE ?
						WHEN `Transaction`.`BuyerID` THEN `Transaction_Message`.`Message_Buyer`
						WHEN `Listing`.`VendorID` THEN `Transaction_Message`.`Message_Vendor`
						WHEN `Transaction`.`MediatorID` THEN `Transaction_Message`.`Message_Mediator`
					END
				) AS MyMessage,
				CASE ?
					WHEN `Transaction`.`BuyerID` THEN `Transaction`.`NextTX_Buyer`
					WHEN `Listing`.`VendorID` THEN `Transaction`.`NextTX_Vendor`
					WHEN `Transaction`.`MediatorID` THEN `Transaction`.`NextTX_Site`
				END AS MyTransaction,
				CASE ?
					WHEN `Transaction`.`BuyerID` THEN `Transaction`.`Order_Buyer`
					WHEN `Listing`.`VendorID` THEN `Transaction`.`Order_Vendor`
					WHEN `Transaction`.`MediatorID` THEN FALSE
				END AS MyOrder,
				`Listing`.`Name`,
				Vendor.`ID`,
				Vendor.`Alias`,
				Buyer.`ID`,
				Buyer.`Alias`,
				`Transaction`.`MediatorID`,
				`PaymentMethod`.`CryptocurrencyID`,
				`Transaction_Message`.`Encrypted`
			FROM
				`Transaction_Message`
			INNER JOIN
				`Transaction` ON
					`Transaction_Message`.`TransactionID` = `Transaction`.`ID`
			INNER JOIN
				`Listing` ON
					`Transaction`.`ListingID` = `Listing`.`ID`
			INNER JOIN
				`User` Vendor ON
					`Listing`.`VendorID` = Vendor.`ID`
			INNER JOIN
				`User` Buyer ON
					`Transaction`.`BuyerID` = Buyer.`ID`
			INNER JOIN
				`PaymentMethod` ON
					`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
			WHERE
				`Transaction_Message`.`ID` = ? AND
				(
					`Transaction`.`BuyerID` = ? OR
					`Listing`.`VendorID` = ? OR
					`Transaction`.`MediatorID` = ?
				) AND
				`Transaction_Message`.`SenderID` != ?
			LIMIT	1
		") ){
			$stmt_getProposal->bind_param(
				'iiiiiiii',
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$proposal_id,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID
			);
			$stmt_getProposal->execute();
			$stmt_getProposal->store_result();
			
			if( $stmt_getProposal->num_rows == 1 ){
				$stmt_getProposal->bind_result(
					$transactionID,
					$redeem_script,
					$multisig_address,
					$my_message,
					$my_transaction,
					$my_order,
					$listing_name,
					$vendor_id,
					$vendor_alias,
					$buyer_id,
					$buyer_alias,
					$mediator_id,
					$cryptocurrencyID,
					$encrypted
				);
				$stmt_getProposal->fetch();
				$transactionIdentifier = $this->getTransactionIdentifier($transactionID);
				$cryptocurrency = $this->User->getCryptocurrency($cryptocurrencyID);
				
				$rsa = new RSA();
				
				$rsa_tx = $mediator_id == $this->User->ID ? new RSA(SITE_RSA_PRIVATE_KEY) : $rsa;
				
				$message = json_decode($encrypted ? $rsa->qDecrypt($my_message) : $my_message, true);
				
				if( $message['Type'] == 'proposal' ){
					$proposal = $message['Proposal'];
					
					$transactions = json_decode( $rsa_tx->qDecrypt($my_transaction), true);
					$order = $my_order ? json_decode( $rsa_tx->qDecrypt($my_order), true) : FALSE;
					
					switch($proposal['Type']){
						case 'refund':
							if (
								$this->_signAndPushDisputeRefundTransaction(
									$transactionID,
									$cryptocurrency,
									$proposal['Content']['hex']
								)
							)
								return array('tx/' . $transactionIdentifier . '/', $transactionID);
							else {
								$_SESSION['temp_notifications'][] = array(
									'Content' => 'This transaction does not appear to have been signed correctly.',
									'Design' => array(
										'Color' => 'red',
										'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
									)
								);
								return array(false, $transactionID);
							}
						break;
						case 'reship':
							$new_tx = array_merge($transactions, array(
								'reship_percentage' => $proposal['Value']
							));
							
							$new_tx_encrypted_buyer = $rsa->qEncrypt( json_encode( $new_tx ), $this->User->Info(0, $buyer_id, 'PublicKey') );
							
							$new_tx_encrypted_vendor = $rsa->qEncrypt( json_encode( $new_tx ), $this->User->Info(0, $vendor_id, 'PublicKey') );
							
							$timeout = $order['EscrowTimeout'];
							
							$stmt_updateTransaction = $this->db->prepare("
								UPDATE
									`Transaction`
								SET
									`Status` = 'in transit',
									`NextTX_Vendor` = ?,
									`NextTX_Buyer` = ?,
									`Timeout` = NOW() + INTERVAL ? DAY
								WHERE
									`ID` = ?
							");
							
							$stmt_deleteProposals = $this->db->prepare("
								DELETE
									`Transaction_Message`
								FROM
									`Transaction_Message`
								INNER JOIN	`Transaction`
									ON	`Transaction_Message`.`TransactionID` = `Transaction`.`ID`
								INNER JOIN	`Listing`
									ON	`Transaction`.`ListingID` = `Listing`.`ID`
								WHERE
									`Transaction`.`ID` = ?
								AND	(`Transaction`.`BuyerID` = ? OR `Listing`.`VendorID` = ?)
							");
							
							if (
								$stmt_updateTransaction !== false &&
								$stmt_deleteProposals !== false
							){
								
								$stmt_updateTransaction->bind_param(
									'ssii',
									$new_tx_encrypted_vendor,
									$new_tx_encrypted_buyer,
									$timeout,
									$transactionID
								);
								$stmt_deleteProposals->bind_param('iii', $transactionID, $this->User->ID, $this->User->ID);
								
								if ($stmt_updateTransaction->execute()){
									$stmt_deleteProposals->execute();
									
									$this->User->incrementUserNotification(
										USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE,
										-1,
										[
											$vendor_id,
											$buyer_id
										]
									);
									$this->User->incrementUserNotification(
										USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
										-1,
										$vendor_id
									);
									
									return array('tx/' . $transactionIdentifier . '/', $transactionID);
								} else
									return array(false, $transactionID);	
							}
						break;
					}
				} else {
					$_SESSION['temp_notifications'][] = array(
						'Content' => 'This is not a valid proposal',
						'Design' => array(
							'Color' => 'red',
							'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
						)
					);
					return array(false, $transactionID);
				}
			} else
				$_SESSION['temp_notifications'][] = array(
					'Content' => 'Proposal could not be found',
					'Design' => array(
						'Color' => 'red',
						'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
					)
				);
		}
		
		return false;	
	}
	
	
	public function toggleTransactionShipped($transactionID){
		$m = $this->db->m;
		$mKey = 'recentAction-' . $this->User->ID . '-toggleShipped-' . $transactionID;
		
		if ($m->get($mKey)){
			$shipped = FALSE;
			$m->delete($mKey);
			
			return $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							`Transaction`.`StatusChanged` = FALSE AND
							incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
					SET
						`Transaction`.`Shipped` = FALSE,
						`Transaction`.`StatusChanged` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1
					WHERE
						`Transaction`.`ID` = ? AND
						`Listing`.`VendorID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			);
		} elseif (
			$toggleShipped = $this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							`Transaction`.`StatusChanged` = FALSE AND
							incrementedNotification.`UserID` = `Transaction`.`BuyerID` AND
							incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED . "
					SET
						`Shipped` = TRUE,
						incrementedNotification.`Value` = incrementedNotification.`Value` + 1,
						`Transaction`.`StatusChanged` = TRUE
					WHERE
						`Transaction`.`ID` = ? AND
						`Listing`.`VendorID` = ?
				",
				'ii',
				[
					$transactionID,
					$this->User->ID
				]
			)
		) 
			return $m->set($mKey, TRUE, ALLOWED_TIME_TO_UNMARK_SHIPPED);
		
		return false;
	}
	
	public function markTransactionPaid($transactionID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`Unit` ON
							`Listing`.`UnitID` = `Unit`.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					LEFT JOIN
						`ListingGroup` ON
							`Listing_Group`.`GroupID` = `ListingGroup`.`ID` AND
							`ListingGroup`.`SynchronizeStock` = TRUE
					LEFT JOIN
						`Unit` groupStockUnit ON
							`ListingGroup`.`UnitID` = groupStockUnit.`ID`
					LEFT JOIN
						`Listing_Group` LG2 ON
							`ListingGroup`.`ID` = LG2.`GroupID`
					LEFT JOIN
						`Listing` groupListing ON
							LG2.`ListingID` = groupListing.`ID`
					LEFT JOIN
						`Unit` groupListingUnit ON
							groupListing.`UnitID` = groupListingUnit.`ID`
					LEFT JOIN
						`User_Notification` incrementedNotification ON
							`ListingGroup`.`ID` IS NULL AND
							`Listing`.`Inactive` = FALSE AND
							`Listing`.`Quantity_Left` >= `Listing`.`Quantity_Minimum` AND
							CAST(`Listing`.`Quantity_Left` AS SIGNED) - CAST(`Transaction`.`Quantity` AS SIGNED) < `Listing`.`Quantity_Minimum` AND
							incrementedNotification.`UserID` = `Listing`.`VendorID` AND
							incrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_LISTING_OUT_OF_STOCK . "
					SET
						`Transaction`.`Paid` = TRUE,
						`Transaction`.`Deposited` = TRUE,
						`Transaction`.`Timeout` = NOW() + INTERVAL " . PENDING_DEPOSIT_CONFIRMATION_TIMEOUT_DAYS . " DAY,
						`Listing`.`Quantity_Left` = IF(
							`ListingGroup`.`ID` IS NULL,
							GREATEST(
								0,
								CAST(`Listing`.`Quantity_Left` AS SIGNED) - CAST(`Transaction`.`Quantity` AS SIGNED)
							),
							`Listing`.`Quantity_Left`
						),
						`Listing`.`Inactive` = IF(
							`ListingGroup`.`ID` IS NULL AND
							CAST(`Listing`.`Quantity_Left` AS SIGNED) - CAST(`Transaction`.`Quantity` AS SIGNED) < `Listing`.`Quantity_Minimum`,
							TRUE,
							`Listing`.`Inactive`
						),
						incrementedNotification.`Value` = 1,
						`ListingGroup`.`Stock` =
							GREATEST(
								0,
								CAST(`ListingGroup`.`Stock` * groupStockUnit.`ConversionFactor` AS DECIMAL(8,2)) -
								CAST(`Transaction`.`Quantity` * `Listing`.`Quantity` * `Unit`.`ConversionFactor` AS DECIMAL(8,2))
							) /
							IF(
								groupStockUnit.`Base` = FALSE AND
								(
									GREATEST(
										0,
										CAST(`ListingGroup`.`Stock` * groupStockUnit.`ConversionFactor` AS DECIMAL(8,2)) -
										CAST(`Transaction`.`Quantity` * `Listing`.`Quantity` * `Unit`.`ConversionFactor` AS DECIMAL(8,2))
									) + 0.1
								) % groupStockUnit.`ConversionFactor` < 0.2,
								groupStockUnit.`ConversionFactor`,
								1
							),
						`ListingGroup`.`UnitID` =
							IF(
								groupStockUnit.`Base` = FALSE AND
								(
									GREATEST(
										0,
										CAST(`ListingGroup`.`Stock` * groupStockUnit.`ConversionFactor` AS DECIMAL(8,2)) -
										CAST(`Transaction`.`Quantity` * `Listing`.`Quantity` * `Unit`.`ConversionFactor` AS DECIMAL(8,2))
									) + 0.1
								) % groupStockUnit.`ConversionFactor` < 0.2,
								groupStockUnit.`ID`,
								groupStockUnit.`BaseID`
							),
						groupListing.`Quantity_Left` =
							FLOOR(
								GREATEST(
									0,
									CAST(`ListingGroup`.`Stock` * groupStockUnit.`ConversionFactor` AS DECIMAL(8,2)) -
									CAST(`Transaction`.`Quantity` * `Listing`.`Quantity` * `Unit`.`ConversionFactor` AS DECIMAL(8,2))
								) /
								(groupListing.`Quantity` * groupListingUnit.`ConversionFactor`)
							),
						LG2.`OutOfStock` =
							FLOOR(
								GREATEST(
									0,
									CAST(`ListingGroup`.`Stock` * groupStockUnit.`ConversionFactor` AS DECIMAL(8,2)) -
									CAST(`Transaction`.`Quantity` * `Listing`.`Quantity` * `Unit`.`ConversionFactor` AS DECIMAL(8,2))
								) /
								(groupListing.`Quantity` * groupListingUnit.`ConversionFactor`)
							) <
							groupListing.`Quantity_Minimum`
					WHERE
						`Transaction`.`ID` = ? AND
						`Transaction`.`Paid` = FALSE
				",
				'i',
				[$transactionID]
			);
	}
	
	public function markTransactionDeposited(
		$transactionID,
		$checkUser = true
	){
		return	$this->db->qQuery(
				"
					UPDATE
						`Transaction`
					SET
						`Deposited` = TRUE
					WHERE
						`ID` = ? " . (
							$checkUser
								? 'AND `BuyerID` = ?'
								: false
						) . "
				",
				'i' . ($checkUser ? 'i' : false),
				array_merge(
					[$transactionID],
					$checkUser
						? [$this->User->ID]
						: []
				)
			);
	}
	
	private function getInputsFromAddressHistory(
		$cryptocurrencyID,
		$addressHistory,
		$multisigAddress,
		&$inputsValue,
		&$hadOutgoing = null
	){
		$cryptocurrency = $hadOutgoing = false;
		$inputsValue = 0;
		$inputs = [];
		foreach ($addressHistory as $addressHistoryEntry)
			if (
				$rawTransaction = $this->getTXHEXfromID(
					$cryptocurrencyID,
					$addressHistoryEntry['tx_hash']
				)
			){
				$cryptocurrency = $cryptocurrency ?: $this->User->getCryptocurrency($cryptocurrencyID);
				$outputs = $this->decodeTXWithCoinbin(
					$cryptocurrency,
					$rawTransaction
				);
				
				foreach ($outputs as $output){
					$outputAddress = $output['address'];
					if ($outputAddress !== $multisigAddress){
						$hadOutgoing = true;
						continue;
					}
					
					$inputs[] = [
						'txid' 	=> $addressHistoryEntry['tx_hash'],
						'vout' 	=> $output['prevout_n'],
						'value'	=> $output['value']
					];
					$inputsValue += $output['value']/1e8;
				}
			}
		
		return $inputs;
	}
	
	private function getRawTransactionSize($hex){ // in kilobyte
		return strlen(pack('H*', $hex)) / 1e3;
	}
	
	/*private function getTotalUnconfirmedSatoshisPerKilobyte(
		$multisigAddress,
		&$totalSize,
		&$unconfirmedInputs,
		&$unconfirmedInputsValue
	){
		if ($addressHistory = ElectrumDaemon::getAddressHistory($multisigAddress)){
			$totalFee = $totalSize = 0;
			foreach ($addressHistory as $i => $addressHistoryEntry) {
				if($addressHistoryEntry['height'] !== 0){
					unset($addressHistoryEntries[$i]);
					continue;
				}
					
				$totalFee += $addressHistoryEntry['fee'];
				
				$rawTX = $this->getTXHEXfromID($addressHistoryEntry['tx_hash']);
				$totalSize += $this->getRawTransactionSize($rawTX);
			}
			
			if($totalSize > 0){
				$unconfirmedInputs = $this->getInputsFromAddressHistory(
					$addressHistory,
					$multisigAddress,
					$unconfirmedInputsValue
				);
				return ceil($totalFee / $totalSize);
			}
		}
		
		return false;
	}
	
	private function _setTransactionFeeBumpRequirement(
		$transactionID,
		$feeBumpRequirement
	){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`FeeBump` = ?
				WHERE
					`ID` = ?
			",
			'ii',
			[
				$feeBumpRequirement,
				$transactionID
			]
		);
	}
	
	private function _getTransactionFeeBumpRequirement($transactionID){
		return $this->db->qSelect(
			"
				SELECT
					`FeeBump`
				FROM
					`Transaction`
				WHERE
					`ID` = ?
			",
			'i',
			[$transactionID]
		)[0]['FeeBump'];
	}
	
	public function getTransactionFeeBumpRequirement(
		$transactionID,
		$multisigAddress,
		$redeemScript,
		$requiredSigs
	){
		$feeBumpRequirement = $this->_getTransactionFeeBumpRequirement($transactionID);
		if ($feeBumpRequirement !== null)
			return $feeBumpRequirement
				? ceil($feeBumpRequirement / 1e8 * BITCOIN_FLOAT_ROUNDING_COEFFICIENT) / BITCOIN_FLOAT_ROUNDING_COEFFICIENT
				: false;
		
		$totalSatoshiPerKilobyte = $this->getTotalUnconfirmedSatoshisPerKilobyte(
			$multisigAddress,
			$totalSize,
			$unconfirmedInputs,
			$unconfirmedInputsValue
		);
		$lowestSatoshisPerKilobyte = $this->getCryptocurrencyFeePerKilobyte(CRYPTOCURRENCIES_FEE_LEVEL_LOWEST) * BITCOIN_FEE_LOW_LOWEST_FEE_LEVEL_COEFFICIENT;
		$hasLowFee = $totalSatoshiPerKilobyte < $lowestSatoshisPerKilobyte;
		if ($hasLowFee){
			$targetSatoshisPerKilobyte = $this->getCryptocurrencyFeePerKilobyte(BITCOIN_FEE_LEVEL_CPFP_TARGET);
			$feeBumpRequirement =
				floor(($targetSatoshisPerKilobyte - $totalSatoshiPerKilobyte) * $totalSize) +
				$this->estimateDynamicFee(
					$unconfirmedInputs,
					[$multisigAddress],
					$unconfirmedInputsValue,
					$redeemScript,
					$requiredSigs,
					$this->getCryptocurrencyFeePerKilobyte(BITCOIN_FEE_LEVEL_CPFP_TARGET),
					0,
					$cpfpTXSize
				) * 1e8;
			
			var_dump(
				$feeBumpRequirement,
				$totalSatoshiPerKilobyte
			);
			die;
				
			$this->_setTransactionFeeBumpRequirement(
				$transactionID,
				$feeBumpRequirement
			);
			
			return ceil($feeBumpRequirement / 1e8 * BITCOIN_FLOAT_ROUNDING_COEFFICIENT) / BITCOIN_FLOAT_ROUNDING_COEFFICIENT;
		}
			
		$this->_setTransactionFeeBumpRequirement(
			$transactionID,
			0
		);
		return false;
	}
	
	private function _insertPartiallySignedFeeBumpTX(
		$transactionID,
		$hexPartial
	){
		return $this->db->qQuery(
			"
				INSERT INGORE INTO
					`Transaction_FeeBump` (`TransactionID`, `Hex_Partial`)
				VALUES
					(?, ?)
			",
			'is',
			[
				$transactionID,
				$hexPartial
			]
		);
	}
	
	private function _setTransactionsFeeBumped($transactionIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`FeeBumped` = TRUE
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($transactionIDs)), ', ') . ")
			",
			str_repeat('i', count($transactionIDs)),
			$transactionIDs
		);
	}
	
	public function bumpTransactionsBitcoinFee($transactionIDs){
		$bumpedTransactions = [];
		foreach($transactionIDs as $transactionID){
			$transaction = $this->_getTransaction($transactionID);
			
			// Check that deposits aren't still with low fee, i.e. buyer fucked up again
			$totalSatoshiPerKilobyte = $this->getTotalUnconfirmedSatoshisPerKilobyte(
				$transaction['MultiSigAddress'],
				$totalSize,
				$unconfirmedInputs,
				$unconfirmedInputsValue
			);
			$lowestSatoshisPerKilobyte = $this->getCryptocurrencyFeePerKilobyte(CRYPTOCURRENCIES_FEE_LEVEL_LOWEST) * BITCOIN_FEE_LOW_LOWEST_FEE_LEVEL_COEFFICIENT;
			$hasLowFee = $totalSatoshiPerKilobyte < $lowestSatoshisPerKilobyte;
			
			if ($hasLowFee)
				$this->_setTransactionFeeBumpRequirement(
					$transactionID,
					null
				);
			elseif ($addressHistory = ElectrumDaemon::getAddressHistory($transaction['MultiSigAddress'])){
				foreach ($addressHistory as $i => $addressHistoryEntry)
					if($addressHistoryEntry['height'] !== 0){
						unset($addressHistory[$i]);
						continue;
					}
			
				if($addressHistory){
					$feeBumpRequirement = ceil($this->_getTransactionFeeBumpRequirement($transactionID) / 1e8 * BITCOIN_FLOAT_ROUNDING_COEFFICIENT) / BITCOIN_FLOAT_ROUNDING_COEFFICIENT;
						    
					$outputs = [
						$transaction['MultiSigAddress'] => floor(($inputsValue - $feeBumpRequirement) * BITCOIN_FLOAT_ROUNDING_COEFFICIENT) / BITCOIN_FLOAT_ROUNDING_COEFFICIENT
					];

					$privateKey_WIF_site = $this->getBIP32PrivateKeyWIF($transactionID);

					$rawTransaction = $this->createTXWithCoinbin(
						$unconfirmedInputs,
						$outputs,
						$transaction['RedeemScript']
					);
				
					$signedTransaction = $this->signWithCoinbin($rawTransaction, $privateKey_WIF_site);
					
					if(
						(
							$transaction['Escrow'] &&
							$this->_insertPartiallySignedFeeBumpTX(
								$transactionID,
								$signedTransaction
							)
						) ||
						(
							!$transaction['Escrow'] &&
							$this->queueTX(
								$transactionID,
								$signedTransaction,
								TRUE,
								$transaction['BuyerID'],
								null,
								null
							)
						)
					)
						$bumpedTransactionIDs[] = $transactionID;	
				}
			}
		}
		
		return $bumpedTransactionIDs ? $this->_setTransactionsFeeBumped($bumpedTransactionIDs) : false;
	}*/
	
	public function fetchCryptocurrencyFeeLevels($cryptocurrencyID = false){
		$cryptocurrencyFeeLevelsIndexed = false;
		if(
			$cryptocurrencyFeeLevels = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Level`,
						`Description`
					FROM
						`CryptocurrencyNetworkFee`
				"
			)
		)
			foreach($cryptocurrencyFeeLevels as $cryptocurrencyFeeLevel)
				$cryptocurrencyFeeLevelsIndexed[ $cryptocurrencyFeeLevel['Level'] ] = $cryptocurrencyFeeLevel['Description'];
		
		return $cryptocurrencyFeeLevelsIndexed;
	}
	
	private function decrementQuantityLeft($listingID, $quantity){
		if( $stmt_decrementQuantityLeft = $this->db->prepare("
			UPDATE
				`Listing`
			SET
				`Listing`.`Quantity_Left` = GREATEST(
					0,
					CAST(`Listing`.`Quantity_Left` AS SIGNED) - ?
				),
				`Listing`.`Inactive` = IF(
					`Listing`.`Quantity_Left` < `Listing`.`Quantity_Minimum`,
					TRUE,
					`Listing`.`Inactive`
				)
			WHERE
				`Listing`.`ID` = ?
		") ){
			$stmt_decrementQuantityLeft->bind_param('ii', $quantity, $listingID);
			
			return $stmt_decrementQuantityLeft->execute();	
		}
	}
	
	private function incrementQuantityLeft($listingID, $quantity){
		if( $stmt_incrementQuantityLeft = $this->db->prepare("
			UPDATE
				`Listing`
			SET
				`Listing`.`Quantity_Left` = `Listing`.`Quantity_Left` + ?
			WHERE
				`Listing`.`ID` = ?
		") ){
			
			$stmt_incrementQuantityLeft->bind_param('ii', $quantity, $listingID);
			
			if( $stmt_incrementQuantityLeft->execute() ){
				return true;
			}
			
		}
	}
	
	private function updateOrderVersion($order){
		
		switch($order['Version']){
			case 1:
				$order['Escrow'] = true;
			break;
			default:
				return $order;
		}
		
		return $order;
		
	}
	
	public function getTXHEXfromID(
		$cryptocurrencyID,
		$TXID
	){
		if (!preg_match(REGEX_CRYPTOCURRENCY_TRANSACTION_HASH, $TXID))
		      return false;
		
		$rawTransaction = false;
		
		$m = $this->db->m;
		$memcachedIndex = 'electrumTX-' . $cryptocurrencyID . '-' . $TXID;
		
		if ($memcachedData = $m->get($memcachedIndex))
			$rawTransaction = $memcachedData;
		else {
			$response = false;
			while (
				$response === false &&
				$electrumServer = $this->_getElectrumServer(
					$cryptocurrencyID,
					$connectionAttempts,
					$previousServerIDs
				)
			)
				$rawTransaction = ElectrumServer::getTransaction(
					$electrumServer['Host'],
					$electrumServer['Port'],
					$TXID,
					$response
				);
		}
		
		if ($rawTransaction){
			$m->set(
				$memcachedIndex,
				$rawTransaction,
				ELECTRUM_BALANCE_CACHE_EXPIRATION
			);
			
			return $rawTransaction;
		}
		
		return false;
	}
	
	private function getAddressBalanceBlockchainInfo(
		$address,
		$requiredConfirmations,
		$satoshis = FALSE
	){
		if(
			$balanceSatoshis = NXS::get_data('https://blockchainbdgpzk.onion/q/addressbalance/' . urlencode($address) . '?confirmations=' . $requiredConfirmations)
		)
			return $satoshis ? $balanceSatoshis : ($balanceSatoshis / 1e8);
			
		return FALSE;
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
	
	public function findNextOrderID($transactionID){
		$transactionIDs = $this->db->qSelect(
			"
				SELECT
					`Transaction`.`ID` AS txID
				FROM
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				INNER JOIN
					`Transaction` previousTransaction ON
						previousTransaction.`ID` = ?
				WHERE
					`PaymentMethod`.`UserID` = ? AND
					`Transaction`.`Timeout` > NOW() AND
					`Transaction`.`ID` != previousTransaction.`ID` AND
					(
						`Transaction`.`Status` = 'pending accept' OR
						(
							`Transaction`.`Shipped` = FALSE AND
							(
								`Transaction`.`Status` = 'in transit' OR
								(
									`Transaction`.`Status` = 'pending feedback' AND
									`Transaction`.`Escrow` = FALSE
								)
							)
						)
					) AND
					(
						`Transaction`.`DateTime` < previousTransaction.`DateTime` OR
						(
							`Transaction`.`DateTime` = previousTransaction.`DateTime` AND
							`Transaction`.`ID` < previousTransaction.`ID`
						) OR
						(
							previousTransaction.`ID` = (
								SELECT
									`Transaction`.`ID`
								FROM
									`Transaction`
								INNER JOIN
									`PaymentMethod` ON
										`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
								WHERE
									`PaymentMethod`.`UserID` = ? AND
									`Transaction`.`Timeout` > NOW() AND
									(
										`Transaction`.`Status` = 'pending accept' OR
										(
											`Transaction`.`Shipped` = FALSE AND
											(
												`Transaction`.`Status` = 'in transit' OR
												(
													`Transaction`.`Status` = 'pending feedback' AND
													`Transaction`.`Escrow` = FALSE
												)
											)
										)
									)
								ORDER BY
									`Transaction`.`DateTime` ASC,
									`Transaction`.`ID` ASC
								LIMIT
									1
							) AND
							`Transaction`.`ID` = (
								SELECT
									`Transaction`.`ID`
								FROM
									`Transaction`
								INNER JOIN
									`PaymentMethod` ON
										`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
								WHERE
									`PaymentMethod`.`UserID` = ? AND
									`Transaction`.`Timeout` > NOW() AND
									(
										`Transaction`.`Status` = 'pending accept' OR
										(
											`Transaction`.`Shipped` = FALSE AND
											(
												`Transaction`.`Status` = 'in transit' OR
												(
													`Transaction`.`Status` = 'pending feedback' AND
													`Transaction`.`Escrow` = FALSE
												)
											)
										)
									)
								ORDER BY
									`Transaction`.`DateTime` DESC,
									`Transaction`.`ID` DESC
								LIMIT
									1
							)
						)
					)
				ORDER BY
					`Transaction`.`DateTime` DESC,
					`Transaction`.`ID` DESC
				LIMIT	1
			",
			'iiii',
			[
				$transactionID,
				$this->User->ID,
				$this->User->ID,
				$this->User->ID
			]
		);
		
		if($nextOrderID = $transactionIDs[0]['txID'])
			return $nextOrderID;
		else
			return FALSE;
	}
	
	private function getAddressHistory(
		$cryptocurrencyID,
		$address
	){
		$m = $this->db->m;
		
		$addressHistory = null;
		if ($electrumCached = $m->get('electrumAddressHistory-' . $address))
			$addressHistory = $electrumCached;
		
		while (
			!is_array($addressHistory) &&
			$electrumServer = $this->_getElectrumServer(
				$cryptocurrencyID,
				$connectionAttempts,
				$previousServerIDs
			)
		)
			$addressHistory = ElectrumServer::getAddressHistory(
				$electrumServer['Host'],
				$electrumServer['Port'],
				$address
			);
		
		if (
			!$electrumCached &&
			is_array($addressHistory)
		)
			$m->set(
				'electrumAddressHistory-' . $address,
				$addressHistory,
				ELECTRUM_UNSPENT_OUTPUTS_CACHE_EXPIRATION
			);
				
		return $addressHistory;
	}
	
	public function createSegwitTransaction(
		$cryptocurrency,
		$WIFKey,
		$outputAddress,
		&$inputAddress = null,
		&$inputsExceededMax = null
	){
		$privateKey = BitcoinLib::WIF_to_private_key($WIFKey);
		$publicKey = BitcoinLib::private_key_to_public_key(
			$privateKey['key'],
			true
		);
		
		$redeemScript = NXS::getSegwitRedeemScript($publicKey);
		$inputAddress = BitcoinLib::public_key_to_address(
			$redeemScript,
			$cryptocurrency->prefixScriptHash
		);
		
		if (
			list(
				$inputsValue,
				$inputs
			) =  $this->getUnspentOutputs(
				$cryptocurrency->ID,
				$inputAddress,
				REQUIRED_TX_CONFIRMATIONS_BROADCAST,
				FALSE,
				$redeemScript,
				false,
				500,
				false,
				$previousElectrumServerIDs,
				$inputsExceededMax
			)
		){
			$chargableTransactionSize = $this->estimateSignedSegwitTransactionSize(count($inputs));
			
			$minerFee = $cryptocurrency->parseValue(
				max(
					$this->getCryptocurrencyFeePerKilobyte(
						CRYPTOCURRENCIES_FEE_LEVEL_LOWEST,
						$cryptocurrency->ID
					),
					SEGWIT_TRANSACTION_MINIMUM_FEE_PER_KILOBYTE
				) *
				$chargableTransactionSize /
				1e11
			);
			
			$outputs = [
				$outputAddress => $inputsValue - $minerFee
			];
		
			if (
				$unsignedTransaction = $this->createTXWithCoinbin(
					$cryptocurrency,
					$inputs,
					$outputs,
					$redeemScript,
					$nLockTime,
					$unsignedTxSize
				)
			)
				return $unsignedTransaction;
		}
		
		return false;
	}
	
	private function getUnspentOutputsFromDatabase($address){
		return	$this->db->qSelect(
				"
					SELECT
						`TXID` txid,
						`Index` vout,
						`Value` value
					FROM
						`UnspentOutput`
					WHERE
						`Address` = ?
				",
				's',
				[$address]
			);
	}
	
	private function getUnspentOutputs(
		$cryptocurrencyID,
		$address,
		$requiredConfirmations = REQUIRED_TX_CONFIRMATIONS_BROADCAST,
		$max_value = FALSE,
		$redeemScript = FALSE,
		$showError = true,
		$max = 50,
		$checkDatabase = true,
		&$previousServerIDs = null,
		&$inputsExceededMax = null
	){
		// TRY ELECTRUM
		if ($requiredConfirmations >= TX_CONFIRMATIONS_ELECTRUM_CONFIRMED){
			if (
				$checkDatabase &&
				$unspentOutputsFromDatabase = $this->getUnspentOutputsFromDatabase($address)
			)
				return [
					array_sum(
						array_map(
							function($input){
								return $input['value'] / 1e8;
							},
							$unspentOutputsFromDatabase
						)
					),
					$unspentOutputsFromDatabase
				];
			
			$m = $this->db->m;
			
			$unspentOutputs = null;
			if ($electrumCached = $m->get('electrumUnspentOutputs-' . $address))
				$unspentOutputs = $electrumCached;
			
			while (
				!is_array($unspentOutputs) &&
				$electrumServer = $this->_getElectrumServer(
					$cryptocurrencyID,
					$connectionAttempts,
					$previousServerIDs
				)
			)
				$unspentOutputs = ElectrumServer::getAddressUnspentOutputs(
					$electrumServer['Host'],
					$electrumServer['Port'],
					$address
				);
			
			if (
				$unspentOutputs &&
				is_array($unspentOutputs)
			){
				$inputsExceededMax = count($unspentOutputs) > $max;
				
				if (!$electrumCached)
					$m->set(
						'electrumUnspentOutputs-' . $address,
						$unspentOutputs,
						ELECTRUM_UNSPENT_OUTPUTS_CACHE_EXPIRATION
					);
				
				$inputs = array();
				$inputs_for_signing = array();
				$inputs_value = 0;
				
				$i = 0;
				
				while (
					isset($unspentOutputs[$i]) &&
					(
						!$max_value ||
						NXS::compareFloatNumbers(
							$inputs_value,
							$max_value,
							'<'
						)
					) &&
					$i < $max
				){
					$unspent_output = $unspentOutputs[$i];
					
					$inputs[] = $input = array(
						'txid'	=> $unspent_output['tx_hash'],
						'vout'	=> $unspent_output['tx_pos'],
						'value'	=> $unspent_output['value']
					);
					
					$inputs_value += $unspent_output['value'] / 1e8;
					
					$i++;
				}
				
				return array($inputs_value, $inputs);
			}
		}
		
		if ($showError)
			$_SESSION['temp_notifications']['indeterminate_outputs'] = array(
				'Content' => 'Could not determine unspent outputs. Please try again in a few minutes',
				'Design' => array(
					'Color' => 'red',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				)
			);
		return false;
	}
	
	private function _fetchTransactionsPendingBroadcast(){
		return $this->db->qSelect(
			"
				SELECT DISTINCT
					`PendingBroadcast`.`ID`,
					`Transaction`.`ID` transactionID,
					`PendingBroadcast`.`Hex`,
					`PendingBroadcast`.`BroadcastAttempts`,
					`Listing`.`VendorID`,
					`PendingBroadcast`.`UserID` broadcasterID,
					`PaymentMethod`.`CryptocurrencyID`,
					`BroadcastAttempts` >= " . MAXIMUM_BROADCAST_ATTEMPTS . " failedBroadcast,
					`Transaction`.`MultiSigAddress`
				FROM
					`PendingBroadcast`
				INNER JOIN
					`Transaction` ON
						`PendingBroadcast`.`TransactionID` = `Transaction`.`ID`
				INNER JOIN
					`Listing` ON
						`Transaction`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				ORDER BY
					failedBroadcast ASC,
					`PendingBroadcast`.`DateTime` ASC
			"
		);
	}
	
	private function _incrementTransactionsBroadcastAttempts($attemptIDs){
		return $this->db->qQuery(
			"
				UPDATE
					`PendingBroadcast`
				SET
					`BroadcastAttempts` = `BroadcastAttempts` + 1
				WHERE
					`ID` IN (" . rtrim(str_repeat('?, ', count($attemptIDs)), ', ') . ")
			",
			str_repeat('i', count($attemptIDs)),
			$attemptIDs
		);
	}
	
	public function broadcastTransactions(){
		$successfulIDs = $failedIDs = [];
		
		if ($transactionsPendingBroadcast = $this->_fetchTransactionsPendingBroadcast()){
			foreach ($transactionsPendingBroadcast as $transaction){
				$successfulPush =
					(
						$this->pushTX(
							$transaction['CryptocurrencyID'],
							$transaction['Hex']
						) ||
						(
							$transaction['failedBroadcast'] &&
							$this->_checkEmptyAddress(
								$transaction['CryptocurrencyID'],
								$transaction['MultiSigAddress']
							)
						)
					) &&
					$this->_removePendingBroadcast($transaction['transactionID']);
				
				if ($successfulPush) {
					if ($transaction['failedBroadcast']){
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
							-1,
							$transaction['VendorID']
						);
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
							-1,
							$transaction['broadcasterID']
						);
					}
				} elseif (!$transaction['failedBroadcast']) {
					$this->_incrementTransactionsBroadcastAttempts([$transaction['ID']]);
					
					if ($transaction['BroadcastAttempts'] == MAXIMUM_BROADCAST_ATTEMPTS - 1){
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS,
							1,
							$transaction['VendorID']
						);
						$this->User->incrementUserNotification(
							USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL,
							1,
							$transaction['broadcasterID']
						);
					}
				}
			}
		}
		
		return true;
	}
	
	private function pushTX(
		$cryptocurrencyID,
		$hex
	){
		$response = false;
		while (
			$response === false &&
			$electrumServer = $this->_getElectrumServer(
				$cryptocurrencyID,
				$connectionAttempts,
				$previousServerIDs
			)
		)
			$successfulPush = ElectrumServer::broadcastTransaction(
				$electrumServer['Host'],
				$electrumServer['Port'],
				$hex,
				$response
			);
			
		return $successfulPush;
	}
	
	private function queueTX(
		$transactionID,
		$hex,
		$multisig = TRUE,
		$userID = NULL
	){
		return $this->db->qQuery(
			"
				INSERT INTO
					`PendingBroadcast` (
						`TransactionID`,
						`WIF_Site`,
						`Hex`,
						`Hex_Partial`,
						`Multisig`,
						`UserID`
					)
				VALUES
					(?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY
					UPDATE
						`Hex`			= ?,
						`Hex_Partial`		= ?,
						`Multisig`		= ?,
						`UserID`		= ?,
						`BroadcastAttempts` = 0
			",
			'isssiissii',
			array(
				$transactionID,
				$SiteWIF,
				$hex,
				$partiallySignedHex,
				$multisig,
				$userID,
				$hex,
				$partiallySignedHex,
				$multisig,
				$userID
			)
		);
	}
	
	public function signWithCoinbin(
		$cryptocurrency,
		$hex,
		$wif
	){
		return Coinbin::run(
			'sign.js',
			'signTXWithWIF',
			[
				$cryptocurrency->prefixPublic,
				$cryptocurrency->prefixPrivate,
				$cryptocurrency->prefixScriptHash,
				$cryptocurrency->bech32HRP,
				$hex,
				$wif
			]
		);
	}
	
	private function estimateSignedSegwitTransactionSize($segwitInputCount){
		return 46 + 100*$segwitInputCount;
	}
	
	private function estimateSignedTransactionSize(
		$unsignedTxSize,
		$numInputs,
		$numSigs,
		$empiricalCoefficient = BITCOIN_TRANSACTION_SIZE_ESTIMATION_EMPIRICAL_COEFFICIENT,
		$empiricalExponent = BITCOIN_TRANSACTION_SIZE_ESTIMATION_EMPIRICAL_EXPONENT
	){
		return ceil($unsignedTxSize + $empiricalCoefficient*($numInputs*$numSigs)**$empiricalExponent);
	}
	
	public function getCryptocurrencyFeePerKilobyte( // in satoshi/kb
		$feeLevel,
		$cryptocurrencyID = CRYPTOCURRENCIES_CRYPTOCURRENCY_ID_DEFAULT
	){
		if(
			$result = $this->db->qSelect(
				"
					SELECT
						IFNULL(
							(
								SELECT
									`Satoshis`
								FROM
									`CryptocurrencyNetworkFee`
								WHERE
									`Level` = ? AND
									`CryptocurrencyID` = ?
							),
							(
								SELECT
									`Satoshis`
								FROM
									`CryptocurrencyNetworkFee`
								WHERE
									`Level` = " . CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT . " AND
									`CryptocurrencyID` = ?
							)
						) satoshis
				",
				'iii',
				[
					$feeLevel,
					$cryptocurrencyID,
					$cryptocurrencyID
				]
			)
		)
				return $result[0]['satoshis'];
		
		return false;
	}
	
	private function estimateDynamicFee(
		$cryptocurrency,
		$inputs,
		$outputAddresses,
		$inputsValue,
		$redeemScript,
		$numSigs,
		$satoshisPerKiloByte,
		$nLockTime = 0,
		&$signedTxSize = null
	){
		$provisionalOutputs = [];
		foreach ($outputAddresses as $address)
			$provisionalOutputs[$address] = $cryptocurrency->parseValue($inputsValue/count($outputAddresses), true);
		
		$this->createTXWithCoinbin(
			$cryptocurrency,
			$inputs,
			$provisionalOutputs,
			$redeemScript,
			$nLockTime,
			$unsignedTxSize
		);
		
		$signedTxSize = $this->estimateSignedTransactionSize(
			$unsignedTxSize,
			count($inputs),
			$numSigs
		);
		
		return $cryptocurrency->parseValue($satoshisPerKiloByte * $signedTxSize / 1e11);
	}
	
	public function decodeTXWithCoinbin(
		$cryptocurrency,
		$rawTransaction
	){
		if (
			$outputs = Coinbin::run(
				'decode.js',
				'decodeTX',
				[
					$cryptocurrency->prefixPublic,
					$cryptocurrency->prefixPrivate,
					$cryptocurrency->prefixScriptHash,
					$cryptocurrency->bech32HRP,
					$rawTransaction
				],
				true
			)
		)
			return array_map(
				function($output){
					return [
						'prevout_n'	=> $output[0],
						'address'	=> $output[1],
						'value'		=> $output[2]
					];
				},
				$outputs
			);
		
		return false;
	}
	
	public function createTXWithCoinbin(
		$cryptocurrency,
		$inputs,
		$outputs,
		$redeemScript,
		$nLockTime = 0,
		&$txSize = null
	){
		$formatted_outputs = array();
		foreach($outputs as $address => $value)
			$formatted_outputs[] = array(
				'address' => $address,
				'value' => $value
			);
			
		$outputs = $formatted_outputs;
		
		$inputs = json_encode($inputs);
		$outputs = json_encode($outputs);
		
		$result = Coinbin::run(
			'create.js',
			'createTXWithInputs',
			[
				$cryptocurrency->prefixPublic,
				$cryptocurrency->prefixPrivate,
				$cryptocurrency->prefixScriptHash,
				$cryptocurrency->bech32HRP,
				$inputs,
				$outputs,
				$redeemScript,
				$nLockTime
			]
		);
		
		$txSize = (int) $result[1];
		
		return $result[0];
	}
	
	private function getMarketplaceAddressForTransaction($transactionID){
		if(
			$keyIndices = $this->db->qSelect(
				"
					SELECT
						(
							SELECT
								COUNT(DISTINCT MPAKI2.`MarketplaceAddressKeyIndex`)
							FROM
								`Transaction_MarketplaceAddressKeyIndex` MPAKI2
							WHERE
								MPAKI2.`ExtendedPublicKeyID` = `Transaction_MarketplaceAddressKeyIndex`.`ExtendedPublicKeyID` AND
								(
									MPAKI2.`DateTime` < `Transaction_MarketplaceAddressKeyIndex`.`DateTime` OR
									(
										MPAKI2.`DateTime` = `Transaction_MarketplaceAddressKeyIndex`.`DateTime` AND
										MPAKI2.`MarketplaceAddressKeyIndex` < `Transaction_MarketplaceAddressKeyIndex`.`MarketplaceAddressKeyIndex`
									)
								)
						) keyIndex,
						`BIP32`.`ExtendedPublicKey`,
						Cryptocurrency.`Prefix_Public`,
						Cryptocurrency.`Prefix_ScriptHash`
					FROM
						`Transaction_MarketplaceAddressKeyIndex`
					INNER JOIN
						`BIP32` ON
							`Transaction_MarketplaceAddressKeyIndex`.`ExtendedPublicKeyID` = `BIP32`.`ID`
					INNER JOIN
						`Transaction` ON
							`Transaction_MarketplaceAddressKeyIndex`.`TransactionID` = `Transaction`.`ID`
					INNER JOIN
						`PaymentMethod` ON
							`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
					INNER JOIN
						`Currency` Cryptocurrency ON
							`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
					WHERE
						`Transaction_MarketplaceAddressKeyIndex`.`TransactionID` = ?
				",
				'i',
				[$transactionID]
			)
		)
			return	NXS::getBIP32Address(
					$keyIndices[0]['keyIndex'],
					$keyIndices[0]['ExtendedPublicKey'],
					$keyIndices[0]['Prefix_Public'],
					$keyIndices[0]['Prefix_ScriptHash']
				);
		
		if ($this->_insertMarketplaceAddressKeyIndex($transactionID))
			return $this->getMarketplaceAddressForTransaction($transactionID);
				
		return false;
	}

	private function validateXPUB($xpub){
		try {
			$key = BIP32::import($xpub);
		} catch(Exception $e) {
			return false;
		}
		
		return true;
	}
    
	public function getBIP32PrivateKeyWIF(
		$keyID,
		$prefixPublic,
		$BIP32ExtendedPrivateKey = SITE_BIP32_EXTENDED_PRIVATE_KEY
	){
		$privateKey_extended = BIP32::build_key(
			array(
				$BIP32ExtendedPrivateKey,
				'm/0'
			),
			$keyID
		);
		$privateKey = BIP32::import($privateKey_extended[0]);
		$privateKey_wif = BitcoinLib::private_key_to_WIF(
			$privateKey['key'],
			TRUE,
			$prefixPublic
		);
		
		return $privateKey_wif;
	}
	
	// @TEST
	private function _insertVendorAddressKeyIndex($transactionID){
		return $this->db->qQuery(
			"
				UPDATE
					`Transaction`
				SET
					`Transaction`.`AddressKey` = IFNULL(
						(
							SELECT
								MIN(t2.`AddressKey`) + 1
							FROM
								`Transaction` t2
							WHERE
								t2.`PaymentMethodID` = `Transaction`.`PaymentMethodID` AND
								NOT EXISTS (
									SELECT
										t3.`ID`
									FROM
										`Transaction` t3
									WHERE
										t3.`PaymentMethodID` = `Transaction`.`PaymentMethodID` AND
										t3.`AddressKey` = t2.`AddressKey` + 1
								)
						),
						0
					)
				WHERE
					`Transaction`.`ID` = ? AND
					`Transaction`.`AddressKey` IS NULL
			",
			'i',
			[$transactionID]
		);
	}
	
	private function getVendorBIP32AddressForTransaction($transactionID){
		$BIP32andK = $this->db->qSelect(
			"
				SELECT
					`PaymentMethod`.`ExtendedPublicKey`,
					IF(
						`Transaction`.`Status` IN ('pending feedback', 'in dispute'),
						`Transaction`.`AddressKey`,
						`PaymentMethod`.`Index`
					) AddressKey,
					Cryptocurrency.`Prefix_Public`,
					Cryptocurrency.`Prefix_ScriptHash`
				FROM
					`Transaction`
				INNER JOIN
					`PaymentMethod` ON
						`Transaction`.`PaymentMethodID` = `PaymentMethod`.`ID`
				INNER JOIN
					`Currency` Cryptocurrency ON
						`PaymentMethod`.`CryptocurrencyID` = Cryptocurrency.`ID`
				WHERE
					`Transaction`.`ID` = ?
			",
			'i',
			[
				$transactionID
			]
		);
		
		if ($BIP32andK[0]['AddressKey'] !== null)
			return NXS::getBIP32Address(
				$BIP32andK[0]['AddressKey'],
				$BIP32andK[0]['ExtendedPublicKey'],
				$BIP32andK[0]['Prefix_Public'],
				$BIP32andK[0]['Prefix_ScriptHash']
			);
		elseif ($this->_insertVendorAddressKeyIndex($transactionID))
			return $this->getVendorBIP32AddressForTransaction($transactionID);
			
		return false;
	}
	
	private function insertTransactionEvent(
		$transactionID,
		$event
	){
		return $this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Transaction_Event` (
						`TransactionID`,
						`Event`,
						`Date`
					)
				VALUES (
					?,
					?,
					NOW()
				)
			",
			'is',
			[
				$transactionID,
				$event
			]
		);
	}
}
