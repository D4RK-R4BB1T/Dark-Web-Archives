<?php

/**
 * CatalogModel
 */
class CatalogModel {
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	public function fetchShippingDestinations(
		$vendorAlias = false,
		&$shipsToPreference = false
	){
		$preferredShipsTo = $this->User->Attributes['Preferences']['CatalogFilter']['ships_to'] ?: SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $this->User->Attributes['Preferences']['LocaleID'];
		list(
			$destinationType,
			$destinationID
		) = explode(
			SHIPPING_FILTER_DELIMITER,
			$preferredShipsTo,
			2
		);
		
		$locales = [];
		$countries = $this->db->qSelect(
			"
				SELECT DISTINCT
					`Country`.`ID`,
					`Country`.`Name`,
					`Locale`.`ID` localeID,
					`Locale`.`Name` localeName
				FROM
					`Locale_Country`
				INNER JOIN
					`Country` ON
						`Locale_Country`.`CountryID` = `Country`.`ID`
				INNER JOIN
					`Locale` ON
						`Locale_Country`.`LocaleID` = `Locale`.`ID`
				INNER JOIN
					`Listing_Country` ON
						`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`
				INNER JOIN
					`Listing` ON
						`Listing_Country`.`ListingID` = `Listing`.`ID`
				INNER JOIN
					`User` Vendor ON
						`Listing`.`VendorID` = Vendor.`ID`
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
					`Listing`.`Inactive` = FALSE AND
					`Listing`.`Stealth` = FALSE AND
					(
						`Listing_Group`.`GroupID` IS NULL OR
						`Listing_Group`.`OutOfStock` = FALSE
					) AND
					Vendor.`Stealth` = FALSE " . (
						$vendorAlias
							? 'AND Vendor.`Alias` = ?'
							: false
					) . "
			",
			$vendorAlias ? 's' : false,
			$vendorAlias ? [$vendorAlias] : false,
			false,
			true
		);
		
		if($countries)
			foreach ($countries as $country){
				$countryArr = [
					'ID'	=> $country['ID'],
					'Name'	=> $country['Name']
				];
			
				if (isset($locales[ $country['localeID'] ]))
					$locales[ $country['localeID'] ]['countries'][] = $countryArr;
				else
					$locales[ $country['localeID'] ] = [
						'ID'		=> $country['localeID'],
						'Name'		=> $country['localeName'],
						'countries'	=> [
							$countryArr
						]
					];
				
				if(
					(
						$destinationType == SHIPPING_FILTER_PREFIX_LOCALE &&
						$destinationID == $country['localeID']
					) ||
					(
						$destinationType == SHIPPING_FILTER_PREFIX_COUNTRY &&
						$destinationID == $country['ID']
					)
				)
					$shipsToPreference = true;
			}
		
