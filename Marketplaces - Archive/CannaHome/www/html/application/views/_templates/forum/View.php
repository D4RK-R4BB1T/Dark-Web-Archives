<?php 

class templateView extends View {
	private $User;
	
	function __construct(Database $db, $user){
		parent::__construct('forum', $db, $user->IsTester);
		
		$this->db = $db;
		$this->User = $user;
		
		$this->Member = ($this->User->ID !== false);
		
		if($this->Member){
			$this->MessageCount = $this->User->Info('MessageCount');
		}
		
		$this->sites = $this->fetchSites();
		$this->isForum = TRUE;
		
		// Site-specific data
		$this->FooterPages = $this->fetchSitePages();
		
		// Custom User CSS
		if( $customCSS = $this->User->Info('UserCSS') )
			$this->inlineStylesheet = $customCSS;
		else
			$this->inlineStylesheet = '';
		
		// Common Infos
		$this->UserAlias		= empty($this->User->Alias) ? false : $this->User->Alias;
		$this->UserVendor		= $this->User->IsVendor;
		$this->UserMod			= $this->User->IsMod;
		$this->UserAdmin		= $this->User->IsAdmin;
		
		// Handle Universal GET Requests
		$this->doActions();		
	}
	
	public function renderFlairs(
		$flairs,
		$userAlias = false,
		$commentID = false
	){
		if ($flairs)
			foreach ($flairs as $posterFlair) {
				if ($posterFlair['isEditable'] && $userAlias){
					if ($this->flairModalIDs === null)
						$this->flairModalIDs = [];
						
					if (isset($this->flairModalIDs[$userAlias]))
						$editFlairModalID = $this->flairModalIDs[$userAlias];
					else {
						$editFlairModalID = 'edit_flair-' . uniqid();
						$this->flairModalIDs[$userAlias] = $editFlairModalID; ?>
				<input id="<?= $editFlairModalID ?>" type="checkbox" hidden>
				<div class="modal">
					<label for="<?= $editFlairModalID ?>"></label>
					<div class="rows-10 formatted">
						<label for="<?= $editFlairModalID ?>" class="close">&times;</label>
						<form method="post" action="<?= URL . 'forum/update_user_flair/' . $userAlias . '/' . ($this->UserMod ? $posterFlair['classID'] . '/' : false); ?>" class="cols-10">
							<input type="hidden" name="edit_flair_return" value="<?= $this->currentPath . ($commentID ? '#comment-' . $commentID : false); ?>">
							<div class="col-9">
								<label class="text">
									<input name="flair" maxlength="<?= USER_CLASS_TEXT_MAX_LENGTH ?>" <?= $posterFlair['hasCustomText'] ? 'value="' . $posterFlair['text'] . '"' : 'placeholder="Add custom flair text"'; ?> type="text">
								</label>
							</div>
							<div class="col-3">
								<button name="csrf" value="<?= $this->getCSRFToken() ?>" type="submit" class="btn arrow-right wide">Submit</button>
							</div>
						</form>
					</div>
				</div>
				<?php
					}
					echo '<label for="' . $editFlairModalID . '"';
				} else
					echo '<span';
				
				echo ' class="flair ' . $posterFlair['color'] . ($posterFlair['Rank'] ? ' rank-' . $posterFlair['Rank'] : false) . '">';
				if ($posterFlair['icon'])
					echo '<i class="' . $posterFlair['icon'] . '"></i>';
				echo '<span>' . $posterFlair['text'] . '</span></';
				
				echo $posterFlair['isEditable']
					? 'label'
					: 'span';
				
				echo '>';
			}
	}
	
	public function renderMemberButton($href, $title, $style = 'btn big color', $label = false, $login_modal_id = 'login-modal'){
		if($this->Member)
			echo '<' . ($label ? 'label' : 'a') . ' class="' . $style . '" ' . ($label ? 'for' : 'href') . '="' . $href . '">' . $title . '</' . ($label ? 'label' : 'a') . '>';
		else
			echo '<label class="' . $style . '" for="' . $login_modal_id . '">' . $title . '</label>';
	}
	
