<?php

class BrowseModel {
	/**
	 * Constructor, expects a Database connection
	 * @param Database $db The Database object
	 */
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	private function _parseRatingBreakdown($ratingBreakdown){
		$totalRatings = array_sum(
			array_map(
				function ($row){
					return $row['ratingCount'];
				},
				$ratingBreakdown
			)
		);
		
		return	array_map(
				function ($row) use ($totalRatings){
					return	array_merge(
							$row,
							[
								'ratingPercentage' => NXS::formatDecimal(
									$row['ratingCount'] / $totalRatings * 100,
									2
								)
							]
						);
				},
				$ratingBreakdown
			);
	}
	
	public function getUserRatingAttributeBreakdown($userAlias){
		if (
			$ratingAttributeBreakdown = $this->db->qSelect(
				"
					SELECT
						`RatingAttribute`.`Name`,
						`Icon`.`Class` icon,
						SUM(IF(`Transaction_Rating`.`Rating_Vendor` = 5, 1, IF(`Transaction_Rating`.`TransactionID` IS NOT NULL, -1, 0))) ratingCount
					FROM
						`RatingAttribute`
					INNER JOIN
						`Icon` ON
							`RatingAttribute`.`IconID` = `Icon`.`ID`
					LEFT JOIN
						`Transaction_Rating` ON
							`Transaction_Rating`.`AttributeID` = `RatingAttribute`.`ID` AND
							`Transaction_Rating`.`VendorID` = (
								SELECT `ID`
								FROM `User`
								WHERE `Alias` = ?
							)
					GROUP BY
						`RatingAttribute`.`ID`
					ORDER BY
						ratingCount DESC
				",
				's',
				[$userAlias]
			)
		){
			$ratingAttributeBreakdown = $this->_parseRatingBreakdown($ratingAttributeBreakdown);
			
			$percentageSum = 0;
			$ratingBreakdown_indexed = [];
			foreach ($ratingAttributeBreakdown as $i => $ratingAttribute){
				$index =
					'<i class="' . $ratingAttribute['icon'] . '"></i> ' .
					str_replace(
						'<br>',
						' ',
						$ratingAttribute['Name']
					);
				
				while ($percentageSum + NXS::formatDecimal($ratingAttribute['ratingPercentage'], 0) > 100)
					$ratingAttribute['ratingPercentage'] -= 1;
				
				while (
					$i == count($ratingAttributeBreakdown) - 1 &&
					$percentageSum + NXS::formatDecimal($ratingAttribute['ratingPercentage'], 0) < 100
				)
					$ratingAttribute['ratingPercentage'] += 1;
				
				$ratingBreakdown_indexed[$index] = $ratingAttribute['ratingPercentage'];
					
				$percentageSum += NXS::formatDecimal($ratingAttribute['ratingPercentage'], 0);
			}
			
			return $ratingBreakdown_indexed;
		}
		
		return false;
	}
	
