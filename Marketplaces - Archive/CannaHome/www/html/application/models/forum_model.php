<?php

/**
 * Forum Model
 */
class ForumModel
{
	public function __construct(Database $db, $user){
		$this->db = $db;
		$this->User = $user;
	}
	
	public function markAllPostsRead(){
		return	$this->db->qQuery(
				"
					UPDATE
						`Blog_Subscription`
					SET
						`SeenPostID` = IFNULL(
							(
								SELECT
									`ID`
								FROM
									`BlogPost`
								WHERE
									`BlogID` = `Blog_Subscription`.`BlogID`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							),
							(
								SELECT
									`ID`
								FROM
									`BlogPost`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							)
						)
					WHERE
						`SubscriberID` = ?
				",
				'i',
				[$this->User->ID]
			) +
			$this->db->qQuery(
				"
					UPDATE
						`BlogPost_Subscription`
					SET
						`SeenCommentID` = IFNULL(
							(
								SELECT
									`ID`
								FROM
									`BlogPostComment`
								WHERE
									`BlogPostID` = `BlogPost_Subscription`.`BlogPostID`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							),
							(
								SELECT
									`ID`
								FROM
									`BlogPostComment`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							)
						)
					WHERE
						`SubscriberID` = ?
				",
				'i',
				[$this->User->ID]
			) +
			$this->db->qQuery(
				"
					UPDATE
						`Discussion_Subscription`
					SET
						`SeenCommentID` = IFNULL(
							(
								SELECT
									`ID`
								FROM
									`Discussion_Comment`
								WHERE
									`DiscussionID` = `Discussion_Subscription`.`DiscussionID`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							),
							(
								SELECT
									`ID`
								FROM
									`Discussion_Comment`
								ORDER BY
									`DateInserted` DESC,
									`ID` DESC
								LIMIT 1
							)
						)
					WHERE
						`SubscriberID` = ?
				",
				'i',
				[$this->User->ID]
			) +
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Notification_User` (
							`NotificationID`,
							`UserID`
						)
					SELECT
						`ID`,
						?
					FROM
						`Notification`
					WHERE
						`DiscussionID` IS NOT NULL OR
						`BlogPostID` IS NOT NULL
				",
				'i',
				[$this->User->ID]
			);
	}
	
	public function fetchDiscussionCategories(){
		$stmt_getDiscussionCategories = $this->db->prepare("
			SELECT
				`DiscussionCategory`.`ID`,
				`DiscussionCategory`.`Name`,
				`DiscussionCategory`.`Alias`,
				(
					SELECT
						COUNT(`Discussion`.`ID`)
					FROM
						`Discussion`
					WHERE
						`CategoryID` = `DiscussionCategory`.`ID`
				) + (
					SELECT
						COUNT(`BlogPost`.`ID`)
					FROM
						`BlogPost`
					INNER JOIN
						`Blog` ON
							`BlogPost`.`BlogID` = `Blog`.`ID`
					WHERE
						`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
				),
				(
					`User`.`Moderator` = TRUE OR
					`User`.`Admin` = TRUE OR
					(
						(
							`User`.`PostingPrivileges` = TRUE OR
							`User`.`Vendor` = TRUE OR
							`DiscussionCategory`.`AmountTransacted_Post` <= ?
						) AND
						(
							`UserClass_DiscussionCategory`.`UserClassID` IS NULL OR
							`UserClass_DiscussionCategory`.`Post` = FALSE OR
							thisUser_Class.`UserID` IS NOT NULL
						) AND
						(
							`Vendor_Post` = FALSE OR
							`User`.`Vendor` = TRUE
						) AND
						`Admin_Post` = FALSE
					)
				),
				`DiscussionCategoryGroup`.`Label`
			FROM
				`DiscussionCategory`
			INNER JOIN
				`DiscussionCategoryGroup` ON
					`DiscussionCategory`.`DiscussionCategoryGroupID` = `DiscussionCategoryGroup`.`ID`
			INNER JOIN
				`User` ON
					`User`.`ID` = ?
			LEFT JOIN
				`UserClass_DiscussionCategory` ON
					`UserClass_DiscussionCategory`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
			LEFT JOIN
				`User_Class` thisUser_Class ON
					`User`.`ID` = thisUser_Class.`UserID` AND
					`UserClass_DiscussionCategory`.`UserClassID` = thisUser_Class.`ClassID` AND
					IFNULL(thisUser_Class.`Rank`, 1) != 0
			WHERE
				`SiteID` = ? AND
				(
					`Vendor_View` = FALSE OR
					`User`.`Vendor` = TRUE
				) AND
				(
					`User`.`Moderator` = TRUE OR
					`DiscussionCategory`.`UserClass_View` = FALSE OR
					thisUser_Class.`UserID` IS NOT NULL
				)
			ORDER BY
				`DiscussionCategoryGroup`.`Sort` ASC,
				`DiscussionCategory`.`Sort` ASC
		");
		