		return $locales;
	}
	
	private function _fetchLocaleIDs(){
		return array_map(
			function($locale){
				return $locale['ID'];
			},
			$this->db->qSelect(
				"
					SELECT
						`ID`
					FROM
						`Locale`
				"
			)
		);
	}
	
	private function _fetchCountryIDs(){
		return array_map(
			function($country){
				return $country['CountryID'];
			},
			$this->db->qSelect(
				"
					SELECT
						`CountryID`
					FROM
						`Locale_Country`
				"
			)
		);
	}
	
	public function applyListingsFilter(){
		if (!empty($_POST)){
			if (isset($_POST['payment_methods'])){
				$this->User->updatePrefs(
					[
						'CatalogFilter' => [
							'cryptocurrencies' =>
								$_POST['payment_methods'] == $_POST['payment_method_options']
									? false
									: $_POST['payment_methods']
						]
					]
				);
			}
			
			if (isset($_POST['shipping_destination'])){
				$shipsFrom = $shipsTo = FALSE;
			
				if ($_POST['shipping_destination'] === "0")
					$shipsTo = $shipsFrom = 0;
				elseif ($_POST['shipping_destination'] === "-1")
					$shipsTo = $shipsFrom = -1;
				elseif(
					list(
						$destinationType,
						$destinationID
					) = explode(
						SHIPPING_FILTER_DELIMITER,
						$_POST['shipping_destination'],
						2
					)
				){
					switch ($destinationType) {
						case SHIPPING_FILTER_PREFIX_LOCALE:
							$shippingIDs = $this->_fetchLocaleIDs();
						break;
						case SHIPPING_FILTER_PREFIX_COUNTRY:
							$shippingIDs = $this->_fetchCountryIDs();
						break;
						default:
							return false;
					}
		
					if(
						in_array(
							$destinationID,
							$shippingIDs
						)
					){
						$shipsTo = $destinationType . SHIPPING_FILTER_DELIMITER . $destinationID;
				
						if (
							isset($_POST['same_origin']) &&
							!isset($_POST['international_vendors'])
						)
							$shipsFrom = $shipsTo;
					}
				}
		
				if ($shipsTo !== FALSE){
					$catalogFilter = [
						'ships_to' => $shipsTo
					];
				
					$catalogFilter['ships_from'] =
						$shipsFrom !== FALSE
							? $shipsFrom
							: -1;
				
					$this->User->updatePrefs(
						['CatalogFilter' => $catalogFilter]
					);
				}
			}
		}
		
		return true;
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
				`Name`,
				`Top`
			FROM
				`Country`
			WHERE
				`Enabled` IS TRUE
			ORDER BY
				`Top` DESC,
				`Name`
		");
		
		if( false != $stmt_Continent && false != $stmt_Country ){
			$stmt_Continent->execute();
			$stmt_Continent->store_result();
			$stmt_Continent->bind_result($continent_id, $continent_name);
			$continents = array();
			while($stmt_Continent->fetch() ){
				$continents[$continent_id] = array(
					'Name' => $continent_name
				);
			}
			
			$stmt_Country->execute();
			$stmt_Country->store_result();
			$stmt_Country->bind_result($country_id, $country_continent_id, $country_name, $country_top);
			while($stmt_Country->fetch() ){
				$continents[$country_continent_id]['Countries'][] = $countries[$country_id] = array(
					'ID'	=> $country_id,
					'Name'	=> $country_name,
					'Top'	=> $country_top
				);
			}
			
			return array($continents, $countries);
		}
	}
	
	public function fetchListingCategories(
		$activeCategory = FALSE,
		$vendorAlias = FALSE,
		$query = FALSE,
		$shipsTo = false,
		$cryptocurrencies = false
	){
		$shipsTo = $shipsTo ?: $this->User->Attributes['Preferences']['CatalogFilter']['ships_to'];
		
		$finalCategories = $allCategories = $allCategories_alias = [];
		
		$stmt_getCategories_types = '';
		$stmt_getCategories_params = $stmt_getCategories_joins = [];
		
		// Shipping Filters
		if (
			$shipsTo > -1 && // is not anywhere
			(
				$shipsTo = $shipsTo ?: SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $this->User->Attributes['Preferences']['LocaleID']
			) &&
			list(
				$shippingType,
				$shippingID
			) = explode(
				SHIPPING_FILTER_DELIMITER,
				$shipsTo,
				2
			)
		){
			$shipsFromSame =
				!$vendorAlias &&
				$this->User->Attributes['Preferences']['CatalogFilter']['ships_from'] !== -1;
			switch($shippingType){
				case SHIPPING_FILTER_PREFIX_LOCALE:
					$stmt_getCategories_joins[] = "
						INNER JOIN `Locale_Country` ON
							`LocaleID` = ?
						INNER JOIN `Listing_Country` ON
							`Listing`.`ID` = `Listing_Country`.`ListingID` AND
							`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID` " . (
								$shipsFromSame
									? ' AND `Listing`.`CountryID` = `Listing_Country`.`CountryID`' 
									: false
							) . "
					";
					$stmt_getCategories_types .= 'i';
					$stmt_getCategories_params[] = $shippingID;
				break;
				case SHIPPING_FILTER_PREFIX_COUNTRY:
					$stmt_getCategories_joins[] = "
						INNER JOIN `Listing_Country` ON
							`Listing`.`ID` = `Listing_Country`.`ListingID` AND
							`Listing_Country`.`CountryID` = ?" . (
								$shipsFromSame
									? ' AND `Listing`.`CountryID` = `Listing_Country`.`CountryID`' 
									: false
							) . "
					";
					$stmt_getCategories_types .= 'i';
					$stmt_getCategories_params[] = $shippingID;
				break;
			}
		}
		
		// Cryptocurrency Filters
		if (
			$cryptocurrencies !== null &&
			$cryptocurrencies = $this->User->Attributes['Preferences']['CatalogFilter']['cryptocurrencies']
		){
			$stmt_getCategories_joins[] = "
				INNER JOIN
					`Listing_PaymentMethod` ON
						`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
				INNER JOIN
					`PaymentMethod` ON
						`PaymentMethod`.`Enabled` = TRUE AND
						`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
						`PaymentMethod`.`CryptocurrencyID` IN (" . rtrim(str_repeat('?, ', count($cryptocurrencies)), ', ') . ")
			";
			$stmt_getCategories_types .= str_repeat('i', count($cryptocurrencies));
			$stmt_getCategories_params = array_merge(
				$stmt_getCategories_params,
				$cryptocurrencies
			);
		} else {
			$stmt_getCategories_joins[] = "
				INNER JOIN
					`Listing_PaymentMethod` ON
						`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
				INNER JOIN
					`PaymentMethod` ON
						`PaymentMethod`.`Enabled` = TRUE AND
						`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID`
			";
		}
			
		$stmt_getCategories_query = "
			SELECT
				`ListingCategory`.`ID`,
				`ParentID`,
				`Name`,
				`Alias`,
				(
					SELECT
						COUNT(DISTINCT `Listing`.`ID`)
					FROM
						`Listing`
					INNER JOIN	`User`
						ON	`Listing`.`VendorID` = `User`.`ID`
					" . (
						$stmt_getCategories_joins
							? implode(PHP_EOL, $stmt_getCategories_joins)
							: FALSE
					) . "
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`Listing`.`Inactive` = FALSE
					AND	(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						)
					AND	`Listing`.`Stealth` = FALSE
					AND	`Listing`.`CategoryID` = `ListingCategory`.`ID`
					AND	`User`.`Stealth` = FALSE
					" . 
					(
						$vendorAlias
							? "AND	`User`.`Alias` = ?"
							: FALSE
					) . (
						$query
							? "AND MATCH(`Listing`.`Name`) AGAINST (? IN BOOLEAN MODE)"
							: FALSE
					) . "
				) AS ListingCount
			FROM
				`ListingCategory`
			ORDER BY
				`Sort` ASC, `Name` ASC
		";
		
		list(
			$allowedListingCategoryID,
			$defaultListingCategoryID
		) = $this->db->getSiteInfo(
			'ListingCategoryID',
			'DefaultListingCategoryID'
		);
		
		if ($vendorAlias){
			$stmt_getCategories_types	.= 's';
			$stmt_getCategories_params[]	= $vendorAlias;
		}
		
		if ($query){
			$stmt_getCategories_types	.= 's';
			$stmt_getCategories_params[]	= $query;
		}
		
		array_walk(
			$this->db->qSelect(
				$stmt_getCategories_query,
				$stmt_getCategories_types,
				$stmt_getCategories_params,
				false,
				true
			),
			function($array) use (&$allCategories){
				$allCategories[ $array['ID'] ] = array_merge(
					$array,
					array(
						'ParentID' => $array['ParentID'] ? $array['ParentID'] : 0
					)
				);
				if( !empty($alias) )
					$allCategories_alias[ $array['Alias'] ] = $array;
			}
		);
		
		$categories = NXS::makeRecursive($allCategories);
		
		if( $allowedListingCategoryID ){
			$categories = $fullCategories = NXS::reduceCategories($allowedListingCategoryID, $categories, $allCategories);
			$categories = $categories['Children'];
		}
		
		$categories = NXS::tallyCount(
			$categories,
			'ListingCount', 
			'Children'
		);
		
		if (
			!$activeCategory &&
			(
				$allowedListingCategoryID ||
				$defaultListingCategoryID
			)
		)
			$activeCategory = $defaultListingCategoryID ? $defaultListingCategoryID : $allowedListingCategoryID;
		elseif (
			$activeCategory &&
			$activeCategory !== 'index' &&
			(
				is_numeric($activeCategory) ||
				isset($allCategories_alias[$activeCategory])
			) 
		){
			$activeCategory = is_numeric($activeCategory) ? $activeCategory : $allCategories_alias[$activeCategory];
			$activeCategoryName = $allCategories[ $activeCategory ]['Name'];
			$activeCategory = $allowedListingCategoryID ? NXS::filterCategory($activeCategory, $categories) : $activeCategory;
		} else
			$activeCategory = $activeCategoryName = FALSE;
		
		if ( $activeCategory ){
			
			if( isset($allCategories[$activeCategory]) ){
				
				if( $activeCategory !== $allowedListingCategoryID)
					$activeCategories = array(
						$activeCategory => array(
							$activeCategory,
							$allCategories[$activeCategory]['Name'],
							$allCategories[$activeCategory]['Alias']
						)
					);
	
				$arr = $allCategories[$activeCategory];
				while(
					array_key_exists('ParentID', $arr) &&
					$arr['ParentID'] != 0 &&
					(
						!$allowedListingCategoryID ||
						(
							$arr['ID'] != $allowedListingCategoryID &&
							$arr['ParentID'] != $allowedListingCategoryID
						)
					)
				){
					$activeCategories[ $arr['ParentID'] ] = array(
						$arr['ParentID'],
						$allCategories[$arr['ParentID']]['Name'],
						$allCategories[$arr['ParentID']]['Alias']
					);
					$arr = $allCategories[$arr['ParentID']];
				}
				
				if ( $allowedListingCategoryID == $activeCategory )
					$visibleCategories = $allCategories;
				else
					$visibleCategories = NXS::reduceCategories(
						$activeCategory,
						$categories
					);
				
				$visibleCategories = NXS::linearArray($visibleCategories);
			}
		} else
			$activeCategories = $visibleCategories = false;
		
		return array(
			$categories,
			$activeCategories,
			$visibleCategories,
			$activeCategoryName
		);
	}
	
	public function fetchListingSubCategories($category){
		if ($category){
			$stmt_getListingSubCategories_where = '
				WHERE
					(
						`ParentID` = ? OR
						`ParentID` = (
							SELECT	`ParentID`
							FROM	`ListingCategory`
							WHERE	`ID` = ?
						)
					)
				AND	(
						SELECT	COUNT(`Listing`.`ID`)
						FROM	`Listing`
						WHERE	`Listing`.`CategoryID` = `ListingCategory`.`ID`
					) > 0
			';
			$stmt_getListingSubCategories_types = 'ii';
		} else {
			$stmt_getListingSubCategories_where = '
				WHERE
					`ParentID` IS NULL
				AND	(
						(
							SELECT	COUNT(`Listing`.`ID`)
							FROM	`Listing`
							WHERE	`Listing`.`CategoryID` = `ListingCategory`.`ID`
						) > 0 OR
						(
							SELECT	COUNT(`Listing`.`ID`)
							FROM	`Listing`
							WHERE	`Listing`.`CategoryID` IN (
								SELECT	`ListingCategory`.`ID`
								FROM	`ListingCategory`
								WHERE	`ListingCategory`.`ParentID` = `ListingCategory`.`ID`
							)
						) > 0
					)
			';
			$stmt_getListingSubCategories_types = false;
		}
		
		$stmt_getListingSubCategories_query = "
			SELECT
				`ID`,
				`Name`,
				`Alias`
			FROM
				`ListingCategory`
			" . $stmt_getListingSubCategories_where . "
			ORDER BY
				`Sort` ASC
		";
		
		if ($stmt_getListingSubCategories = $this->db->prepare($stmt_getListingSubCategories_query)){
			
			if( $stmt_getListingSubCategories_types )
				$stmt_getListingSubCategories->bind_param(
					$stmt_getListingSubCategories_types,
					$category,
					$category
				);
			$stmt_getListingSubCategories->execute();
			$stmt_getListingSubCategories->store_result();
			$stmt_getListingSubCategories->bind_result(
				$id,
				$name,
				$alias
			);
			
			$subCategories = array();
			while( $stmt_getListingSubCategories->fetch() )
				$subCategories[] = array(
					'ID'	=>	$id,
					'Name'	=>	$name,
					'Alias'	=>	$alias
				);
			
			//print_r($subCategories); die;
			
			return $subCategories;
			
		}
		
	}
	
	private function _fetchListingGroupMembers(
		$listingID,
		$cryptocurrency = FALSE,
		$pricePerUnit = FALSE,
		$isTrivialGroup = TRUE
	){
		if(
			$listings = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Listing`.`ID`,
						`Listing`.`Name`,
						`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
						`Listing`.`Quantity`,
						`Unit`.`Name_Singular` UnitName_Singular,
						`Unit`.`Name_Plural` UnitName_Plural,
						`Unit`.`Abbreviation` UnitAbbreviation,
						`Listing`.`ID` = ? isActiveListing,
						`Listing_Group`.`Label`,
						(
							`Listing`.`Quantity_Minimum` > 1 AND
							`Listing`.`Quantity` = 1
						) isPerUnit,
						IF(
							`Listing`.`Quantity_Minimum` > 1,
							`Listing`.`Quantity_Minimum`*`Listing`.`Quantity`,
							NULL
						) minimumQuantity
						" . (
							$pricePerUnit
								? ',
									`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) unitPrice,
									baseUnit.`Abbreviation` baseUnit
								'
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
					" . (
						$pricePerUnit
							? '
								INNER JOIN
									`Unit` baseUnit ON
										baseUnit.`Base` = TRUE AND
										baseUnit.`DimensionID` = `Unit`.`DimensionID`
							'
							: false
					) . "
					WHERE
						`Listing`.`Inactive` = FALSE AND
						`Listing_Group`.`OutOfStock` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE AND
						`Listing_Group`.`GroupID` = (
							SELECT
								`Listing_Group`.`GroupID`
							FROM
								`Listing_Group`
							WHERE
								`Listing_Group`.`ListingID` = ?
						)
					ORDER BY
						`Listing`.`Quantity` * `Unit`.`ConversionFactor` * `Listing`.`Quantity_Minimum` ASC,
						`Listing`.`Quantity_Minimum` > 1 ASC
						#,EUR_Price ASC
				",
				'ii',
				[
					$listingID,
					$listingID
				]
			)
		){
			$cryptocurrency = $cryptocurrency ?: $this->User->Cryptocurrency;
			$useAbbreviatedUnits =
				$isTrivialGroup &&
				count($listings) > LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY_NON_ABBREVIATED_UNITS &&
				count($listings) <= LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY_SINGLE_ROW;
			
			return array_map(
				function($listing) use ($cryptocurrency, $useAbbreviatedUnits){
					switch(TRUE){
						case $useAbbreviatedUnits:
							$unit = $listing['UnitAbbreviation'];
						break;
						case $listing['Quantity'] <= 1:
							$unit = $listing['UnitName_Singular'];
						break;
						default:
							$unit = $listing['UnitName_Plural'];
					}
					
					return array_merge(
						$listing,
						[
							'B36'		=> NXS::getB36($listing['ID']),
							'price'		=>
								$pricePerUnit
									?	NXS::formatPrice($this->User->Currency, $listing['unitPrice']) .
										' / ' . $listing['baseUnit']
									:	NXS::formatPrice($this->User->Currency, $listing['EUR_Price']) .
										(
											$listing['isPerUnit']
												? ' / ' . $listing['UnitAbbreviation']
												: false
										),
							'price_crypto'	=> $cryptocurrency->formatPrice($listing['EUR_Price']),
							'quantity'	=>
								$listing['Label']
									?: (
										$listing['minimumQuantity']
											? 	NXS::formatDecimal(
													$listing['minimumQuantity'],
													2,
													DEFAULT_DECIMAL_SEPARATOR,
													DEFAULT_THOUSANDS_SEPARATOR,
													2
												) .
												' ' .
												$unit .
												'+'
											:	NXS::formatDecimal(
													$listing['Quantity'],
													2,
													DEFAULT_DECIMAL_SEPARATOR,
													DEFAULT_THOUSANDS_SEPARATOR,
													2
												) .
												' ' .
												$unit
									)
						]
					);
				},
				$listings
			);
		}
		
		return FALSE;	
	}
	
	public function fetchLocaleOptions(){
		return	$this->db->qSelect(
				"
					SELECT DISTINCT
						`Locale`.`ID`,
						`Locale`.`Name`,
						`Locale`.`Flag`,
						(
							SELECT
								COUNT(DISTINCT Vendor.`ID`)
							FROM
								`User` Vendor
							INNER JOIN
								`Listing` ON
									Vendor.`ID` = `Listing`.`VendorID`
							INNER JOIN
								`Listing_PaymentMethod` ON
									`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
							INNER JOIN
								`PaymentMethod` ON
									`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
									`PaymentMethod`.`Enabled` = TRUE
							INNER JOIN
								`Locale_Country` ON
									`Locale_Country`.`CountryID` = `Listing`.`CountryID`
							LEFT JOIN
								`Listing_Group` ON
									`Listing`.`ID` = `Listing_Group`.`ListingID`
							WHERE
								`Locale_Country`.`LocaleID` = `Locale`.`ID` AND
								Vendor.`Vendor` = TRUE AND
								Vendor.`Stealth` = FALSE AND
								`Listing`.`Inactive` = FALSE AND
								`Listing`.`Stealth` = FALSE AND
								(
									`Listing_Group`.`GroupID` IS NULL OR
									`Listing_Group`.`OutOfStock` = FALSE
								)
						) activeVendorCount
					FROM
						`Locale`
					INNER JOIN
						`Locale_Country` ON
							`Locale`.`ID` = `Locale_Country`.`LocaleID`
					INNER JOIN
						`Listing` ON
							`Locale_Country`.`CountryID` = `Listing`.`CountryID`
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
						`Listing`.`Inactive` = FALSE AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						)
				",
				false,
				false,
				false,
				true
			);
	}
	
	public function fetchListings(
		$categories,
		$page,
		$sort,
		$query = FALSE,
		$vendorAlias = FALSE,
		$shipsTo = false,
		$cryptocurrencies = false,
		$onlyFeaturedListings = null,
		$listingsPerPage = LISTINGS_PER_PAGE,
		$excludeListingIDs = false
	){
		$prioritizeFeatured = true;
		if (
			!$categories &&
			!$vendorAlias
		){
			$prioritizeFeatured = false;
			
			if (
				$page == 1 &&
				$onlyFeaturedListings === null
			){
				$argumentList = [
					$categories,
					$page,
					$sort,
					$query,
					$vendorAlias,
					$shipsTo,
					$cryptocurrencies
				];
			
				list(
					$featuredlistingCount,
					$trueFeaturedListingCount,
					$featuredListings
				) = call_user_func_array(
					[
						$this,
						'fetchListings'
					],
					array_merge(
						$argumentList,
						[
							true,
							FEATURED_LISTINGS_PER_SEARCH_PAGE
						]
					)
				);
				
				$featuredListings = $featuredListings ?: [];
				
				list(
					$listingCount,
					$trueListingCount,
					$listings
				) = call_user_func_array(
					[
						$this,
						'fetchListings'
					],
					array_merge(
						$argumentList,
						[
							false,
							$listingsPerPage,
							array_map(
								function($listing){
									return $listing['ID'];
								},
								$featuredListings
							)
						]
					)
				);
				
				return [
					$listingCount,
					$trueListingCount,
					array_merge(
						$featuredListings,
						$listings
					)
				];
			}
		}
		
		$shipsTo = $shipsTo ?: $this->User->Attributes['Preferences']['CatalogFilter']['ships_to'];
		
		$whereClause = [
			"Vendor.`Stealth` = FALSE"
		];
		
		$types_fetch = $types_count = '';
		$vars_fetch = $vars_count = $joins = [];
		
		if ($query){
			$types_fetch .= 's';
			$vars_fetch[] = $query;
		}
		
		// Shipping Filters
		if (
			$shipsTo > -1 && // is not anywhere
			(
				$shipsTo = $shipsTo ?: SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $this->User->Attributes['Preferences']['LocaleID']
			) &&
			list(
				$shippingType,
				$shippingID
			) = explode(
				SHIPPING_FILTER_DELIMITER,
				$shipsTo,
				2
			)
		){
			$shipsFromSame =
				!$vendorAlias &&
				$this->User->Attributes['Preferences']['CatalogFilter']['ships_from'] !== -1;
			switch($shippingType){
				case SHIPPING_FILTER_PREFIX_LOCALE:
					$joins[] = "
						INNER JOIN `Locale_Country` ON
							`LocaleID` = ?
						INNER JOIN `Listing_Country` ON
							`Listing`.`ID` = `Listing_Country`.`ListingID` AND
							`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID` " . (
								$shipsFromSame
									? ' AND `Listing`.`CountryID` = `Listing_Country`.`CountryID`' 
									: false
							) . "
					";
					
					$types_count .= 'i';
					$vars_count[] = $shippingID;
			
					$types_fetch .= 'i';
					$vars_fetch[] = $shippingID;
				break;
				case SHIPPING_FILTER_PREFIX_COUNTRY:
					$joins[] = "
						INNER JOIN `Listing_Country` ON
							`Listing`.`ID` = `Listing_Country`.`ListingID` AND
							`Listing_Country`.`CountryID` = ?" . (
								$shipsFromSame
									? ' AND `Listing`.`CountryID` = `Listing_Country`.`CountryID`' 
									: false
							) . "
					";
					
					$types_count .= 'i';
					$vars_count[] = $shippingID;
			
					$types_fetch .= 'i';
					$vars_fetch[] = $shippingID;
				break;
			}
		}
		
		// Cryptocurrency Filters
		if (
			$cryptocurrencies !== null &&
			$cryptocurrencies = $this->User->Attributes['Preferences']['CatalogFilter']['cryptocurrencies'])
		{
			$joins[] = "
				INNER JOIN
					`Listing_PaymentMethod` ON
						`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
				INNER JOIN
					`PaymentMethod` ON
						`PaymentMethod`.`Enabled` = TRUE AND
						`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID` AND
						`PaymentMethod`.`CryptocurrencyID` IN (" . rtrim(str_repeat('?, ', count($cryptocurrencies)), ', ') . ")
			";
			
			$types_count .= str_repeat('i', count($cryptocurrencies));
			$vars_count = array_merge(
				$vars_count,
				$cryptocurrencies
			);
			
			$types_fetch .= str_repeat('i', count($cryptocurrencies));
			$vars_fetch = array_merge(
				$vars_fetch,
				$cryptocurrencies
			);
		} else
			$joins[] = "
				INNER JOIN
					`Listing_PaymentMethod` ON
						`Listing`.`ID` = `Listing_PaymentMethod`.`ListingID`
				INNER JOIN
					`PaymentMethod` ON
						`PaymentMethod`.`Enabled` = TRUE AND
						`Listing_PaymentMethod`.`PaymentMethodID` = `PaymentMethod`.`ID`
			";
		
		$sortPerUnit = false;
		$groupRepresentativeID = $groupRepresentativeID_mostPopular = "
			(
				SELECT
					`Listing`.`ID`
				FROM
					`Listing`
				INNER JOIN
					`Listing_Group` lG ON
						`Listing`.`ID` = lG.`ListingID`
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
					lG.`OutOfStock` = FALSE AND
					`Listing`.`Approved` = TRUE AND
					`Listing`.`Stealth` = FALSE
				ORDER BY
					`Listing`.`Featured` DESC,
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
		";
		
		if ($onlyFeaturedListings)
			$sort = 'random';
		
		switch($sort){
			case 'random':
				$sort = 'RAND()';
				break;
			case 'price_asc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					EUR_Price ASC";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`Price`/`Currency`.`1EUR` ASC
						LIMIT 1
					)
				";
			break;
			case 'price_desc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					EUR_Price DESC";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`Price`/`Currency`.`1EUR` DESC
						LIMIT 1
					)
				";
			break;
			case 'price_m_asc':
				$sortPerUnit = true;
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DimensionID` = " . DIMENSION_ID_MASS . " DESC,
					unitPrice ASC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`DimensionID` = " . DIMENSION_ID_MASS . " DESC,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) ASC
						LIMIT 1
					)
				";
			break;
			case 'price_m_desc':
				$sortPerUnit = true;
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DimensionID` = " . DIMENSION_ID_MASS . " DESC,
					unitPrice DESC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`DimensionID` = " . DIMENSION_ID_MASS . " DESC,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) DESC
						LIMIT 1
					)
				";
			break;
			case 'price_v_asc':
				$sortPerUnit = true;
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DimensionID` = " . DIMENSION_ID_VOLUME . " DESC,
					unitPrice ASC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`DimensionID` = " . DIMENSION_ID_VOLUME . " DESC,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) ASC
						LIMIT 1
					)
				";
			break;
			case 'price_v_desc':
				$sortPerUnit = true;
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DimensionID` = " . DIMENSION_ID_VOLUME . " DESC,
					unitPrice ASC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`DimensionID` = " . DIMENSION_ID_VOLUME . " DESC,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) DESC
						LIMIT 1
					)
				";
			break;
			case 'name_asc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					Name ASC";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`Name` ASC
						LIMIT 1
					)
				";
			break;
			case 'name_desc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					Name DESC";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`Name` DESC
						LIMIT 1
					)
				";
			break;
			case 'id_asc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DateAdded` ASC,
					ID ASC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`DateAdded` ASC,
							`Listing`.`ID` ASC
						LIMIT 1
					)
				";
			break;
			case 'id_desc':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					`DateAdded` DESC,
					ID DESC
				";
				$groupRepresentativeID = "
					(
						SELECT
							`Listing`.`ID`
						FROM
							`Listing`
						INNER JOIN
							`Listing_Group` lG ON
								`Listing`.`ID` = lG.`ListingID`
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
							lG.`OutOfStock` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE
						ORDER BY
							`Featured` DESC,
							`Listing`.`Name` DESC
						LIMIT 1
					)
				";
			break;
			case 'popular':
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					canonicalRatingCount DESC
				";
			break;
			// case 'rating':
			default:
				// Modified Bayesian Estimate
				$modifiedBayesianEstimate = "
					(
						canonicalAverageRating *
						canonicalRatingCount +
						" . LISTING_SORT_RATING_BAYESIAN_ESTIMATE_WEIGHTING_FACTOR . " *
						(
							SELECT	AVG(Listing_Rating.`Rating_Vendor`)
							FROM  	`Transaction_Rating` Listing_Rating
							WHERE	Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
						)
					) /
					(
						canonicalRatingCount + " . LISTING_SORT_RATING_BAYESIAN_ESTIMATE_WEIGHTING_FACTOR . "
					) +
					" . LISTING_SORT_RATING_BAYESIAN_ESTIMATE_CORRECTION_COEFFICIENT . " *
					LOG10(canonicalRatingCount)
				";
				
				$sort = "
					" . ($prioritizeFeatured ? "`Featured` DESC," : false) . "
					canonicalAverageRating IS NOT NULL DESC,
					canonicalAverageRating > 4.5 DESC,
					canonicalRatingCount > 10 DESC,
					" . $modifiedBayesianEstimate . " DESC
				";
		}
		
		// Filter Categories
		if ($categories){
			$questionMarks = rtrim( str_repeat('?, ', count($categories) ), ', ');
			$whereClause[] = '
				`Listing`.`CategoryID` IN (' . $questionMarks . ')
			';
			
			$types_count .= str_repeat('i', count($categories));
			$vars_count = array_merge(
				$vars_count,
				$categories
			);
			
			$types_fetch .= str_repeat('i', count($categories));
			$vars_fetch = array_merge(
				$vars_fetch,
				$categories
			);
		}
		
		if ($vendorAlias){
			$whereClause[] = "Vendor.`Alias` = ?";
			
			$types_count .= 's';
			$vars_count[] = $vendorAlias;
			
			$types_fetch .= 's';
			$vars_fetch[] = $vendorAlias;
		}
		
		if ($onlyFeaturedListings)
			$whereClause[] = "`Listing`.`Featured` != FALSE";
		
		if ($query){
			$whereClause[] = "MATCH(`Listing`.`Name`) AGAINST (? IN BOOLEAN MODE)";
			
			$types_count .= 's';
			$vars_count[] = $query;
			
			$types_fetch .= 's';
			$vars_fetch[] = $query;
			
			$sort = 'Relevance DESC';
			$groupRepresentativeID = $groupRepresentativeID_mostPopular;
		}
		
		$types_fetch .= $types_fetch;
		$vars_fetch = array_merge(
			$vars_fetch,
			$vars_fetch
		);
		
		$types_count .= $types_count . $types_count;
		$vars_count = array_merge(
			$vars_count,
			$vars_count,
			$vars_count
		);
		
		$listingCount_query = "
			SELECT
				( # All Listings
					SELECT
						COUNT(DISTINCT `Listing`.`ID`)
					FROM
						`Listing`
					" . (
						$joins
							? implode(' ', $joins)
							: false
					) . "
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`Listing`.`Inactive` = FALSE AND
						(
							`Listing_Group`.`GroupID` IS NULL OR
							`Listing_Group`.`OutOfStock` = FALSE
						) AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE
						" . (
							$whereClause
								? "AND " .
								implode(
									$whereClause,
									' AND '
								)
								: FALSE
						) . "
				) trueListingCount,
				( # Singular Listings
					SELECT
						COUNT(DISTINCT `Listing`.`ID`)
					FROM
						`Listing`
					" . (
						$joins
							? implode(' ', $joins)
							: false
					) . "
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					LEFT JOIN
						`Listing_Group` ON
							`Listing`.`ID` = `Listing_Group`.`ListingID`
					WHERE
						`Listing`.`Inactive` = FALSE AND
						`Listing`.`Approved` = TRUE AND
						`Listing`.`Stealth` = FALSE AND
						`Listing_Group`.`GroupID` IS NULL
						" . (
							$whereClause
								? "AND " .
								implode(
									$whereClause,
									' AND '
								)
								: FALSE
						) . "
				) +
				( # Groups
					SELECT
						COUNT(DISTINCT `Listing_Group`.`GroupID`)
					FROM
						`Listing_Group`
					INNER JOIN
						`Listing` ON
							`Listing_Group`.`ListingID` = `Listing`.`ID`
					" . (
						$joins
							? implode(' ', $joins)
							: false
					) . "
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					WHERE
						`Listing`.`ID` = " . $groupRepresentativeID . " 
						" . (
							$whereClause
								? "AND " .
								implode(
									$whereClause,
									' AND '
								)
								: FALSE
						) . "
				) listingCount
		";
		
		$listingCounts = $this->db->qSelect(
			$listingCount_query,
			$types_count,
			$vars_count,
			false,
			!$query
		);
		
		$listingCount = $listingCounts[0]['listingCount'];
		$trueListingCount = $listingCounts[0]['trueListingCount'];
		
		$offset = NXS::getOffset(
			$listingCount,
			$listingsPerPage,
			$page
		);
		
		$types_fetch .= 'i';
		$vars_fetch[] = $offset;
		
		$listings_query = "
			SELECT
				*
			FROM
				(
					( # Singular Listings
						SELECT
							DISTINCT `Listing`.`ID` ID,
							`Listing`.`DateAdded`,
							`Listing`.`Name` Name,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Listing`.`Price` / `Currency`.`1EUR` / `Listing`.`Quantity`,
								`Listing`.`Price` / `Currency`.`1EUR`
							) EUR_Price,
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
									COUNT(Listing_Rating.`ID`)
								FROM
									`Transaction_Rating` Listing_Rating
								WHERE
									Listing_Rating.`ListingID` = `Listing`.`ID` AND
									Listing_Rating.`Rating_Vendor` IS NOT NULL AND
									Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
							) canonicalRatingCount,
							(
								SELECT
									COUNT(Listing_Rating.`Rating_Vendor`)
								FROM
									`Transaction_Rating` Listing_Rating
								WHERE 
									Listing_Rating.`ListingID` = `Listing`.`ID` AND
									Listing_Rating.`Content` IS NOT NULL
							) commentCount,
							(
								SELECT
									AVG(Listing_Rating.`Rating_Vendor`)
								FROM
									`Transaction_Rating` Listing_Rating
								WHERE
									Listing_Rating.`ListingID` = `Listing`.`ID` AND
									Listing_Rating.`Rating_Vendor` IS NOT NULL
							) averageRating,
							(
								SELECT
									AVG(Listing_Rating.`Rating_Vendor`)
								FROM
									`Transaction_Rating` Listing_Rating
								WHERE
									Listing_Rating.`ListingID` = `Listing`.`ID` AND
									Listing_Rating.`Rating_Vendor` IS NOT NULL AND
									Listing_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
							) canonicalAverageRating,
							Vendor.`Alias` vendorAlias,
							`Listing`.`Excerpt`,
							FALSE isFavorite, #`User_Listing`.`UserID` IS NOT NULL isFavorite,
							FALSE GroupID,
							`Listing`.`Featured` " . (
								$query
									? ', MATCH(`Listing`.`Name`) AGAINST (?) Relevance'
									: FALSE
							) . ",
							FALSE isTrivialGroup,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Unit`.`Abbreviation`,
								FALSE
							) perUnit,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Listing`.`Quantity_Minimum` * `Listing`.`Quantity`,
								NULL
							) minimumQuantity,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) unitPrice,
							`Unit`.`DimensionID`,
							baseUnit.`Abbreviation` baseUnit,
							`Country`.`Name` originCountry
						FROM
							`Listing`
						" . (
							$joins
								? implode(' ', $joins)
								: false
						) . "
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`User` Vendor ON
								`Listing`.`VendorID` = Vendor.`ID`
						INNER JOIN
							`Country` ON
								`Listing`.`CountryID` = `Country`.`ID`
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
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
						INNER JOIN
							`Unit` baseUnit ON
								baseUnit.`Base` = TRUE AND
								baseUnit.`DimensionID` = `Unit`.`DimensionID`
						WHERE
							`Listing`.`Inactive` = FALSE AND
							`Listing`.`Approved` = TRUE AND
							`Listing`.`Stealth` = FALSE AND
							`Listing_Group`.`GroupID` IS NULL
							" . (
								$whereClause
									? "AND " .
									implode(
										$whereClause,
										' AND '
									)
									: FALSE
							) . "
					) UNION ALL
					( # Listing Groups
						SELECT DISTINCT
							`Listing`.`ID` ID,
							`Listing`.`DateAdded`,
							`Listing`.`Name` Name,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Listing`.`Price` / `Currency`.`1EUR` / `Listing`.`Quantity`,
								`Listing`.`Price` / `Currency`.`1EUR`
							) EUR_Price,
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
									COUNT(DISTINCT Group_Rating.`ID`)
								FROM
									`Transaction_Rating` Group_Rating
								INNER JOIN
									`Listing_Group` lG ON
										Group_Rating.`ListingID` = lG.`ListingID`
								WHERE
									lG.`GroupID` = `Listing_Group`.`GroupID` AND
									Group_Rating.`Rating_Vendor` IS NOT NULL AND
									Group_Rating.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
							) canonicalRatingCount,
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
									Group_Rating.`Content` IS NOT NULL
							) commentCount,
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
							) canonicalAverageRating,
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
									Group_Rating.`Rating_Vendor` IS NOT NULL
							) averageRating,
							Vendor.`Alias` vendorAlias,
							`Listing`.`Excerpt`,
							FALSE isFavorite, # `User_Listing`.`UserID` IS NOT NULL isFavorite,
							`Listing_Group`.`GroupID`,
							`Listing`.`Featured` " . (
								$query
									? ', MATCH(`Listing`.`Name`) AGAINST (?) Relevance'
									: FALSE
							) . ",
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
									lG.`OutOfStock` = FALSE AND
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Stealth` = FALSE
							) <= " . LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY . " AND
							(
								SELECT
									COUNT(DISTINCT IFNULL(lG.`Label`, CONCAT(`Listing`.`Quantity`, '-', `Listing`.`Quantity_Minimum`, '-', `Listing`.`UnitID`)))
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
									lG.`OutOfStock` = FALSE AND
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Stealth` = FALSE
							) = (
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
									lG.`OutOfStock` = FALSE AND
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Stealth` = FALSE
							) isTrivialGroup,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Unit`.`Abbreviation`,
								FALSE
							) perUnit,
							IF(
								`Listing`.`Quantity_Minimum` > 1,
								`Listing`.`Quantity_Minimum` * `Listing`.`Quantity`,
								NULL
							) minimumQuantity,
							`Listing`.`Price` / `Currency`.`1EUR` / (`Listing`.`Quantity` * `Unit`.`ConversionFactor`) unitPrice,
							`Unit`.`DimensionID`,
							baseUnit.`Abbreviation` baseUnit,
							`Country`.`Name` originCountry
						FROM
							`Listing_Group`
						INNER JOIN
							`Listing` ON
								`Listing_Group`.`ListingID` = `Listing`.`ID`
						" . (
							$joins
								? implode(' ', $joins)
								: false
						) . "
						INNER JOIN
							`Currency` ON
								`Listing`.`CurrencyID` = `Currency`.`ID`
						INNER JOIN
							`User` Vendor ON
								`Listing`.`VendorID` = Vendor.`ID`
						INNER JOIN
							`Country` ON
								`Listing`.`CountryID` = `Country`.`ID`
						LEFT JOIN
							`Listing_Image` ON
								`Listing_Image`.`ListingID` = `Listing`.`ID` AND
								`Listing_Image`.`Primary` = TRUE
						LEFT JOIN
							`Image` ON
								`Listing_Image`.`ImageID` = `Image`.`ID`
						INNER JOIN
							`Unit` ON
								`Listing`.`UnitID` = `Unit`.`ID`
						INNER JOIN
							`Unit` baseUnit ON
								baseUnit.`Base` = TRUE AND
								baseUnit.`DimensionID` = `Unit`.`DimensionID`
						WHERE
							`Listing`.`ID` = " . $groupRepresentativeID . " 
							" . (
								$whereClause
									? "AND " .
									implode(
										$whereClause,
										' AND '
									)
									: FALSE
							) . "
					)
				) a
			ORDER BY
				".$sort.",
				ID DESC
			LIMIT
				?,
				" . $listingsPerPage . "
		";
		
		if(
			$listings = $this->db->qSelect(
				$listings_query,
				$types_fetch,
				$vars_fetch,
				false,
				(!$query)
			)
		){
			$cryptocurrency = $this->User->Cryptocurrency;
			
			$listingCryptocurrencies = [
				$cryptocurrency->ID => $cryptocurrency
			];
			
			array_walk(
				$listings,
				function(&$listing) use ($cryptocurrency, $sortPerUnit, &$listingCryptocurrencies){
					if ($listing['GroupID'])
						$listing['options'] = $this->_fetchListingGroupMembers(
							$listing['ID'],
							$cryptocurrency,
							$sortPerUnit,
							$listing['isTrivialGroup']
						);
					
					$paymentMethods = $this->User->getListingPaymentMethods(
						$listing['ID'],
						$allCryptocurrencies,
						$activeCryptocurrencies,
						$activePaymentMethodIDs
					);
					
					if (
						in_array(
							$cryptocurrency->ID,
							$activePaymentMethodIDs
						)
					)
						$listingCryptocurrency = $cryptocurrency;
					elseif (
						$alternativePaymentMethodIDs = array_filter(
							$activePaymentMethodIDs,
							function($activePaymentMethodID) use ($listingCryptocurrencies){
								return array_key_exists(
									$activePaymentMethodID,
									$listingCryptocurrencies
								);
							}
						)
					){
						$alternativePaymentMethodID = array_shift($alternativePaymentMethodIDs);
						$listingCryptocurrency = $listingCryptocurrencies[$alternativePaymentMethodID];
					} else {
						$alternativePaymentMethodID = array_shift($activePaymentMethodIDs);
						$listingCryptocurrency = $listingCryptocurrencies[$alternativePaymentMethodID] = $this->User->getCryptocurrency($alternativePaymentMethodID);
					}
					
					if ($sortPerUnit){
						$price =
							NXS::formatPrice($this->User->Currency, $listing['unitPrice']) .
							' / ' . $listing['baseUnit'];
						$priceCrypto = '';
					} else {
						$price =
							NXS::formatPrice($this->User->Currency, $listing['EUR_Price']) . 
							(
								$listing['perUnit']
									? ' / ' . $listing['perUnit']
									: false
							);
						$priceCrypto =
							$listing['minimumQuantity']
								? NXS::formatDecimal($listing['minimumQuantity']) . ' ' . $listing['perUnit'] . ' min'
								: $listingCryptocurrency->formatPrice($listing['EUR_Price']);
					}
					
					if ($listing['exceededMaximumVisibleRatings'] = $listing['ratingCount'] > MAX_VISIBLE_INDIVIDUAL_RATINGS)
						$listing['ratingCount'] = floor($listing['ratingCount'] / MAX_VISIBLE_INDIVIDUAL_RATINGS) * MAX_VISIBLE_INDIVIDUAL_RATINGS;
					
					$listing = array_merge(
						$listing,
						[
							'B36'			=> NXS::getB36($listing['ID']),
							'price'			=> $price,
							'price_crypto'		=> $priceCrypto,
							'Image'			=> $listing['Image'] ? NXS::getPictureVariant($listing['Image'], IMAGE_MEDIUM_SUFFIX) : false,
							'cryptocurrencies'	=> $activeCryptocurrencies,
							'paymentMethods'	=> !$this->User->IsVendor ? $paymentMethods : false
						]
					);
				}
			);
		}
		
		if ($excludeListingIDs)
			$listings = array_filter(
				$listings,
				function ($listing) use ($excludeListingIDs){
					return	!in_array(
							$listing['ID'],
							$excludeListingIDs
						);
				}
			);
		
		return [
			$listingCount,
			$trueListingCount,
			$listings
		];
	}
	
	public function fetchFAQCategories(){
		
		$stmt_getFAQCategories = $this->db->prepare("
			SELECT
				`FAQCategory`.`ID`,
				`FAQCategory`.`Name`,
				`FAQCategory`.`Alias`,
				`FAQCategory`.`Icon`,
				(
					SELECT
						COUNT(`FAQ`.`ID`)
					FROM
						`FAQ`
					WHERE
						`FAQ`.`CategoryID` = `FAQCategory`.`ID`
				)
			FROM
				`FAQCategory`
			WHERE
				`FAQCategory`.`SiteID` = ?
			ORDER BY
				`FAQCategory`.`Sort`
		");
		
		$stmt_getFAQTitles = $this->db->prepare("
			SELECT
				`FAQ`.`ID`,
				`FAQ`.`CategoryID`,
				`FAQ`.`Title`
			FROM
				`FAQ`
			INNER JOIN	`FAQCategory`
				ON	`FAQ`.`CategoryID` = `FAQCategory`.`ID`
			WHERE
				`FAQCategory`.`SiteID` = ?
		");
		
		if( false !== $stmt_getFAQCategories && false !== $stmt_getFAQTitles ){
			
			$stmt_getFAQCategories->bind_param('i', $this->db->site_id);
			$stmt_getFAQCategories->execute();
			$stmt_getFAQCategories->store_result();
			$stmt_getFAQCategories->bind_result($category_id, $category_name, $category_alias , $category_icon, $category_faq_count);
			
			$stmt_getFAQTitles->bind_param('i', $this->db->site_id);
			$stmt_getFAQTitles->execute();
			$stmt_getFAQTitles->store_result();
			$stmt_getFAQTitles->bind_result($faq_id, $faq_category_id, $faq_title);
			
			$faqs = array();
			while( $stmt_getFAQTitles->fetch() ){
				
				$faqs[$faq_category_id][] = array(
					'id' => $faq_id,
					'title' => $faq_title
				);
				
			}
			
			$faq_categories = array();
			while( $stmt_getFAQCategories->fetch() ){
				
				$faq_categories[$category_id] = array(
					'id' => $category_id,
					'name' => $category_name,
					'alias' => !empty($category_alias) ? $category_alias : false,
					'icon' => !empty($category_icon) ? $category_icon : false,
					'faq_count' => $category_faq_count,
					'faqs' => $faqs[$category_id]
				);
				
			}
			
			return array($faq_categories, $stmt_getFAQTitles->num_rows);
			
		} else {
			die(); //$this->db->error);
		}
		
	}
	
	public function fetchFAQs($category_id, $query=false){
		$stmt_fetchFAQCategory_types = $stmt_fetchFAQs_types = '';
		$stmt_fetchFAQCategory_types_params = $stmt_fetchFAQs_params = array();
		
		$stmt_fetchFAQs_types = 'ii';
		$stmt_fetchFAQs_params[] = &$this->User->ID;
		$stmt_fetchFAQs_params[] = &$this->User->ID;
		
		if ( !$category_id ){
			$stmt_fetchFAQCategory_where = '`FAQCategory`.`ID` = (
				SELECT	MIN(FAQCat.`ID`)
				FROM	`FAQCategory` FAQCat
				WHERE	FAQCat.`SiteID` = `FAQCategory`.`SiteID`
			)';
			$stmt_fetchFAQs_where = '`CategoryID` = (
				SELECT	MIN(FAQCat.`ID`)
				FROM	`FAQCategory` FAQCat
				WHERE	FAQCat.`SiteID` = `FAQCategory`.`SiteID`
			)';
		} elseif( is_numeric($category_id) ){
			$stmt_fetchFAQCategory_where = 'FAQCategory.`ID` = ?';
			$stmt_fetchFAQCategory_types .= 'i';
			$stmt_fetchFAQCategory_params[] = &$category_id;
			
			
			$stmt_fetchFAQs_where = '`CategoryID` = ?';
			$stmt_fetchFAQs_types .= 'i';
			$stmt_fetchFAQs_params[] = &$category_id;
		} else {
			$stmt_fetchFAQCategory_where = '`Alias` = ?';
			$stmt_fetchFAQCategory_types .= 's';
			$stmt_fetchFAQCategory_params[] = &$category_id;
			
			$stmt_fetchFAQs_where = '`FAQCategory`.`Alias` = ?';
			$stmt_fetchFAQs_types .= 's';
			$stmt_fetchFAQs_params[] = &$category_id;
		}	
		
		$stmt_fetchFAQCategory = $this->db->prepare("
			SELECT
				`Name`,
				`Description`
			FROM
				`FAQCategory`
			WHERE
				" . $stmt_fetchFAQCategory_where . "
			AND	`SiteID` = ?
			LIMIT 1
		");
		
		$stmt_fetchFAQs = $this->db->prepare("
			SELECT
				`FAQ`.`ID`,
				`FAQ`.`CategoryID`,
				`FAQ`.`Title`,
				`FAQ`.`Content`,
				(
					SELECT
						COUNT(*)
					FROM
						`FAQ_Vote`
					WHERE
						`FAQID` = `FAQ`.`ID`
					AND	`RaterID` = ?
				),
				(
					SELECT
						`Vote`
					FROM
						`FAQ_Vote`
					WHERE
						`FAQID` = `FAQ`.`ID`
					AND	`RaterID` = ?
				)
			FROM
				`FAQ`
			INNER JOIN
				`FAQCategory` ON `FAQ`.`CategoryID` = `FAQCategory`.`ID`
			WHERE
				" . $stmt_fetchFAQs_where . "
			AND	`FAQCategory`.`SiteID` = ?
		");
		
		$stmt_fetchFAQCategory_types .= 'i';
		$stmt_fetchFAQCategory_params[] = &$this->db->site_id;
		
		$stmt_fetchFAQs_types .= 'i';
		$stmt_fetchFAQs_params[] = &$this->db->site_id;
		
		if( false !== $stmt_fetchFAQs && false !== $stmt_fetchFAQCategory ){
			
			$stmt_fetchFAQCategory_args = array_merge(
				array( $stmt_fetchFAQCategory_types ),
				$stmt_fetchFAQCategory_params
			);
			call_user_func_array(
				array($stmt_fetchFAQCategory, 'bind_param'),
				$stmt_fetchFAQCategory_args
			);
			
			$stmt_fetchFAQCategory->execute();
			$stmt_fetchFAQCategory->store_result();
			if( $stmt_fetchFAQCategory->num_rows == 1 ){
				
				$stmt_fetchFAQCategory->bind_result($category_name, $category_description);
				$stmt_fetchFAQCategory->fetch();
				
				$stmt_fetchFAQs_args = array_merge(
					array( $stmt_fetchFAQs_types ),
					$stmt_fetchFAQs_params
				);
				call_user_func_array(
					array($stmt_fetchFAQs, 'bind_param'),
					$stmt_fetchFAQs_args
				);
				//$stmt_fetchFAQs->bind_param('ii' . $stmt_types . 'i', $this->User->ID, $this->User->ID, $category_id, $this->db->site_id);
				$stmt_fetchFAQs->execute();
				$stmt_fetchFAQs->store_result();
				
				$stmt_fetchFAQs->bind_result($faq_id, $faq_category_id, $faq_title, $faq_content, $faq_vote_count, $faq_vote);
					
				$faqs = array();
				while( $stmt_fetchFAQs->fetch() ){
					$faqs[] = array(
						'id' => $faq_id,
						'category_id' => $faq_category_id,
						'title' => $faq_title,
						'content' => preg_replace_callback('~\[constant="(.*?)"\]~', function($matches){
							return constant($matches[1] != strip_tags($matches[1]) ? $matches[1] : '<p>' . $matches[1] . '</p>');
						}, $faq_content),
						'vote' => ($faq_vote == 1 || $faq_vote_count == 0)
					);
				}
				
				return array(
					'category' => array(
						'name' => $category_name,
						'description' => $category_description
					),
					'faqs' => $faqs
				);
			} else {
				return false;
			}
			
		} else {
			die(); //$this->db->error);
		}
		
	}
	
	public function searchFAQs(){
		
		$stmt_fetchFAQs = $this->db->prepare("
			SELECT
				`FAQ`.`ID`,
				`FAQ`.`CategoryID`,
				`FAQ`.`Title`,
				`FAQ`.`Content`,
				(
					SELECT
						COUNT(*)
					FROM
						`FAQ_Vote`
					WHERE
						`FAQID` = `FAQ`.`ID`
					AND	`RaterID` = ?
				),
				(
					SELECT
						`Vote`
					FROM
						`FAQ_Vote`
					WHERE
						`FAQID` = `FAQ`.`ID`
					AND	`RaterID` = ?
				)
			FROM
				`FAQ`
			INNER JOIN
				`FAQCategory` ON `FAQ`.`CategoryID` = `FAQCategory`.`ID`
			WHERE
				MATCH(`Title`) AGAINST (?)
			AND	`FAQCategory`.`SiteID` = ?
		");
		
		if( false !== $stmt_fetchFAQs ){
			
			$stmt_fetchFAQs->bind_param('iisi', $this->User->ID, $this->User->ID, $_GET['q'], $this->db->site_id);
			$stmt_fetchFAQs->execute();
			$stmt_fetchFAQs->store_result();
			
			$stmt_fetchFAQs->bind_result($faq_id, $faq_category_id, $faq_title, $faq_content, $faq_vote_count, $faq_vote);
				
			$faqs = array();
			while( $stmt_fetchFAQs->fetch() ){
				$faqs[] = array(
					'id' => $faq_id,
					'category_id' => $faq_category_id,
					'title' => $faq_title,
					'content' => preg_replace_callback('~\[constant="(.*?)"\]~', function($matches){
						return constant($matches[1]);
					}, $faq_content),
					'vote' => ($faq_vote == 1 || $faq_vote_count == 0)
				);
			}
			
			return array(
				'category' => false,
				'faqs' => $faqs
			);
			
		} else {
			die(); //$this->db->error);
		}
		
	}
	
	private function getConstant($matches){
		return constant($matches[1]);
	}
	
	public function voteFAQ($faq_id){
		
		if( $stmt_InsertVote = $this->db->prepare("
			INSERT INTO
				`FAQ_Vote` (`FAQID`, `RaterID`, `Vote`)
			VALUES
				(?, ?, ?)
			ON DUPLICATE KEY UPDATE `Vote` = ?
		") ){
			
			switch($_POST['faq_vote']){
				case '1':
					$vote = '1';
				break;
				case '0':
					$vote = '0';
				break;
				default:
					return false;		
			}
			
			$stmt_InsertVote->bind_param('iiii', $faq_id, $this->User->ID, $vote, $vote);
			if( $stmt_InsertVote->execute() ){
				
				if( $stmt_get_category_id = $this->db->prepare("
					SELECT
						`FAQCategory`.`ID`,
						`FAQCategory`.`Alias`
					FROM
						`FAQ`
					INNER JOIN
						`FAQCategory` ON `FAQ`.`CategoryID` = `FAQCategory`.`ID`
					WHERE
						`FAQ`.`ID` = ?
					LIMIT 1
				") ){
					
					$stmt_get_category_id->bind_param('i', $faq_id);
					$stmt_get_category_id->execute();
					$stmt_get_category_id->store_result();
					$stmt_get_category_id->bind_result($category_id, $category_alias);
					$stmt_get_category_id->fetch();
					
					return empty($category_alias) ? $category_alias : $category_id;
					
				}
				
			}
			
		}
		
	}
	
	public function fetchPage($page){
		if( is_numeric($page) ){
			$where = '`ID` = ?';
			$stmt_type = 'i';
		} else {
			$where = '`Alias` = ?';
			$stmt_type = 's';
		}
		
		if( $stmt_fetchPage = $this->db->prepare("
			SELECT
				`Title`,
				`Content`
			FROM
				`Page`
			WHERE
				".$where."
			AND	`SiteID` = ?
			LIMIT 1
		") ){
			$stmt_fetchPage->bind_param($stmt_type . 'i', $page, $this->db->site_id);
			$stmt_fetchPage->execute();
			$stmt_fetchPage->store_result();
			
			if( $stmt_fetchPage->num_rows == 1 ){
			
				$stmt_fetchPage->bind_result($page_title, $page_content);
				$stmt_fetchPage->fetch();
				
				return array(
					'title' => $page_title,
					'content' => $page_content
				);
				
			} else {
				return false;	
			}
		}
	}
	
	public function fetchFrontpageCategories(){
		$frontpageCategories = $this->db->qSelect(
			"
				SELECT
					`Name` as name,
					`Alias` as alias,
					`FrontImageURL` as image
				FROM
					`ListingCategory`
				INNER JOIN	`Site_ListingCategory`
					ON	`ListingCategory`.`ID` = `Site_ListingCategory`.`CategoryID`
					AND	`Site_ListingCategory`.`SiteID` = ?
					AND	`Site_ListingCategory`.`Front` = TRUE
				ORDER BY
					`ListingCategory`.`Sort` ASC
			",
			'i',
			array($this->db->site_id)
		);
		
		return $frontpageCategories;	
	}
	
	public function fetchFrontpageListings(){
		$m = $this->db->m;
		$mKey = 'frontpageListings-' . $this->User->Attributes['Preferences']['LocaleID'];
		
		if ($frontpageListings = $m->get($mKey))
			return $frontpageListings;
		
		$cryptocurrency = $this->User->Cryptocurrency;
		
		$IDs = [];
		$parseListings = function($listing) use ($cryptocurrency, &$IDs){
			$IDs[] = $listing['ID'];
			return array_merge(
				$listing,
				[
					'B36'		=> NXS::getB36($listing['ID']),
					'price'		=> NXS::formatPrice($this->User->Currency, $listing['EUR_Price']),
					'price_crypto'	=> $cryptocurrency->formatPrice($listing['EUR_Price']),
					'Image'		=> NXS::getPictureVariant($listing['Image'], IMAGE_THUMBNAIL_SUFFIX)
				]
			);
		};
		
		$recentlyAddedListings = array_map(
			$parseListings,
			$this->db->qSelect(
				"
					SELECT
						*
					FROM
						(
							( # Singular Listings
								SELECT
									DISTINCT `Listing`.`ID` ID,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									`Listing`.`DateAdded`,
									Vendor.`Alias` vendorAlias
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
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								INNER JOIN
									`Locale_Country` ON
										`Locale_Country`.`LocaleID` = ? AND
										`Locale_Country`.`CountryID` = `Listing`.`CountryID`
								INNER JOIN
									`Listing_Country` ON
										`Listing_Country`.`ListingID` = `Listing`.`ID` AND
										`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`
								INNER JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								INNER JOIN
									`Image` ON
										`Listing_Image`.`ImageID` = `Image`.`ID`
								LEFT JOIN
									`Listing_Group` ON
										`Listing`.`ID` = `Listing_Group`.`ListingID`
								WHERE
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Approved` = TRUE AND
									`Listing`.`Stealth` = FALSE AND
									`Listing_Group`.`GroupID` IS NULL AND
									Vendor.`Stealth` = FALSE
							) UNION ALL
							( # Listing Groups
								SELECT
									`Listing`.`ID` ID,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									`Listing`.`DateAdded`,
									Vendor.`Alias` vendorAlias
								FROM
									`Listing_Group`
								INNER JOIN
									`Listing` ON
										`Listing_Group`.`ListingID` = `Listing`.`ID`
								INNER JOIN
									`Currency` ON
										`Listing`.`CurrencyID` = `Currency`.`ID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
								INNER JOIN
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								INNER JOIN
									`Locale_Country` ON
										`Locale_Country`.`LocaleID` = ? AND
										`Locale_Country`.`CountryID` = `Listing`.`CountryID`
								INNER JOIN
									`Listing_Country` ON
										`Listing_Country`.`ListingID` = `Listing`.`ID` AND
										`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`
								INNER JOIN
									`Listing_Image` ON
										`Listing_Image`.`ListingID` = `Listing`.`ID` AND
										`Listing_Image`.`Primary` = TRUE
								INNER JOIN
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
										INNER JOIN
											`User` Vendor ON
												`PaymentMethod`.`UserID` = Vendor.`ID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											lG.`OutOfStock` = FALSE AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											Vendor.`Stealth` = FALSE
										ORDER BY
											`Listing`.`DateAdded` DESC
										LIMIT 1
									)
							)
						) a
					ORDER BY
						`DateAdded` DESC
					LIMIT
						" . FRONTPAGE_LISTINGS_COUNT . "
				",
				'iiii',
				[
					$this->db->site_id,
					$this->User->Attributes['Preferences']['LocaleID'],
					$this->db->site_id,
					$this->User->Attributes['Preferences']['LocaleID']
				]
			)
		);

		if(
			$recentlyActiveListings = $this->db->qSelect(
				"
					SELECT
						*
					FROM
						(
							( # Singular Listings
								SELECT
									DISTINCT `Listing`.`ID` ID,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									`Listing`.`DateAdded`,
									Vendor.`Alias` vendorAlias,
									(
										SELECT
											COUNT(DISTINCT `Transaction`.`ID`)
										FROM
											`Transaction`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										WHERE
											`Transaction`.`ListingID` = `Listing`.`ID` AND
											`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "' AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) CanonicalOrderCount
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
									`Site_ListingCategory` ON
										`Listing`.`CategoryID` = `Site_ListingCategory`.`CategoryID` AND
										`Site_ListingCategory`.`SiteID` = ?
								INNER JOIN
									`Locale_Country` ON
										`Locale_Country`.`LocaleID` = ? AND
										`Locale_Country`.`CountryID` = `Listing`.`CountryID`
								INNER JOIN
									`Listing_Country` ON
										`Listing_Country`.`ListingID` = `Listing`.`ID` AND
										`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`
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
								WHERE
									`Listing`.`Inactive` = FALSE AND
									`Listing`.`Approved` = TRUE AND
									`Listing`.`Stealth` = FALSE AND
									`Listing_Group`.`GroupID` IS NULL AND
									Vendor.`Stealth` = FALSE
							) UNION ALL
							( # Listing Groups
								SELECT
									`Listing`.`ID` ID,
									`Listing`.`Name` Name,
									`Listing`.`Price`/`Currency`.`1EUR` EUR_Price,
									CONCAT(
										'/" . UPLOADS_PATH . "',
										`Image`.`Filename`
									) Image,
									`Listing`.`DateAdded`,
									Vendor.`Alias` vendorAlias,
									(
										SELECT
											COUNT(DISTINCT `Transaction`.`ID`)
										FROM
											`Transaction`
										INNER JOIN
											`Transaction_Event` ON
												`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
										INNER JOIN
											`Listing` L2 ON
												`Transaction`.`ListingID` = L2.`ID`
										INNER JOIN
											`Listing_Group` LG2 ON
												L2.`ID` = LG2.`ListingID`
										WHERE
											LG2.`GroupID` = `Listing_Group`.`GroupID` AND
											`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "' AND
											`Transaction_Event`.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
									) CanonicalOrderCount
								FROM
									`Listing_Group`
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
								INNER JOIN
									`Locale_Country` ON
										`Locale_Country`.`LocaleID` = ? AND
										`Locale_Country`.`CountryID` = `Listing`.`CountryID`
								INNER JOIN
									`Listing_Country` ON
										`Listing_Country`.`ListingID` = `Listing`.`ID` AND
										`Locale_Country`.`CountryID` = `Listing_Country`.`CountryID`
								INNER JOIN
									`User` Vendor ON
										`Listing`.`VendorID` = Vendor.`ID`
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
										INNER JOIN
											`User` Vendor ON
												`PaymentMethod`.`UserID` = Vendor.`ID`
										WHERE
											lG.`GroupID` = `Listing_Group`.`GroupID` AND
											lG.`OutOfStock` = FALSE AND
											`Listing`.`Inactive` = FALSE AND
											`Listing`.`Approved` = TRUE AND
											`Listing`.`Stealth` = FALSE AND
											Vendor.`Stealth` = FALSE
										ORDER BY
											(
												SELECT
													COUNT(DISTINCT `Transaction`.`ID`)
												FROM
													`Transaction`
												INNER JOIN
													`Transaction_Event` ON
														`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
												WHERE
													`Transaction`.`ListingID` = `Listing`.`ID` AND
													`Transaction_Event`.`Event` = '" . TRANSACTION_EVENTS_FLAG_PAID . "' AND
													`Transaction_Event`.`Date` > NOW() - INTERVAL " . DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE . " DAY
											) DESC
										LIMIT 1
									)
							)
						) a
					ORDER BY
						CanonicalOrderCount DESC
					LIMIT
						" . FRONTPAGE_LISTINGS_COUNT . "
				",
				'iiii',
				[
					$this->db->site_id,
					$this->User->Attributes['Preferences']['LocaleID'],
					$this->db->site_id,
					$this->User->Attributes['Preferences']['LocaleID']
				]
			)
		)
			$recentlyActiveListings = array_map(
				$parseListings,
				$recentlyActiveListings
			);
		
		$frontpageListings = [
			'bestsellers' => $recentlyActiveListings,
			'new' => $recentlyAddedListings
		];
		$m->set($mKey, $frontpageListings, MEMCACHED_FRONTPAGE_LISTINGS_EXPIRATION);
		
		return $frontpageListings;
	}
	
	public function fetchAllVendors(){
		return $this->db->qSelect(
			"
				SELECT DISTINCT
					`User`.`Alias`
				FROM
					`User`
				WHERE
					`User`.`Vendor` = TRUE AND
					`User`.`Stealth` = FALSE
			"
		);
	}
	
	public function getCategoryID($alias){
		if (
			$categoryIDs = $this->db->qSelect(
				"
					SELECT	`ID`
					FROM	`ListingCategory`
					WHERE	`Alias` = ?
				",
				's',
				[$alias],
				false,
				true
			)
		)
			return $categoryIDs[0]['ID'];
	}
	
	public function getUserAlias($alias){
		return $this->db->qSelect(
			"
				SELECT	`Alias`
				FROM	`User`
				WHERE	`Alias` = ?
			",
			's',
			array($alias)
		)[0]['Alias'];
	}
	
	private function getChildren($active, $all, $key = 0){
		$active = array_reverse($active);
		while( isset($active[$key][0]) ){
			$all = $all[ $active[$key][0] ]['Children'];
			$key++;
		}
		$all = array(
			'ID' => $active[$key-1][0],
			'Name' => $active[$key-1][1],
			'Children' => $all
		);
		return $all;
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
			
			$allCategories = array();
			while( $stmt_getCategories->fetch() ){
				$allCategories[ $category_id ] = array(
					'ID' => $category_id,
					'ParentID' => !empty($parent_id) ? $parent_id : 0
				);
			}
			
			$recursive_categories = NXS::makeRecursive($allCategories);
				
			return $allCategories;	
		}
	}
	
	private function getChildrenCategoryIDs($parent_category_id){
		$allCategories = $this->getCategories($allCategories_recursive);
		
		$visibleCategories = NXS::reduceCategories(
			$parent_category_id,
			$allCategories_recursive
		);
		
		$visibleCategories = NXS::linearArray($visibleCategories);
		
		return $visibleCategories;
	}
	
	private function getCategoryParentID($category_id, &$parent_alias = false){
		$stmt_getParentID = $this->db->prepare("
			SELECT
				`ID`,
				`Alias`
			FROM
				`ListingCategory`
			WHERE
				`ID` = (
					SELECT	`ParentID`
					FROM	`ListingCategory`
					WHERE	`ID` = ?
				)
		");
		
		$stmt_getParentID->bind_param('i', $category_id);
		$stmt_getParentID->execute();
		$stmt_getParentID->store_result();
		$stmt_getParentID->bind_result($parent_id, $parent_alias);
		$stmt_getParentID->fetch();
		
		return $parent_id;
	}
}