	public function getUserRatingBreakdown($userAlias){
		if (
			$ratingBreakdown = $this->db->qSelect(
				"
					SELECT
						`Transaction_Rating`.`Rating_Vendor`,
						COUNT(DISTINCT `Transaction_Rating`.`ID`) ratingCount
					FROM
						`User`
					INNER JOIN
						`Transaction_Rating` ON
							`User`.`ID` = `Transaction_Rating`.`VendorID`
					WHERE
						`User`.`Alias` = ? AND
						`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
					GROUP BY
						`Transaction_Rating`.`Rating_Vendor`
					ORDER BY
						`Transaction_Rating`.`Rating_Vendor` DESC
				",
				's',
				[$userAlias]
			)
		){
			$ratingBreakdown = $this->_parseRatingBreakdown($ratingBreakdown);
			
			$ratingBreakdown_indexed = [];
			foreach ($ratingBreakdown as $rating)
				$ratingBreakdown_indexed[$rating['Rating_Vendor']] = $rating['ratingPercentage'];
			
			$percentageSum = 0;
			for ($i = 1; $i <= 5; $i++){
				if (!isset($ratingBreakdown_indexed[$i]))
					$ratingBreakdown_indexed[$i] = 0;
				else {
					while ($percentageSum + NXS::formatDecimal($ratingBreakdown_indexed[$i], 0) > 100)
						$ratingBreakdown_indexed[$i] -= 1;
					
					while (
						$i == 5 &&
						$percentageSum + NXS::formatDecimal($ratingBreakdown_indexed[$i], 0) < 100
					)
						$ratingBreakdown_indexed[$i] += 1;
						
					$percentageSum += NXS::formatDecimal($ratingBreakdown_indexed[$i], 0);
				}
			}
			
			krsort($ratingBreakdown_indexed);
					
			return $ratingBreakdown_indexed;
		}
		
		return false;
	}
	
	private function _fetchListingGroupMembers(
		$listingID,
		$cryptocurrency = FALSE,
		$quantity = FALSE,
		$getLabels = FALSE,
		$getAbbreviations = false,
		$includeSelf = false,
		&$trivialGroup = null
	){
		if(
			$listings = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Listing`.`ID`,
						`Listing`.`Name`,
						IF(
							`Listing`.`Quantity_Minimum` = 1,
							`Listing`.`Price`/`Currency`.`1EUR`,
							`Listing`.`Price`/`Currency`.`1EUR` / `Listing`.`Quantity`
						) EUR_Price,
						IF(
							`Listing`.`Quantity_Minimum` = 1,
							FALSE,
							`Unit`.`Abbreviation`
						) perUnit,
						IF(
							`Listing`.`Quantity_Minimum` = 1,
							NULL,
							`Listing`.`Quantity_Minimum` * `Listing`.`Quantity`
						) minimumQuantity " . (
							$getLabels
								? ",
								IFNULL(
									`Listing_Group`.`Label`,
									IF(
										`Listing`.`Quantity_Minimum` = 1,
										CONCAT(
											`Listing`.`Quantity`,
											' ',
											" . (
												$getAbbreviations
													? "`Unit`.`Abbreviation`"
													: "
													IF(
														`Listing`.`Quantity` > 1,
														`Unit`.`Name_Plural`,
														`Unit`.`Name_Singular`
													)
													"
											) . "
										),
										false
									)
								) label"
								: false
						) . "
					FROM
						`Listing`
					INNER JOIN
						`Listing_PaymentMethod` ON
							`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
							`PaymentMethod`.`Enabled` = TRUE
					INNER JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					INNER JOIN
						`Currency` ON
							`Listing`.`CurrencyID` = `Currency`.`ID`
					INNER JOIN
						`Unit` ON
							`Listing`.`UnitID` = `Unit`.`ID`
					WHERE
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE AND
						" . (!$includeSelf ? "`Listing`.`ID` != ? AND " : false) . "
						`Listing_Group`.`GroupID` = (
							SELECT
								`Listing_Group`.`GroupID`
							FROM
								`Listing_Group`
							WHERE
								`Listing_Group`.`ListingID` = ?
						) AND
						`Listing_Group`.`OutOfStock` = FALSE
					ORDER BY
						EUR_Price ASC
					" . (
						$quantity
							? 'LIMIT ?'
							: FALSE
					) . "
				",
				'i' . (!$includeSelf ? 'i' : false) . ($quantity ? 'i' : FALSE),
				array_merge(
					[$listingID],
					!$includeSelf
						? [$listingID]
						: [],
					$quantity
						? [$quantity]
						: []
				)
			)
		){
			$cryptocurrency = $cryptocurrency ?: $this->User->Cryptocurrency;
			$trivialGroup = true;
			$labels = [];
			
			if (
				count($listings) >
				(
					$includeSelf
						? 1
						: 0
				)
			)
				return	array_map(
						function($listing) use (
							$cryptocurrency,
							$getLabels,
							&$trivialGroup,
							&$labels
						){
							$listing = array_merge(
								$listing,
								[
									'B36'		=> NXS::getB36($listing['ID']),
									'price'		=>
										NXS::formatPrice($this->User->Currency, $listing['EUR_Price']) .
										(
											$listing['perUnit']
												? ' / ' . $listing['perUnit']
												: false
										),
									'price_crypto'	=>
										$listing['minimumQuantity']
											? NXS::formatDecimal($listing['minimumQuantity']) . ' ' . $listing['perUnit'] . ' minimum'
											: $cryptocurrency->formatPrice($listing['EUR_Price']),
									'altLabel'	=>
										$listing['minimumQuantity']
											? NXS::formatDecimal($listing['minimumQuantity']) . ' ' . $listing['perUnit'] . '+'
											: false
								]
							);
					
							if (
								$hasLabel =
									$getLabels &&
									$listing['label']
							)
								$listing['label'] = preg_replace_callback(
									REGEX_LISTING_QUANTITY_EXTRACT_NUMBER_UNIT,
									function($matches){
										return
											NXS::formatDecimal(
												$matches[1],
												2,
												DEFAULT_DECIMAL_SEPARATOR,
												DEFAULT_THOUSANDS_SEPARATOR,
												2
											) . ' ' . $matches[2];
									},
									$listing['label']
								);
								
							if (
								$trivialGroup =
									$trivialGroup &&
									(
										$hasLabel ||
										$listing['altLabel']
									) &&
									!in_array(
										($listing['label'] ?: $listing['altLabel']),
										$labels
									)
							)
								$labels[] = ($listing['label'] ?: $listing['altLabel']);
							
							return $listing;
						},
						$listings
					);
		}
		
		return FALSE;	
	}
	
	private function _fetchRelatedListings(
		$listingID,
		$cryptocurrency = FALSE
	){
		$cryptocurrency = $cryptocurrency ?: $this->User->Cryptocurrency;
		if(
			$relatedListings = $this->db->qSelect(
				"
					SELECT
						*
					FROM
						(
							( # Singular Listings
								SELECT
									DISTINCT `Listing`.`ID` ID,
									`Listing`.`DateAdded`,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									(
										SELECT
											COUNT(Listing_Rating.`ID`)
										FROM
											`Transaction_Rating` Listing_Rating
										WHERE
											Listing_Rating.`ListingID` = `Listing`.`ID` AND
											Listing_Rating.`Rating_Vendor` IS NOT NULL
									) ratingCount,
									(
										SELECT
											AVG(Listing_Rating.`Rating_Vendor`)
										FROM
											`Transaction_Rating` Listing_Rating
										WHERE
											Listing_Rating.`ListingID` = `Listing`.`ID` AND
											Listing_Rating.`Rating_Vendor` IS NOT NULL AND
											Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) averageRating,
									FALSE groupMemberCount
								FROM
									`Listing`
								INNER JOIN
									`Listing_PaymentMethod` ON
										`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
								INNER JOIN
									`PaymentMethod` ON
										`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
										`PaymentMethod`.`Enabled` = TRUE
								INNER JOIN
									`Listing` targetListing ON
										targetListing.`ID` = ?
								INNER JOIN
									`Currency` ON
										`Listing`.`CurrencyID` = `Currency`.`ID`
								LEFT JOIN
									`Listing_Group` ON
										`Listing`.`ID` = `Listing_Group`.`ListingID`
								LEFT JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								LEFT JOIN
									`Image` ON
										`Listing_Image`.`ImageID` = `Image`.`ID`
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								WHERE
									`Listing`.`ID` != targetListing.`ID` AND
									`Listing`.`VendorID` = targetListing.`VendorID` AND
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Approved` = TRUE AND
									`Listing`.`Stealth` = FALSE AND
									`Listing_Group`.`GroupID` IS NULL
							) UNION ALL
							( # Listing Groups
								SELECT
									`Listing`.`ID` ID,
									`Listing`.`DateAdded`,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									(
										SELECT
											COUNT(DISTINCT Group_Rating.`ID`)
										FROM
											`Transaction_Rating` Group_Rating
										INNER JOIN
											`Listing_Group` lG ON
												Group_Rating.`ListingID` = lG.`ListingID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											Group_Rating.`Rating_Vendor` IS NOT NULL
									) ratingCount,
									(
										SELECT
											AVG(Group_Rating.`Rating_Vendor`)
										FROM
											`Transaction_Rating` Group_Rating
										INNER JOIN
											`Listing_Group` lG ON
												Group_Rating.`ListingID` = lG.`ListingID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											Group_Rating.`Rating_Vendor` IS NOT NULL AND
											Group_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) averageRating,
									(
										SELECT
											COUNT(DISTINCT lG.`ListingID`)
										FROM
											`Listing_Group` lG
										INNER JOIN
											`Listing` ON
												lG.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`Listing_PaymentMethod` ON
												`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
										INNER JOIN
											`PaymentMethod` ON
												`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
												`PaymentMethod`.`Enabled` = TRUE
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											lG.`OutOfStock` = FALSE
									) groupMemberCount
								FROM
									`Listing_Group`
								INNER JOIN
									`Listing` targetListing ON
										targetListing.`ID` = ?
								INNER JOIN
									`Listing` ON
										`Listing_Group`.`ListingID` = `Listing`.`ID`
								INNER JOIN
									`Currency` ON
										`Listing`.`CurrencyID` = `Currency`.`ID`
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								LEFT JOIN
									`Listing_Group` targetListing_Group ON
										targetListing_Group.`ListingID` = targetListing.`ID`
								LEFT JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								LEFT JOIN
									`Image` ON
										`Listing_Image`.`ImageID` = `Image`.`ID`
								WHERE
									(
										targetListing_Group.`GroupID` IS NULL OR
										`Listing_Group`.`GroupID` != targetListing_Group.`GroupID`
									) AND
									`Listing`.`ID` = (
										SELECT
											`Listing`.`ID`
										FROM
											`Listing`
										INNER JOIN
											`Listing_PaymentMethod` ON
												`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
										INNER JOIN
											`PaymentMethod` ON
												`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
												`PaymentMethod`.`Enabled` = TRUE
										INNER JOIN
											`Listing_Group` lG ON
												`Listing`.`ID` = lG.`ListingID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											`Listing`.`VendorID` = targetListing.`VendorID` AND
											lG.`OutOfStock` = FALSE
										ORDER BY
											(
												SELECT
													COUNT(Listing_Rating.`ID`)
												FROM
													`Transaction_Rating` Listing_Rating
												WHERE
													Listing_Rating.`ListingID` = `Listing`.`ID` AND
													Listing_Rating.`Rating_Vendor` IS NOT NULL AND
													Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
											) DESC
										LIMIT 1
									)
							)
						) a
					ORDER BY
						ratingCount DESC,
						DateAdded DESC,
						ID DESC
					LIMIT
						" . RELATED_LISTINGS_PER_PAGE . "
				",
				'iiii',
				[
					$listingID,
					$this->db->site_id,
					$listingID,
					$this->db->site_id
				]
			)
		)
			return $this->_parseRelatedListings($relatedListings, $cryptocurrency);
		
		return FALSE;
	}
	
	private function getShippingName(
		$shippingType,
		$shippingID
	){
		if (
			$localities = $this->db->qSelect(
				"	SELECT	`Name`
					FROM	`" . ($shippingType == SHIPPING_FILTER_PREFIX_LOCALE ? 'Locale' : 'Country') . "`
					WHERE	`ID` = ?
				",
				'i',
				[$shippingID]
			)
		)
			return $localities[0]['Name'];
		
		return false;
	}
	
	private function getListingShippingAvailability($listingID){
		$shipsTo = $this->User->Attributes['Preferences']['CatalogFilter']['ships_to'];
		if (
			$shipsTo > -1 && // is not anywhere
			($shipsTo = $shipsTo ?: SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $this->User->Attributes['Preferences']['LocaleID']) &&
			list(
				$shippingType,
				$shippingID
			) = explode(
				SHIPPING_FILTER_DELIMITER,
				$shipsTo,
				2
			)
		)
			return
				$this->db->qSelect(
					"	SELECT
							`Listing`.`ID`
						FROM
							`Listing`" .
					(
						$shippingType == SHIPPING_FILTER_PREFIX_LOCALE
							? "	INNER JOIN `Locale_Country` ON
									`LocaleID` = ?
								INNER JOIN `Listing_Country` ON
									`Listing`.`ID` = `Listing_Country`.`ListingID` AND
									`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`"
							: "	INNER JOIN `Listing_Country` ON
									`Listing`.`ID` = `Listing_Country`.`ListingID` AND
									`Listing_Country`.`CountryID` = ?"
					) .
					" WHERE `Listing`.`ID` = ?",
					'ii',
					[
						$shippingID,
						$listingID
					]
				)
					? true
					: $this->getShippingName(
						$shippingType,
						$shippingID
					);
					
		return true;
	}
	
	public function fetchListing($id){
		$minimum_vendor_reputation = $this->db->getSiteInfo('MinimumVendorReputation');
		$minimum_vendor_reputation = $minimum_vendor_reputation ? $minimum_vendor_reputation : 0;
		
		$stmt_getBasics_types = 'iii';
		$stmt_getBasics_params = array(
			&$this->User->ID,
			&$id,
			&$minimum_vendor_reputation
		);
		
		$stmt_getRelatedListing_types = 'ii';
		$stmt_getRelatedListing_params = array(
			&$vendor_id,
			&$id,
		);
		
		if( $allowed_category_id = $this->db->getSiteInfo('ListingCategoryID') ){
			$allowed_category_ids = array_merge(
				array($allowed_category_id),
				$this->getChildrenCategoryIDs($allowed_category_id)
			);
			$stmt_category_ids = array();
			$stmt_category_types = '';
			foreach( $allowed_category_ids as $key => $category_id ){
				$stmt_getBasics_types .= 'i';
				$stmt_getRelatedListing_types .= 'i';
				$stmt_getBasics_params[] = &$allowed_category_ids [ $key ];
				$stmt_getRelatedListing_params[] = &$allowed_category_ids [ $key ];
			}
		} else
			$allowed_category_ids = false;
		
		$stmt_getBasics_types .= 'is';
		$stmt_getBasics_params[] = &$this->User->ID;
		$stmt_getBasics_params[] = &$this->User->AccessPrefix;
		
		$stmt_getBasics_args = array_merge(
			array( $stmt_getBasics_types ),
			$stmt_getBasics_params
		);
		
		$stmt_getRelatedListing_args = array_merge(
			array( $stmt_getRelatedListing_types ),
			$stmt_getRelatedListing_params
		);
		
		$stmt_getBasics = $this->db->prepare("
			SELECT
				`User`.`ID`,
				`User`.`Alias`,
				(
					SELECT
						COUNT(DISTINCT `Listing`.`ID`)
					FROM
						`Listing`
					INNER JOIN
						`Listing_PaymentMethod` ON
							`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
							`PaymentMethod`.`Enabled` = TRUE
					LEFT JOIN
						`Listing_Group` ON
							`Listing_Group`.`ListingID` = `Listing`.`ID`
					WHERE
						`Listing`.`Inactive`	= FALSE AND
						`Listing`.`Stealth`	= FALSE AND
						`Listing`.`VendorID`	= `User`.`ID` AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						)
				),
				`Listing`.`Name`,
				IF(
					`Listing`.`Quantity_Minimum` > 1,
					`Listing`.`Price` / `Currency`.`1EUR` / `Listing`.`Quantity`,
					`Listing`.`Price` / `Currency`.`1EUR`
				),
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
				),
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
				),
				(
					SELECT
						COUNT(DISTINCT Listing_Rating.`ID`)
					FROM
						`Transaction_Rating` Listing_Rating
					LEFT JOIN
						`Listing_Group` LG2 ON
							Listing_Rating.`ListingID` = LG2.`ListingID`
					WHERE
						Listing_Rating.`Content` IS NOT NULL AND
						(
							Listing_Rating.`ListingID` = `Listing`.`ID` OR
							LG2.`GroupID` = `Listing_Group`.`GroupID`
						)
				),
				`Listing`.`Excerpt`,
				`Listing`.`HTML`,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					`Image`.`Filename`
				) Image,
				IF( (
					SELECT	COUNT(`Listing_Continent`.`ContinentID`)
					FROM	`Listing_Continent`
					WHERE	`Listing_Continent`.`ListingID` = `Listing`.`ID`
				) = (
					SELECT	COUNT(`Continent`.`ID`)
					FROM	`Continent`
				), TRUE, FALSE),
				(
					SELECT
						`Country`.`Name`
					FROM
						`Listing_Attribute`
					INNER JOIN	`Country`
						ON	`Listing_Attribute`.`Value` = `Country`.`ID`
					WHERE
						`Listing_Attribute`.`ListingID` = `Listing`.`ID`
					AND	`Listing_Attribute`.`AttributeID` = " . LISTING_ATTRIBUTE_FROM_COUNTRY . "
				),
				(
					SELECT
						`Continent`.`Name`
					FROM
						`Listing_Attribute`
					INNER JOIN	`Continent`
						ON	`Listing_Attribute`.`Value` = `Continent`.`ID`
					WHERE
						`Listing_Attribute`.`ListingID` = `Listing`.`ID`
					AND	`Listing_Attribute`.`AttributeID` = " . LISTING_ATTRIBUTE_FROM_CONTINENT . "
				),
				CASE
					WHEN `Listing`.`Quantity_Left` IS NULL
						THEN NULL
					WHEN `Listing`.`Quantity_Left` = 0
						THEN 0
					WHEN `Listing`.`Quantity_Left` <= `Listing`.`Quantity_Critical`
						THEN
							CONCAT(
								`Listing`.`Quantity_Left`*`Listing`.`Quantity`,
								IF(`Unit`.`ID` IS NOT NULL,
									CONCAT(
										' ',
										IF(
											`Listing`.`Quantity` = 1 AND `Listing`.`Quantity_Left` = 1,
											`Unit`.`Name_Singular`,
											`Name_Plural`
										)
									),
									3
								)
							)
					ELSE TRUE
				END,
				(
					SELECT
						`User_Section`.`HTML`
					FROM
						`User_Section`
					WHERE
						`VendorID` = `User`.`ID` AND
						`Type`	= 'policy'
					LIMIT
						1
				),
				IF(`User_Listing`.`UserID` IS NULL, FALSE, TRUE),
				IFNULL(`Listing_Group`.`GroupID`, FALSE),
				IF(
					`Listing`.`Quantity_Minimum` > 1,
					`Unit`.`Name_Singular`,
					FALSE
				),
				IF(
					`Listing`.`Quantity_Minimum` > 1,
					`Listing`.`Quantity_Minimum` * `Listing`.`Quantity`,
					NULL
				),
				`Unit`.`Abbreviation`
			FROM
				`Listing`
			INNER JOIN
				`Listing_PaymentMethod` ON
					`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
			INNER JOIN
				`PaymentMethod` ON
					`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
					`PaymentMethod`.`Enabled` = TRUE
			INNER JOIN
				`User` ON
					`Listing`.`VendorID` = `User`.`ID`
			INNER JOIN
				`Currency` ON
					`Listing`.`CurrencyID` = `Currency`.`ID`
			LEFT JOIN
				`Unit` ON
					`Listing`.`UnitID` = `Unit`.`ID`
			LEFT JOIN
				`User_Listing` ON
					`Listing`.`ID` = `User_Listing`.`ListingID` AND
					`User_Listing`.`UserID`	= ?
			LEFT JOIN
				`Listing_Image`	ON
					`Listing_Image`.`ListingID` = `Listing`.`ID` AND
					`Listing_Image`.`Primary` = TRUE
			LEFT JOIN
				`Image` ON
					`Listing_Image`.`ImageID` = `Image`.`ID`
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			WHERE
				`Listing`.`ID` = ? AND
				(
					(
						`Listing`.`Inactive` = FALSE AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						) AND
						`Listing`.`Approved` = TRUE AND
						`User`.`Reputation` >= ?
					" . ( $allowed_category_ids ? 'AND `Listing`.`CategoryID` IN (' . ( rtrim( str_repeat('?, ', count($allowed_category_ids)), ', ') ) . ')' : false ) ."
					) OR
					`User`.`ID` = ? " . (
						$this->User->IsMod
							? ' OR 1 = 1'
							: false
					) . "
				) AND
				(
					`User`.`Stealth` = FALSE OR
					(
						`User`.`SecretPrefix` IS NOT NULL AND
						`User`.`SecretPrefix` = ?
					)
				)
			LIMIT 1
		");
		
		$stmt_getAttributes = $this->db->prepare("
			SELECT
				`ListingAttribute`.`ID`,
				`ListingAttribute`.`Attribute`,
				`ListingAttribute`.`Type`,
				`Listing_Attribute`.`Value`
			FROM
				`Listing_Attribute`
			INNER JOIN
				`ListingAttribute` ON `Listing_Attribute`.`AttributeID` = `ListingAttribute`.`ID`
			WHERE
				`Listing_Attribute`.`ListingID` = ?
			LIMIT 10
		");
		
		$stmt_getShippingOptions = $this->db->prepare("
			SELECT
				`ListingShipping`.`Name`,
				`ListingShipping`.`Description`,
				`ListingShipping`.`Price`/`Currency`.`1EUR`
			FROM
				`Listing_Shipping`
			INNER JOIN	`ListingShipping`
				ON	`Listing_Shipping`.`ShippingID` = `ListingShipping`.`ID`
			INNER JOIN	`Currency`
				ON	`ListingShipping`.`CurrencyID` = `Currency`.`ID`
			WHERE
				`Listing_Shipping`.`ListingID` = ?
		");
		
		$stmt_getShippingDestinations = $this->db->prepare("
			SELECT
				`Country`.`Name`
			FROM
				`Listing_Country`
			INNER JOIN	`Country`
				ON	`Listing_Country`.`CountryID` = `Country`.`ID`
			WHERE
				`Listing_Country`.`ListingID` = ?
		");
		
		if(
			false !== $stmt_getBasics &&
			false !== $stmt_getRelatedListing &&
			false !== $stmt_getAttributes &&
			false !== $stmt_getShippingOptions
		){
			
			call_user_func_array(
				array(
					$stmt_getBasics,
					'bind_param'
				),
				$stmt_getBasics_args
			);
			
			$stmt_getBasics->execute();
			$stmt_getBasics->store_result();
			
			if ($stmt_getBasics->num_rows > 0){
				$stmt_getBasics->bind_result(
					$vendor_id,
					$vendor_alias,
					$listingCount,
					$listing_name,
					$listing_price_eur,
					$listing_rating,
					$listing_rating_count,
					$listing_comment_count,
					$listingExcerpt,
					$listing_html,
					$listing_image,
					$listing_ships_worldwide,
					$listing_ships_from_country,
					$listing_ships_from_continent,
					$listing_critical_quantity,
					$listing_vendor_policy,
					$listingFavorite,
					$listingGroupID,
					$listingPerUnit,
					$listingMinimumQuantity,
					$listingUnitAbbreviation
				);
				$stmt_getBasics->fetch();
				$bulk_prices = FALSE;
				
				$paymentMethods = $this->User->getListingPaymentMethods(
					$id,
					$allPaymentMethods,
					$activePaymentMethods,
					$activePaymentMethodIDs
				);
				
				$shippingAvailability = $this->getListingShippingAvailability($id);
				
				if (in_array($this->User->Cryptocurrency->ID, $activePaymentMethodIDs))
					$cryptocurrency = $this->User->Cryptocurrency;
				else
					$cryptocurrency = $this->User->getCryptocurrency(array_shift($activePaymentMethodIDs));
				
				$relatedListings = $this->_fetchRelatedListings(
					$id,
					$cryptocurrency
				);
				
				$options =
					$listingGroupID
						? $this->_fetchListingGroupMembers(
							$id,
							$cryptocurrency,
							false,
							true
						)
						: FALSE;
				
				if ($exceededMaximumVisibleRatings = $listing_rating_count > MAX_VISIBLE_INDIVIDUAL_RATINGS)
					$listing_rating_count = floor($listing_rating_count / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
				
				$listing = array(
					'vendor' => array(
						'alias'		=> $vendor_alias,
						'listingCount'	=> $listingCount,
						'policy'	=> $listing_vendor_policy
					),
					'relatedListings' => $relatedListings,
					'listing' => array(
						'name'			=> $listing_name,
						'price'			=> NXS::formatPrice($this->User->Currency, $listing_price_eur),
						'price_crypto'		=> $cryptocurrency->formatPrice($listing_price_eur),
						'bulk_prices'		=> $bulk_prices,
						'rating'		=> $listing_rating,
						'rating_count'		=> $listing_rating_count,
						'commentCount'		=> $listing_comment_count,
						'critical_quantity'	=> $listing_critical_quantity,
						'favorite'		=> $listingFavorite,
						'perUnit'		=> $listingPerUnit,
						'minimumQuantity'	=> 
							$listingMinimumQuantity
								? NXS::formatDecimal($listingMinimumQuantity) . ' ' . $listingUnitAbbreviation . ' minimum'
								: false,
						'shippingAvailability'	=> $shippingAvailability,
						'exceededMaximumVisibleRatings' => $exceededMaximumVisibleRatings
					),
					'options' => $options,
					'paymentMethods' => $paymentMethods
				);
				
				if( $stmt_getAttributes->num_rows > 0 ){
					$stmt_getAttributes->bind_result($attribute_id, $attribute_name, $attribute_type, $attribute_value);
					
					$attributes = array();
					while( $stmt_getAttributes->fetch() ){
						$attributes[ $attribute_id ] = array(
							'name'	=> $attribute_name,
							'type'	=> $attribute_type,
							'value'	=> $attribute_value);
					}
					
				} else
					$attributes = false;
				
				$stmt_getShippingOptions->bind_param('i', $id);
				$stmt_getShippingOptions->execute();
				$stmt_getShippingOptions->store_result();
				if( $stmt_getShippingOptions->num_rows > 0 ){
					$stmt_getShippingOptions->bind_result($shipping_name, $shipping_description, $shipping_price_eur);
					
					$shipping_options = array();
					while( $stmt_getShippingOptions->fetch() ){
						$shipping_options[] = array(
							'name' => $shipping_name,
							'description' => $shipping_description,
							'price' => NXS::formatPrice($this->User->Currency, $shipping_price_eur),
							'price_crypto' => $cryptocurrency->formatPrice($shipping_price_eur)
						);
					}
					
					$shipping = array(
						'ships_from' => array(
							'continent' => !empty($listing_ships_from_continent) ? $listing_ships_from_continent : false, 
							'country' => !empty($listing_ships_from_country) ? $listing_ships_from_country : false, 
						),
						'shipping_options' => $shipping_options
					);
					
				} elseif ( isset($attributes[LISTING_ATTRIBUTE_FROM_CONTINENT]) || isset($attributes[LISTING_ATTRIBUTE_FROM_COUNTRY]) ) {
					$shipping = array(
						'ships_from' => array(
							'continent' => !empty($listing_ships_from_continent) ? $listing_ships_from_continent : false, 
							'country' => !empty($listing_ships_from_country) ? $listing_ships_from_country : false, 
						),
						'shipping_options' => false
					);
				} else {
					$shipping = false;
				}
				
				if( $shipping ){
					if( $listing_ships_worldwide == 1 ){
						$ships_to = 'Worldwide';
					} else {
					
						$stmt_getShippingDestinations->bind_param('i', $id);
						$stmt_getShippingDestinations->execute();
						$stmt_getShippingDestinations->store_result();
						
						if( $stmt_getShippingDestinations->num_rows > 0 ){
							
							$stmt_getShippingDestinations->bind_result($shipping_destination);
							
							$ships_to = array();
							while( $stmt_getShippingDestinations->fetch() ){
								$ships_to[] = $shipping_destination;
							}
							
							$ships_to = '<em>' . implode('</em>, <em>', $ships_to) . '</em>';
							
						} else {
							
							$ships_to = false;
							
						}
					
					}
					
					$shipping = array_merge($shipping, array(
						'ships_to' => $ships_to
					));
					
				}
				
				if( $stmt_getGallery = $this->db->prepare("
					SELECT
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) Image
					FROM
						`Listing_Image`
					INNER JOIN
						`Image` ON
							`Listing_Image`.`ImageID` = `Image`.`ID`
					WHERE
						`ListingID` = ?
					ORDER BY
						`Primary` ASC
					LIMIT 3
				") ){
					$stmt_getGallery->bind_param('i', $id);
					$stmt_getGallery->execute();
					$stmt_getGallery->store_result();
					if($stmt_getGallery->num_rows > 0){
						$stmt_getGallery->bind_result($gallery_image);
						
						while( $stmt_getGallery->fetch() ){
							$thumbnail = NXS::getPictureVariant($gallery_image, IMAGE_MEDIUM_SUFFIX);
							
							$gallery_images[] = array(
								'big' => $gallery_image,
								'small' => $thumbnail
							);
						}
					}
				}
				
				if(
					$listing_comment_count > 0 &&
					$stmt_getFeaturedComment = $this->db->prepare("
						SELECT
							*
						FROM
							(
								SELECT
									Listing_Rating.`Rating_Vendor`,
									Listing_Rating.`Content`,
									Listing_Rating.`Date`
								FROM
									`Transaction_Rating` Listing_Rating
								INNER JOIN
									`Listing` ON
										`Listing`.`ID` = ?
								LEFT JOIN
									`Listing_Group` ON
										`Listing`.`ID` = `Listing_Group`.`ListingID`
								LEFT JOIN
									`Listing_Group` LG2 ON
										Listing_Rating.`ListingID` = LG2.`ListingID`
								WHERE
									Listing_Rating.`Rating_Vendor` > 2 AND
									Listing_Rating.`Content` IS NOT NULL AND
									(
										Listing_Rating.`ListingID` = `Listing`.`ID` OR
										LG2.`GroupID` = `Listing_Group`.`GroupID`
									)
								ORDER BY
									Listing_Rating.`ID` DESC
								LIMIT 10
							) T
						ORDER BY RAND()
						LIMIT " . LISTING_FEATURED_COMMENT_COUNT . "
					")
				){
					$stmt_getFeaturedComment->bind_param('i', $id);
					$stmt_getFeaturedComment->execute();
					$stmt_getFeaturedComment->store_result();
					if( $stmt_getFeaturedComment->num_rows > 0 ){
						$stmt_getFeaturedComment->bind_result(
							$comment_rating,
							$comment_content,
							$comment_date
						);
						
						$featured_comments = array();
						while( $stmt_getFeaturedComment->fetch() )
							$featured_comments[] = array(
								'rating'	=> $comment_rating,
								'content'	=> $comment_content,
								'date'		=> strtolower(date('M jS, Y', strtotime($comment_date)))
							);
							
					} else
						$featured_comments = false;
				}
				
				if( empty($listing_html) ){
					$listing_html = NXS::formatText($listing_description);
					
					if( $stmt_updateListingHTML = $this->db->prepare("
						UPDATE
							`Listing`
						SET
							`HTML` = ?
						WHERE
							`ID` = ?
					") ){
						
						$stmt_updateListingHTML->bind_param('si', $listing_html, $id);
						$stmt_updateListingHTML->execute();
						
					}
					
				}
				
				$stmt_getListingQuestions = $this->db->prepare("
					SELECT
						DISTINCT `Listing_Question`.`ID`,
						`Listing_Question`.`Title`,
						`Listing_Question`.`HTML`,
						IF(
							thisListing.`VendorID` = thisUser.`ID`,
							`Listing_Question`.`Content`,
							FALSE
						)
					FROM
						`Listing_Question`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					INNER JOIN
						`Listing` thisListing ON
							thisListing.`ID` = ?
					LEFT JOIN
						`Listing_Group` thisListingGroup ON
							thisListing.`ID` = thisListingGroup.`ListingID`
					LEFT JOIN
						`Listing_Group` listingGroupMembers ON
							thisListingGroup.`GroupID` = listingGroupMembers.`GroupID`
					WHERE
						(
							`Listing_Question`.`ListingID` = thisListing.`ID` OR
							`Listing_Question`.`ListingID` = listingGroupMembers.`ListingID`
						) AND
						(
							`Listing_Question`.`HTML` IS NOT NULL OR
							thisListing.`VendorID` = thisUser.`ID`
						)
					ORDER BY
						`Listing_Question`.`Sort` ASC
				");
				
				$stmt_getListingQuestions->bind_param('ii', $this->User->ID, $id);
				$stmt_getListingQuestions->execute();
				$stmt_getListingQuestions->store_result();
				
				if( $stmt_getListingQuestions->num_rows > 0 ){
					
					$stmt_getListingQuestions->bind_result(
						$question_id,
						$question_title,
						$question_html,
						$question_raw
					);
					
					$questions = array();
					while( $stmt_getListingQuestions->fetch() ){
						$questions[] = array(
							'id' => $question_id,
							'title' => $question_title,
							'html' => $question_html,
							'raw' => empty($question_raw) ? false : $question_raw
						);
					}
					
				} else {
					$questions = false;
				}
				
				// Summary
				if( !$listingExcerpt ){
					$start = strpos($listing_html, '<p>');
					$end = strpos($listing_html, '</p>');
					$listingExcerpt = substr(
						$listing_html,
						$start+3,
						$end-$start-3
					);
				}
				
				$listing = array_merge_recursive($listing, array(
					'listing' => array(
						'description'		=> $listing_html,
						'summary'		=> $listingExcerpt,
						'images'		=> $gallery_images,
						'attributes'		=> $attributes,
						'shipping'		=> $shipping,
						'featured_comments'	=> $featured_comments,
						'questions'		=> $questions
					),
				));
				
				if( !$attributes ){
					
					$attributes = array();
					
					if( !empty( $listing_quantity ) )
						$attributes[LISTING_ATTRIBUTE_QUANTITY] = array(
							'name' => 'Quantity',
							'type' => 'special',
							'value' => $listing_quantity
						);
					
					if( !empty( $listing_quantity_left ) )
						$attributes[LISTING_ATTRIBUTE_QUANTITY_LEFT] = array(
							'name' => 'Stock',
							'type' => 'special',
							'value' => $listing_quantity_left
						);
						
					if( $listing_critical_quantity !== FALSE )
						$attributes[LISTING_ATTRIBUTE_CRITICAL_QUANTITY] =  array(
							'name' => 'Critical Quantity',
							'type' => 'special',
							'value' => $listing_critical_quantity
						);	
					
					
					if($attributes){
						$listing = array_merge_recursive($listing, array(
							'listing' => array(
								'attributes' => $attributes
							)
						));
					}
					
				}
				
				$this->_insertListingUniqueView($id);
				
				//print_r($listing); die;
				return $listing;
				
			} else
				return false;
		}
		
	}
	
	private function _insertListingUniqueView($listingID){
		return	$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Listing_UniqueView` (
							`ListingID`,
							`UserID`,
							`Date`
						)
					VALUES (
						?,
						?,
						NOW()
					)
				",
				'ii',
				[
					$listingID,
					$this->User->ID
				]
			);
	}
	
	public function fetchComments($listing_id, $page){
		$stmt_getListingStats = $this->db->prepare("
			SELECT
				`Name`,
				`User`.`Alias`
			FROM
				`Listing`
			INNER JOIN	`User`
				ON	`Listing`.`VendorID` = `User`.`ID`
			WHERE	`Listing`.`ID` = ?
		");
		
		$stmt_countComments = $this->db->prepare("
			SELECT
				COUNT(DISTINCT Listing_Rating.`ID`)
			FROM
				`Transaction_Rating` Listing_Rating
			INNER JOIN
				`Listing` ON
					`Listing`.`ID` = ?
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			LEFT JOIN
				`Listing_Group` LG2 ON
					Listing_Rating.`ListingID` = LG2.`ListingID`
			WHERE
				Listing_Rating.`Content` IS NOT NULL AND
				(
					Listing_Rating.`ListingID` = `Listing`.`ID` OR
					LG2.`GroupID` = `Listing_Group`.`GroupID`
				) AND
				Listing_Rating.`Date` >= LEAST(
					NOW() - INTERVAL " . MAX_AGE_VISIBLE_TRANSACTION_COMMENTS_MONTHS . " MONTH,
					IFNULL(
						(
							SELECT
								`Transaction_Rating`.`Date`
							FROM
								`Transaction_Rating`
							LEFT JOIN
								`Listing_Group` LG3 ON
									`Transaction_Rating`.`ListingID` = LG3.`ListingID`
							WHERE
								(
									`Transaction_Rating`.`ListingID` = `Listing`.`ID` OR
									LG3.`GroupID` = `Listing_Group`.`GroupID`
								) AND
								`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
							ORDER BY
								`Transaction_Rating`.`Date` DESC
							LIMIT
								" . VENDOR_AVERAGE_RATING_MINIMUM_RATINGS . ", 1
						),
						NOW()
					)
				)
		");
		
		$stmt_getComments = $this->db->prepare("
			SELECT
				Listing_Rating.`Content`,
				Listing_Rating.`Rating_Vendor`,
				Listing_Rating.`Date`
			FROM
				`Transaction_Rating` Listing_Rating
			INNER JOIN
				`Listing` ON
					`Listing`.`ID` = ?
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			LEFT JOIN
				`Listing_Group` LG2 ON
					Listing_Rating.`ListingID` = LG2.`ListingID`
			WHERE
				Listing_Rating.`Content` IS NOT NULL AND
				(
					Listing_Rating.`ListingID` = `Listing`.`ID` OR
					LG2.`GroupID` = `Listing_Group`.`GroupID`
				) AND
				Listing_Rating.`Date` >= LEAST(
					NOW() - INTERVAL " . MAX_AGE_VISIBLE_TRANSACTION_COMMENTS_MONTHS . " MONTH,
					IFNULL(
						(
							SELECT
								`Transaction_Rating`.`Date`
							FROM
								`Transaction_Rating`
							LEFT JOIN
								`Listing_Group` LG3 ON
									`Transaction_Rating`.`ListingID` = LG3.`ListingID`
							WHERE
								(
									`Transaction_Rating`.`ListingID` = `Listing`.`ID` OR
									LG3.`GroupID` = `Listing_Group`.`GroupID`
								) AND
								`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
							ORDER BY
								`Transaction_Rating`.`Date` DESC
							LIMIT
								" . VENDOR_AVERAGE_RATING_MINIMUM_RATINGS . ", 1
						),
						NOW()
					)
				)
			ORDER BY
				Listing_Rating.`Date` DESC
			LIMIT
				?,
				" . REVIEWS_PER_PAGE . "
		");
		
		if( FALSE !== $stmt_getListingStats && FALSE !== $stmt_countComments && FALSE !== $stmt_getComments ){
			
			$stmt_getListingStats->bind_param('i', $listing_id);
			$stmt_getListingStats->execute();
			$stmt_getListingStats->store_result();
			$stmt_getListingStats->bind_result($listing_name, $vendor_alias);
			$stmt_getListingStats->fetch();
			
			$stmt_countComments->bind_param('i', $listing_id);
			$stmt_countComments->execute();
			$stmt_countComments->store_result();
			$stmt_countComments->bind_result($comment_count);
			$stmt_countComments->fetch();
			
			if($comment_count > 0) {
				
				if( ceil($comment_count/REVIEWS_PER_PAGE) < $page ){
					$offset = 0;
					$this->User->Notifications->quick('FatalError', 'Invalid Page');
				} else {
					$offset = REVIEWS_PER_PAGE*($page - 1);
				}
				
				$stmt_getComments->bind_param('ii', $listing_id, $offset);
				$stmt_getComments->execute();
				$stmt_getComments->store_result();
				$stmt_getComments->bind_result(
					$comment_content,
					$comment_rating,
					$comment_date
				);
				
				$comments = array();
				while( $stmt_getComments->fetch() ){
					$comments[] = array(
						'content' => $comment_content,
						'rating' => $comment_rating,
						'date' => strtolower(date('M jS, Y', strtotime($comment_date))),
					);
				}
				
				return array($listing_name, $vendor_alias, $comment_count, $comments);
				
			} else
				return false;
			
		}
		
	}
	
	private function _fetchVendorListings(
		$vendorID,
		$cryptocurrency = false
	){
		$cryptocurrency = $cryptocurrency ?: $this->User->Cryptocurrency;
		
		if(
			$vendorListings = $this->db->qSelect(
				"
					SELECT
						*
					FROM
						(
							( # Singular Listings
								SELECT
									DISTINCT `Listing`.`ID` ID,
									`Listing`.`DateAdded`,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									LEAST(
										(
											SELECT
												COUNT(Listing_Rating.`ID`)
											FROM
												`Transaction_Rating` Listing_Rating
											WHERE
												Listing_Rating.`ListingID` = `Listing`.`ID` AND
												Listing_Rating.`Rating_Vendor` IS NOT NULL
										),
										IFNULL(
											Vendor.`MaxVisibleRatings`,
											" . MAX_VISIBLE_RATINGS_DEFAULT . "
										)
									) ratingCount,
									(
										SELECT
											AVG(Listing_Rating.`Rating_Vendor`)
										FROM
											`Transaction_Rating` Listing_Rating
										WHERE
											Listing_Rating.`ListingID` = `Listing`.`ID` AND
											Listing_Rating.`Rating_Vendor` IS NOT NULL AND
											Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) averageRating,
									FALSE groupMemberCount
								FROM
									`Listing`
								INNER JOIN
									`Listing_PaymentMethod` ON
										`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
								INNER JOIN
									`PaymentMethod` ON
										`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
										`PaymentMethod`.`Enabled` = TRUE
								INNER JOIN
									`Currency` ON
										`Listing`.`CurrencyID` = `Currency`.`ID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
								LEFT JOIN
									`Listing_Group` ON
										`Listing`.`ID` = `Listing_Group`.`ListingID`
								LEFT JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								LEFT JOIN
									`Image` ON
										`Listing_Image`.`ImageID` = `Image`.`ID`
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								WHERE
									`Listing`.`VendorID` = ? AND
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Approved` = TRUE AND
									`Listing`.`Stealth` = FALSE AND
									`Listing_Group`.`GroupID` IS NULL
							) UNION ALL
							( # Listing Groups
								SELECT
									`Listing`.`ID` ID,
									`Listing`.`DateAdded`,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									LEAST(
										(
											SELECT
												COUNT(DISTINCT Group_Rating.`ID`)
											FROM
												`Transaction_Rating` Group_Rating
											INNER JOIN
												`Listing_Group` lG ON
													Group_Rating.`ListingID` = lG.`ListingID`
											WHERE
												lG.`GroupID` = `Listing_Group`.`GroupID` AND
												Group_Rating.`Rating_Vendor` IS NOT NULL
										),
										IFNULL(
											Vendor.`MaxVisibleRatings`,
											" . MAX_VISIBLE_RATINGS_DEFAULT . "
										)
									) ratingCount,
									(
										SELECT
											AVG(Group_Rating.`Rating_Vendor`)
										FROM
											`Transaction_Rating` Group_Rating
										INNER JOIN
											`Listing_Group` lG ON
												Group_Rating.`ListingID` = lG.`ListingID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											Group_Rating.`Rating_Vendor` IS NOT NULL AND
											Group_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) averageRating,
									(
										SELECT
											COUNT(DISTINCT lG.`ListingID`)
										FROM
											`Listing_Group` lG
										INNER JOIN
											`Listing` ON
												lG.`ListingID` = `Listing`.`ID`
										INNER JOIN
											`Listing_PaymentMethod` ON
												`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
										INNER JOIN
											`PaymentMethod` ON
												`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
												`PaymentMethod`.`Enabled` = TRUE
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											lG.`OutOfStock` = FALSE
									) groupMemberCount
								FROM
									`Listing_Group`
								INNER JOIN
									`Listing` ON
										`Listing_Group`.`ListingID` = `Listing`.`ID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
								INNER JOIN
									`Currency` ON
										`Listing`.`CurrencyID` = `Currency`.`ID`
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								LEFT JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								LEFT JOIN
									`Image` ON
										`Listing_Image`.`ImageID` = `Image`.`ID`
								WHERE
									`Listing`.`ID` = (
										SELECT
											`Listing`.`ID`
										FROM
											`Listing`
										INNER JOIN
											`Listing_PaymentMethod` ON
												`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
										INNER JOIN
											`PaymentMethod` ON
												`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
												`PaymentMethod`.`Enabled` = TRUE
										INNER JOIN
											`Listing_Group` lG ON
												`Listing`.`ID` = lG.`ListingID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											`Listing`.`VendorID` = ? AND
											lG.`OutOfStock` = FALSE
										ORDER BY
											(
												SELECT
													COUNT(Listing_Rating.`ID`)
												FROM
													`Transaction_Rating` Listing_Rating
												WHERE
													Listing_Rating.`ListingID` = `Listing`.`ID` AND
													Listing_Rating.`Rating_Vendor` IS NOT NULL AND
													Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
											) DESC
										LIMIT 1
									)
							)
						) a
					ORDER BY
						ratingCount DESC,
						`DateAdded` DESC,
						ID DESC
					LIMIT
						" . FEATURED_LISTINGS_PER_PAGE . "
				",
				'iiii',
				[
					$this->db->site_id,
					$vendorID,
					$this->db->site_id,
					$vendorID
				]
			)
		)
			return $this->_parseRelatedListings($vendorListings, $cryptocurrency);
		
		return FALSE;
	}
	
	private function _parseRelatedListings(
		$relatedListings,
		$cryptocurrency
	){
		foreach($relatedListings as $key => $listing){
			$options =
				$listing['groupMemberCount']
					? $this->_fetchListingGroupMembers(
						$listing['ID'],
						$cryptocurrency,
						false, //LISTINGS_GRID_OPTIONS_MAX_QUANTITY
						true,
						true,
						true,
						$trivialGroup
					)
					: FALSE;
			
			if ($listing['exceededMaximumVisibleRatings'] = $listing['ratingCount'] > MAX_VISIBLE_INDIVIDUAL_RATINGS)
				$listing['ratingCount'] = floor($listing['ratingCount'] / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
			
			$relatedListings[$key] = array_merge(
				$listing,
				[
					'B36'			=> NXS::getB36($listing['ID']),
					'price'			=> NXS::formatPrice($this->User->Currency, $listing['EUR_Price']),
					'price_crypto'		=> $cryptocurrency->formatPrice($listing['EUR_Price']),
					'options'		=> $options,
					'trivialOptions'	=> $trivialGroup,
					'Image'			=> NXS::getPictureVariant($listing['Image'], IMAGE_MEDIUM_SUFFIX)
				]
			);
		}
		
		return $relatedListings;
	}
	
	public function fetchVendor($vendor_alias){
		$stmt_getRelatedListing_types = 'i';
		$stmt_getRelatedListing_params = array(&$user_id);
		
		$stmt_getRelatedListing_args = array_merge(
			array( $stmt_getRelatedListing_types ),
			$stmt_getRelatedListing_params
		);
		
		$stmt_getVendor_query = "
			SELECT
				`User`.`ID` AS UserID,
				`User`.`Alias` UserAlias,
				(
					SELECT
						IF(
							`User`.`Vendor`,
							AVG(User_Rating.`Rating_Vendor`),
							AVG(User_Rating.`Rating_Buyer`)
						)
					FROM
						`Transaction_Rating` User_Rating
					WHERE
						(
							User_Rating.`BuyerID` = `User`.`ID` AND
							User_Rating.`Rating_Buyer` IS NOT NULL
						) OR
						(
							User_Rating.`VendorID` = `User`.`ID` AND
							User_Rating.`Rating_Vendor` IS NOT NULL AND
							User_Rating.`Date` >= LEAST(
								NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY,
								IFNULL(
									(
										SELECT
											`Transaction_Rating`.`Date`
										FROM
											`Transaction_Rating`
										WHERE
											`Transaction_Rating`.`VendorID` = `User`.`ID` AND
											`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
										ORDER BY
											`Transaction_Rating`.`Date` DESC
										LIMIT
											" . VENDOR_AVERAGE_RATING_MINIMUM_RATINGS . ", 1
									),
									NOW()
								)
							)
						)
				) Rating,
				LEAST(
					(
						SELECT	COUNT(User_Rating.`ID`)
						FROM	`Transaction_Rating` User_Rating
						WHERE	(
								User_Rating.`BuyerID` = `User`.`ID` AND
								User_Rating.`Rating_Buyer` IS NOT NULL
							) OR
							(
								User_Rating.`VendorID` = `User`.`ID` AND
								User_Rating.`Rating_Vendor` IS NOT NULL
							)
					),
					IFNULL(
						`User`.`MaxVisibleRatings`,
						" . MAX_VISIBLE_RATINGS_DEFAULT . "
					)
				) RatingCount,
				(
					SELECT
						COUNT(DISTINCT `Listing`.`ID`)
					FROM
						`Listing`
					INNER JOIN
						`Listing_PaymentMethod` ON
							`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
							`PaymentMethod`.`Enabled` = TRUE
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`Listing_Group`.`GroupID` IS NULL AND
						`VendorID` = `User`.`ID` AND
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE
				) +
				(
					SELECT
						COUNT(DISTINCT `Listing_Group`.`GroupID`)
					FROM
						`Listing`
					INNER JOIN
						`Listing_PaymentMethod` ON
							`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
					INNER JOIN
						`PaymentMethod` ON
							`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
							`PaymentMethod`.`Enabled` = TRUE
					INNER JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`VendorID` = `User`.`ID` AND
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE AND
						`Listing_Group`.`OutOfStock` = FALSE
				) ListingCount,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					`Image`.`Filename`
				) Image,
				`User`.`BuyCount` BuyCount,
				`User`.`SellCount` SellCount,
				`User`.`PGP` PGP,
				(
					SELECT
						COUNT(*)
					FROM
						`Transaction_Rating` User_Rating
					WHERE
						User_Rating.`VendorID` = `User`.`ID`
					AND	User_Rating.`Content` IS NOT NULL
				) CommentCount,
				`User`.`Vendor` IsVendor,
				(
					SELECT
						COUNT(`User_User`.`FollowerID`)
					FROM
						`User_User`
					WHERE
						`User_User`.`UserID` = `User`.`ID`
				) followerCount,
				(
					SELECT
						COUNT(`Discussion_Comment`.`ID`)
					FROM
						`Discussion_Comment`
					WHERE
						`Discussion_Comment`.`PosterID` = `User`.`ID` 
				) +
				(
					SELECT
						COUNT(`BlogPost`.`ID`)
					FROM
						`BlogPost`
					WHERE
						`BlogPost`.`PosterID` = `User`.`ID`
				) postCount,
				(
					SELECT
						COUNT(`User_User`.`FollowerID`)
					FROM
						`User_User`
					WHERE
						`User_User`.`UserID` = `User`.`ID`
					AND	`User_User`.`FollowerID` = ?
				) isFollowing,
				time_to_sec(
					timediff(
						NOW(),
						`User`.`LastSeen`
					)
				) / 3600 lastSeen_hours,
				`Banned`,
				`Admin`
			FROM
				`User`
			LEFT JOIN
				`Image` ON
					`User`.`ImageID` = `Image`.`ID`
			WHERE
				`User`.`Alias` = ?
			LIMIT 1
		";
		
		$stmt_getFeaturedComments = $this->db->prepare("
			SELECT
				*
			FROM
				(
					SELECT
						User_Rating.`Rating_Vendor`,
						User_Rating.`Content`,
						User_Rating.`Date`,
						`Listing`.`Name`
					FROM
						`Transaction_Rating` User_Rating
					INNER JOIN	`User` Rated
						ON	Rated.`ID` = ?
					LEFT JOIN	`User` Rater
						ON	User_Rating.`BuyerID` = Rater.`ID`
					LEFT JOIN	`Listing`
						ON	User_Rating.`ListingID` = `Listing`.`ID`
					WHERE
						User_Rating.`VendorID` = Rated.`ID`
					AND	User_Rating.`Rating_Vendor` > 2
					AND	User_Rating.`Content` IS NOT NULL
					ORDER BY
						User_Rating.`Rating_Vendor` DESC
					LIMIT 10
				) T
			ORDER BY RAND()
			LIMIT " . FEATURED_VENDOR_COMMENTS_PER_PAGE . "
		");
		
		$stmt_getSections = $this->db->prepare("
			SELECT
				`Name`,
				`HTML`
			FROM
				`User_Section`
			WHERE
				`VendorID` = ?
			ORDER BY `Sort`
		");
		
		if(
			false !== $stmt_getFeaturedComments &&
			false !== $stmt_getSections
		){
			$minimum_vendor_reputation = $this->db->getSiteInfo('MinimumVendorReputation');
			$minimum_vendor_reputation = $minimum_vendor_reputation ? $minimum_vendor_reputation : 0;
			
			$getVendor = $this->db->qSelect(
				$stmt_getVendor_query,
				'is',
				array(
					$this->User->ID,
					$vendor_alias
				)
			);
			
			if (count($getVendor) == 1){ 
				$getVendor = $getVendor[0];
				
				$user_id = $getVendor['UserID'];
				$user_alias = $getVendor['UserAlias'];
				$user_rating = $getVendor['Rating'];
				$user_rating_count = $getVendor['RatingCount'];
				$user_listing_count = $getVendor['ListingCount'];
				$user_image = $getVendor['Image'];
				$user_buy_count = $getVendor['BuyCount'];
				$user_sell_count = $getVendor['SellCount'];
				$user_pgp = $getVendor['PGP'];
				$comment_count = $getVendor['CommentCount'];
				$is_vendor = $getVendor['IsVendor'];
				
				$vendorCategories = $is_vendor ? $this->getVendorCategories($user_id, 1, 3) : FALSE;
				if($vendorUpdate = $this->getVendorUpdate($user_id)){
					$vendorUpdate['content'] = $vendorUpdate['content'];
					$vendorUpdate['date'] = strtolower(date('M j', strtotime($vendorUpdate['date'])));
					$vendorUpdate['content'] = '<p><strong>' . $vendorUpdate['date'] . '</strong>: ' . $vendorUpdate['content'];				
				}
				
				if (!$this->db->forum){
					$cryptocurrency = $this->User->Cryptocurrency;
					
					$relatedListings = $this->_fetchVendorListings($user_id, $cryptocurrency);
					
					$stmt_getFeaturedComments->bind_param('i', $user_id);
					$stmt_getFeaturedComments->execute();
					$stmt_getFeaturedComments->store_result();
					$stmt_getFeaturedComments->bind_result(
						$comment_rating,
						$user_rating_comment,
						$user_rating_date,
						$user_rating_listing
					);
					  
					$featured_comments = array();
					while( $stmt_getFeaturedComments->fetch() )
						$featured_comments[] = array(
							'rating'	=> $comment_rating,
							'comment'	=> $user_rating_comment,
							'date'		=> strtolower(date('M jS, Y', strtotime($user_rating_date))),
							'listing'	=> $user_rating_listing ? $user_rating_listing : FALSE
						);
				} else
					$related_listings = $featured_comments = false;
					
				$stmt_getSections->bind_param('i', $user_id);
				$stmt_getSections->execute();
				$stmt_getSections->store_result();
				
				if( $stmt_getSections->num_rows > 0 ){
					$stmt_getSections->bind_result($section_name, $section_html);
					
					$vendor_sections = array();
					while( $stmt_getSections->fetch() ){
						$vendor_sections[] = array(
							'name' => $section_name,
							'anchor' => preg_replace(
								'/\s+/', '-',
								preg_replace(
									'/[^\w\s-]/',
									'',
									strtolower($section_name)
								)
							),
							'content' => $section_html
						);
					}
				} else
					$vendor_sections = false;
				
				$distinctions = $this->db->qSelect(
					"
						SELECT
						    `Distinction`.`Name`,
						    `Distinction`.`Icon`,
						    `Distinction`.`Color`,
						    `Distinction`.`Style`
						FROM
						    `Distinction`
						INNER JOIN `User_Distinction`
						    ON
							`Distinction`.`ID` = `User_Distinction`.`DistinctionID`
						WHERE
						    `User_Distinction`.`UserID` = ?
					",
					'i',
					[
						$user_id
					]
				);
				
				if ($getVendor['Admin'])
					$lastSeen = '<strong>4:20</strong>';
				elseif ($getVendor['lastSeen_hours'] > 24){
					$lastSeen_days = ceil($getVendor['lastSeen_hours'] / 24);
					$lastSeen = "<strong>&#126;" . $lastSeen_days . "</strong> days ago";
				} elseif ($getVendor['lastSeen_hours'] < GRANULARITY_VENDOR_LAST_SEEN_HOURS) {
					//$lastSeen = "Less than " . GRANULARITY_VENDOR_LAST_SEEN_HOURS . " hours ago";
					$lastSeen = "Today";
				} else {
					$lastSeen_hours_granular = NXS::roundUpToMultiple($getVendor['lastSeen_hours'], GRANULARITY_VENDOR_LAST_SEEN_HOURS);
					$lastSeen = "<strong>&#126;" . $lastSeen_hours_granular . "</strong> hours ago";
				}
				
				if ($exceededMaximumVisibleRatings = $user_rating_count > MAX_VISIBLE_INDIVIDUAL_RATINGS)
					$user_rating_count = floor($user_rating_count / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
				
				$vendor = array(
					'alias'			=> $user_alias,
					'rating'		=> $user_rating,
					'rating_count'		=> $user_rating_count,
					'listing_count'		=> $user_listing_count,
					'image'			=> NXS::getPictureVariant($user_image, IMAGE_MEDIUM_SUFFIX),
					'buy_count'		=> $user_buy_count >= 0
									? $user_buy_count
									: FALSE,
					'sell_count'		=> $user_sell_count >= 0
									? $user_sell_count
									: FALSE,
					'pgp'			=> empty($user_pgp)
									? FALSE
									: $user_pgp,
					'commentCount'		=> $comment_count,
					'sections'		=> $vendor_sections,
					'featured_comments'	=> $featured_comments,
					'relatedListings'	=> $relatedListings,
					'is_vendor'		=> $is_vendor,
					'categories'		=> $vendorCategories,
					'update'		=> $vendorUpdate,
					'followerCount'		=> $getVendor['followerCount'],
					'postCount'		=> $getVendor['postCount'],
					'isFollowing'		=> $getVendor['isFollowing'],
					'distinctions'		=> $distinctions,
					'lastSeen'		=> $lastSeen,
					'banned'		=> $getVendor['Banned'],
					'exceededMaximumVisibleRatings' => $exceededMaximumVisibleRatings
				);
			} else
				return false;
			
			return $vendor;
		} else
			die(); //$this->db->error);
	}
	
	private function getVendorCategories(
		$vendorID,
		$maxDepth = 1,
		$maxResults = 3,
		$lowestDepth = 5,
		$vendorCategories = []
	){
		$allVendorCategories = $this->db->qSelect(
			"
				SELECT DISTINCT
					LC3.`Name`,
					LC3.`Depth`
				FROM
					`Listing`
				INNER JOIN
					`Listing_PaymentMethod` ON
						`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
				INNER JOIN
					`PaymentMethod` ON
						`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
						`PaymentMethod`.`Enabled` = TRUE
				INNER JOIN	`ListingCategory` LC1
					ON	`Listing`.`CategoryID` = LC1.`ID`
				LEFT JOIN	`ListingCategory` LC2
					ON	LC1.`ParentID` = LC2.`ID`
				INNER JOIN	`ListingCategory` LC3
					ON	(
							LC2.`ParentID` = LC3.`ID` OR
							LC2.`ID` = LC3.`ID` OR
							LC1.`ID` = LC3.`ID`
						)
				LEFT JOIN
					`Listing_Group` ON
						`Listing`.`ID` = `Listing_Group`.`ListingID`
				WHERE
					`Listing`.`VendorID` = ?
				AND	`Listing`.`Inactive` = FALSE
				AND	`Listing`.`Stealth` = FALSE
				AND	`Listing`.`Approved` = TRUE AND
					(
						`Listing_Group`.`GroupID` IS NULL OR	
						`Listing_Group`.`OutOfStock` = FALSE
					)
			",
			'i',
			array($vendorID)
		);
		
		if($allVendorCategories){
			array_walk(
				$allVendorCategories,
				function($array, $key) use (&$vendorCategories, &$lowestDepth){
					$vendorCategories[ $array['Depth'] ][] = $array['Name'];
					$lowestDepth = $array['Depth'] < $lowestDepth ? $array['Depth'] : $lowestDepth;
				}
			);
			
			$results = $vendorCategories[ $lowestDepth ];
			
			for(
				$i = $lowestDepth;
				count($vendorCategories[ $i ]) == 1 &&
				isset($vendorCategories[ $i + 1 ]) &&
				count($vendorCategories[ $i + 1 ]) <= $maxResults &&
				$i + 1 <= $maxDepth;
				$i++
			)
				$results = $vendorCategories[ $i + 1 ];
			
			return $results;
		} else
			return FALSE;
	}
	
	private function getVendorUpdate($vendorID){
		$vendorUpdate = $this->db->qSelect(
			"
				SELECT
					`BlogPost`.`ID` as ID,
					`BlogPost`.`HTML` as content,
					`BlogPost`.`DateUpdated` as date,
					`Blog`.`Alias` BlogAlias
				FROM
					`BlogPost`
				INNER JOIN
					`Blog` ON
						`BlogPost`.`BlogID` = `Blog`.`ID`
				WHERE
					`Blog`.`UserID` = ? AND
					`BlogPost`.`DateUpdated` > NOW() - INTERVAL " . USER_BLOG_POST_VISIBLE_ON_PROFILE_DAYS_SINCE_UPDATED . " DAY
				ORDER BY
					`BlogPost`.`DateInserted` DESC,
					`BlogPost`.`ID` DESC
				LIMIT 1
			",
			'i',
			array($vendorID)
		);
		
		return $vendorUpdate[0];
	}
	
	public function fetchVendorComments($vendor_alias, $page){
		$stmt_getVendorStats = $this->db->prepare("
			SELECT
				`ID`,
				`Alias`,
				(
					SELECT	COUNT(User_Rating.`ID`)
					FROM	`Transaction_Rating` User_Rating
					WHERE
						User_Rating.`VendorID` = `User`.`ID` AND
						User_Rating.`Content` IS NOT NULL AND
						User_Rating.`Date` >= LEAST(
							NOW() - INTERVAL " . MAX_AGE_VISIBLE_TRANSACTION_COMMENTS_MONTHS . " MONTH,
							IFNULL(
								(
									SELECT
										`Transaction_Rating`.`Date`
									FROM
										`Transaction_Rating`
									WHERE
										`Transaction_Rating`.`VendorID` = `User`.`ID` AND
										`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
									ORDER BY
										`Transaction_Rating`.`Date` DESC
									LIMIT
										" . VENDOR_AVERAGE_RATING_MINIMUM_RATINGS . ", 1
								),
								NOW()
							)
						)
				)
			FROM	`User`
			WHERE	`Alias` = ?
		");
		
		$stmt_getComments = $this->db->prepare("
			SELECT
				User_Rating.`Content`,
				User_Rating.`Rating_Vendor`,
				User_Rating.`Date`,
				`Listing`.`Name`,
				IF(
					`Listing`.`ID` IS NOT NULL AND
					(
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Stealth` = FALSE AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						)
					),
					`Listing`.`ID`,
					FALSE
				) listingID
			FROM
				`Transaction_Rating` User_Rating
			LEFT JOIN	`User`
				ON	User_Rating.`BuyerID` = `User`.`ID`
			LEFT JOIN	`Listing`
				ON	User_Rating.`ListingID` = `Listing`.`ID`
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			WHERE
				User_Rating.`VendorID` = ? AND
				User_Rating.`Content` IS NOT NULL AND
				User_Rating.`Date` >= LEAST(
					NOW() - INTERVAL " . MAX_AGE_VISIBLE_TRANSACTION_COMMENTS_MONTHS . " MONTH,
					IFNULL(
						(
							SELECT
								`Transaction_Rating`.`Date`
							FROM
								`Transaction_Rating`
							WHERE
								`Transaction_Rating`.`VendorID` = User_Rating.`VendorID` AND
								`Transaction_Rating`.`Rating_Vendor` IS NOT NULL
							ORDER BY
								`Transaction_Rating`.`Date` DESC
							LIMIT
								" . VENDOR_AVERAGE_RATING_MINIMUM_RATINGS . ", 1
						),
						NOW()
					)
				)
			ORDER BY
				User_Rating.`Date` DESC
			LIMIT ?, " . REVIEWS_PER_PAGE . "
		");
		
		if( false !== $stmt_getVendorStats && false !== $stmt_getComments ){
			$stmt_getVendorStats->bind_param('s', $vendor_alias);
			$stmt_getVendorStats->execute();
			$stmt_getVendorStats->store_result();
			$stmt_getVendorStats->bind_result(
				$vendor_id,
				$vendor_alias,
				$comment_count
			);
			$stmt_getVendorStats->fetch();
			
			if( $comment_count > 0 ){
				
				if( ceil($comment_count/REVIEWS_PER_PAGE) < $page ){
					$offset = 0;
					$this->User->Notifications->quick('FatalError', 'Invalid Page');
				} else {
					$offset = REVIEWS_PER_PAGE*($page - 1);
				}
				
				$stmt_getComments->bind_param('ii', $vendor_id, $offset);
				$stmt_getComments->execute();
				$stmt_getComments->store_result();
				$stmt_getComments->bind_result(
					$comment_content,
					$comment_rating,
					$comment_date,
					$listing_name,
					$listing_id
				);
				
				$comments = array();
				while( $stmt_getComments->fetch() ){
					$comments[] = array(
						'content'	=> $comment_content,
						'rating'	=> $comment_rating,
						'date'		=> strtolower(date('M jS, Y', strtotime($comment_date))),
						'listing'	=> $listing_name ? $listing_name : FALSE,
						'b36'		=> $listing_id ? NXS::getB36($listing_id) : false
					);
				}
				
				return array(
					$vendor_alias,
					$comment_count,
					$comments
				);
				
			} else
				return false;
		}
	}
	
	public function fetchVendorListings($vendor_alias, $page){
		$stmt_countListings_types = $stmt_getListings_types = 's';
		$stmt_countListings_params = $stmt_getListings_params = array(&$vendor_alias);
		
		if( $allowed_category_id = $this->db->getSiteInfo('ListingCategoryID') ){
			$allowed_category_ids = array_merge(
				array($allowed_category_id),
				$this->getChildrenCategoryIDs($allowed_category_id)
			);
			$stmt_category_ids = array();
			$stmt_category_types = '';
			foreach( $allowed_category_ids as $key => $category_id ){
				$stmt_countListings_types .= 'i';
				$stmt_getListings_types .= 'i';
				
				$stmt_countListings_params[] = &$allowed_category_ids [ $key ];
				$stmt_getListings_params[] = &$allowed_category_ids [ $key ];
			}
		} else
			$allowed_category_ids = false;
		
		$stmt_getListings_types .= 'i';
		$stmt_getListings_params[] = &$offset;
		
		$stmt_countListings_args = array_merge(
			array( $stmt_countListings_types ),
			$stmt_countListings_params
		);
		$stmt_getListings_args = array_merge(
			array( $stmt_getListings_types ),
			$stmt_getListings_params
		);
		
		$stmt_getVendorAlias = $this->db->prepare("
			SELECT	`Alias`
			FROM	`User`
			WHERE	`Alias` = ?
		");
		
		$stmt_countListings = $this->db->prepare("
			SELECT
				COUNT(DISTINCT `Listing`.`ID`)
			FROM
				`Listing`
			INNER JOIN
				`Listing_PaymentMethod` ON
					`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
			INNER JOIN
				`PaymentMethod` ON
					`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
					`PaymentMethod`.`Enabled` = TRUE
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			WHERE
				`Listing`.`VendorID` = ?
			AND	(
					`Listing_Group`.`GroupID` IS NULL OR
					`Listing_Group`.`OutOfStock` = FALSE
				)
			AND	`Listing`.`Inactive` = FALSE
			AND	`Listing`.`Approved` = TRUE
			AND	`Listing`.`Stealth`	= FALSE
			" . ( $allowed_category_ids ? 'AND `Listing`.`CategoryID` IN (' . ( rtrim( str_repeat('?, ', count($allowed_category_ids)), ', ') ) . ')' : false ) . "
		");
		
		$stmt_getListings = $this->db->prepare("
			SELECT DISTINCT
				`Listing`.`ID`,
				`Listing`.`Name`,
				`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					`Image`.`Filename`
				) Image,
				(
					SELECT	AVG(Listing_Rating.`Rating_Vendor`)
					FROM	`Transaction_Rating` Listing_Rating
					WHERE
						Listing_Rating.`ListingID` = `Listing`.`ID`
					AND	Listing_Rating.`Rating_Vendor` IS NOT NULL
				) AverageRating,
				(
					SELECT	COUNT(Listing_Rating.`ID`)
					FROM   `Transaction_Rating` Listing_Rating
					WHERE 
						Listing_Rating.`ListingID` = `Listing`.`ID`
					AND	Listing_Rating.`Rating_Vendor` IS NOT NULL
				)
			FROM
				`Listing`
			INNER JOIN
				`Listing_PaymentMethod` ON
					`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
			INNER JOIN
				`PaymentMethod` ON
					`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
					`PaymentMethod`.`Enabled` = TRUE
			INNER JOIN
				`Currency` ON `Listing`.`CurrencyID` = `Currency`.`ID`
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
			WHERE
				`VendorID` = ?
			AND	`Inactive` = FALSE
			AND	`Approved` = TRUE
			AND	(
					`Listing_Group`.`GroupID` IS NULL OR
					`Listing_Group`.`OutOfStock` = FALSE
				)
			AND	`Stealth`	= FALSE
			" . ( $allowed_category_ids ? 'AND `Listing`.`CategoryID` IN (' . ( rtrim( str_repeat('?, ', count($allowed_category_ids)), ', ') ) . ')' : false ) ."
			ORDER BY
				(
					SELECT COUNT(`Transaction`.`ID`)
					FROM   `Transaction`
					WHERE  `Transaction`.`ListingID` = `Listing`.`ID`
				) DESC
			LIMIT ?, " . LISTINGS_PER_PAGE . "
		");
		
		if( false !== $stmt_getVendorAlias && false !== $stmt_countListings && false !== $stmt_getListings ){
			
			$stmt_getVendorAlias->bind_param('s', $vendor_alias);
			$stmt_getVendorAlias->execute();
			$stmt_getVendorAlias->store_result();
			$stmt_getVendorAlias->bind_result($vendor_alias);
			$stmt_getVendorAlias->fetch();
			
			call_user_func_array(
				array($stmt_countListings, 'bind_param'),
				$stmt_countListings_args
			);
			$stmt_countListings->execute();
			$stmt_countListings->store_result();
			$stmt_countListings->bind_result($listing_count);
			$stmt_countListings->fetch();
			
			if($listing_count > 0) {
				
				if( false !== $stmt_getCategories && false !== $stmt_getListings ){
					
					if( ceil($listing_count/LISTINGS_PER_PAGE) < $page ){
						$offset = 0;
						$this->User->Notifications->quick('FatalError', 'Invalid Page');
					} else {
						$offset = LISTINGS_PER_PAGE*($page - 1);
					}
					
					call_user_func_array(
						array($stmt_getListings, 'bind_param'),
						$stmt_getListings_args
					);
					$stmt_getListings->execute();
					$stmt_getListings->store_result();
					$stmt_getListings->bind_result(
						$listing_id,
						$listing_name,
						$listing_price_eur,
						$listing_image,
						$listing_rating,
						$listing_rating_count
					);
					
					$listings = array();
					while($stmt_getListings->fetch())
						$listings[] = array(
							'id' => $listing_id,
							'name' => $listing_name,
							'price' => NXS::formatPrice($this->User->Currency, $listing_price_eur),
							'price_crypto' => $cryptocurrency->formatPrice($listing_price_eur),
							'image' => NXS::getPictureVariant($listing_image, IMAGE_MEDIUM_SUFFIX),
							'rating' => $listing_rating,
							'rating_count' => $listing_rating_count
						);
					
					return array(
						$vendor_alias,
						$listing_count,
						$listings
					);
					
				}
			
			} else {
				// NO LISTINGS
				return false;
			}
		}
	}
	
	public function findVendorAlias($prefix){
		if( $stmt_findVendorAlias = $this->db->prepare("
			SELECT
				`Alias`
			FROM
				`User`
			WHERE
				`SecretPrefix` = ?
		") ){
			$stmt_findVendorAlias->bind_param('s', $prefix);
			$stmt_findVendorAlias->execute();
			$stmt_findVendorAlias->store_result();
			
			if( $stmt_findVendorAlias->num_rows > 0 ){
				
				$stmt_findVendorAlias->bind_result($vendor_alias);
				$stmt_findVendorAlias->fetch();
				
				return $vendor_alias;
				
			} else {
				return false;
			}	
		}	
	}
	
	public function fetchUserProfileCSS($userAlias){
		if(
			$profileCSS = $this->db->qSelect(
				"
					SELECT
						`ProfileCSS`
					FROM
						`User`
					WHERE
						`Alias` = ?
				",
				's',
				array(
					$userAlias
				)
			)
		)
			return $profileCSS[0]['ProfileCSS'];
		else
			return FALSE;
	}
	
	private function getCategories(&$recursive_categories = false){
		
		if( $stmt_getCategories = $this->db->prepare("
			SELECT
				`ID`,
				`ParentID`
			FROM
				`ListingCategory`
		") ){
			
			$stmt_getCategories->execute();
			$stmt_getCategories->store_result();
			$stmt_getCategories->bind_result($category_id, $parent_id);
			
			$all_categories = array();
			while( $stmt_getCategories->fetch() ){
				$all_categories[ $category_id ] = array(
					'id' => $category_id,
					'parent' => !empty($parent_id) ? $parent_id : 0
				);
			}
			
			$recursive_categories = NXS::makeRecursive($all_categories);
				
			return $all_categories;
			
		}
		
	}
	
	private function getChildren($active, $all, $key = 0){
		$active = array_reverse($active);
		while( isset($active[$key][0]) ){
			$all = $all[ $active[$key][0] ]['children'];
			$key++;
		}
		$all = array('id' => $active[$key-1][0], 'name' => $active[$key-1][1], 'children' => $all);
		return $all;
	}
	
	private function linearArray($arr){
		$values = array($arr['id']);
		
		if ( array_key_exists('children', $arr) ) {
			foreach( $arr['children'] as $child ){
				$values = array_merge($values, $this->linearArray($child));
			}
		}
		return $values;
	}
	
	private function getChildrenCategoryIDs($parent_category_id){
		
		$all_categories = $this->getCategories($all_categories_recursive);
		
		$visible_categories = NXS::reduceCategories(
			$parent_category_id,
			$all_categories_recursive
		);
		
		$visible_categories = $this->linearArray($visible_categories);
		
		return $visible_categories;
		
	}
}