		if( false !== $stmt_getDiscussionCategories ){
			$stmt_getDiscussionCategories->bind_param(
			  'dii',
			  $this->User->Attributes['TotalTransacted'],
			  $this->User->ID,
			  $this->db->site_id
			);
			$stmt_getDiscussionCategories->execute();
			$stmt_getDiscussionCategories->store_result();
			$stmt_getDiscussionCategories->bind_result(
				$categoryID,
				$categoryName,
				$categoryAlias,
				$categoryDiscussionCount,
				$categoryPostingPrivileges,
				$categoryGroupLabel
			);
			
			$totalDiscussionCount = 0;
			
			$categories = array();
			while( $stmt_getDiscussionCategories->fetch() ){
				$categories[$categoryID] = array(
					'ID'			=> $categoryID,
					'name'			=> $categoryName,
					'URL'			=> URL . 'discussions/' . $categoryAlias . '/',
					'alias'			=> $categoryAlias,
					'discussionCount'	=> $categoryDiscussionCount,
					'hasPostingPrivileges'	=> $categoryPostingPrivileges,
					'groupLabel'		=> $categoryGroupLabel
				);
				$categoryNames[$categoryAlias] = $categoryID;
				$totalDiscussionCount += $categoryDiscussionCount;
			}
			
			/*$categories =
				[
					[
						'ID'			=> FALSE,
						'name'			=> 'All Discussions',
						'URL'			=> URL . 'discussions/all/',
						'alias'			=> 'all',
						'discussionCount'	=> $totalDiscussionCount,
						'hasPostingPrivileges'	=> FALSE
					]
				] +
				$categories;*/
			
			$categoryNames['all'] = FALSE;
			
			return [$categories, $categoryNames, $totalDiscussionCount];
		} else {
			//die($this->db->error);
		}
	}
	
	public function fetchLatestUpdates($limit = LATEST_UPDATES_COUNT, $notForum = FALSE){
		$latestUpdates = $this->db->qSelect(
			"
				SELECT DISTINCT
					`User`.`Alias` userAlias,
					CONCAT(
						'/" . UPLOADS_PATH . "',
						`Image`.`Filename`
					) image,
					`BlogPost`.`ID` ID,
					`BlogPost`.`HTML` content,
					`BlogPost`.`DateUpdated` dateUpdated
				FROM
					`User`
				INNER JOIN
					`Blog` ON
						`User`.`ID` = `Blog`.`UserID`
				INNER JOIN
					`BlogPost` ON
						`BlogPost`.`ID` = (
							SELECT
								`BlogPost`.`ID`
							FROM
								`BlogPost`
							WHERE
								`BlogPost`.`BlogID` = `Blog`.`ID`
							ORDER BY
								`BlogPost`.`DateInserted` DESC
							LIMIT
								1
						)
				LEFT JOIN
					`Image` ON
						`User`.`ImageID` = `Image`.`ID`
				ORDER BY
					`BlogPost`.`DateUpdated` DESC
				LIMIT " . $limit . "
			"
		);
		
		if( $latestUpdates ){
			return array_map(
				function($array) use ($notForum){
					$updateExcerpt = strip_tags(NXS::getExcerpt($array['content'], LATEST_UPDATE_EXCERPT_MAX_LENGTH));
					return array_merge(
						$array,
						array(
							'image'			=> NXS::getPictureVariant($array['image'], IMAGE_THUMBNAIL_SUFFIX),
							'icon'			=> FALSE,
							'dateUpdated'		=> date('F j, Y', strtotime($array['dateUpdated'])),
							'content'		=> $updateExcerpt,
							'URL'			=> URL . ($notForum ? 'go/forum/' : FALSE) . 'post/' . $array['ID'] . '/'
						)
					);
				},
				$latestUpdates
			);
		} else
			return FALSE;
	}
	
	public function _createVendorBlog(){
		if(
			$blogID = $this->db->qQuery(
				"
					INSERT INTO
						`Blog` (
							`DiscussionCategoryID`,
							`UserID`,
							`Alias`,
							`Title`
						)
					VALUES
						(
							1,
							?,
							?,
							?
						)
				",
				'iss',
				array(
					$this->User->ID,
					$this->User->Alias,
					$this->User->Alias
				)
			)
		){
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Blog_PostingPrivileges` (`BlogID`, `PosterID`)
					VALUES
						(?, ?)
				",
				'ii',
				[
					$blogID,
					$this->User->ID
				]
			);
			
			$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Blog_Subscription` (
							`BlogID`,
							`SubscriberID`,
							`SeenPostID`
						)
					SELECT
						?,
						`FollowerID`,
						0
					FROM
						`User_User`
					WHERE
						`UserID` = ?
				",
				'ii',
				[
					$blogID,
					$this->User->ID
				]
			);
			
			return true;
		}
			
		return true;
	}
	
	public function getUserBlog(){
		if(
			$blog = $this->db->qSelect(
				"
					SELECT
						`Alias`
					FROM
						`Blog`
					WHERE
						`UserID` = ?
				",
				'i',
				array(
					$this->User->ID
				)
			)
		)
			return $blog[0];
		
		if (
			$this->User->IsVendor &&
			$this->_createVendorBlog()
		)
			return $this->getUserBlog();
		
		return FALSE;
	}
	
	public function getUserDiscussion($userID = FALSE){
		$userID = $userID ?: $this->User->ID;
		
		$userDiscussion = $this->db->qSelect(
			"
				SELECT
					`Discussion`.`ID`,
					COUNT(`Discussion_Comment`.`ID`) commentCount
				FROM
					`Discussion`
				LEFT JOIN	`Discussion_Comment`
					ON	`Discussion`.`ID` = `Discussion_Comment`.`DiscussionID`
				WHERE
					`UserID` = ?
			",
			'i',
			array($userID)
		);
				
		return $userDiscussion[0] ?: FALSE;
	}
	
	public function countForumEntries(
		$categoryID = FALSE,
		$query = false
	){
		$stmt_countForumEntries_types = 'i';
		$stmt_countForumEntries_vars = [$this->User->ID];
		
		if($categoryID){
			$stmt_countForumEntries_types .= 'i';
			$stmt_countForumEntries_vars[] = $categoryID;
		}
		
		if($query){
			$stmt_countForumEntries_types .= 's';
			$stmt_countForumEntries_vars[] = $query;
		}
		
		$stmt_countForumEntries_query = "
			SELECT
				COUNT(DISTINCT `ForumItem`.`Identifier`) Count
			FROM
				`ForumItem`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			INNER JOIN
				`DiscussionCategory` ON
					`ForumItem`.`CategoryID` = `DiscussionCategory`.`ID`
			LEFT JOIN
				`User_Class` thisUser_Class ON
					thisUser.`ID` = thisUser_Class.`UserID` AND
					IFNULL(thisUser_Class.`Rank`, 1) != 0
			LEFT JOIN
				`UserClass_DiscussionCategory` ON
					`UserClass_DiscussionCategory`.`UserClassID` = thisUser_Class.`ClassID` AND
					`UserClass_DiscussionCategory`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
			WHERE
				(
					`DiscussionCategory`.`Vendor_View` = FALSE OR
					thisUser.`Vendor` = TRUE
				) AND
				(
					thisUser.`Moderator` = TRUE OR
					`DiscussionCategory`.`UserClass_View` = FALSE OR
					`UserClass_DiscussionCategory`.`UserClassID` IS NOT NULL
				) " . (
					$categoryID
						? "AND `ForumItem`.`CategoryID` = ?"
						: FALSE
				) . (
					$query
						? "AND MATCH(`ForumItem`.`Title`) AGAINST (? IN BOOLEAN MODE)"
						: FALSE
				) . "
		";
		
		if(
			$forumEntryCount = $this->db->qSelect(
				$stmt_countForumEntries_query,
				$stmt_countForumEntries_types,
				$stmt_countForumEntries_vars,
				TRUE
			)[0]['Count']
		)
			return $forumEntryCount;
		
		return FALSE;
	}
	
	public function changeDiscussionCategory($discussionID, $categoryID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Discussion`
					SET
						`CategoryID` = ?
					WHERE
						`ID` = ?
				",
				'ii',
				[
					$categoryID,
					$discussionID
				]
			) &&
			$this->updateDiscussionForumItem($discussionID);
	}
	
	public function updateDiscussionForumItem($discussionID){
		return	$this->db->qQuery(
				"
					INSERT INTO `ForumItem` (
						`Identifier`,
						`CategoryID`,
						`DiscussionID`,
						`BlogPostID`,
						`PosterID`,
						`Title`,
						`DateInserted`,
						`DateActive`,
						`CommentCount`,
						`Sink`,
						`FirstDiscussionCommentID`,
						`FirstBlogPostCommentID`,
						`RecentDiscussionCommentID`,
						`RecentBlogPostCommentID`,
						`ListingID`
					)
					SELECT
						CAST(
							CONV(
								SUBSTRING(
									MD5(
										CONCAT(
											'Discussion',
											'-',
											`ID`
										)
									),
									1,
									16
								),
								16,
								10
							) AS UNSIGNED INTEGER
						),
						`CategoryID`,
						`ID`,
						NULL,
						`PosterID`,
						`Title`,
						(
							SELECT
								`DateInserted`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
							ORDER BY
								`Discussion_Comment`.`DateInserted` ASC,
								`Discussion_Comment`.`ID` ASC
							LIMIT
								1
						),
						(
							SELECT
								`DateInserted`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
							ORDER BY
								`Discussion_Comment`.`DateInserted` DESC,
								`Discussion_Comment`.`ID` DESC
							LIMIT
								1
						),
						(
							SELECT
								COUNT(`Discussion_Comment`.`ID`)
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
						) - 1,
						`Discussion`.`Sink`,
						(
							SELECT
								`ID`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
							ORDER BY
								`Discussion_Comment`.`DateInserted` ASC,
								`Discussion_Comment`.`ID` ASC
							LIMIT
								1
						),
						NULL,
						(
							SELECT
								`ID`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
							ORDER BY
								`Discussion_Comment`.`DateInserted` DESC,
								`Discussion_Comment`.`ID` DESC
							LIMIT
								1
						),
						NULL,
						`ListingID`
					FROM
						`Discussion`
					WHERE
						`Discussion`.`ID` = ?
					ON DUPLICATE KEY
						UPDATE
							`DateInserted` = (
								SELECT
									`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT
									1
							),
							`DateActive` = (
								SELECT
									`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` DESC,
									`Discussion_Comment`.`ID` DESC
								LIMIT
									1
							),
							`CommentCount` = (
								SELECT
									COUNT(`Discussion_Comment`.`ID`)
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
							) - 1,
							`Sink` = `Discussion`.`Sink`,
							`FirstDiscussionCommentID` = (
								SELECT
									`ID`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT
									1
							),
							`RecentDiscussionCommentID` = (
								SELECT
									`ID`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` DESC,
									`Discussion_Comment`.`ID` DESC
								LIMIT
									1
							),
							`ListingID` = `Discussion`.`ListingID`,
							`CategoryID` = `Discussion`.`CategoryID`
				",
				'i',
				[$discussionID]
			);
	}
	
	public function updateBlogPostForumItem($blogPostID){
		return	$this->db->qQuery(
				"
					INSERT INTO `ForumItem` (
						`Identifier`,
						`CategoryID`,
						`DiscussionID`,
						`BlogPostID`,
						`PosterID`,
						`Title`,
						`DateInserted`,
						`DateActive`,
						`CommentCount`,
						`Sink`,
						`FirstDiscussionCommentID`,
						`FirstBlogPostCommentID`,
						`RecentDiscussionCommentID`,
						`RecentBlogPostCommentID`,
						`ListingID`
					)
					SELECT
						CAST(
							CONV(
								SUBSTRING(
									MD5(
										CONCAT(
											'BlogPost',
											'-',
											`BlogPost`.`ID`
										)
									),
									1,
									16
								),
								16,
								10
							) AS UNSIGNED INTEGER
						),
						`Blog`.`DiscussionCategoryID`,
						NULL,
						`BlogPost`.`ID`,
						`PosterID`,
						`BlogPost`.`Title`,
						`DateInserted`,
						IFNULL(
							(
								SELECT
									`BlogPostComment`.`DateInserted`
								FROM
									`BlogPostComment`
								WHERE
									`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
								ORDER BY
									`BlogPostComment`.`DateInserted` DESC,
									`BlogPostComment`.`ID` DESC
								LIMIT
									1
							),
							`DateInserted`
						),
						(
							SELECT
								COUNT(`BlogPostComment`.`ID`)
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
						),
						0,
						NULL,
						(
							SELECT
								`BlogPostComment`.`ID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
							ORDER BY
								`BlogPostComment`.`DateInserted` ASC,
								`BlogPostComment`.`ID` ASC
							LIMIT
								1
						),
						NULL,
						(
							SELECT
								`BlogPostComment`.`ID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
							ORDER BY
								`BlogPostComment`.`DateInserted` DESC,
								`BlogPostComment`.`ID` DESC
							LIMIT
								1
						),
						NULL
					FROM
						`BlogPost`
					INNER JOIN
						`Blog` ON
							`BlogID` = `Blog`.`ID`
					WHERE
						`BlogPost`.`ID` = ?
					ON DUPLICATE KEY UPDATE
						`Title` = `BlogPost`.`Title`,
						`DateActive` = IFNULL(
							(
								SELECT
									`BlogPostComment`.`DateInserted`
								FROM
									`BlogPostComment`
								WHERE
									`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
								ORDER BY
									`BlogPostComment`.`DateInserted` DESC,
									`BlogPostComment`.`ID` DESC
								LIMIT
									1
							),
							`BlogPost`.`DateInserted`
						),
						`CommentCount` = (
							SELECT
								COUNT(`BlogPostComment`.`ID`)
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
						),
						`FirstBlogPostCommentID` = (
							SELECT
								`BlogPostComment`.`ID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
							ORDER BY
								`BlogPostComment`.`DateInserted` ASC,
								`BlogPostComment`.`ID` ASC
							LIMIT
								1
						),
						`RecentBlogPostCommentID` = (
							SELECT
								`BlogPostComment`.`ID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
							ORDER BY
								`BlogPostComment`.`DateInserted` DESC,
								`BlogPostComment`.`ID` DESC
							LIMIT
								1
						)
				",
				'i',
				[$blogPostID]
			);
	}
	
	public function fetchForumEntries(
		$categoryID = false,
		$sort = false,
		$page = 1,
		$entriesPerPage = DISCUSSIONS_PER_PAGE,
		$query = false,
		$countEntries = true
	){
		$stmt_fetchForumEntries_types = 'i';
		$stmt_fetchForumEntries_vars = [$this->User->ID];
		
		if ($query){
			$stmt_fetchForumEntries_types = 's' . $stmt_fetchForumEntries_types . 's';
			$stmt_fetchForumEntries_vars = array_merge(
				[$query],
				$stmt_fetchForumEntries_vars,
				[$query]
			);
			
			$sort = 'Relevance DESC';
		} else {
			switch($sort){
				case 'comments_desc':
					$sort = '`ForumItem`.`CommentCount` DESC';
					break;
				default: // case 'recency':
					$sort = '`ForumItem`.`DateActive` DESC';
			}
		}
		
		if ($categoryID){
			$stmt_fetchForumEntries_types .= 'i';
			$stmt_fetchForumEntries_vars[] = $categoryID;
		}
		
		$forumEntryCount =
			$countEntries || $page > 1
				? $this->countForumEntries($categoryID, $query)
				: $entriesPerPage;
		
		$offset = NXS::getOffset(
			$forumEntryCount,
			$entriesPerPage,
			$page
		);
		
		$stmt_fetchForumEntries_types .= 'i';
		$stmt_fetchForumEntries_vars[] = $offset;
		
		$stmt_fetchForumEntries_query = "
			SELECT DISTINCT
				IF(
					`ForumItem`.`DiscussionID` IS NOT NULL,
					'Discussion',
					'BlogPost'
				) Type,
				IFNULL(`ForumItem`.`DiscussionID`, `ForumItem`.`BlogPostID`) ID,
				`ForumItem`.`CategoryID`,
				`ForumItem`.`DateInserted`,
				`ForumItem`.`Title`,
				IF (
					`ForumItem`.`DiscussionID` IS NOT NULL,
					CASE
						WHEN
							`ForumItem`.`ListingID` IS NOT NULL AND
							Poster.`Vendor` = FALSE
						THEN
							'" . FORUM_REVIEW_LABEL . "'
						WHEN
							`Discussion`.`TypeID` = '" . FORUM_VENDOR_NOMINATION_TYPE_ID . "'
						THEN
							'" . FORUM_VENDOR_NOMINATION_LABEL . "'
					END,
					FALSE
				) Status,
				IF (
					`ForumItem`.`DiscussionID` IS NOT NULL,
					CASE
						WHEN
							`ForumItem`.`ListingID` IS NOT NULL AND
							Poster.`Vendor` = FALSE
						THEN
							'yellow'
						WHEN
							`Discussion`.`TypeID` = " . FORUM_VENDOR_NOMINATION_TYPE_ID . "
						THEN
							'" . FORUM_VENDOR_NOMINATION_COLOR . "'
					END,
					FALSE
				) Color,
				Poster.`ID` PosterID,
				Poster.`Alias` PosterAlias,
				MostRecentCommenter.`Alias` MostRecentCommenterAlias,
				MostRecentCommenter.`ID` MostRecentCommenterID,
				`ForumItem`.`CommentCount`,
				CASE
					WHEN	`Notification`.`ID` IS NOT NULL AND
						`Notification_User`.`NotificationID` IS NULL
					THEN
						TRUE
					WHEN	`ForumItem`.`DiscussionID` IS NOT NULL
					THEN
						`Discussion_Subscription`.`SeenCommentID` IS NOT NULL AND
						`ForumItem`.`DateActive` > (
							SELECT	`DateInserted`
							FROM	`Discussion_Comment`
							WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
						)
					WHEN	`ForumItem`.`BlogPostID` IS NOT NULL
					THEN
						(
							`Blog_Subscription`.`SeenPostID` IS NOT NULL AND
							`ForumItem`.`DateInserted` > (
								SELECT	`DateInserted`
								FROM	`BlogPost`
								WHERE	`ID` = `Blog_Subscription`.`SeenPostID`
							)
						) OR
						(
							`BlogPost_Subscription`.`SeenCommentID` IS NOT NULL AND
							`ForumItem`.`DateActive` > (
								SELECT	`DateInserted`
								FROM	`BlogPostComment`
								WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
							)
						)
					ELSE
						FALSE
				END NewEntries,
				(
					`Notification`.`ID` IS NOT NULL AND
					`Notification_User`.`NotificationID` IS NULL
				) isNotificationEntry,
				`Blog`.`UserID` UserID,
				CASE
					WHEN	uploadedImage.`ID` IS NOT NULL THEN
							CONCAT(
								'/" . UPLOADS_PATH . "',
								uploadedImage.`Filename`
							)
					WHEN	listingImage.`ID` IS NOT NULL THEN
							CONCAT(
								'/" . UPLOADS_PATH . "',
								listingImage.`Filename`
							)			
					ELSE
						IF(
							userImage.`ID` IS NOT NULL,
							CONCAT(
								'/" . UPLOADS_PATH . "',
								userImage.`Filename`
							),
							FALSE
						)
				END UserImage,
				`ForumItem`.`DateActive`,
				IFNULL(
					`Discussion`.`Announce`,
					`BlogPost`.`Stickied`
				) Announce,
				`Blog`.`Alias` BlogAlias,
				`Blog`.`Title` BlogTitle,
				(
					`DiscussionCategory`.`Vendor_View` = FALSE OR
					thisUser.`Vendor` = TRUE
				) AND
				(
					thisUser.`Moderator` = TRUE OR
					`DiscussionCategory`.`UserClass_View` = FALSE OR
					`UserClass_DiscussionCategory`.`UserClassID` IS NOT NULL
				) CanView,
				IFNULL(
					`Discussion`.`Closed`,
					`BlogPost`.`Closed`
				) Closed,
				CASE
					WHEN
						`Discussion_Subscription`.`SeenCommentID` IS NOT NULL
					THEN
						IFNULL(
							(
								SELECT
									`Discussion_Comment`.`ID`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID` AND
									`Discussion_Comment`.`DateInserted` > (
										SELECT	`DateInserted`
										FROM	`Discussion_Comment`
										WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
									)
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT 1
							),
							`ForumItem`.`RecentDiscussionCommentID`
						)
					WHEN
						`BlogPost_Subscription`.`SeenCommentID` IS NOT NULL
					THEN
						IFNULL(
							(
								SELECT
									`BlogPostComment`.`ID`
								FROM
									`BlogPostComment`
								WHERE
									`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID` AND
									`BlogPostComment`.`DateInserted` > (
										SELECT	`DateInserted`
										FROM	`BlogPostComment`
										WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
									)
								ORDER BY
									`BlogPostComment`.`DateInserted` ASC,
									`BlogPostComment`.`ID` ASC
								LIMIT 1
							),
							`ForumItem`.`RecentBlogPostCommentID`
						)
					ELSE
						FALSE
				END SeenCommentID,
				IFNULL(
					`ForumItem`.`RecentDiscussionCommentID`,
					`ForumItem`.`RecentBlogPostCommentID`
				) MostRecentCommentID,
				IF(
					(
						`Notification`.`ID` IS NOT NULL AND
						`Notification_User`.`NotificationID` IS NULL
					),
					`Notification`.`ID`,
					FALSE
				) dismissableNotificationID,
				" . (
					$query
						? 'MATCH(`ForumItem`.`Title`) AGAINST (?) Relevance,'
						: FALSE
				) . "
				`Discussion`.`ListingID`,
				FirstDiscussionComment.`Anonymous` PosterIsAnonymous,
				RecentDiscussionComment.`Anonymous` MostRecentCommenterAnonymous,
				`Listing`.`Name` listingName,
				Vendor.`Alias` vendorAlias,
				`Country`.`ISO` countryISO,
				(
					`Listing`.`Inactive` = FALSE AND
					`Listing`.`Stealth` = FALSE AND
					(
						`Listing_Group`.`GroupID` IS NULL OR
						`Listing_Group`.`OutOfStock` = FALSE
					)
				) listingIsAvailable,
				uploadedImage.`ID` IS NOT NULL hasUploadedPictures,
				IF(
					thisUser.`Moderator` = TRUE,
					IFNULL(
						(
							SELECT
								`CommentID`
							FROM
								`Discussion_Comment_Report`
							INNER JOIN
								`Discussion_Comment` ON
									`CommentID` = `ID`
							INNER JOIN
								`User` reporter ON
									`Discussion_Comment_Report`.`UserID` = reporter.`ID`
							WHERE
								`DiscussionID` = `Discussion`.`ID` AND
								`Discussion_Comment_Report`.`Cleared` = FALSE AND
								reporter.`Banned` = FALSE
							ORDER BY
								`Discussion_Comment`.`DateInserted` ASC,
								`Discussion_Comment`.`ID` ASC
							LIMIT 1
						),
						FALSE
					),
					FALSE
				) reportedCommentID
			FROM
				`ForumItem`
			INNER JOIN
				`User` thisUser ON
					thisUser.`ID` = ?
			INNER JOIN
				`User` Poster ON
					`ForumItem`.`PosterID` = Poster.`ID`
			INNER JOIN
				`DiscussionCategory` ON
					`ForumItem`.`CategoryID` = `DiscussionCategory`.`ID`
			LEFT JOIN
				`Discussion` ON
					`ForumItem`.`DiscussionID` = `Discussion`.`ID`
			LEFT JOIN
				`BlogPost` ON
					`ForumItem`.`BlogPostID` = `BlogPost`.`ID`
			LEFT JOIN
				`Blog` ON
					`BlogPost`.`BlogID` = `Blog`.`ID`
			LEFT JOIN
				`Discussion_Comment` FirstDiscussionComment ON
					FirstDiscussionComment.`ID` = `ForumItem`.`FirstDiscussionCommentID`
			LEFT JOIN
				`Discussion_Comment` RecentDiscussionComment ON
					RecentDiscussionComment.`ID` = `ForumItem`.`RecentDiscussionCommentID`
			LEFT JOIN
				`BlogPostComment` RecentBlogPostComment ON
					RecentBlogPostComment.`ID` = `ForumItem`.`RecentBlogPostCommentID`
			LEFT JOIN
				`User` MostRecentCommenter ON
					MostRecentCommenter.`ID` = IFNULL(
						RecentDiscussionComment.`PosterID`,
						RecentBlogPostComment.`CommenterID`
					)
			LEFT JOIN
				`Discussion_Subscription` ON
					`ForumItem`.`DiscussionID` = `Discussion_Subscription`.`DiscussionID` AND
					`Discussion_Subscription`.`SubscriberID` = thisUser.`ID`
			LEFT JOIN
				`Blog_Subscription` ON
					`Blog`.`ID` = `Blog_Subscription`.`BlogID` AND
					thisUser.`ID` = `Blog_Subscription`.`SubscriberID`
			LEFT JOIN
				`BlogPost_Subscription` ON
					`ForumItem`.`BlogPostID` = `BlogPost_Subscription`.`BlogPostID` AND
					thisUser.`ID` = `BlogPost_Subscription`.`SubscriberID`
			LEFT JOIN
				`Notification` ON
					(
						`Notification`.`BlogPostID` = `ForumItem`.`BlogPostID` OR
						`Notification`.`DiscussionID` = `ForumItem`.`DiscussionID`
					) AND
					`Notification`.`Type` IN (
						'Everybody',
						" . (
							$this->User->IsMod
								? "'Vendor', 'Buyer'"
								: (
									$this->User->IsVendor
										? "'Vendor'"
										: "'Buyer'"
								)
						) . "
					)
			LEFT JOIN
				`Notification_User` ON
					`Notification_User`.`NotificationID` = `Notification`.`ID` AND
					`Notification_User`.`UserID` = thisUser.`ID`
			LEFT JOIN
				`DiscussionComment_Image` ON
					`DiscussionComment_Image`.`DiscussionCommentID` = `ForumItem`.`FirstDiscussionCommentID` AND
					`DiscussionComment_Image`.`ImageID` = (
						SELECT	MIN(`ImageID`)
						FROM	`DiscussionComment_Image`
						WHERE
							`DiscussionComment_Image`.`DiscussionCommentID` = `ForumItem`.`FirstDiscussionCommentID`
					)
			LEFT JOIN
				`Image` uploadedImage ON
					uploadedImage.`ID` = `DiscussionComment_Image`.`ImageID`
			LEFT JOIN
				`Listing_Image` ON
					`DiscussionComment_Image`.`ImageID` IS NULL AND
					`Discussion`.`ListingID` = `Listing_Image`.`ListingID` AND
					`Listing_Image`.`Primary` = TRUE
			LEFT JOIN
				`Image` listingImage ON
					listingImage.`ID` = `Listing_Image`.`ImageID`
			LEFT JOIN
				`Image` userImage ON
					userImage.`ID` = Poster.`ImageID`
			LEFT JOIN
				`User_Class` Poster_Class ON
					Poster.`ID` = Poster_Class.`UserID` AND
					Poster_Class.`Public` = TRUE
			LEFT JOIN
				`User_Class` MostRecentCommenter_Class ON
					MostRecentCommenter.`ID` = MostRecentCommenter_Class.`UserID` AND
					MostRecentCommenter_Class.`Public` = TRUE
			LEFT JOIN
				`UserClass` PosterClass ON
					Poster_Class.`ClassID` = PosterClass.`ID`
			LEFT JOIN
				`UserClass` MostRecentCommenterClass ON
					MostRecentCommenter_Class.`ClassID` = MostRecentCommenterClass.`ID`
			LEFT JOIN
				`User_Class` thisUser_Class ON
					thisUser.`ID` = thisUser_Class.`UserID` AND
					IFNULL(thisUser_Class.`Rank`, 1) != 0
			LEFT JOIN
				`UserClass_DiscussionCategory` ON
					`UserClass_DiscussionCategory`.`UserClassID` = thisUser_Class.`ClassID` AND
					`UserClass_DiscussionCategory`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
			LEFT JOIN
				`Listing` ON
					`Discussion`.`ListingID` = `Listing`.`ID`
			LEFT JOIN
				`User` Vendor ON
					`Listing`.`VendorID` = Vendor.`ID`
			LEFT JOIN
				`Country` ON
					`Listing`.`CountryID` = `Country`.`ID`
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			WHERE
				(
					thisUser.`Vendor` = TRUE OR
					`DiscussionCategory`.`Vendor_View` = FALSE
				) AND
				(
					thisUser.`Moderator` = TRUE OR
					`DiscussionCategory`.`UserClass_View` = FALSE OR
					`UserClass_DiscussionCategory`.`UserClassID` IS NOT NULL
				)
				" . (
					$categoryID
						? "AND `ForumItem`.`CategoryID` = ?"
						: false
				) . (
					$query
						? "AND MATCH(`ForumItem`.`Title`) AGAINST (? IN BOOLEAN MODE)"
						: false
				) . "
			ORDER BY
				CASE
					WHEN	`Notification`.`ID` IS NOT NULL AND
						`Notification_User`.`NotificationID` IS NULL
					THEN
						TRUE
					WHEN	`ForumItem`.`DiscussionID` IS NOT NULL
					THEN
						`Discussion_Subscription`.`SeenCommentID` IS NOT NULL AND
						`ForumItem`.`DateActive` > (
							SELECT	`DateInserted`
							FROM	`Discussion_Comment`
							WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
						)
					WHEN	`ForumItem`.`BlogPostID` IS NOT NULL
					THEN
						(
							`Blog_Subscription`.`SeenPostID` IS NOT NULL AND
							`ForumItem`.`DateInserted` > (
								SELECT	`DateInserted`
								FROM	`BlogPost`
								WHERE	`ID` = `Blog_Subscription`.`SeenPostID`
							)
						) OR
						(
							`BlogPost_Subscription`.`SeenCommentID` IS NOT NULL AND
							`ForumItem`.`DateActive` > (
								SELECT	`DateInserted`
								FROM	`BlogPostComment`
								WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
							)
						)
					ELSE
						FALSE
				END DESC,
				(
					`Notification`.`ID` IS NOT NULL AND
					`Notification_User`.`NotificationID` IS NULL
				) ASC,
				(
					thisUser.`Moderator` = TRUE &&
					reportedCommentID != FALSE
				) DESC,
				IFNULL(
					`Discussion`.`Announce`,
					`BlogPost`.`Stickied`
				) DESC,
				IFNULL(`Discussion`.`Sink`, 0) ASC,
				" . $sort . ",
				Type = 'BlogPost' DESC
			LIMIT
				?, " . $entriesPerPage . "
		";
		
		if(
			$forumEntries_result = $this->db->qSelect(
				$stmt_fetchForumEntries_query,
				$stmt_fetchForumEntries_types,
				$stmt_fetchForumEntries_vars
			)
		){
			$forumEntries = array();
			$userFlairs = [];
			foreach($forumEntries_result as $forumEntry){
				switch (true){
					case $forumEntry['Announce'] && !$forumEntry['NewEntries'] && !$forumEntry['reportedCommentID']:
						$entryColor = 'green';
						break;
					case $forumEntry['NewEntries'] && $forumEntry['isNotificationEntry']:
						$entryColor = 'blue';
						break;
					case $forumEntry['reportedCommentID']:
						$entryColor = FORUM_REPORTED_DISCUSSION_COLOR;
						break;
					default:
						$entryColor = false;
				}
				$forumEntries[] = array(
					'Type'			=> $forumEntry['Type'],
					'ID'			=> $forumEntry['ID'],
					'categoryID'		=> $forumEntry['CategoryID'],
					'dateInserted'		=> $forumEntry['DateInserted'],
					'title'			=> $forumEntry['Title'],
					'status'		=> $forumEntry['Status'],
					'color'			=> $entryColor,
					'posterAlias'		=> $forumEntry['PosterAlias'],
					'posterImage'		=>
						NXS::getPictureVariant(
							$forumEntry['UserImage'],
							IMAGE_THUMBNAIL_SUFFIX
						),
					'recentCommenter'	=> $forumEntry['MostRecentCommenterAlias'],
					'commentCount'		=> $forumEntry['CommentCount'],
					'highlighted'		=>
						$forumEntry['NewEntries'] ||
						$forumEntry['Announce'] ||
						$forumEntry['reportedCommentID'],
					'blogAlias'		=> $forumEntry['BlogAlias'],
					'blogTitle'		=> $forumEntry['BlogTitle'],
					'stickied'		=> $forumEntry['Announce'],
					'closed'		=> $forumEntry['Closed'],
					'newEntries'		=> $forumEntry['NewEntries'],
					'seenCommentID'		=> $forumEntry['SeenCommentID'],
					'posterFlair'		=> 
						!$forumEntry['PosterIsAnonymous']
							? $this->fetchUserFlairs(
								$forumEntry['PosterID'],
								false,
								1,
								$userFlairs
							)
							: false,
					'recentCommenterFlair'	=>
						!$forumEntry['MostRecentCommenterAnonymous']
							? $this->fetchUserFlairs(
								$forumEntry['MostRecentCommenterID'],
								false,
								1,
								$userFlairs
							)
							: false,
					'badgeURL'		=>
						$forumEntry['BadgeURL']
							?: URL . ($forumEntry['Type'] == 'BlogPost' ? 'post' : 'discussion') . '/' . $forumEntry['ID'] . '/',
					'badgeColor'		=> $forumEntry['Color'],
					'latestCommentID'	=> $forumEntry['MostRecentCommentID'],
					'dismissableNotificationID' =>$forumEntry['dismissableNotificationID'],
					'ListingID'		=> $forumEntry['ListingID'],
					'PosterIsAnonymous'	=> $forumEntry['PosterIsAnonymous'],
					'MostRecentCommenterAnonymous' => $forumEntry['MostRecentCommenterAnonymous'],
					'listingName'		=> $forumEntry['listingName'],
					'vendorAlias'		=> $forumEntry['vendorAlias'],
					'countryISO'		=> $forumEntry['countryISO'],
					'listingIsAvailable'	=> $forumEntry['listingIsAvailable'],
					'hasUploadedPictures'	=> $forumEntry['hasUploadedPictures'],
					'reportedCommentID'	=> $forumEntry['reportedCommentID']
				);
			}
			
			return [
				$forumEntryCount,
				$forumEntries
			];
		}
		
		return FALSE;
	}
	
	public function fetchDiscussions($category, $page, $sort, $query = false){
		$stmt_types = 'i';
		$stmt_vars = array(&$this->User->ID);
		
		$where = array();
		
		// Only allowed categories
		$where[] = "
			`DiscussionCategory`.`SiteID` = ?
		AND	(
				`DiscussionCategory`.`Vendor_View` = FALSE OR
				Me.`Vendor` = TRUE
			)
		";
		$stmt_types .= 'i';
		$stmt_vars[] = &$this->db->site_id;
		
		if($category){
			$where[] = "`Discussion`.`CategoryID` = ?";
			$stmt_types .= 'i';
			
			$stmt_vars[] = &$category;
		}
		
		if($query){
			$join[] = "INNER JOIN `Discussion_Comment` ON `Discussion`.`ID` = `Discussion_Comment`.`DiscussionID`";
			$where[] = "MATCH(`Discussion`.`Title`) AGAINST (? IN BOOLEAN MODE) OR MATCH(`Discussion_Comment`.`Content`) AGAINST (? IN BOOLEAN MODE)";
			$stmt_types .= 'ss';
			
			$stmt_vars[] = &$query;
			$stmt_vars[] = &$query;	
		}
		
		switch($sort){
			case 'comments_desc':
				$sort = "
				(
					SELECT	COUNT(`Discussion_Comment`.`ID`)
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
				) DESC";
			break;
			// case 'recency':
			default:
				$sort = '
				`DiscussionCategory`.`Sink` ASC,
				`Discussion`.`Sink` ASC,
				(
					SELECT	MAX(`DateInserted`)
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
				) DESC';
		}
		
		$stmt_countDiscussions = $this->db->prepare("
			SELECT
				COUNT(`Discussion`.`ID`)
			FROM
				`Discussion`
			INNER JOIN	`User` Me
				ON	Me.`ID` = ?
			INNER JOIN	`DiscussionCategory`
				ON	`Discussion`.`CategoryID` = `DiscussionCategory`.`ID`
			" . (!empty($join) ? implode(' ', $join) : false) . "
			" . (!empty($where) ? "WHERE " . implode(' AND ', $where) : false) . "
		");
		
		$stmt_getDiscussions_query = "
			SELECT
				`Discussion`.`ID`,
				`Discussion`.`CategoryID`,
				(
					SELECT	`DateInserted`
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
					LIMIT	1
				),
				`Discussion`.`Title`,
				IFNULL(`Discussion`.`Status`, FALSE),
				`Discussion`.`Color`,
				`User`.`Alias`,
				MostRecentCommenter.`Alias`,
				(
					SELECT	COUNT(`Discussion_Comment`.`ID`)
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
				),
				IF(
					`Discussion_Subscription`.`SeenCommentID` IS NOT NULL,
					IF(
						IFNULL(
							(
								SELECT
									`Discussion_Comment`.`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` DESC,
									`Discussion_Comment`.`ID` DESC
								LIMIT
									1
							),
							'" . MYSQL_DATETIME_RANGE_LOWEST . "'
						) > (
							SELECT	`DateInserted`
							FROM	`Discussion_Comment`
							WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`,
						)
						TRUE,
						FALSE
					),
					FALSE
				) AS NewPosts,
				IF(
					`Discussion`.`UserID` IS NULL,
					FALSE,
					CONCAT(
						'/" . UPLOADS_PATH . "',
						`Image`.`Filename`
					)
				),
				IF(`Discussion`.`UserID` IS NOT NULL, TRUE, FALSE)
			FROM
				`Discussion`
			INNER JOIN	`User` Me
				ON	Me.`ID` = ?
			INNER JOIN	`DiscussionCategory`
				ON	`Discussion`.`CategoryID` = `DiscussionCategory`.`ID`
			INNER JOIN	`User`
				ON	`Discussion`.`PosterID` = `User`.`ID`
			" . (!empty($join) ? implode(' ', $join) : false) . "
			LEFT JOIN
				`User` MostRecentCommenter ON
					MostRecentCommenter.`ID` = (
						SELECT		`PosterID`
						FROM		`Discussion_Comment`
						WHERE		`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
						ORDER BY	`Discussion_Comment`.`DateInserted` DESC
						LIMIT		1
					)
			LEFT JOIN	`Discussion_Subscription`
				ON	`Discussion`.`ID` = `Discussion_Subscription`.`DiscussionID`
				AND	`Discussion_Subscription`.`SubscriberID` = ?
			LEFT JOIN
				`Image` ON
					`User`.`ImageID` = `Image`.`ID`
			" . (!empty($where) ? "WHERE " . implode(' AND ', $where) : false) . "
			GROUP BY
				`Discussion`.`ID`
			ORDER BY
				`Announce` DESC,
				NewPosts DESC,
				" . $sort . "
			LIMIT ?, " . DISCUSSIONS_PER_PAGE . "
		";
		
		$stmt_getDiscussions = $this->db->prepare($stmt_getDiscussions_query);
		
		if( false !== $stmt_countDiscussions && false !== $stmt_getDiscussions ){
			if(!empty($stmt_vars) ){
				$stmt_args_count = array_merge( array($stmt_types), $stmt_vars);
				call_user_func_array(array($stmt_countDiscussions, 'bind_param'), $stmt_args_count);
			}
			
			$stmt_countDiscussions->execute();
			$stmt_countDiscussions->store_result();
			$stmt_countDiscussions->bind_result($discussion_count);
			$stmt_countDiscussions->fetch();
			
			$discussions = array();
			
			if($discussion_count > 0){
				if( ceil($discussion_count/DISCUSSIONS_PER_PAGE) < $page ){
					$offset = 0;
					$this->User->Notifications->quick('FatalError', 'Invalid Page');
				} else
					$offset = DISCUSSIONS_PER_PAGE*($page - 1);
				
				$stmt_types = 'i' . $stmt_types . 'i';
				array_unshift($stmt_vars, '');
				$stmt_vars[0] = &$this->User->ID;
				$stmt_vars[] = &$offset;
				
				$stmt_args = array_merge( array($stmt_types), $stmt_vars);
				call_user_func_array(array($stmt_getDiscussions, 'bind_param'), $stmt_args);
				$stmt_getDiscussions->execute();
				$stmt_getDiscussions->store_result();
				$stmt_getDiscussions->bind_result(
					$discussionID,
					$discussionCategoryID,
					$discussionDateInserted,
					$discussionTitle,
					$discussionStatus,
					$discussionColor,
					$posterAlias,
					$recentCommenterAlias,
					$commentCount,
					$highlighted,
					$posterImage,
					$isUpdateThread
				);
				
				while( $stmt_getDiscussions->fetch() ){
					$discussions[] = array(
						'ID'				=> $discussionID,
						'categoryID'		=> $discussionCategoryID,
						'dateInserted'		=> $discussionDateInserted,
						'title'				=> $discussionTitle,
						'status'			=> $discussionStatus,
						'color'				=> $discussionColor,
						'posterAlias'		=> $posterAlias,
						'posterImage'		=> NXS::getPictureVariant($posterImage, IMAGE_THUMBNAIL_SUFFIX),
						'recentCommenter'	=> $recentCommenterAlias,
						'commentCount'		=> $commentCount - 1,
						'highlighted'		=> $highlighted == 1,
						'isUpdateThread'	=> $isUpdateThread
					);
				}
				
			}
			
			return array($discussion_count, $discussions);
		} else {
			die();//$this->db->error);
		}
	}
	
	public function fetchComment($comment_id){
		if( $stmt_getComment = $this->db->prepare("
			SELECT
				`Content`
			FROM
				`Discussion_Comment`
			WHERE
				`ID` = ?
		") ){
			
			$stmt_getComment->bind_param('i', $comment_id);
			$stmt_getComment->execute();
			$stmt_getComment->store_result();
			
			if( $stmt_getComment->num_rows == 1 ){
				$stmt_getComment->bind_result($comment_raw_content);
				$stmt_getComment->fetch();
				
				return array(
					'content' => $comment_raw_content
				);
			} else
				return false;
		}
	}
	
	public function fetchComments($discussion_id, $sort, $page){
		if (!empty($_POST)){
			if( isset($_POST['reset_filter']) ){
				unset($_SESSION['forum']);
				$this->User->updatePrefs(
					array(
						'ForumFilter' => FALSE
					)
				);
			} else
				$_SESSION['forum'] = array_merge(
					array(
						'hide_comments'		=> FALSE
					),
					$_POST
				);
			
			$url = URL . substr(strtok($_SERVER['REQUEST_URI'], '?'), 1);
			header('Location: '.$url); die;
		}
		else{
			$filter_arr = array();
			if (!empty($_SESSION['forum'])){
				$filter_arr = $_SESSION['forum'];
			} elseif (isset($this->User->Attributes['Preferences']['ForumFilter']) ) {
				$filter_arr = $this->User->Attributes['Preferences']['ForumFilter'];
			}
			foreach ( $filter_arr as $key => $value ){
				switch($key){
					case 'hide_comments':
						$$key = $value;
					break;
				}
			}
		}
		
		$stmt_getDiscussion = $this->db->prepare("
			SELECT
				`Discussion`.`ID`,
				`Discussion`.`CategoryID`,
				`User`.`Alias`,
				`Discussion`.`Title`,
				IFNULL(
					`Discussion`.`Status`,
					CASE
						WHEN
							`Discussion`.`ListingID` IS NOT NULL AND
							`User`.`Vendor` = FALSE
						THEN
							'" . FORUM_REVIEW_LABEL . "'
						WHEN
							`Discussion`.`TypeID` = '" . FORUM_VENDOR_NOMINATION_TYPE_ID . "'
						THEN
							'" . FORUM_VENDOR_NOMINATION_LABEL . "'
					END
				),
				CASE
					WHEN
						`Discussion`.`ListingID` IS NOT NULL AND
						`User`.`Vendor` = FALSE
					THEN
						'yellow'
					WHEN
						`Discussion`.`TypeID` = '" . FORUM_VENDOR_NOMINATION_TYPE_ID . "'
					THEN
						'" . FORUM_VENDOR_NOMINATION_COLOR . "'
				END Color,
				CASE WHEN	`Discussion_Subscription`.`DiscussionID` IS NOT NULL
					THEN	TRUE
					ELSE	FALSE
				END,
				`Discussion`.`Closed`,
				(
					SELECT	COUNT(`ID`)
					FROM	`Discussion_Comment`
					WHERE	`DiscussionID` = `Discussion`.`ID`
				),
				`Discussion`.`UserID`,
				`DiscussionCategory`.`AmountTransacted_Comment`,
				`DiscussionCategory`.`Vendor_Comment`,
				`Discussion_Subscription`.`SeenCommentID`,
				`Discussion`.`ListingID`,
				`Listing`.`Name`,
				Vendor.`Alias`,
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
				) minimumQuantity,
				(
					`Listing`.`Inactive` = FALSE AND
					`Listing`.`Stealth` = FALSE AND
					(
						`Listing_Group`.`GroupID` IS NULL OR
						`Listing_Group`.`OutOfStock` = FALSE
					)
				) isAvailable,
				CONCAT(
					'/" . UPLOADS_PATH . "',
					listingImage.`Filename`
				) listingImage,
				`Currency`.`ISO`,
				`Currency`.`Symbol`,
				`Currency`.`1EUR`,
				`DiscussionType`.`AllowAnonymous` OR `DiscussionType`.`OnlyAnonymous`,
				`DiscussionType`.`OnlyAnonymous` OR
				(
					`DiscussionType`.`AllowAnonymous` AND
					(
						SELECT	`Anonymous`
						FROM	`Discussion_Comment` DC2
						WHERE
							DC2.`DiscussionID` = `Discussion`.`ID` AND
							DC2.`PosterID` = Me.`ID`
						ORDER BY
							DC2.`DateInserted` DESC,
							DC2.`ID` DESC
						LIMIT
							1
					) = TRUE
				),
				`DiscussionType`.`AllowImages`,
				`DiscussionType`.`OnlyAnonymous`,
				IF (
					`Me`.`Moderator`,
					`Discussion`.`Sink`,
					FALSE
				) Sink
			FROM
				`Discussion`
			INNER JOIN	`User` Me
				ON	Me.`ID` = ?
			INNER JOIN	`DiscussionCategory`
				ON	`Discussion`.`CategoryID` = `DiscussionCategory`.`ID`
			INNER JOIN	`User`
				ON	`Discussion`.`PosterID` = `User`.`ID`
			INNER JOIN
				`DiscussionType` ON
					`Discussion`.`TypeID` = `DiscussionType`.`ID`
			LEFT JOIN	`Discussion_Subscription`
				ON	`Discussion_Subscription`.`DiscussionID` = `Discussion`.`ID`
				AND	`SubscriberID` = Me.`ID`
			LEFT JOIN
				`Listing` ON
					`Discussion`.`ListingID` = `Listing`.`ID`
			LEFT JOIN
				`User` Vendor ON
					`Listing`.`VendorID` = Vendor.`ID`
			LEFT JOIN
				`Currency` ON
					`Listing`.`CurrencyID` = `Currency`.`ID`
			LEFT JOIN
				`Listing_Image` ON
					`Discussion`.`ListingID` = `Listing_Image`.`ListingID` AND
					`Listing_Image`.`Primary` = TRUE
			LEFT JOIN
				`Image` listingImage ON
					`Listing_Image`.`ImageID` = listingImage.`ID`
			LEFT JOIN
				`Unit` ON
					`Listing`.`UnitID` = `Unit`.`ID`
			LEFT JOIN
				`Listing_Group` ON
					`Listing`.`ID` = `Listing_Group`.`ListingID`
			LEFT JOIN
				`UserClass_DiscussionCategory` ON
					Me.`Moderator` = FALSE AND
					`UserClass_DiscussionCategory`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
			LEFT JOIN
				`User_Class` thisUser_Class ON
					`UserClass_DiscussionCategory`.`UserClassID` = thisUser_Class.`ClassID` AND
					Me.`ID` = thisUser_Class.`UserID`
			WHERE
				`Discussion`.`ID` = ? AND
				`DiscussionCategory`.`SiteID` = ? AND
				(
					`DiscussionCategory`.`Vendor_View` = FALSE OR
					Me.`Vendor` = TRUE
				) AND
				(
					Me.`Moderator` = TRUE OR
					`DiscussionCategory`.`UserClass_View` = FALSE OR
					thisUser_Class.`UserID` IS NOT NULL
				)
			GROUP BY
				`Discussion`.`ID`
		");
		
		if( false !== $stmt_getDiscussion ){
			$stmt_getDiscussion->bind_param(
				'iii',
				$this->User->ID,
				$discussion_id,
				$this->db->site_id
			);
			$stmt_getDiscussion->execute();
			$stmt_getDiscussion->store_result();
			$stmt_getDiscussion->bind_result(
				$discussion_id,
				$discussion_category_id,
				$discussion_poster_alias,
				$discussion_title,
				$discussion_status,
				$discussion_color,
				$discussion_subscribed,
				$discussion_closed,
				$comment_count,
				$discussion_userID,
				$amountTransacted_comment,
				$vendor_comment,
				$seenCommentID,
				$listingID,
				$listingName,
				$listingVendorAlias,
				$listingEURPrice,
				$listingPerUnit,
				$listingMinimumQuantity,
				$listingIsAvailable,
				$listingImage,
				$listingCurrencyISO,
				$listingCurrencySymbol,
				$listingCurrencyXEUR,
				$allowAnonymous,
				$previouslyCommentedAnonymously,
				$allowImages,
				$onlyAnonymous,
				$discussionSink
			);
			$stmt_getDiscussion->fetch();
			
			if($comment_count == 0)
				return false;
			
			$stmt_getComments_where = array('`Discussion_Comment`.`DiscussionID` = ?');
			
			if( isset($hide_comments) && $hide_comments ){
				if($discussion_userID)
					$stmt_getComments_where[] = "`Discussion_Comment`.`PosterID` = `Discussion`.`UserID`";
				$this->User->updatePrefs(
					array('
						ForumFilter' => array(
							'hide_comments' => TRUE
						)
					)
				);
			} else
				$this->User->updatePrefs(
					array(
						'ForumFilter' => array(
							'hide_comments' => FALSE
						)
					)
				);
			
			$sort = !$sort && $discussion_userID
				? 'id_desc'
				: $sort;
			
			switch($sort){
				case 'id_desc':
					$sort = '
						`Discussion_Comment`.`DateInserted` DESC,
						`Discussion_Comment`.`ID` DESC
					';
					$recentFirst = true;
					break;
				// case 'id_asc':
				default:
					$sort = '
						`Discussion_Comment`.`DateInserted` ASC,
						`Discussion_Comment`.`ID` ASC
					';
					$recentFirst = false;
			}
			
			$stmt_getComments = $this->db->prepare("
				SELECT
					`Discussion_Comment`.`ID`,
					`User`.`ID`,
					`User`.`Alias`,
					CONCAT(
						'/" . UPLOADS_PATH . "',
						`Image`.`Filename`
					) Image,
					`User`.`Signature`,
					`Discussion_Comment`.`DateInserted`,
					`Discussion_Comment`.`DateUpdated`,
					(
						SELECT	SUM(`Vote`)
						FROM	`Discussion_Vote`
						WHERE	`Discussion_Vote`.`CommentID` = `Discussion_Comment`.`ID`
					),
					IFNULL(
						(
							SELECT	`Vote`
							FROM	`Discussion_Vote`
							WHERE
								`Discussion_Vote`.`CommentID` = `Discussion_Comment`.`ID`
							AND	`Discussion_Vote`.`VoterID` = Me.`ID`
							LIMIT	1
						),
						0
					),
					`Content`,
					`Discussion_Comment`.`HTML`,
					`Discussion_Comment_Report`.`CommentID` IS NOT NULL,
					IF (
						`Discussion_Comment`.`Anonymous`,
						(
							SELECT	COUNT(DISTINCT DC2.`PosterID`)
							FROM	`Discussion_Comment` DC2
							WHERE
								DC2.`Anonymous` = TRUE AND
								DC2.`DiscussionID` = `Discussion_Comment`.`DiscussionID` AND
								(
									DC2.`DateInserted` < (
										SELECT	`DateInserted`
										FROM	`Discussion_Comment` DC3
										WHERE
											DC3.`Anonymous` = TRUE AND
											DC3.`PosterID` = `Discussion_Comment`.`PosterID` AND
											DC3.`DiscussionID` = `Discussion_Comment`.`DiscussionID`
										ORDER BY
											`DateInserted` ASC,
											`ID` ASC
										LIMIT
											1
									) OR
									(
										DC2.`DateInserted` = (
											SELECT	`DateInserted`
											FROM	`Discussion_Comment` DC3
											WHERE
												DC3.`Anonymous` = TRUE AND
												DC3.`PosterID` = `Discussion_Comment`.`PosterID` AND
												DC3.`DiscussionID` = `Discussion_Comment`.`DiscussionID`
											ORDER BY
												`DateInserted` ASC,
												`ID` ASC
											LIMIT
												1
										) AND
										DC2.`ID` < (
											SELECT	`ID`
											FROM	`Discussion_Comment` DC3
											WHERE
												DC3.`PosterID` = `Discussion_Comment`.`PosterID` AND
												DC3.`DiscussionID` = `Discussion_Comment`.`DiscussionID`
											ORDER BY
												`DateInserted` ASC,
												`ID` ASC
											LIMIT
												1
										)
									)
								)
						),
						NULL
					) anonymousCommenterID,
					IF (
						Me.`Moderator`,
						(
							SELECT
								GROUP_CONCAT(DISTINCT reporter.`Alias` ORDER BY reporter.`Alias` SEPARATOR ',')
							FROM
								`Discussion_Comment_Report`
							INNER JOIN
								`User` reporter ON
									reporter.`ID` = `Discussion_Comment_Report`.`UserID`
							WHERE
								`CommentID` = `Discussion_Comment`.`ID` AND
								`Discussion_Comment_Report`.`Cleared` = FALSE AND
								reporter.`Banned` = FALSE
						),
						FALSE
					) reporterAliases
				FROM
					`Discussion_Comment`
				INNER JOIN
					`User` Me ON
						Me.`ID` = ?
				INNER JOIN
					`User` ON
						`Discussion_Comment`.`PosterID` = `User`.`ID`
				INNER JOIN
					`Discussion` ON
						`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
				LEFT JOIN
					`Discussion_Comment_Report` ON
						`Discussion_Comment`.`ID` = `Discussion_Comment_Report`.`CommentID` AND
						Me.`ID` = `Discussion_Comment_Report`.`UserID`
				LEFT JOIN
					`Image` ON
						`User`.`ImageID` = `Image`.`ID`
				WHERE
					" . implode(' AND ', $stmt_getComments_where) . "
				ORDER BY
					" . $sort . "
				LIMIT	?, " . DISCUSSION_COMMENTS_PER_PAGE . "
			");
			
			if ($stmt_getDiscussion->num_rows == 1){
				if (ceil($comment_count/DISCUSSION_COMMENTS_PER_PAGE) < $page){
					$offset = 0;
					$this->User->Notifications->quick('FatalError', 'Invalid Page');
					return false;
				} else {
					$offset = DISCUSSION_COMMENTS_PER_PAGE*($page - 1);
				}
				
				$stmt_getComments->bind_param('iii', $this->User->ID, $discussion_id, $offset);
				$stmt_getComments->execute();
				$stmt_getComments->store_result();
				
				if ($stmt_getComments->num_rows > 0){
					$stmt_getComments->bind_result(
						$comment_id,
						$comment_poster_id,
						$comment_poster_alias,
						$comment_poster_image,
						$comment_poster_signature,
						$comment_date_inserted,
						$comment_date_updated,
						$comment_votes,
						$comment_user_vote,
						$comment_content_raw,
						$comment_content,
						$comment_reported,
						$anonymousCommenterID,
						$reporterAliases
					);
					
					$comments = array();
					$userFlairs = [];
					while ($stmt_getComments->fetch()){
						$comment_content = $comment_content ?: $this->fixUnformattedContent($comment_id, $comment_content_raw);
						
						$dateInserted	= date('F j, Y', strtotime($comment_date_inserted));
						$dateUpdated	= date('F j, Y', strtotime($comment_date_updated));
						$comments[] = array(
							'ID'			=> $comment_id,
							'posterAlias'		=> 
								$anonymousCommenterID !== NULL
									? '<em>Anonymous</em>' . (
										$comment_poster_alias == $this->User->Alias ||
										$this->User->IsMod
											? ' (' . (
												$this->User->IsMod
													? $comment_poster_alias
													: 'You'
											) . ')'
											: false
									)
									: $comment_poster_alias,
							'isPoster'		=> $comment_poster_alias == $this->User->Alias,
							'posterImage'		=>
								$anonymousCommenterID === NULL
									? NXS::getPictureVariant($comment_poster_image, IMAGE_THUMBNAIL_SUFFIX)
									: false,
							'posterSignature'	=>
								$anonymousCommenterID === NULL
									? $comment_poster_signature
									: false,
							'dateInserted'		=> $dateInserted,
							'dateUpdated'		=> $dateInserted == $dateUpdated ? FALSE : $dateInserted,
							'votes'			=> $comment_votes,
							'userVote'		=> $comment_user_vote,
							'content'		=> $comment_content,
							'reported'		=> $comment_reported,
							'rawContent'		=>
								$this->User->IsMod
									?	$comment_content_raw
									:	preg_replace('/(\[quote[^\]]*) date=[^\]]*(\])/is', '$1$2', $comment_content_raw),
							'posterFlairs'		=>
								$anonymousCommenterID === NULL
									? $this->fetchUserFlairs(
										$comment_poster_id,
										true,
										false,
										$userFlairs,
										true
									)
									: false,
							'anonymousCommenterID'	=> $anonymousCommenterID,
							'anonymousHueRotateDeg'	=>
								$anonymousCommenterID !== NULL
									? (
										NXS::partitionNumber($anonymousCommenterID) +
										NXS::partitionNumber(substr($discussion_id, -1))
									) % 360
									: false,
							'images'		=> $this->_getDiscussionCommentImages($comment_id),
							'reporterAliases'	=> $reporterAliases
						);
					}
					
					$highestCommentID =
						$recentFirst
							? $comments[0]['ID']
							: array_pop((array_slice($comments, -1)))['ID'];
					
					$this->updateSubscription($discussion_id, $highestCommentID);
					
					$closed =
						!$this->User->IsAdmin &&
						!$this->User->IsMod &&
						(
							$discussion_closed ||
							(
								$vendor_comment &&
								!$this->User->IsVendor
							) ||
							(
								$amountTransacted_comment > $this->User->Attributes['TotalTransacted'] &&
								$this->User->Info(0, 'PostingPrivileges') == FALSE
							)
						);
					
					$listing = false;
					if ($listingName){
						$currency = [
							'ISO'		=> $listingCurrencyISO,
							'Symbol'	=> $listingCurrencySymbol,
							'XEUR'		=> $listingCurrencyXEUR
						];
						$listing = [
							'name'		=> $listingName,
							'vendorAlias'	=> $listingVendorAlias,
							'B36'		=> NXS::getB36($listingID),
							'price'		=>
								NXS::formatPrice($currency, $listingEURPrice) .
								(
									$listingPerUnit
										? ' / ' . $listingPerUnit
										: false
								),
							'price_crypto'	=>
								$listingMinimumQuantity
									? NXS::formatDecimal($listingMinimumQuantity) . ' ' . $listingPerUnit . ' minimum'
									: $this->User->Cryptocurrency->formatPrice($listingEURPrice),
							'image'		=> NXS::getPictureVariant($listingImage, IMAGE_THUMBNAIL_SUFFIX),
							'available'	=> $listingIsAvailable
						];
					}
					
					return array(
						$comment_count,
						array(
							'ID'		=> $discussion_id,
							'categoryID'	=> $discussion_category_id,
							'posterAlias'	=> $discussion_poster_alias,
							'title'		=> $discussion_title,
							'status'	=> $discussion_status,
							'color'		=> $discussion_color,
							'subscribed'	=> ($discussion_subscribed == 1),
							'closed'	=> $closed,
							'comments'	=> $comments,
							'userID'	=> $discussion_userID,
							'seenCommentID'	=> $seenCommentID,
							'listing'	=> $listing,
							'allowAnonymous' => $allowAnonymous,
							'previouslyCommentedAnonymously' => $previouslyCommentedAnonymously,
							'allowImages'	=> $allowImages,
							'onlyAnonymous' => $onlyAnonymous,
							'sink'		=> $discussionSink
						)
					);
				}
			}
		}
		
		return false;
	}
	
	private function _getDiscussionCommentImages($discussionCommentID){
		if (
			$images = $this->db->qSelect(
				"
					SELECT
						`Image`.`ID`,
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) image
					FROM
						`DiscussionComment_Image`
					INNER JOIN
						`Image` ON
							`DiscussionComment_Image`.`ImageID` = `Image`.`ID`
					WHERE
						`DiscussionComment_Image`.`DiscussionCommentID` = ?
				",
				'i',
				[$discussionCommentID]
			)
		)
			return	array_map(
					function($image){
						$image['thumbnail'] = NXS::getPictureVariant($image['image'], IMAGE_THUMBNAIL_SUFFIX);
						return $image;
					},
					$images
				);
		
		return false;
	}
	
	public function editUserFlair(
		$userAlias,
		$classID,
		$forumText
	){
		return	$this->db->qQuery(
				"
					UPDATE
						`User_Class`
					INNER JOIN
						`User` ON
							`User_Class`.`UserID` = `User`.`ID`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					LEFT JOIN
						`User_Class` thisUser_Class ON
							thisUser.`Moderator` = FALSE AND
							`User_Class`.`ClassID` = " . USER_CLASS_ID_STAR_BUYERS . " AND
							thisUser_Class.`UserID` = thisUser.`ID` AND
							thisUser_Class.`ClassID` = " . USER_CLASS_ID_STAR_BUYERS . " AND
							(
								(
									thisUser.`ID` = `User_Class`.`UserID` AND
									thisUser_Class.`Rank` >= " . FORUM_STAR_MEMBER_PRIVILEGES_EDIT_OWN_FLAIR_RANK . "
								) OR
								(
									thisUser_Class.`Rank` >= " . FORUM_STAR_MEMBER_PRIVILEGES_ADD_NEW_FLAIR_RANK . " AND
									`User_Class`.`ForumText` IS NULL
								)
							)
					SET
						`User_Class`.`ForumText` = ?,
						`User_Class`.`SetByUserID` = thisUser.`ID`
					WHERE
						`User`.`Alias` = ? AND
						`User_Class`.`ClassID` = ? AND
						(
							thisUser.`Moderator` = TRUE OR
							thisUser_Class.`UserID` IS NOT NULL
						)
				",
				'issi',
				[
					$this->User->ID,
					$forumText,
					$userAlias,
					$classID
				]
			);
	}
	
	private function fetchUserFlairs(
		$userID,
		$getIcons = true,
		$limit = false,
		&$fetchedUserFlairs = [],
		$checkEditable = false
	){
		if (isset($fetchedUserFlairs[$userID]) )
			return $fetchedUserFlairs[$userID];
		
		$stmtTypes = 'i';
		$stmtParams = [$userID];
		
		if ($checkEditable){
			$stmtTypes .= 'i';
			array_unshift(
				$stmtParams,
				$this->User->ID
			);
		}
		
		if ($limit){
			$stmtTypes .= 'i';
			$stmtParams[] = $limit;
		}
			
		$userFlairs = $this->db->qSelect(
			"
				SELECT
					IFNULL(
						`User_Class`.`ForumText`,
						`UserClass`.`ForumText`
					) text,
					IFNULL(
						`User_Class`.`ForumColor`,
						`UserClass`.`ForumColor`
					) color
					" . (
						$getIcons
							? ", IFNULL(
								`User_Class`.`ForumIcon`,
								`UserClass`.`ForumIcon`
							) icon"
							: false
					) . ",
					`User_Class`.`Rank`,
					" . (
						$checkEditable
							? "	`User_Class`.`ForumText` IS NOT NULL hasCustomText,
								`User_Class`.`ClassID` classID,
								`User`.`ID` IS NOT NULL
								"
							: 'FALSE'
					) . " isEditable
				FROM
					`User_Class`
				INNER JOIN
					`UserClass` ON
						`User_Class`.`ClassID` = `UserClass`.`ID`
				" . (
					$checkEditable
						? "
							INNER JOIN
								`User` thisUser ON
									thisUser.`ID` = ?
							LEFT JOIN
								`User_Class` thisUser_Class ON
									thisUser.`Moderator` = FALSE AND
									`User_Class`.`ClassID` = " . USER_CLASS_ID_STAR_BUYERS . " AND
									thisUser_Class.`UserID` = thisUser.`ID` AND
									thisUser_Class.`ClassID` = " . USER_CLASS_ID_STAR_BUYERS . " AND
									(
										(
											thisUser.`ID` = `User_Class`.`UserID` AND
											thisUser_Class.`Rank` >= " . FORUM_STAR_MEMBER_PRIVILEGES_EDIT_OWN_FLAIR_RANK . "
										) OR
										(
											thisUser_Class.`Rank` >= " . FORUM_STAR_MEMBER_PRIVILEGES_ADD_NEW_FLAIR_RANK . " AND
											`User_Class`.`ForumText` IS NULL
										)
									)
							LEFT JOIN
								`User` ON
									(
										thisUser.`Moderator` = TRUE OR
										thisUser_Class.`UserID` IS NOT NULL
									) AND
									`User`.`ID` = `User_Class`.`UserID`
						"
						: false
				) . "
				WHERE
					`User_Class`.`UserID` = ? AND
					IFNULL(`User_Class`.`Rank`, 1) != 0
				ORDER BY
					`User_Class`.`Primary` DESC
				" . (
					$limit
						? 'LIMIT ?'
						: false
				) . "
			",
			$stmtTypes,
			$stmtParams
		);
		
		if ($userFlairs && $limit == 1)
			$userFlairs = $userFlairs[0];
		
		$fetchedUserFlairs[$userID] = $userFlairs;
		return $userFlairs;
	}
	
	public function getReviewableListings(){
		return	$this->db->qSelect(
				"
					SELECT DISTINCT
						`Listing`.`ID`,
						CONCAT(
							Vendor.`Alias`,
							' ',
							`Listing`.`Name`
						) Name
					FROM
						`Transaction`
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
					INNER JOIN
						`Listing` ON
							`Transaction`.`ListingID` = `Listing`.`ID`
					INNER JOIN
						`User` Vendor ON
							`Listing`.`VendorID` = Vendor.`ID`
					LEFT JOIN
						`Discussion` ON
							`Discussion`.`PosterID` = `Transaction`.`BuyerID` AND
							(
								`Discussion`.`ListingID` IN (
									SELECT	`ListingID`
									FROM	`Listing_Group`
									WHERE	`GroupID` = (
										SELECT	`GroupID`
										FROM	`Listing_Group`
										WHERE	`ListingID` = `Transaction`.`ListingID`
									)
								) OR
								`Discussion`.`ListingID` = `Transaction`.`ListingID`
							) AND
							(
								SELECT
									`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT
									1
							) > NOW() - INTERVAL 3 MONTH
					WHERE
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction_Event`.`Event` = 'accepted' AND
						`Transaction_Event`.`Date` > NOW() - INTERVAL 3 MONTH AND
						`Discussion`.`ID` IS NULL
				",
				'i',
				[$this->User->ID]
			);
	}
	
	public function hasReviewableListing(){
		return	$this->db->qSelect(
				"
					SELECT
						`Transaction`.`ListingID`
					FROM
						`Transaction`
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
					LEFT JOIN
						`Discussion` ON
							`Discussion`.`PosterID` = `Transaction`.`BuyerID` AND
							(
								`Discussion`.`ListingID` IN (
									SELECT	`ListingID`
									FROM	`Listing_Group`
									WHERE	`GroupID` = (
										SELECT	`GroupID`
										FROM	`Listing_Group`
										WHERE	`ListingID` = `Transaction`.`ListingID`
									)
								) OR
								`Discussion`.`ListingID` = `Transaction`.`ListingID`
							) AND
							(
								SELECT
									`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT
									1
							) > NOW() - INTERVAL 3 MONTH
					WHERE
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction_Event`.`Event` = 'accepted' AND
						`Transaction_Event`.`Date` > NOW() - INTERVAL 3 MONTH AND
						`Discussion`.`ID` IS NULL
					LIMIT
						1
				",
				'i',
				[$this->User->ID]
			);
	}
	
	public function fetchBlogsWithPostingPrivileges($blogAlias = FALSE){
		if(
			$blogs = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Blog`.`Title`,
						`Blog`.`Alias`,
						`DiscussionCategory`.`Name` CategoryName,
						`DiscussionCategory`.`Alias` CategoryAlias,
						`DiscussionCategory`.`ID` CategoryID,
						`Blog`.`UserID` = `User`.`ID` MyBlog
					FROM
						`Blog`
					INNER JOIN
						`User` ON
							`User`.`ID` = ?
					INNER JOIN
						`Blog_PostingPrivileges` ON
							(
								`Blog`.`ID` = `Blog_PostingPrivileges`.`BlogID` AND
								`User`.`ID` = `Blog_PostingPrivileges`.`PosterID`
							) OR
							(
								`User`.`Admin` = TRUE OR
								`User`.`Moderator` = TRUE
							)
					INNER JOIN
						`DiscussionCategory` ON
							`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
				",
				'i',
				array(
					$this->User->ID
				)
			)
		){
			$blogCategories = array();
			$myBlog = FALSE;
			
			foreach($blogs as $blog){
				$blogCategories[ $blog['CategoryAlias'] ][] = $blog;
				
				if( $blog['Alias'] == $blogAlias )
					$myBlog = $blog;
			}
			
			return array($blogCategories, $myBlog);
			
		}
			
		return FALSE;
	}
	
	public function fetchBlog($blogAlias, $sort, $page){
		$blogs = 
			$blogAlias
				? $this->db->qSelect(
					"
						SELECT
							`Blog`.`Title`,
							`Blog`.`Alias`,
							`Blog`.`ID`,
							(
								SELECT	COUNT(`BlogPost`.`ID`)
								FROM
									`BlogPost`
								WHERE
									`BlogPost`.`BlogID` = `Blog`.`ID`
							) PostCount,
							IF(
								ISNULL(`Blog_Subscription`.`BlogID`),
								FALSE,
								TRUE
							) Subscribed,
							`Blog`.`DiscussionCategoryID`
						FROM
							`Blog`
						INNER JOIN
							`DiscussionCategory` ON
								`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
						INNER JOIN
							`User` ON
								`User`.`ID` = ?
						LEFT JOIN
							`Blog_Subscription`
								ON
									`Blog_Subscription`.`SubscriberID` = `User`.`ID` AND
									`Blog`.`ID` = `Blog_Subscription`.`BlogID`
						WHERE
							`Blog`.`Alias` = ? AND
							(
								`DiscussionCategory`.`Vendor_View` = FALSE OR
								`User`.`Vendor` = TRUE
							)
					
					",
					'is',
					[
						$this->User->ID,
						$blogAlias
					]
				)
				: $this->db->qSelect(
					"
						SELECT
							(
								SELECT	COUNT(`BlogPost`.`ID`)
								FROM
									`BlogPost`
								INNER JOIN
									`Blog` ON
										`BlogPost`.`BlogID` = `Blog`.`ID`
								INNER JOIN
									`DiscussionCategory` ON
										`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
								INNER JOIN
									`User` ON
										`User`.`ID` = ?
								WHERE
									`DiscussionCategory`.`Vendor_View` = FALSE OR
									`User`.`Vendor` = TRUE
							) PostCount,
							FALSE ID
					",
					'i',
					[
						$this->User->ID
					]
				);
		
		if($blog = $blogs[0]){
			if($blog['PostCount'] > 0){
				$offset = NXS::getOffset(
					$blog['PostCount'],
					BLOG_POSTS_PER_PAGE,
					$page
				);
				
				$blog['BlogPosts'] = $this->fetchBlogPosts($blog['ID'], $sort, $offset);
			} else
				$blog['BlogPosts'] = FALSE;
			
			return $blog;
		} else
			return FALSE;
	}
	
	public function fetchBlogPosts($blogID, $sort, $offset, $quantity = BLOG_POSTS_PER_PAGE){
		switch($sort){
			case 'id_asc':
				$orderBy = '
					`BlogPost`.`DateInserted` ASC,
					`BlogPost`.`ID` ASC
				';
			break;
			//case 'id_desc':
			default:
				$orderBy = '
					`BlogPost`.`DateInserted` DESC,
					`BlogPost`.`ID` DESC
				';
			break;
		}
		
		$blogPosts = $this->db->qSelect(
			"
				SELECT
					`BlogPost`.`ID`,
					(
						( # User is Admin or Moderator?
							thisUser.`Admin` OR
							thisUser.`Moderator`
						) IS FALSE AND
						`BlogPost`.`Closed`
					) Closed,
					`BlogPost`.`Status`,
					`BlogPost`.`Title` Title,
					IF(
						( # is editable by user?
							thisUser.`Admin` OR
							thisUser.`Moderator` OR
							thisUser.`ID` = poster.`ID`
						)= TRUE,
						`BlogPost`.`Content`,
						FALSE
					) RawContent,
					`BlogPost`.`HTML`,
					`BlogPost`.`DateInserted`,
					`BlogPost`.`DateUpdated`,
					poster.`Signature` PosterSignature,
					poster.`Alias` PosterAlias,
					CONCAT(
						'/" . UPLOADS_PATH . "',
						`Image`.`Filename`
					) PosterImage,
					(
						SELECT	COUNT(`BlogPostComment`.`ID`)
						FROM
							`BlogPostComment`
						WHERE	`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
					) CommentCount,
					`Blog`.`Title` BlogTitle,
					`Blog`.`Alias` BlogAlias
				FROM
					`BlogPost`
				INNER JOIN
					`User` thisUser ON
						thisUser.`ID` = ?
				INNER JOIN
					`User` poster ON
						`BlogPost`.`PosterID` = poster.`ID`
				INNER JOIN
					`Blog` ON
						`BlogPost`.`BlogID` = `Blog`.`ID`
				INNER JOIN
					`DiscussionCategory` ON
						`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
				LEFT JOIN
					`Image` ON
						poster.`ImageID` = `Image`.`ID`
				WHERE
					(
						`DiscussionCategory`.`Vendor_View` = FALSE OR
						thisUser.`Vendor` = TRUE
					) " . (
						$blogID
							? "AND `BlogPost`.`BlogID` = ?"
							: false
					) . "
				ORDER BY
					" . $orderBy . "
				LIMIT
					?,	# offset
					?	# quantity
			",
			$blogID ? 'iiii' : 'iii',
			$blogID
				? [
					$this->User->ID,
					$blogID,
					$offset,
					$quantity
				]
				: [
					$this->User->ID,
					$offset,
					$quantity
				]
		);
		$blogPosts = array_map(
			function($array){
				return array_merge(
					$array,
					array(
						'PosterImage'	=> NXS::getPictureVariant($array['PosterImage'], IMAGE_THUMBNAIL_SUFFIX),
						'DateInserted'	=> date('F j, Y', strtotime($array['DateInserted']))
					)
				);
			},
			$blogPosts
		);
		
		if($blogPosts){
			foreach($blogPosts as $key => $blogPost){
				if($blogPost['CommentCount'] > 0){
					$blogPost['Comments'] = $this->fetchBlogComments(
						$blogPost['ID'],
						SORT_BY_BLOG_POSTS_COMMENTS,
						0,
						BLOG_POSTS_COMMENTS_COUNT
					);
					
					$blogPost['Comments'] = array_reverse($blogPost['Comments']);
				} else
					$blogPost['Comments'] = FALSE;
					
				$blogPosts[$key] = $blogPost;
			}
		
			return $blogPosts;
		} else {
			return FALSE;
		}
	}
	
	public function editBlogPost($blogPostID){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['blog_post'][$key] = htmlspecialchars($value);
			
			$title = trim(htmlspecialchars($_POST['title']));
			
			unset($_SESSION['blog_feedback']);
			
			if ( !empty($title) && !preg_match("/[\w][^\n]{1," . (MAX_LENGTH_DISCUSSION_TITLE - 1) . "}/", $title) )
				$_SESSION['blog_feedback']['title'] = 'Titles must be no longer than 100 characters.';
			
			if( empty($title) )
				$title = NULL;
			
			$content = $_POST['content'];
			if( strlen($content) < 1 )
				$_SESSION['blog_feedback']['content'] = 'Content cannot be empty';
			
			$html = NXS::formatText($content, $this->db);
			$content = htmlspecialchars($content);
			
			if(
				$this->db->qQuery(
					"
						UPDATE
							`BlogPost`
						INNER JOIN
							`User` thisUser ON
								thisUser.`ID` = ?
						SET
							`BlogPost`.`Title` = ?,
							`BlogPost`.`Content` = ?,
							`BlogPost`.`HTML` = ?,
							`BlogPost`.`DateUpdated` = NOW()
						WHERE
							`BlogPost`.`ID` = ? AND
							(
								`BlogPost`.`PosterID` = thisUser.`ID` OR
								thisUser.`Moderator` OR
								thisUser.`Admin`
							)
					",
					'isssi',
					[
						$this->User->ID,
						$title,
						$content,
						$html,
						$blogPostID
					]
				)
			){
				unset(
					$_SESSION['blog_feedback'],
					$_SESSION['blog_post']
				);
				
				$this->updateBlogPostForumItem($blogPostID);
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function editBlogPostComment($blogPostCommentID){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['blog_post_comment'][$key] = htmlspecialchars($value);
			
			$content = $_POST['content-' . $blogPostCommentID];
			if( strlen($content) < 1 )
				return false;
			
			$html = NXS::formatText($content, $this->db);
			$content = htmlspecialchars($content);
			
			if(
				$this->db->qQuery(
					"
						UPDATE
							`BlogPostComment`
						INNER JOIN
							`User` thisUser ON
								thisUser.`ID` = ?
						SET
							`BlogPostComment`.`Content` = ?,
							`BlogPostComment`.`HTML` = ?
						WHERE
							`BlogPostComment`.`ID` = ? AND
							(
								`BlogPostComment`.`CommenterID` = thisUser.`ID` OR
								thisUser.`Moderator` OR
								thisUser.`Admin`
							)
					",
					'issi',
					[
						$this->User->ID,
						$content,
						$html,
						$blogPostCommentID
					]
				)
			){
				unset(
					$_SESSION['blog_post_comment']
				);
				
				list(
					$blogPostID,
					$blogPostPage
				) = $this->findBlogPostComment($blogPostCommentID);
				$this->updateBlogPostForumItem($blogPostID);
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function fetchBlogPost(
		$ID,
		$commentSort,
		$commentPage,
		$commentQuantity = BLOG_COMMENTS_PER_PAGE
	){
		if(
			$blogPosts = $this->db->qSelect(
				"
					SELECT
						`BlogPost`.`ID`,
						`BlogPost`.`BlogID`,
						`BlogPost`.`Title`,
						IF(
							( # is editable by user?
								thisUser.`Admin` OR
								thisUser.`Moderator` OR
								thisUser.`ID` = poster.`ID`
							)= TRUE,
							`BlogPost`.`Content`,
							FALSE
						) RawContent,
						`BlogPost`.`Status`,
						`BlogPost`.`HTML`,
						`BlogPost`.`DateInserted`,
						`BlogPost`.`DateUpdated`,
						(
							SELECT	COUNT(`BlogPostComment`.`ID`)
							FROM	`BlogPostComment`
							WHERE	`BlogPostComment`.`BlogPostID` = `BlogPost`.`ID`
						) CommentCount,
						poster.`ID` PosterID,
						poster.`Alias` PosterAlias,
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) PosterImage,
						poster.`Signature` PosterSignature,
						`Blog`.`Title` BlogTitle,
						`Blog`.`Alias` BlogAlias,
						`Blog`.`DiscussionCategoryID`,
						`BlogPost_Subscription`.`BlogPostID` Subscribed,
						`BlogPost`.`Closed`,
						`BlogPost_Subscription`.`SeenCommentID`
					FROM
						`BlogPost`
					INNER JOIN
						`User` thisUser
							ON
								thisUser.`ID` = ?
					INNER JOIN
						`User` poster
							ON
								poster.`ID` = `BlogPost`.`PosterID`
					INNER JOIN
						`Blog` ON
							`BlogPost`.`BlogID` = `Blog`.`ID`
					INNER JOIN
						`DiscussionCategory` ON
							`Blog`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
					LEFT JOIN
						`BlogPost_Subscription` ON
							`BlogPost`.`ID` = `BlogPost_Subscription`.`BlogPostID` AND
							thisUser.`ID` = `BlogPost_Subscription`.`SubscriberID`
					LEFT JOIN
						`Image` ON
							poster.`ImageID` = `Image`.`ID`
					WHERE
						`BlogPost`.`ID` = ? AND
						(
							`DiscussionCategory`.`Vendor_View` = FALSE OR
							thisUser.`Vendor` = TRUE
						)
				",
				'ii',
				array(
					$this->User->ID,
					$ID
				)
			)
		){
			$fetchedUserFlairs = [];
			$blogPosts = array_map(
				function($array) use (&$fetchedUserFlairs){
					return array_merge(
						$array,
						array(
							'PosterImage'	=> NXS::getPictureVariant($array['PosterImage'], IMAGE_THUMBNAIL_SUFFIX),
							'DateInserted'	=> date('F j, Y', strtotime($array['DateInserted'])),
							'PosterFlairs'	=> $this->fetchUserFlairs(
								$array['PosterID'],
								true,
								false,
								$fetchedUserFlairs,
								true
							)
						)
					);
				},
				$blogPosts
			);
			
			if($blogPost = $blogPosts[0]){
				if($blogPost['CommentCount'] > 0){
					$commentOffset = NXS::getOffset(
						$blogPost['CommentCount'],
						BLOG_COMMENTS_PER_PAGE,
						$commentPage
					);
					
					$blogPost['Comments'] = $this->fetchBlogComments(
						$ID,
						$commentSort,
						$commentOffset,
						$commentQuantity
					);
					
				} else
					$blogPost['Comments'] = FALSE;
				
				$this->updateBlogSubscription(
					$blogPost['BlogID'],
					$ID
				);
				return $blogPost;
			}
		}
		
		return FALSE;
	}
	
	public function updateBlogSubscription($blogID, $postID){
		$this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Notification_User` (
						`NotificationID`,
						`UserID`
					)
				SELECT
					`Notification`.`ID`,
					?
				FROM
					`Notification`
				WHERE
					`Notification`.`BlogPostID` = ?
			",
			'ii',
			[
				$this->User->ID,
				$postID
			]
		);
		
		return	$this->db->qQuery(
				"
					UPDATE
						`Blog_Subscription`
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `Blog_Subscription`.`SubscriberID` AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
					SET
						`Blog_Subscription`.`SeenPostID` = ?,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Blog_Subscription`.`BlogID` = ? AND
						`Blog_Subscription`.`SubscriberID` = ? AND
						(
							SELECT	`DateInserted`
							FROM	`BlogPost`
							WHERE	`ID` = ?
						) >
						IFNULL(
							(
								SELECT	`DateInserted`
								FROM	`BlogPost`
								WHERE	`ID` = `Blog_Subscription`.`SeenPostID`
							),
							'" . MYSQL_DATETIME_RANGE_LOWEST . "'
						)
				",
				'iiii',
				[
					$postID,
					$blogID,
					$this->User->ID,
					$postID
				]
			);
	}
	
	public function fetchBlogComments(
		$blogPostID,
		$sort,
		$offset,
		$quantity = BLOG_COMMENTS_PER_PAGE
	){
		switch($sort){
			case 'id_asc':
				$orderBy = '
					`BlogPostComment`.`DateInserted` ASC,
					`BlogPostComment`.`ID` ASC';
			break;
			//case 'id_desc':
			default:
				$orderBy = '
					`BlogPostComment`.`DateInserted` DESC,
					`BlogPostComment`.`ID` DESC
				';
			break;
		}
		
		if(
			$comments = $this->db->qSelect(
				"
					SELECT
						`BlogPostComment`.`ID`,
						`BlogPostComment`.`HTML`,
						IF(
							(
								thisUser.`Moderator` = TRUE ||
								thisUser.`ID` = commenter.`ID`
							),
							`BlogPostComment`.`Content`,
							NULL
						) RawContent,
						`BlogPostComment`.`DateInserted`,
						commenter.`ID` CommenterID,
						commenter.`Alias` CommenterAlias,
						CONCAT(
							'/" . UPLOADS_PATH . "',
							`Image`.`Filename`
						) CommenterImage,
						IFNULL(
							`BlogPostComment_Vote`.`Vote`,
							FALSE
						) Vote,
						IFNULL(
							(
								SELECT	SUM(`BlogPostComment_Vote`.`Vote`)
								FROM	`BlogPostComment_Vote`
								WHERE	`BlogPostComment_Vote`.`BlogPostCommentID` = `BlogPostComment`.`ID`
							),
							0
						) Score,
						commenter.`Vendor` isVendor,
						commenter.`Moderator` isModerator
					FROM
						`BlogPostComment`
					INNER JOIN
						`User` thisUser
							ON
								thisUser.`ID` = ?
					INNER JOIN
						`User` commenter
							ON
								`BlogPostComment`.`CommenterID` = commenter.`ID`
					LEFT JOIN
						`BlogPostComment_Vote`
							ON
								thisUser.`ID` = `BlogPostComment_Vote`.`VoterID` AND
								`BlogPostComment_Vote`.`BlogPostCommentID` = `BlogPostComment`.`ID`
					LEFT JOIN
						`Image` ON
							commenter.`ImageID` = `Image`.`ID`
					WHERE
						`BlogPostComment`.`BlogPostID` = ?
					ORDER BY
						" . $orderBy . "
					LIMIT
						?, # offset
						? # quantity
				",
				'iiii',
				array(
					$this->User->ID,
					$blogPostID,
					$offset,
					$quantity
				)
			)
		){
			$userFlairs = [];
			$comments = array_map(
				function($comment) use (&$userFlairs){
					switch(true){
						case $comment['isModerator']:
							$commenterColor = 'red';
						break;
						case $comment['isVendor']:
							$commenterColor = 'green';
						break;
						default:
							$commenterColor = 'blue';
					}
					return array_merge(
						$comment,
						[
							'CommenterImage' 	=> NXS::getPictureVariant($comment['CommenterImage'], IMAGE_THUMBNAIL_SUFFIX),
							'CommenterColor' 	=> $commenterColor,
							'CommenterFlair'	=> $this->fetchUserFlairs(
								$comment['CommenterID'],
								false,
								1,
								$userFlairs
							),
							'DateInserted'		=> date('M j, \'y', strtotime($comment['DateInserted']))
						]
					);
				},
				$comments
			);
			
			switch($sort){
				case 'id_asc':
					$commentWithHighestID = array_pop((array_slice($comments, -1)));
				break;
				//case 'id_desc':
				default:
					$commentWithHighestID = $comments[0];
				break;
			}
			
			$this->updateBlogPostSubscription(
				$blogPostID,
				$commentWithHighestID['ID']
			);
			
			return $comments;
		} else
			return FALSE;
	}
	
	public function updateBlogPostSubscription($blogPostID, $commentID){
		return	$this->db->qQuery(
				"
					UPDATE
						`BlogPost_Subscription`
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `BlogPost_Subscription`.`SubscriberID` AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
					SET
						`BlogPost_Subscription`.`SeenCommentID` = ?,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`BlogPost_Subscription`.`BlogPostID` = ? AND
						`BlogPost_Subscription`.`SubscriberID` = ? AND
						(
							SELECT	`DateInserted`
							FROM	`BlogPostComment`
							WHERE	`ID` = ?
						) >
						IFNULL(
							(
								SELECT	`DateInserted`
								FROM	`BlogPostComment`
								WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
							),
							'" . MYSQL_DATETIME_RANGE_LOWEST . "'
						)
				",
				'iiii',
				[
					$commentID,
					$blogPostID,
					$this->User->ID,
					$commentID
				]
			);
	}
	
	public function findBlogPostCommenterAlias($blogPostCommentID){
		if(
			$blogPostCommenterAliases = $this->db->qSelect(
				"
					SELECT
						`User`.`Alias` Alias
					FROM
						`BlogPostComment`
					INNER JOIN
						`User` ON
							`BlogPostComment`.`CommenterID` = `User`.`ID`
					WHERE
						`BlogPostComment`.`ID` = ?
				",
				'i',
				array(
					$blogPostCommentID
				)
			)
		)
			return $blogPostCommenterAliases[0]['Alias'];
		
		return FALSE;
	}
	
	public function getBlogPostCommentContent($blogPostCommentID){
		if(
			$blogPostCommentContents = $this->db->qSelect(
				"
					SELECT
						`BlogPostComment`.`Content`
					FROM
						`BlogPostComment`
					WHERE
						`BlogPostComment`.`ID` = ?
				",
				'i',
				[
					$blogPostCommentID
				]
			)
		)
			return $blogPostCommentContents[0]['Content'];
		
		return FALSE;
	}
	
	public function findBlogPostComment($blogPostCommentID){
		if(
			$blogPost = $this->db->qSelect(
				"
					SELECT
						(
							SELECT
								`BlogPostComment`.`BlogPostID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`ID` = ?
						) BlogPostID,
						FLOOR(COUNT(`BlogPostComment`.`ID`) / " . BLOG_COMMENTS_PER_PAGE . " + 1) Page
					FROM
						`BlogPostComment`
					WHERE
						`BlogPostComment`.`DateInserted` < (
							SELECT	`DateInserted`
							FROM	`BlogPostComment`
							WHERE	`ID` = ?
						) AND
						`BlogPostComment`.`BlogPostID` = (
							SELECT
								`BlogPostComment`.`BlogPostID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`ID` = ?
						)
				",
				'iii',
				array(
					$blogPostCommentID,
					$blogPostCommentID,
					$blogPostCommentID
				)
			)
		)
			return array(
				$blogPost[0]['BlogPostID'],
				$blogPost[0]['Page']
			);
		else
			return FALSE;
	}
	
	public function fetchBlogPostingPrivileges(){
		if(
			$blogPostingPrivileges = $this->db->qSelect(
				"
					SELECT DISTINCT
						`Blog`.`ID` BlogID,
						`Blog`.`Title` BlogTitle
					FROM
						`Blog`
					LEFT JOIN
						`Blog_PostingPrivileges` ON
							`Blog_PostingPrivileges`.`BlogID` = `Blog`.`ID`
					INNER JOIN
						`User` ON
							`User`.`ID` = ?
					WHERE
						`Blog_PostingPrivileges`.`PosterID` = `User`.`ID` OR
						(
							`User`.`Admin` = TRUE OR
							`User`.`Moderator` = TRUE
						)
				",
				'i',
				array(
					$this->User->ID
				)
			)
		){
			$blogPostingPrivileges_indexed = array();
			foreach($blogPostingPrivileges as $blogPostingPrivilege){
				$blogPostingPrivileges_indexed[ $blogPostingPrivilege['BlogID'] ] = $blogPostingPrivilege;
			}
			
			return $blogPostingPrivileges_indexed;
		}
		
		return FALSE;
	}
	
	private function fixUnformattedContent($commentID, $unformattedContent){
		$formattedContent = NXS::formatText($unformattedContent, $this->db);
		
		$updateComment = $this->db->qQuery(
		  "
		    UPDATE
		      `Discussion_Comment`
		    SET
		      `HTML` = ?
		    WHERE
		      `ID` = ?
		  ",
		  'si',
		  array(
		    $formattedContent,
		    $commentID
		  )
		);
		
		return $formattedContent;
	}
	
	public function findComment($id){
		if( $stmt_findDiscussion = $this->db->prepare("
			SELECT
				(
					SELECT	`Discussion_Comment`.`DiscussionID`
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`ID` = ?
				),
				FLOOR(COUNT(`Discussion_Comment`.`ID`) / " . DISCUSSION_COMMENTS_PER_PAGE . " + 1)
			FROM
				`Discussion_Comment`
			WHERE
				`Discussion_Comment`.`DateInserted` < (
					SELECT	`DateInserted`
					FROM	`Discussion_Comment`
					WHERE	`ID` = ?
				) AND
				`Discussion_Comment`.`DiscussionID` = (
					SELECT	`Discussion_Comment`.`DiscussionID`
					FROM	`Discussion_Comment`
					WHERE	`Discussion_Comment`.`ID` = ?
				)
		") ){
			$stmt_findDiscussion->bind_param('iii', $id, $id, $id);
			$stmt_findDiscussion->execute();
			$stmt_findDiscussion->store_result();
			$stmt_findDiscussion->bind_result($discussion_id, $page);
			$stmt_findDiscussion->fetch();
			
			if($discussion_id)
				return array($discussion_id, $page);
			else
				return false;
		}
	}
	
	public function insertBlogPostComment($blogPostID, &$blogID = NULL){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['blog_post_comment_post'][$key] = htmlspecialchars($value);
		
			$content = $_POST['content'];
			if( strlen($content) < 1 ){
				$_SESSION['blog_post_comment_feedback']['content'] = 'Comments cannot be empty';
				return FALSE;
			}
			
			$canComment = $this->_canCommentInBlogPost($blogPostID);			
			if($canComment){
				$blogID = $this->_getParentBlog($blogPostID);
			
				$html = NXS::formatText($content, $this->db);
				$content = htmlspecialchars($content);
				
				if(
					$blogPostCommentID = $this->db->qQuery(
						"
							INSERT INTO `BlogPostComment`
								(`BlogPostID`, `CommenterID`, `Content`, `HTML`, `DateInserted`)
							VALUES
								(?, ?, ?, ?, NOW())
						",
						'iiss',
						array(
							$blogPostID,
							$this->User->ID,
							$content,
							$html
						)
					)
				){
					$this->incrementUserNotifications_blogPost(
						$blogPostID,
						$blogPostCommentID
					);
					
					$this->updateBlogPostForumItem($blogPostID);
					
					return $blogPostCommentID;
				}
			}
		}
		
		// DEFAULT:
		return FALSE;
	}
	
	public function _getParentBlog($blogPostID){
		if(
			$blogs = $this->db->qSelect(
				"
					SELECT
						`BlogPost`.`BlogID`
					FROM
						`BlogPost`
					WHERE
						`BlogPost`.`ID` = ?
				",
				'i',
				array(
					$blogPostID
				)
			)
		)
			return $blogs[0]['BlogID'];
			
		return FALSE;
	}
	
	public function _canCommentInBlogPost($blogPostID){
		if(
			$blogPosts = $this->db->qSelect(
				"
					SELECT
						(
							`BlogPost`.`Closed` IS FALSE OR
							`User`.`Admin` OR
							`User`.`Moderator` OR
							`Blog_PostingPrivileges`.`BlogID` IS NOT NULL
						) commentingPrivileges
					FROM
						`BlogPost`
					INNER JOIN
						`User` ON
							`User`.`ID` = ?
					INNER JOIN
						`Blog` ON
							`BlogPost`.`BlogID` = `Blog`.`ID`
					LEFT JOIN
						`Blog_PostingPrivileges` ON
							`Blog`.`ID` = `Blog_PostingPrivileges`.`BlogID` AND
							`User`.`ID` = `Blog_PostingPrivileges`.`PosterID`
					WHERE
						`BlogPost`.`ID` = ?
				",
				'ii',
				array(
					$this->User->ID,
					$blogPostID
				)
			)
		)
			return $blogPosts[0]['commentingPrivileges'];
		
		return FALSE;
	}
	
	public function fetchBlogAlias($blogID){
		if(
			$blogs = $this->db->qSelect(
				"
					SELECT
						`Alias`
					FROM
						`Blog`
					WHERE
						`ID` = ?
				",
				'i',
				array(
					$blogID
				)
			)
		)
			return $blogs[0]['Alias'];
			
		return FALSE;
	}
	
	public function toggleBlogSubscription($blogID, $seenPostID = NULL){
		if( $this->_hasBlogSubscription($blogID) )
			return $this->_deleteBlogSubscription($blogID);
		else
			return $this->_insertBlogSubscription($blogID, $seenPostID);
	}
	
	public function _hasBlogSubscription($blogID){
		return $this->db->qSelect(
			"
				SELECT
					`BlogID`
				FROM
					`Blog_Subscription`
				WHERE
					`BlogID` = ? AND
					`SubscriberID` = ?
			",
			'ii',
			array(
				$blogID,
				$this->User->ID
			)
		);
	}
	
	public function _deleteBlogSubscription($blogID){
		if(
			$this->db->qQuery(
				"
					DELETE FROM
						`Blog_Subscription`
					WHERE
						`BlogID` = ? AND
						`SubscriberID` = ?
				",
				'ii',
				array(
					$blogID,
					$this->User->ID
				)
			)
		)
			return TRUE;
			
		return FALSE;
	}
	
	public function _insertBlogSubscription($blogID, $seenPostID){
		if($seenPostID == NULL){
			$stmt_insertBlogSubscription_seenPostID = "
				IFNULL(
					(
						SELECT
							`ID`
						FROM
							`BlogPost`
						WHERE
							`BlogID` = ?
						ORDER BY
							`DateInserted` DESC,
							`ID` DESC
						LIMIT 1
					),
					0
				)
			";
			$stmt_insertBlogSubscription_vars = array(
				$blogID,
				$this->User->ID,
				$blogID
			);
		} else {
			$stmt_insertBlogSubscription_seenPostID = '?';
			$stmt_insertBlogSubscription_vars = array(
				$blogID,
				$this->User->ID,
				$seenPostID
			);
		}
		
		$stmt_insertBlogSubscription_types = 'iii';
		$stmt_insertBlogSubscription_query = "
			INSERT IGNORE INTO
				`Blog_Subscription` (`BlogID`, `SubscriberID`, `SeenPostID`)
			VALUES
				(?, ?, " . $stmt_insertBlogSubscription_seenPostID . ")
		";
		
		$this->db->qQuery(
			$stmt_insertBlogSubscription_query,
			$stmt_insertBlogSubscription_types,
			$stmt_insertBlogSubscription_vars
		);
			
		return TRUE;
	}
	
	public function toggleBlogPostSubscription($blogPostID, $seenCommentID = NULL){
		if( $this->_hasBlogPostSubscription($blogPostID) ){
			return $this->_deleteBlogPostSubscription($blogPostID);
		} else {
			return $this->insertBlogPostSubscription($blogPostID, $seenCommentID);
		}
	}
	
	public function _hasBlogPostSubscription($blogPostID){
		return $this->db->qSelect(
			"
				SELECT
					`BlogPostID`
				FROM
					`BlogPost_Subscription`
				WHERE
					`BlogPostID` = ? AND
					`SubscriberID` = ?
			",
			'ii',
			array(
				$blogPostID,
				$this->User->ID
			)
		);
	}
	
	public function _deleteBlogPostSubscription($blogPostID){
		if(
			$this->db->qQuery(
				"
					DELETE FROM
						`BlogPost_Subscription`
					WHERE
						`BlogPostID` = ? AND
						`SubscriberID` = ?
				",
				'ii',
				array(
					$blogPostID,
					$this->User->ID
				)
			)
		)
			return TRUE;
			
		return FALSE;
	}
	
	public function insertBlogPostSubscription(
		$blogPostID,
		$seenCommentID = NULL
	){
		if($seenCommentID == NULL){
			$stmt_insertBlogPostSubscription_seenCommentID = "
				IFNULL(
					(
						SELECT
							`ID`
						FROM
							`BlogPostComment`
						WHERE
							`BlogPostID` = ?
						ORDER BY
							`DateInserted` DESC,
							`ID` DESC
						LIMIT
							1
					),
					(
						SELECT
							`ID`
						FROM
							`BlogPostComment`
						ORDER BY
							`DateInserted` DESC,
							`ID` DESC
						LIMIT
							1
					)
				)
			";
			$stmt_insertBlogPostSubscription_vars = array(
				$blogPostID,
				$this->User->ID,
				$blogPostID
			);
		} else {
			$stmt_insertBlogPostSubscription_seenCommentID = '?';
			$stmt_insertBlogPostSubscription_vars = array(
				$blogPostID,
				$this->User->ID,
				$seenCommentID
			);
		}
		
		$stmt_insertBlogPostSubscription_types = 'iii';
		$stmt_insertBlogPostSubscription_query = "
			INSERT IGNORE INTO
				`BlogPost_Subscription` (`BlogPostID`, `SubscriberID`, `SeenCommentID`)
			VALUES
				(?, ?, " . $stmt_insertBlogPostSubscription_seenCommentID . ")
		";
		
		$this->db->qQuery(
			$stmt_insertBlogPostSubscription_query,
			$stmt_insertBlogPostSubscription_types,
			$stmt_insertBlogPostSubscription_vars
		);
			
		return TRUE;
	}
	
	public function insertComment($discussion_id, $comment_id = false){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['comment_post'][$key] = htmlspecialchars($value);
		
			$content = $_POST['content'];
			
			if( strlen($content) < 1 ){
				$_SESSION['comment_feedback']['content'] = 'Comments cannot be empty';
				return false;
			}
			
			$commentingPrivileges = $this->db->qSelect(
				"
					SELECT
						`Discussion`.`ID`,
						`DiscussionType`.`AllowAnonymous`,
						`DiscussionType`.`OnlyAnonymous`
					FROM
						`Discussion`
					INNER JOIN	`DiscussionCategory`
						ON	`Discussion`.`CategoryID` = `DiscussionCategory`.`ID`
					INNER JOIN	`User`
						ON	`User`.`ID` = ?
					INNER JOIN
						`DiscussionType` ON
							`Discussion`.`TypeID` = `DiscussionType`.`ID`
					WHERE
						`Discussion`.`ID` = ? AND
						(
							`User`.`Moderator` = TRUE OR
							`User`.`Admin` = TRUE OR
							(
								(
									`DiscussionCategory`.`AmountTransacted_Comment` <= ? OR
									`User`.`PostingPrivileges` = TRUE OR
									`User`.`Vendor` = TRUE
								) AND
								(
									`DiscussionCategory`.`Vendor_Comment` = FALSE OR
									`User`.`Vendor` = TRUE
								) AND
								`Discussion`.`Closed` = FALSE
							)
						)
				",
				'iid',
				[
					$this->User->ID,
					$discussion_id,
					$this->User->Attributes['TotalTransacted']
				]
			);
			
			if ($commentingPrivileges[0]['ID'] == NULL)
				return FALSE;
			
			$postAnonymous =
				$commentingPrivileges[0]['OnlyAnonymous'] ||
				(
					isset($_POST['submit_anonymous']) &&
					$commentingPrivileges[0]['AllowAnonymous']
				);
			
			$stmt_checkRecentCommenting = $this->db->prepare("
				SELECT
					COUNT(`ID`)
				FROM
					`Discussion_Comment`
				WHERE
					`PosterID` = ?
				AND	`DateInserted` > NOW() - INTERVAL " . MINIMUM_INTERVAL_BETWEEN_COMMENTS_MINUTES . " MINUTE
			");
			
			$stmt_insertComment = $this->db->prepare("
				INSERT INTO
					`Discussion_Comment` (
						`DiscussionID`,
						`PosterID`,
						`DateInserted`,
						`DateUpdated`,
						`Content`,
						`HTML`,
						`Dialog`,
						`Anonymous`
					)
				VALUES
					(?, ?, NOW(), NOW(), ?, ?, ?, ?)
			");
			
			$stmt_updateComment = $this->db->prepare("
				UPDATE
					`Discussion_Comment`
				SET
					`DateUpdated` = NOW(),
					`Content` = ?,
					`HTML` = ?
				WHERE
					`ID` = ?
				AND	`PosterID` = ?
			");
			
			if(
				false !== $stmt_checkRecentCommenting &&
				false !== $stmt_insertComment &&
				false !== $stmt_updateComment
			){	
				$stmt_checkRecentCommenting->bind_param('i', $this->User->ID);
				$stmt_checkRecentCommenting->execute();
				$stmt_checkRecentCommenting->store_result();
				$stmt_checkRecentCommenting->bind_result($recent_comment_count);
				$stmt_checkRecentCommenting->fetch();
				
				if( $comment_id || $recent_comment_count == 0 ) {
					$dialog = FALSE;
					
					// Content Validation
					if( preg_match_all("/\[quote=['\"]?(\d+)['\"]?\]((?:(?!\[\/quote).)*)\[\/quote\]/is", $content, $comment_quotes) > 0){
						if(  preg_match_all('/\[quote=.*].*\[quote.*\[\/quote\]/', $content) > 0 ){
							$_SESSION['comment_feedback']['content'] = 'Quotes cannot be nested.';
							return false;
						}
						
						$stmt_checkQuote = $this->db->prepare("
							SELECT
								COUNT(`ID`),
								`DateUpdated`
							FROM
								`Discussion_Comment`
							WHERE
								`ID` = ?
						");
						
						for( $i = 0; $i < count($comment_quotes[1]); $i++ ){
							$quoted_id = $comment_quotes[1][$i];
							$quoted_content = '"' . $comment_quotes[2][$i] . '"';
							
							$stmt_checkQuote->bind_param('i', $quoted_id);
							$stmt_checkQuote->execute();
							$stmt_checkQuote->store_result();
							$stmt_checkQuote->bind_result($quote_count, $date_updated);
							$stmt_checkQuote->fetch();
							
							if( $quote_count !== 1 ){
								$_SESSION['comment_feedback']['content'] = 'It appears you tried to quote a comment that does not exist. Ensure that the comment exists and that it contains the quoted text (it may have been edited).';
								return false;
							} else
								$quote_dates[ $quoted_id ] = date('Y-m-d', strtotime($date_updated));
						}
						
						// ADD TODAY'S DATE
						$content = preg_replace_callback(
							"/\[quote=['\"]?(\d+)['\"]?\]((?:(?!\[\/quote).)*)\[\/quote\]/is",
							function($matches) use ($quote_dates){
								return '[quote=' . $matches[1] . ' date=' . $quote_dates[ $matches[1] ] . ']' . $matches[2] . '[/quote]';
							},
							$content
						);
					}
					
					$html = NXS::formatText($content, $this->db, $dialog);
					
					if($comment_id) {	
						$stmt_updateComment->bind_param('ssii', $content, $html, $comment_id, $this->User->ID);
						
						if( $stmt_updateComment->execute() ){
							unset(
								$_SESSION['comment_post'],
								$_SESSION['comment_feedback']
							);
							
							$this->updateDiscussionForumItem($discussion_id);
							
							return $comment_id;
						} else
							$_SESSION['temp_notifications'][] = array(
								'Content' => 'Could not edit comment',
								'Anchor' => false,
								'Dismiss' => '.',
								'Design' => array(
									'Color' => 'yellow',
									'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
								)
							);
					} else {
						$stmt_insertComment->bind_param(
							'iissii',
							$discussion_id,
							$this->User->ID,
							$content,
							$html,
							$dialog,
							$postAnonymous
						);
						
						if( $stmt_insertComment->execute() ){	
							// SUCCESS
							
							$commentID = $stmt_insertComment->insert_id;
							
							unset(
								$_SESSION['comment_post'],
								$_SESSION['comment_feedback']
							);
							
							$this->updateSubscription($discussion_id, $commentID);
							
							$this->incrementUserNotifications_discussionSubscription(
								$discussion_id,
								$commentID
							);
							
							$this->db->incrementStatistic('posts', 1);
							
							$this->updateDiscussionForumItem($discussion_id);
							
							return $commentID;
						} else
							$_SESSION['temp_notifications'][] = array(
								'Content' => 'Could not insert comment',
								'Anchor' => false,
								'Dismiss' => '.',
								'Design' => array(
									'Color' => 'yellow',
									'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
								)
							);
					}
				} else {
					$_SESSION['comment_feedback']['content'] = 'Please wait at least one minute before you post another comment.';
					return false;
				}
			}
		}
	}
	
	private function incrementUserNotifications_discussionSubscription(
		$discussionID,
		$commentID
	){
		return $this->db->qSelect(
			"
				UPDATE
					`User_Notification`
				INNER JOIN
					`Discussion_Subscription` ON
						`User_Notification`.`UserID` = `Discussion_Subscription`.`SubscriberID`
				SET
					`User_Notification`.`Value` = IF(
						`Discussion_Subscription`.`SeenCommentID` =
						(
							SELECT
								`Discussion_Comment`.`ID`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion_Subscription`.`DiscussionID` AND
								(
									`Discussion_Comment`.`DateInserted` < (
										SELECT	`DateInserted`
										FROM	`Discussion_Comment`
										WHERE	`ID` = ?
									) OR
									(
										`Discussion_Comment`.`DateInserted` = (
											SELECT	`DateInserted`
											FROM	`Discussion_Comment`
											WHERE	`ID` = ?
										) AND
										`Discussion_Comment`.`ID` < ?
									)
								)
							ORDER BY
								`Discussion_Comment`.`DateInserted` DESC,
								`Discussion_Comment`.`ID` DESC
							LIMIT
								1
						),
						`User_Notification`.`Value` + 1,
						`User_Notification`.`Value`
					)
				WHERE
					`Discussion_Subscription`.`DiscussionID` = ? AND
					`User_Notification`.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
			",
			'iiii',
			[
				$commentID,
				$commentID,
				$commentID,
				$discussionID
			]
		);
	}
	
	private function incrementUserNotifications_blog(
		$blogID,
		$postID
	){
		return $this->db->qSelect(
			"
				UPDATE
					`User_Notification`
				INNER JOIN
					`Blog_Subscription` ON
						`User_Notification`.`UserID` = `Blog_Subscription`.`SubscriberID`
				SET
					`User_Notification`.`Value` = IF(
						`Blog_Subscription`.`SeenPostID` =
						(
							SELECT
								`BlogPost`.`ID`
							FROM
								`BlogPost`
							WHERE
								`BlogPost`.`BlogID` = `Blog_Subscription`.`BlogID` AND
								(
									`BlogPost`.`DateInserted` < (
										SELECT	`DateInserted`
										FROM	`BlogPost`
										WHERE	`ID` = ?
									) OR
									(
										`BlogPost`.`DateInserted` = (
											SELECT	`DateInserted`
											FROM	`BlogPost`
											WHERE	`ID` = ?
										) AND
										`BlogPost`.`ID` < ?
									)
								)
							ORDER BY
								`BlogPost`.`DateInserted` DESC,
								`BlogPost`.`ID` DESC
							LIMIT
								1
						),
						`User_Notification`.`Value` + 1,
						`User_Notification`.`Value`
					)
				WHERE
					`Blog_Subscription`.`BlogID` = ? AND
					`User_Notification`.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
			",
			'iiii',
			[
				$postID,
				$postID,
				$postID,
				$blogID
			]
		);
	}
	
	private function incrementUserNotifications_blogPost(
		$blogPostID,
		$commentID
	){
		return $this->db->qSelect(
			"
				UPDATE
					`User_Notification`
				INNER JOIN
					`BlogPost_Subscription` ON
						`User_Notification`.`UserID` = `BlogPost_Subscription`.`SubscriberID`
				SET
					`User_Notification`.`Value` = IF(
						`BlogPost_Subscription`.`SeenCommentID` =
						(
							SELECT
								`BlogPostComment`.`ID`
							FROM
								`BlogPostComment`
							WHERE
								`BlogPostComment`.`BlogPostID` = `BlogPost_Subscription`.`BlogPostID` AND
								(
									`BlogPostComment`.`DateInserted` < (
										SELECT	`DateInserted`
										FROM	`BlogPostComment`
										WHERE	`ID` = ?
									) OR
									(
										`BlogPostComment`.`DateInserted` = (
											SELECT	`DateInserted`
											FROM	`BlogPostComment`
											WHERE	`ID` = ?
										) AND
										`BlogPostComment`.`ID` < ?
									)
								)
							ORDER BY
								`BlogPostComment`.`DateInserted` DESC,
								`BlogPostComment`.`ID` DESC
							LIMIT
								1
						),
						`User_Notification`.`Value` + 1,
						`User_Notification`.`Value`
					)
				WHERE
					`BlogPost_Subscription`.`BlogPostID` = ? AND
					`User_Notification`.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
			",
			'iiii',
			[
				$commentID,
				$commentID,
				$commentID,
				$blogPostID
			]
		);
	}
	
	public function deleteCommentReport($commentID){
		return	$this->db->qQuery(
				"
					DELETE FROM
						`Discussion_Comment_Report`
					WHERE
						`UserID` = ? AND
						`CommentID` = ?
				",
				'ii',
				[
					$this->User->ID,
					$commentID
				]
			);
	}
	
	public function reportComment($commentID){
		return	$this->db->qQuery(
				"
					INSERT IGNORE INTO
						`Discussion_Comment_Report` (`UserID`, `CommentID`)
					VALUES
						(?, ?)
				",
				'ii',
				[
					$this->User->ID,
					$commentID
				]
			);
	}
	
	public function toggleCommentReport($commentID){
		return	$this->reportComment($commentID)
			?: $this->deleteCommentReport($commentID);
	}
	
	public function clearDiscussionCommentReports($commentID){
		return	$this->db->qQuery(
				"
					UPDATE
						`Discussion_Comment_Report`
					SET
						`Cleared` = TRUE
					WHERE
						`CommentID` = ?
				",
				'i',
				[$commentID]
			);
	}
	
	public function insertBlogPost(){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['blog_post'][$key] = htmlspecialchars($value);
			
			$blogAlias = $_POST['blog'];
			$title = trim(htmlspecialchars($_POST['title']));
			
			unset($_SESSION['blog_feedback']);
			
			if ( !empty($title) && !preg_match("/[\w][^\n]{1," . (MAX_LENGTH_DISCUSSION_TITLE - 1) . "}/", $title) )
				$_SESSION['blog_feedback']['title'] = 'Titles must be no longer than 100 characters.';
			
			if( empty($title) )
				$title = NULL;
			
			$content = $_POST['content'];
			if( strlen($content) < 1 )
				$_SESSION['blog_feedback']['content'] = 'Content cannot be empty';
			
			if( empty($_SESSION['blog_feedback']) ){
				$checkPostingPrivileges = $this->db->qSelect(
					"
						SELECT
							`Blog`.`ID`
						FROM
							`Blog`
						INNER JOIN
							`User` ON
								`User`.`ID` = ?
						INNER JOIN
							`Blog_PostingPrivileges` ON
								(
									`Blog`.`ID` = `Blog_PostingPrivileges`.`BlogID` AND
									`User`.`ID` = `Blog_PostingPrivileges`.`PosterID`
								) OR
								(
									`User`.`Admin` = TRUE OR
									`User`.`Moderator` = TRUE
								)
						WHERE
							`Blog`.`Alias` = ?
					",
					'is',
					array(
						$this->User->ID,
						$blogAlias
					)
				);
				
				if( $checkPostingPrivileges[0]['ID'] == NULL )
					return FALSE;
				$blogID = $checkPostingPrivileges[0]['ID'];
				
				$html = NXS::formatText($content, $this->db);
				$content = htmlspecialchars($content);
				
				if(
					$blogPostID = $this->db->qQuery(
						"
							INSERT INTO `BlogPost`
								(
									`BlogID`,
									`PosterID`,
									`Title`,
									`Content`,
									`HTML`,
									`DateInserted`,
									`DateUpdated`
								)
							VALUES
								(
									?,
									?,
									?,
									?,
									?,
									NOW(),
									NOW()
								)
						",
						'iisss',
						array(
							$blogID,
							$this->User->ID,
							$title,
							$content,
							$html
						)
					)
				){
					unset(
						$_SESSION['blog_feedback'],
						$_SESSION['blog_post']
					);
					
					$this->incrementUserNotifications_blog(
						$blogID,
						$blogPostID
					);
					
					$this->updateBlogPostForumItem($blogPostID);
					
					return $blogPostID;
				}
			} else
				return false;
		}
		
		return FALSE;
	}
	
	private function mayReviewListing($listingID){
		return	$this->db->qSelect(
				"
					SELECT
						`Transaction`.`ListingID`
					FROM
						`Transaction`
					INNER JOIN
						`Transaction_Event` ON
							`Transaction`.`ID` = `Transaction_Event`.`TransactionID`
					LEFT JOIN
						`Discussion` ON
							`Discussion`.`PosterID` = `Transaction`.`BuyerID` AND
							`Discussion`.`ListingID` = `Transaction`.`ListingID` AND
							(
								SELECT
									`DateInserted`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion`.`ID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` ASC,
									`Discussion_Comment`.`ID` ASC
								LIMIT
									1
							) > NOW() - INTERVAL 3 MONTH
					WHERE
						`Transaction`.`ListingID` = ? AND
						`Transaction`.`BuyerID` = ? AND
						`Transaction`.`Status` = 'pending feedback' AND
						`Transaction_Event`.`Event` = 'accepted' AND
						`Transaction_Event`.`Date` > NOW() - INTERVAL 3 MONTH AND
						`Discussion`.`ID` IS NULL
				",
				'ii',
				[
					$listingID,
					$this->User->ID
				]
			);
	}
	
	public function insertDiscussion(){
		if( !empty($_POST) ){
			foreach($_POST as $key => $value)
				$_SESSION['discussion_post'][$key] = htmlspecialchars($value);
			
			$categoryID = $_POST['category'];
			$title = trim(htmlspecialchars($_POST['title']));
			
			unset($_SESSION['discussion_feedback']);
			
			$listingID = NULL;
			$typeID = FORUM_DISCUSSION_TYPEID_DISCUSSION;
			if (isset($_POST['listing'])){
				if ($mayReviewListing = $this->mayReviewListing($_POST['listing'])){
					$categoryID = FORUM_REVIEWS_CATEGORY_ID;
					$listingID = $_POST['listing'];
					$typeID = FORUM_DISCUSSION_TYPEID_LISTING;
				} else
					$_SESSION['discussion_feedback']['listing'] = true;
			} elseif (
				!is_numeric($categoryID) ||
				$categoryID < 1
			)
				$_SESSION['discussion_feedback']['category'] = true;
			
			if (!preg_match("/[\w][^\n]{1," . (MAX_LENGTH_DISCUSSION_TITLE - 1) . "}/", $title))
				$_SESSION['discussion_feedback']['title'] = 'Titles must be no longer than 100 characters.';
			
			
			if (empty($_SESSION['discussion_feedback'])){
				$checkPostingPrivileges = $this->db->qSelect(
					"
						SELECT
							`DiscussionCategory`.`ID`
						FROM
							`DiscussionCategory`
						INNER JOIN	`User`
							ON	`User`.`ID` = ?
						LEFT JOIN
							`UserClass_DiscussionCategory` ON
								`UserClass_DiscussionCategory`.`DiscussionCategoryID` = `DiscussionCategory`.`ID`
						LEFT JOIN
							`User_Class` thisUser_Class ON
								`User`.`ID` = thisUser_Class.`UserID` AND
								`UserClass_DiscussionCategory`.`UserClassID` = thisUser_Class.`ClassID`
						WHERE
							`DiscussionCategory`.`ID` = ? AND
							(
								`User`.`Moderator` = TRUE OR
								`User`.`Admin` = TRUE OR
								(
									(
										`User`.`PostingPrivileges` = TRUE OR
										`DiscussionCategory`.`AmountTransacted_Post` <= ?
									) AND
									(
										`UserClass_DiscussionCategory`.`UserClassID` IS NULL OR
										`UserClass_DiscussionCategory`.`Post` = FALSE OR
										thisUser_Class.`UserID` IS NOT NULL
									) AND
									(
										`DiscussionCategory`.`Vendor_Post` = FALSE OR
										`User`.`Vendor` = TRUE
									) AND
									`DiscussionCategory`.`Admin_Post` = FALSE
								) " . (
									$typeID == FORUM_DISCUSSION_TYPEID_LISTING
										? "OR `DiscussionCategory`.`ID` = " . FORUM_REVIEWS_CATEGORY_ID
										: false
								) . "
							)
					",
					'iid',
					[
						$this->User->ID,
						$categoryID,
						$this->User->Attributes['TotalTransacted']
					]
				);
				
				if( $checkPostingPrivileges[0]['ID'] == NULL )
					return FALSE;
				
				$stmt_insertDiscussion = $this->db->prepare("
					INSERT INTO
						`Discussion` (`CategoryID`, `PosterID`, `Title`, `ListingID`, `TypeID`)
					VALUES
						(?, ?, ?, ?, ?)
				");
				
				if (false !== $stmt_insertDiscussion){
					$stmt_insertDiscussion->bind_param('iisii', $categoryID, $this->User->ID, $title, $listingID, $typeID);
					
					if( $stmt_insertDiscussion->execute() ){
						$discussion_id = $stmt_insertDiscussion->insert_id;
						
						if ($this->insertComment($discussion_id)){
							unset(
								$_SESSION['discussion_post'],
								$_SESSION['discussion_feedback'],
								$_SESSION['comment_feedback'],
								$_SESSION['comment_post']
							);
							
							$this->db->incrementStatistic('posts', 1);
							
							return $discussion_id;
						} else {
							$_SESSION['discussion_feedback']['content'] = $_SESSION['comment_feedback']['content'];
							
							unset($_SESSION['comment_feedback'], $_SESSION['comment_post']);
							
							$this->deleteDiscussion($discussion_id);
							
							return false;
						}
					} else {
						$_SESSION['temp_notifications'][] = array(
							'Content' => 'Could not create discussion',
							'Anchor' => false,
							'Dismiss' => '.',
							'Design' => array(
								'Color' => 'yellow',
								'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
							)
						);
						
						return false;
					}
				}
			} else
				return false;
		}
	}
	
	public function insertUserDiscussion(){
		if( !empty($_POST) ){
			if( !$this->User->IsVendor )
				return FALSE;
			
			$discussionID = $this->db->qQuery(
				"
					INSERT INTO
						`Discussion` (`CategoryID`, `PosterID`, `Title`, `UserID`)
					VALUES
						(
							(
								SELECT	`DiscussionCategory`.`ID`
								FROM	`DiscussionCategory`
								WHERE	`DiscussionCategory`.`Alias` = 'vendors'
							),
							?,
							?,
							?
						)
				",
				'isi',
				array(
					$this->User->ID,
					$this->User->Alias,
					$this->User->ID
				)
			);
			
			if ($discussionID){
				if ($this->insertComment($discussionID)){
					unset(
						$_SESSION['comment_feedback'],
						$_SESSION['comment_post']
					);
					
					return $discussionID;
				} else
					$this->deleteDiscussion($discussionID);
			}
		}
		$_SESSION['temp_notifications'][] = array(
			'Content' => 'Could not create vendor thread',
			'Anchor' => false,
			'Dismiss' => '.',
			'Design' => array(
				'Color' => 'yellow',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			)
		);
		
		return false;
	}
	
	public function deleteDiscussionComment($discussionCommentID){
		if (
			$discussionComment = $this->db->qSelect(
				"
					SELECT
						`Discussion_Comment`.`DiscussionID`,
						(
							SELECT	DC2.`ID`
							FROM
								`Discussion_Comment` DC2
							WHERE
								`Discussion_Comment`.`DiscussionID` = DC2.`DiscussionID` AND
								(
									DC2.`DateInserted` < `Discussion_Comment`.`DateInserted` OR
									(
										DC2.`DateInserted` = `Discussion_Comment`.`DateInserted` AND
										DC2.`ID` < `Discussion_Comment`.`ID`
									)
								)
							ORDER BY
								DC2.`DateInserted` DESC,
								DC2.`ID` DESC
							LIMIT 1
						) priorCommentID
					FROM
						`Discussion_Comment`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					WHERE
						`Discussion_Comment`.`ID` = ? AND
						(
							thisUser.`Moderator` = TRUE OR
							(
								`Discussion_Comment`.`PosterID` = thisUser.`ID` AND
								`Discussion_Comment`.`ID` = (
									SELECT
										DC2.`ID`
									FROM
										`Discussion_Comment` DC2
									WHERE
										DC2.`DiscussionID` = `Discussion_Comment`.`DiscussionID`
									ORDER BY
										DC2.`DateInserted` DESC,
										DC2.`ID` DESC
									LIMIT
										1
								)
							)
						)
				",
				'ii',
				[
					$this->User->ID,
					$discussionCommentID
				]
			)
		){
			if (
				$this->db->qQuery(
					"
						DELETE FROM
							`Discussion_Comment`
						WHERE
							`Discussion_Comment`.`ID` = ?
					",
					'i',
					[$discussionCommentID]
				)
			){
				$this->db->qQuery(
					"
						UPDATE
							`Discussion_Subscription`
						INNER JOIN
							`User_Notification` decrementedNotification ON
								`Discussion_Subscription`.`SubscriberID` = decrementedNotification.`UserID`
						SET
							decrementedNotification.`Value` = GREATEST(
								0,
								CAST(decrementedNotification.`Value` AS SIGNED) - 1
							)
						WHERE
							`Discussion_Subscription`.`DiscussionID` = ? AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . " AND
							`Discussion_Subscription`.`SeenCommentID` = (
								SELECT	`Discussion_Comment`.`ID`
								FROM
									`Discussion_Comment`
								WHERE
									`Discussion_Comment`.`DiscussionID` = `Discussion_Subscription`.`DiscussionID`
								ORDER BY
									`Discussion_Comment`.`DateInserted` DESC,
									`Discussion_Comment`.`ID` DESC
								LIMIT 1
							) AND
							`Discussion_Subscription`.`SeenCommentID` = ?
					",
					'ii',
					[
						$discussionComment[0]['DiscussionID'],
						$discussionComment[0]['priorCommentID']
					]
				);
				
				$this->db->qQuery(
					"
						UPDATE
							`Discussion_Subscription`
						SET
							`Discussion_Subscription`.`SeenCommentID` = ?
						WHERE
							`Discussion_Subscription`.`SeenCommentID` = ?
					",
					'ii',
					[
						$discussionComment[0]['priorCommentID'],
						$discussionCommentID
					]
				);
				
				$this->updateDiscussionForumItem($discussionComment[0]['DiscussionID']);
				
				return true;
			}
		}
			
		$_SESSION['temp_notifications'][] = array(
			'Content' => 'Comment could not be deleted.',
			'Anchor' => false,
			'Dismiss' => '.',
			'Design' => array(
				'Color' => 'yellow',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			)
		);
		return false;
	}
	
	public function deleteDiscussion($discussionID) {
		if (
			$this->db->qSelect(
				"
					SELECT
						`Discussion`.`ID`
					FROM
						`Discussion`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					WHERE
						`Discussion`.`ID` = ? AND
						(
							thisUser.`Moderator` = TRUE OR
							(
								`Discussion`.`PosterID` = thisUser.`ID` AND
								(
									SELECT	COUNT(`ID`)
									FROM	`Discussion_Comment`
									WHERE	`DiscussionID` = `Discussion`.`ID`
								) <= " . MAXIMUM_COMMENT_COUNT_USER_DELETABLE . "
							)
						)
				",
				'ii',
				[
					$this->User->ID,
					$discussionID
				]
			)
		){
			$this->db->qQuery(
				"
					UPDATE
						`Discussion_Subscription`
					INNER JOIN
						`User_Notification` decrementedNotification ON
							`Discussion_Subscription`.`SubscriberID` = decrementedNotification.`UserID`
					SET
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Discussion_Subscription`.`DiscussionID` = ? AND
						decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . " AND
						(
							SELECT	`DateInserted`
							FROM	`Discussion_Comment`
							WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
						) < (
							SELECT
								`Discussion_Comment`.`DateInserted`
							FROM
								`Discussion_Comment`
							WHERE
								`Discussion_Comment`.`DiscussionID` = `Discussion_Subscription`.`DiscussionID`
							ORDER BY
								`Discussion_Comment`.`DateInserted` DESC,
								`Discussion_Comment`.`ID` DESC
							LIMIT 1
						)
				",
				'i',
				[$discussionID]
			);
			$this->db->qQuery(
				"
					DELETE FROM
						`Discussion`
					WHERE
						`Discussion`.`ID` = ?
				",
				'i',
				[$discussionID]
			);
			
			return true;
		}
		
		$_SESSION['temp_notifications'][] = array(
			'Content' => 'Discussion could not be deleted.',
			'Anchor' => false,
			'Dismiss' => '.',
			'Design' => array(
				'Color' => 'yellow',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			)
		);
		return false;
	}
	
	public function deleteBlogPostComment($blogPostCommentID){
		if (
			$blogPostComment = $this->db->qSelect(
				"
					SELECT
						`BlogPostComment`.`BlogPostID`,
						(
							SELECT
								BPC2.`ID`
							FROM
								`BlogPostComment` BPC2
							WHERE
								BPC2.`BlogPostID` = `BlogPostComment`.`BlogPostID` AND
								(
									BPC2.`DateInserted` < `BlogPostComment`.`DateInserted` OR
									(
										BPC2.`DateInserted` = `BlogPostComment`.`DateInserted` AND
										BPC2.`ID` < `BlogPostComment`.`ID`
									)
								)
							ORDER BY
								BPC2.`DateInserted` DESC,
								BPC2.`ID` DESC
							LIMIT
								1
						) priorCommentID
					FROM
						`BlogPostComment`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					WHERE
						`BlogPostComment`.`ID` = ? AND
						(
							thisUser.`Moderator` = TRUE OR
							(
								`BlogPostComment`.`CommenterID` = thisUser.`ID` AND
								`BlogPostComment`.`ID` = (
									SELECT
										BPC2.`ID`
									FROM
										`BlogPostComment` BPC2
									WHERE
										BPC2.`BlogPostID` = `BlogPostComment`.`BlogPostID`
									ORDER BY
										`BlogPostComment`.`DateInserted` DESC,
										`BlogPostComment`.`ID` DESC
									LIMIT
										1
								)
							)
						)
				",
				'ii',
				[
					$this->User->ID,
					$blogPostCommentID
				]
			)
		){
			if (
				$this->db->qQuery(
					"
						DELETE
							`BlogPostComment`
						FROM
							`BlogPostComment`
						WHERE
							`BlogPostComment`.`ID` = ?
					",
					'i',
					[$blogPostCommentID]
				)
			){
				$this->db->qQuery(
					"
						UPDATE
							`BlogPost_Subscription`
						INNER JOIN
							`User_Notification` decrementedNotification ON
								`BlogPost_Subscription`.`SubscriberID` = decrementedNotification.`UserID`
						SET
							decrementedNotification.`Value` = GREATEST(
								0,
								CAST(decrementedNotification.`Value` AS SIGNED) - 1
							)
						WHERE
							`BlogPost_Subscription`.`BlogPostID` = ? AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . " AND
							`BlogPost_Subscription`.`SeenCommentID` = (
								SELECT
									`BlogPostComment`.`ID`
								FROM
									`BlogPostComment`
								WHERE
									`BlogPostComment`.`BlogPostID` = `BlogPost_Subscription`.`BlogPostID`
								ORDER BY
									`BlogPostComment`.`DateInserted` DESC,
									`BlogPostComment`.`ID` DESC
								LIMIT
									1
							) AND
							`BlogPost_Subscription`.`SeenCommentID` = ?
					",
					'ii',
					[
						$blogPostComment[0]['BlogPostID'],
						$blogPostComment[0]['priorCommentID']
					]
				);
				
				$this->db->qQuery(
					"
						UPDATE
							`BlogPost_Subscription`
						SET
							`BlogPost_Subscription`.`SeenCommentID` = ?
						WHERE
							`BlogPost_Subscription`.`SeenCommentID` = ?
					",
					'ii',
					[
						$blogPostComment[0]['priorCommentID'],
						$blogPostCommentID
					]
				);
				
				$this->updateBlogPostForumItem($blogPostComment[0]['BlogPostID']);
				
				return true;
			}
		}
		
		$_SESSION['temp_notifications'][] = array(
			'Content' => 'Comment could not be deleted.',
			'Anchor' => false,
			'Dismiss' => '.',
			'Design' => array(
				'Color' => 'yellow',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			)
		);
		return false;
	}
	
	public function deleteBlogPost($blogPostID){
		if (
			$blogPost = $this->db->qSelect(
				"
					SELECT
						`BlogPost`.`ID`,
						`BlogPost`.`BlogID`,
						(
							SELECT
								BP2.`ID`
							FROM
								`BlogPost` BP2
							WHERE
								BP2.`BlogID` = `BlogPost`.`BlogID` AND
								(
									BP2.`DateInserted` < `BlogPost`.`DateInserted` OR
									(
										BP2.`DateInserted` = `BlogPost`.`DateInserted` AND
										BP2.`ID` < `BlogPost`.`ID`
									)
								)
							ORDER BY
								BP2.`DateInserted` DESC,
								BP2.`ID` DESC
							LIMIT
								1
						) priorPostID
					FROM
						`BlogPost`
					INNER JOIN
						`User` thisUser ON
							thisUser.`ID` = ?
					WHERE
						`BlogPost`.`ID` = ? AND
						(
							thisUser.`Moderator` = TRUE OR
							(
								`BlogPost`.`PosterID` = thisUser.`ID` /*AND
								(
									SELECT	COUNT(`ID`)
									FROM	`BlogPostComment`
									WHERE	`BlogPostID` = `BlogPost`.`ID`
								) = 0*/
							)
						)
				",
				'ii',
				[
					$this->User->ID,
					$blogPostID
				]
			)
		){
			$this->db->qQuery(
				"
					UPDATE
						`BlogPost_Subscription`
					INNER JOIN
						`User_Notification` decrementedNotification ON
							`BlogPost_Subscription`.`SubscriberID` = decrementedNotification.`UserID`
					SET
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`BlogPost_Subscription`.`BlogPostID` = ? AND
						decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . " AND
						(
							SELECT	`DateInserted`
							FROM	`BlogPostComment`
							WHERE	`ID` = `BlogPost_Subscription`.`SeenCommentID`
						) < (
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
						)
				",
				'i',
				[$blogPostID]
			);
			
			if (
				$this->db->qQuery(
					"
						DELETE FROM
							`BlogPost`
						WHERE
							`BlogPost`.`ID` = ?
					",
					'i',
					[$blogPostID]
				)
			){
				$this->db->qQuery(
					"
						UPDATE
							`Blog_Subscription`
						INNER JOIN
							`User_Notification` decrementedNotification ON
								`Blog_Subscription`.`SubscriberID` = decrementedNotification.`UserID`
						SET
							decrementedNotification.`Value` = GREATEST(
								0,
								CAST(decrementedNotification.`Value` AS SIGNED) - 1
							)
						WHERE
							`Blog_Subscription`.`BlogID` = ? AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . " AND
							`Blog_Subscription`.`SeenPostID` = (
								SELECT
									`BlogPost`.`ID`
								FROM
									`BlogPost`
								WHERE
									`BlogPost`.`BlogID` = `Blog_Subscription`.`BlogID`
								ORDER BY
									`BlogPost`.`DateInserted` DESC,
									`BlogPost`.`ID` DESC
								LIMIT
									1
							) AND
							`Blog_Subscription`.`SeenPostID` = ?
					",
					'ii',
					[
						$blogPost[0]['BlogID'],
						$blogPost[0]['priorPostID']
					]
				);
			
				$this->db->qQuery(
					"
						UPDATE
							`Blog_Subscription`
						SET
							`Blog_Subscription`.`SeenPostID` = ?
						WHERE
							`Blog_Subscription`.`SeenPostID` = ?
					",
					'ii',
					[
						$blogPost[0]['priorPostID'],
						$blogPostID
					]
				);
			
				return true;
			}
		}
		
		$_SESSION['temp_notifications'][] = array(
			'Content' => 'Post could not be deleted.',
			'Anchor' => false,
			'Dismiss' => '.',
			'Design' => array(
				'Color' => 'yellow',
				'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
			)
		);
		return false;
	}
	
	public function insertSubscription($discussion_id){
		if( $stmt_insertSubscription = $this->db->prepare("
			INSERT IGNORE INTO
				`Discussion_Subscription` (`SubscriberID`, `DiscussionID`, `SeenCommentID`)
			SELECT
				?, 
				?,
				`Discussion_Comment`.`ID`
			FROM
				`Discussion_Comment`
			WHERE
				`Discussion_Comment`.`DiscussionID` = ?
			ORDER BY
				`Discussion_Comment`.`DateInserted` DESC,
				`Discussion_Comment`.`ID` DESC
			LIMIT
				1
		") ){
			$stmt_insertSubscription->bind_param('iii', $this->User->ID, $discussion_id, $discussion_id);
			
			return $stmt_insertSubscription->execute();
		}
	}
	
	public function deleteSubscription($discussion_id){
		if( $stmt_deleteSubscription = $this->db->prepare("
			DELETE FROM
				`Discussion_Subscription`
			WHERE
				`DiscussionID` = ?
			AND	`SubscriberID` = ?
		") ){
			$stmt_deleteSubscription->bind_param('ii', $discussion_id, $this->User->ID);
			
			return $stmt_deleteSubscription->execute();
		}
	}
	
	
	public function updateSubscription($discussionID, $commentID){
		$this->db->qQuery(
			"
				INSERT IGNORE INTO
					`Notification_User` (
						`NotificationID`,
						`UserID`
					)
				SELECT
					`Notification`.`ID`,
					?
				FROM
					`Notification`
				WHERE
					`Notification`.`DiscussionID` = ?
			",
			'ii',
			[
				$this->User->ID,
				$discussionID
			]
		);
		
		return
			$this->db->qQuery(
				"
					UPDATE
						`Discussion_Subscription`
					LEFT JOIN
						`User_Notification` decrementedNotification ON
							decrementedNotification.`UserID` = `Discussion_Subscription`.`SubscriberID` AND
							decrementedNotification.`TypeID` = " . USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS . "
					SET
						`Discussion_Subscription`.`SeenCommentID` = ?,
						decrementedNotification.`Value` = GREATEST(
							0,
							CAST(decrementedNotification.`Value` AS SIGNED) - 1
						)
					WHERE
						`Discussion_Subscription`.`DiscussionID` = ? AND
						`Discussion_Subscription`.`SubscriberID` = ? AND
						(
							SELECT	`DateInserted`
							FROM	`Discussion_Comment`
							WHERE	`ID` = ?
						) >
						IFNULL(
							(
								SELECT	`DateInserted`
								FROM	`Discussion_Comment`
								WHERE	`ID` = `Discussion_Subscription`.`SeenCommentID`
							),
							'" . MYSQL_DATETIME_RANGE_LOWEST . "'
						)
				",
				'iiii',
				[
					$commentID,
					$discussionID,
					$this->User->ID,
					$commentID
				]
			);
	}
	
	public function addVote($comment_id, $vote){
		if( $stmt_insertVote = $this->db->prepare("
			INSERT INTO	`Discussion_Vote` (`VoterID`, `CommentID`, `Vote`)
				VALUES (?, ?, ?)
			ON DUPLICATE KEY
				UPDATE	`Vote` = ?
		") ){
			$stmt_insertVote->bind_param('iiii', $this->User->ID, $comment_id, $vote, $vote);
			
			return $stmt_insertVote->execute();
		}
	}
	
	public function deleteVote($comment_id){
		if( $stmt_deleteVote = $this->db->prepare("
			DELETE FROM
				`Discussion_Vote`
			WHERE
				`VoterID` = ?
			AND	`CommentID` = ?
		") ){
			$stmt_deleteVote->bind_param('ii', $this->User->ID, $comment_id);
			
			return $stmt_deleteVote->execute();
		}
	}
	
	private function nl2p($string){
		return '<p>' . preg_replace('#(<br>[\r\n]+){2}#', '</p><p>', nl2br($string, false)) . '</p>';
	}
}