	public function renderRating($rating){
		$full_stars = floor($rating);
		$half_stars = ($rating - 0.5) >= $full_stars ? 1 : 0;
		$empty_stars = 5 - $full_stars - $half_stars;
		
		echo str_repeat('<i class="full"></i>', $full_stars) . str_repeat('<i class="half"></i>', $half_stars) . str_repeat('<i class="empty"></i>', $empty_stars);
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
		if (!$URLPrefix)
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
	
	private function fetchSites(){
		$sites = $this->db->qSelect(
			"
				SELECT
					`Site_Domain`.`Domain`,
					`Site_Domain`.`Available`
				FROM
					`Site`
				INNER JOIN
					`Site_Domain` ON
						`Site_Domain`.`SiteID` = `Site`.`ID`
				WHERE
					`Site`.`ForumID` = ? AND
					`Site_Domain`.`Visible` = TRUE
				ORDER BY
					`Site_Domain`.`Available` ASC
			",
			'i',
			array($this->db->site_id)
		);
		
		return array_map(
			function($array){
				return array_merge(
					$array,
					[
						'color'	=> $array['Available'] ? 'green' : 'red',	
						'URL'	=> 'http://' . $array['Domain'] . '/'
					]
				);
			},
			$sites
		);
	}
	
	private function countUserNotifications(){
		
		$count = 0;
		
		$user_infos = $this->User->Info(
			'PendingTransactionCount',
			'InDisputeTransactionCount',
			'UnwithdrawnFinalizedTransactionCount',
			'TransactionRatingCountChange',
			'InTransitBuyingTransactionCountChange',
			'PendingFeedbackTransactionCount',
			'UnansweredListingQuestionCount',
			'UnsuccessfulBroadcastCount'
		);
		
		foreach( $user_infos as $user_info ){
			if( $user_info !== 0)
				$count++;
		}
		
		// Count Side-wide Notifications
		$count = $count + count( $this->User->Notifications->all['Dashboard'] );
		
		return $count;
	}
	
	private function getCurrencies() {
		
		if ( $stmt_Currencies = $this->db->prepare("
			SELECT
				`ID`,
				`ISO`,
				`Symbol`,
				`1EUR`
			FROM
				`Currency`
		") ) {
			$stmt_Currencies->execute();
			$stmt_Currencies->store_result();
			$stmt_Currencies->bind_result($curr_id, $curr_iso, $curr_symbol, $curr_1eur);
			
			$currencies = array();
			while($stmt_Currencies->fetch()){
				if( $curr_id == $this->User->Attributes['Preferences']['CurrencyID'] ){
					$this->User->Currency = array(
						'ID' => $curr_id,
						'ISO' => $curr_iso,
						'Symbol' => $curr_symbol,
						'XEUR' => $curr_1eur
					);
				} else {
					$currencies[] = array(
						'ID' => $curr_id,
						'ISO' => $curr_iso,
						'Symbol' => $curr_symbol,
						'XEUR' => $curr_1eur
					);
				}
			}
			
			$long_list = array_slice($currencies, 3);
			
			usort($long_list, function ($a, $b) {
			   return strcmp(
					strtolower($a['ISO']),
					strtolower($b['ISO'])
				);
			}); 
			
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
		}
		else{
			if (!empty($_SESSION['do'])){
				$_GET['do'] = $_SESSION['do'];
				unset($_SESSION['do']);
			}
			
			
	
			if(isset($_GET['do'])){
				foreach ($_GET['do'] as $action => $value){
					switch ($action) {
						case 'ChangeUserPrefs':
							$new_prefs = array();
							foreach($value as $pref => $value){
								switch($pref){
									case 'CurrencyID':
										if( is_numeric($value) && $value > 0 && array_key_exists(($value-1), $this->Currencies)){
											$new_prefs[$pref] = $value;
										} else {
											$this->User->Notifications->quick('RequestError', '<strong>Error</strong> That currency is not currently supported');
											return false;
										}
									break;
								}
							}
							if($new_prefs == $this->User->Attributes['Preferences'] || !$this->User->updatePrefs($new_prefs)){
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not change user preferences');
								return;
							}
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
												'UserRating_ID' => $this->User->Info('LastUserRatingID')
											)
										)
									)
								) 
							) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissListingRatingCountChange':
							if( !($this->User->updateAttributes(array('LastSeen' => array('ListingRating_ID' => $this->User->Info('LastListingRatingID')))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissTransactionRatingCountChange':
							if (
								!(
									$this->User->updateAttributes(
										array(
											'LastSeen' => array(
												'TransactionRating_ID' => $this->User->Info('LastTransactionRatingID')
											)
										)
									)
								) 
							) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss rating count change');
							}
						break;
						case 'DismissInTransitTransactionCountChange':
							if( !($this->User->updateAttributes(array('LastSeen' => array('InTransit_Transaction_ID' => $this->User->Info('LastInTransitTransactionID')))) ) ) {
								$this->User->Notifications->quick('RequestError', '<strong>Error</strong> Could not dismiss transaction updates');
							}
						break;
						case 'DismissSubscribedDiscussionCountChange':
							$this->User->dismissSubscribedDiscussions();
						break;
						case 'DismissNewUserNotification':
							unset($_SESSION['new_user']);
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
						case 'ToggleDiscussionStickied':
							if($this->UserMod)
								$this->toggleDiscussionStickied($value);
						break;
						case 'ToggleBlogPostStickied':
							if($this->UserMod)
								$this->toggleBlogPostStickied($value);
						break;
						case 'ToggleDiscussionClosed':
							if($this->UserMod)
								$this->toggleDiscussionClosed($value);
						break;
						case 'ToggleBlogPostClosed':
							if($this->UserMod)
								$this->toggleBlogPostClosed($value);
						break;
					}
				}
				header('Location: '.URL.substr($_SERVER['REQUEST_URI'], 1)); die;
			}
		}
	}
	
	private function toggleDiscussionStickied($discussionID){
		return $this->db->qQuery(
			"
				UPDATE
					`Discussion`
				SET
					`Announce` = (`Announce` = FALSE)
				WHERE
					`ID` = ?
			",
			'i',
			[$discussionID]
		);
	}
	
	private function toggleBlogPostStickied($blogPostID){
		return $this->db->qQuery(
			"
				UPDATE
					`BlogPost`
				SET
					`Stickied` = (`Stickied` = FALSE)
				WHERE
					`ID` = ?
			",
			'i',
			[$blogPostID]
		);
	}
	
	private function toggleDiscussionClosed($discussionID){
		return $this->db->qQuery(
			"
				UPDATE
					`Discussion`
				SET
					`Closed` = (`Closed` = FALSE)
				WHERE
					`ID` = ?
			",
			'i',
			[$discussionID]
		);
	}
	
	private function toggleBlogPostClosed($blogPostID){
		return $this->db->qQuery(
			"
				UPDATE
					`BlogPost`
				SET
					`Closed` = (`Closed` = FALSE)
				WHERE
					`ID` = ?
			",
			'i',
			[$blogPostID]
		);
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
