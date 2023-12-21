<?php

class PagesModel
{
	/**
	 * Constructor, expects a Database connection
	 * @param Database $db The Database object
	 */
	public function __construct(Database $db, $user)
	{
		$this->db = $db;
		$this->User = $user;
	}
	
	public function fetchPageTitles(){
		if( $stmt_getPageTitles = $this->db->prepare("
			SELECT
				`Page`.`ID`,
				`Page`.`Alias`,
				`Page`.`Title`
			FROM
				`Page`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			WHERE
				`Page`.`SiteID` = ? AND
				(
					`Page`.`VendorOnly` = FALSE OR
					thisUser.`Vendor` = TRUE
				) AND
				(
					`Page`.`BuyerOnly` = FALSE OR
					thisUser.`Vendor` = FALSE
				)
			ORDER BY
				`Page`.`Sort`,
				`Page`.`Title`
		") ){
			
			$stmt_getPageTitles->bind_param(
				'ii',
				$this->User->ID,
				$this->db->site_id
			);
			$stmt_getPageTitles->execute();
			$stmt_getPageTitles->store_result();
			$stmt_getPageTitles->bind_result($page_id, $page_alias, $page_title);
			
			$pages = array();
			while( $stmt_getPageTitles->fetch() ){
				$pages[] = array(
					'id' => $page_id,
					'alias' => !empty($page_alias) ? $page_alias : false,
					'title' => $page_title
				);
			}
			return $pages;
			
		}
		
	}
	
	public function fetchPage($page){
		$stmt_getPageMeta = $this->db->prepare("
			SELECT
				`Title`,
				`Model`,
				`View`,
				`Content`
			FROM
				`Page`
			WHERE
				`ID` = ?
			AND	`SiteID` = ?
			AND	CASE 
					WHEN `MemberOnly`
						THEN	? != FALSE
					ELSE
						1 = 1
				END
		");
		
		if( $stmt_getPageMeta !== false ){
			
			if(
				!is_numeric($page) &&
				(
					(
						$page = $this->getPageID($page)
					) &&
					$page == false
				)
			)
				return false;
			
			$stmt_getPageMeta->bind_param('iii', $page, $this->db->site_id, $this->User->ID);
			$stmt_getPageMeta->execute();
			$stmt_getPageMeta->store_result();
			
			if( $stmt_getPageMeta->num_rows == 1 ){
				
				$stmt_getPageMeta->bind_result($page_title, $page_model, $page_view, $page_content);
				$stmt_getPageMeta->fetch();
				
				if( $page_model && method_exists($this, $page_model) )
					return array(
						'title' => $page_title,
						'content' => false,
						'data' => $this->{$page_model}(),
						'view' => $page_view
					);
				elseif( $page_content )
					return array(
						'title' => $page_title,
						'content' => $page_content,
						'data' => false,
						'view' => false
					);
				
			} else
				return false;
			
		}
		
	}
	
	private function getVendors(){
		return $this->db->qSelect(
			"
				SELECT DISTINCT
					Vendor.`ID`,
					Vendor.`Alias`,
					`User_LogoElements`.`VendorID` IS NOT NULL hasLogoElements
				FROM
					`User` Vendor
				LEFT JOIN
					`User_LogoElements` ON
						Vendor.`ID` = `User_LogoElements`.`VendorID`
				WHERE
					Vendor.`Vendor` = TRUE AND
					Vendor.`Stealth` = FALSE AND
					Vendor.`Moderator` = FALSE
				ORDER BY
					Vendor.`Alias` ASC
			"
		);
	}
	
	private function _fetchVendorLogoElements($vendorID){
		if(
			$logoElements = $this->db->qSelect(
				"
					SELECT
						`Element`
					FROM
						`User_LogoElements`
					WHERE
						`VendorID` = ?
					ORDER BY
						`Order` ASC
				",
				'i',
				[$vendorID]
			)
		)
			return array_map(
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
						`User_LogoElements` (
							`VendorID`,
							`Order`,
							`Element`
						)
					SELECT
						Vendor.`ID`,
						IFNULL(
							(
								SELECT
									MAX(`User_LogoElements`.`Order`)
								FROM
									`User_LogoElements`
								WHERE
									`User_LogoElements`.`VendorID` = Vendor.`ID`
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
	
	public function fetchVendors(){
		$firstLetters = [
			'A' => [],
			'B' => [],
			'C' => [],
			'D' => [],
			'E' => [],
			'F' => [],
			'G' => [],
			'H' => [],
			'I' => [],
			'J' => [],
			'K' => [],
			'L' => [],
			'M' => [],
			'N' => [],
			'O' => [],
			'P' => [],
			'Q' => [],
			'R' => [],
			'S' => [],
			'T' => [],
			'U' => [],
			'V' => [],
			'W' => [],
			'X' => [],
			'Y' => [],
			'Z' => []
		];
		
		if ($vendors = $this->getVendors()){
			$vendors = array_map(
				function($vendor){
					$vendor['logoElements'] = $vendor['hasLogoElements']
						? $this->_fetchVendorLogoElements($vendor['ID'])
						: $this->_generateVendorLogoElements($vendor['Alias']);
					
					return $vendor;
				},
				$vendors
			);
			
			foreach($vendors as $vendor){
				$firstLetter = 
					strtoupper(
						substr(
						$vendor['Alias'],
						0,
						1
					)
				);
				
				$firstLetters[$firstLetter][] = $vendor;
			}
			
			return $firstLetters;
		}
		
		return false;
	}
	
	public function fetchVendorApplication(){
		$inviteCodes = $this->db->qSelect(
			"
				SELECT
					`Code` code,
					`Type` type
				FROM
					`InviteCode`
				WHERE
					`ClaimedID` = ?
			",
			'i',
			array($this->User->ID)
		);
		
		$application = $this->db->qSelect(
			"
				SELECT
					`Application` AS application,
					`Policy` AS policy
				FROM
					`VendorApplication`
				WHERE
					`UserID` = ?
			",
			'i',
			array($this->User->ID)
		);
		$application = $application[0];
		
		return array_merge(
			$application,
			array(
				'codes' => $inviteCodes,
				'publicKey' => isset($this->User->Attributes['BTCPublic']) ? $this->User->Attributes['BTCPublic'] : FALSE
			)
		);
		
	}
	
	private function getPageID($page_alias){
		
		if( $stmt_getPageID = $this->db->prepare("
			SELECT
				`ID`
			FROM
				`Page`
			WHERE
				`Alias`		= ?
			AND	`SiteID`	= ?
			LIMIT 1
		") ){
			
			$stmt_getPageID->bind_param('si', $page_alias, $this->db->site_id);
			$stmt_getPageID->execute();
			$stmt_getPageID->store_result();
			
			if( $stmt_getPageID->num_rows == 1 ){
				
				$stmt_getPageID->bind_result($page_id);
				$stmt_getPageID->fetch();
				
				return $page_id;
				
			} else
				return false;
			
		}
		
	}
	
}
