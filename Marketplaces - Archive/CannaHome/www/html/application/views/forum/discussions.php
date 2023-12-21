<?php
	if($this->searchQuery)
		$URLPrefix = URL . 'forum/search/all/';
	elseif($this->categoryAlias)
		$URLPrefix = URL . 'discussions/' . $this->categoryAlias . '/';
	else
		$URLPrefix = URL . 'discussions/all/'
?>
<div class="rows-30">
	<?php if ($this->discussions){ ?>
	<h2 class="row band">
		<span><?php echo $this->discussionCount . ' discussion' . ($this->discussionCount == 1 ? FALSE : 's'); ?></span>
		<div>
			<label class="label">Sort by</label><?php if($this->searchQuery){ ?>
			<label class="label"><strong>Relevance</strong></label>
			<?php } else { ?><div class="big-dropdown">
				<span><?php 
				
				$allSortModes = array(
					'recency'		=> 'Recency',
					'comments_desc'	=> 'Comments'
				);
				echo $allSortModes[$this->sortMode];
				unset($allSortModes[$this->sortMode]);
				
				?></span>
				<a class="toggle">More</a>
				<ul class="dropdown">
				<?php foreach( $allSortModes as $key => $sortMode ) { ?>
					<li><a href="<?php echo $URLPrefix . $key . '/' ?>" class="dropdown-link"><?php echo $sortMode ?></a></li>
				<?php } ?>
				</ul>
			</div><?php }
			if (
				$hasHighlightedPosts =
					$this->discussions[0]['highlighted'] &&
					$this->discussions[0]['color'] !== 'green' &&
					$this->discussions[0]['color'] !== 'red'
			){ ?>
			<form><button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" formaction="<?= URL . 'forum/mark_all_posts_read/'; ?>" class="btn purple"><i class="<?= Icon::getClass('check'); ?>"></i>Mark All Posts Read</button></form>
			<?php } ?>
		</div>
	</h2>
	<ul class="row big-list zebra">
	<?php foreach($this->discussions as $discussion) {
		$actualCommentCount =
			$discussion['Type'] == 'BlogPost'
				? $discussion['commentCount']
				: $discussion['commentCount'] + 1;
		$lastPage =
			$discussion['Type'] == 'BlogPost'
				? ceil($actualCommentCount/BLOG_COMMENTS_PER_PAGE)
				: ceil($actualCommentCount/DISCUSSION_COMMENTS_PER_PAGE);
		//$sort = $discussion['isUpdateThread'] ? 'id_desc' : 'id_asc';
		$commentsPerPage =
			$discussion['Type'] == 'BlogPost'
				? BLOG_COMMENTS_PER_PAGE
				: DISCUSSION_COMMENTS_PER_PAGE;
		
		$entryURLPrefix =
			URL .
			(
				$discussion['Type'] == 'BlogPost'
					? 'post/'
					: 'discussion/'
			) .
			$discussion['ID'] . '/';
		$entryURLPrefix_sorting = $entryURLPrefix . 'id_asc/';
		
		if ($discussion['PosterIsAnonymous'])
			$discussion['posterAlias'] = '<em>Anonymous</em>';
		
		if ($discussion['MostRecentCommenterAnonymous'])
			$discussion['recentCommenter'] = '<em>Anonymous</em>';
		
		$entryClass = rtrim(
			'poster-' . strip_tags($discussion['posterAlias']) . ' ' .
			($discussion['highlighted'] ? ' highlight ' : false) .
			$discussion['color']
		);
		
		$isPersonalBlogPost =
			$discussion['Type'] == 'BlogPost' &&
			$discussion['blogTitle'] == $discussion['posterAlias'];
	?>
		<li class="<?php echo $entryClass; ?>">
			<?php 
			if ($this->UserMod) {
				$deleteModalID = "delete" . $discussion['Type'] . '-' . $discussion['ID'];
			?>
			<div class="btns">
				<a class="btn xs <?php echo $discussion['stickied'] ? 'green' : 'minimal'; ?>" href="<?php echo '?do[' . ($discussion['Type'] == 'BlogPost' ? 'ToggleBlogPostStickied' : 'ToggleDiscussionStickied') . ']=' . $discussion['ID']; ?>">
					<i class="<?= Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE'); ?>"></i>
					<div class="hint left"><span><?php echo $discussion['stickied'] ? 'Un-sticky' : 'Sticky'; ?></span></div>
				</a>
				<a class="btn xs <?php echo $discussion['closed'] ? 'yellow' : 'minimal'; ?>" href="<?php echo '?do[' . ($discussion['Type'] == 'BlogPost' ? 'ToggleBlogPostClosed' : 'ToggleDiscussionClosed') . ']=' . $discussion['ID']; ?>">
					<i class="<?= Icon::getClass($discussion['closed'] ? 'PLAY' : 'PAUSE'); ?>"></i>
					<div class="hint left"><span><?php echo $discussion['closed'] ? 'Re-open' : 'Close'; ?></span></div>
				</a>
				<label class="btn xs minimal" for="<?= $deleteModalID; ?>">
					<i class="<?= Icon::getClass('TIMES', true); ?>"></i>
					<div class="hint left"><span>Delete</span></div>
				</label>
			</div>
			<input id="<?= $deleteModalID; ?>" type="checkbox" hidden>
			<div class="modal">
				<label for="<?= $deleteModalID; ?>"></label>
				<div class="rows-10 formatted">
					<label for="<?= $deleteModalID; ?>" class="close">&times;</label>
					<p class="row">Are you sure you wish to delete this item?</p>
					<div class="row cols-10">
						<div class="col-6"><a href="<?= URL . 'forum/' . ($discussion['Type'] == 'BlogPost' ? 'delete_blog_post' : 'delete_discussion') . '/' . $discussion['ID'] . '/'; ?>" class="btn wide">Delete It</a></div>
						<div class="col-6">
							<label for="<?= $deleteModalID; ?>" class="btn wide red">Nevermind</label>
						</div>
					</div>
				</div>
			</div>
			<?php }
			if ($discussion['commentCount'] > 0) { ?>
			<div class="meta">
				<div>
					<?php
					if ($actualCommentCount > $commentsPerPage && 1 == 2){ ?>
					<div>Pages: <a href="<?php echo $entryURLPrefix . ($discussion['Type'] == 'BlogPost' ? '#comments' : FALSE); ?>">1</a><?php
						
					$commentPages = '';
					switch(true){
						case ($actualCommentCount > $commentsPerPage*3):
							$commentPages .=
								' &hellip; <a href="' .
								$entryURLPrefix_sorting .
								$lastPage .
								'/' .
								(
									$discussion['Type'] == 'BlogPost'
										? '#comments'
										: FALSE
								) .
								'">' .
								$lastPage .
								'</a>';
						case ($actualCommentCount > $commentsPerPage*2):
							$commentPages =
								', <a href="' .
								$entryURLPrefix_sorting .
								'3/' .
								(
									$discussion['Type'] == 'BlogPost'
										? '#comments'
										: FALSE
								) .
								'">3</a>' .
								$commentPages;
						case ($actualCommentCount > $commentsPerPage):
							$commentPages =
								', <a href="' .
								$entryURLPrefix_sorting .
								'2/' .
								(
									$discussion['Type'] == 'BlogPost'
										? '#comments'
										: FALSE
								) .
								'">2</a>' .
								$commentPages;
					}
					//echo $commentPages;
						
					?></div><?php } ?>
					<div><?= '<strong>' . $discussion['commentCount'] . '</strong> comment' . ($discussion['commentCount'] == 1 ? FALSE : 's'); ?></div>
					<div>Most recent:<br><a<?php echo $discussion['recentCommenterFlair'] ? ' data-flair="' . $discussion['recentCommenterFlair']['text'] . '" class="flair-' . $discussion['recentCommenterFlair']['color'] . '"' : false; ?> href="<?php echo URL . 'forum/' . ($discussion['Type'] == 'BlogPost' ? 'blog_post_comment' : 'comment') . '/' . $discussion['latestCommentID'] . '/' ?>"><?php echo $discussion['recentCommenter']; ?></a></div>
				</div>
			</div>
			<?php }
			if ($discussion['ListingID'])
				echo '<div class="sub-meta">Review of <strong>' . $discussion['listingName'] . '</strong>, <a target="_blank" href="http://' . $this->db->accessDomain . '/v/' . $discussion['vendorAlias'] . '/">' . $discussion['vendorAlias'] . '</a> (' . $discussion['countryISO'] . ')</div>';
			if($discussion['posterImage']) { ?>
			<div class="image" style="background-image:url(<?php echo $discussion['posterImage'] ?>)"></div>
			<?php }
			if ($discussion['status']) {
				if ($discussion['badgeURL'])
					echo '<a href="' . $discussion['badgeURL'] . '"';
				else
					echo '<div';
					
				echo ' class="badge ' . $discussion['badgeColor'] . '">';
				echo $discussion['status'];
				
				echo '</' . ($discussion['badgeURL'] ? 'a' : 'div') . '>';
			if (
				
				$discussion['hasUploadedPictures']){ ?>
			<a href="<?= $entryURLPrefix; ?>" class="badge">Pictures</a>
			<?php }
			} ?>
			<div class="main">
				<div>
					<a href="<?php
						if (
							$discussion['reportedCommentID'] ||
							(
								$discussion['newEntries'] &&
								$discussion['seenCommentID']
							)
						)
							echo	URL .
								'forum/' .
								(
									$discussion['Type'] == 'BlogPost'
										? 'blog_post_comment'
										: 'comment'
								) .
								'/' .
								($discussion['reportedCommentID'] ?: $discussion['seenCommentID']) .
								'/';
						/*elseif (
							$discussion['newEntries'] &&
							$discussion['Type'] !== 'BlogPost'
						)
							echo $entryURLPrefix_sorting . $lastPage . '/';*/
						else
							echo $entryURLPrefix;
					?>"><?= $discussion['title'];?></a><br>
						<span>Posted on <strong><?php echo date('F j Y', strtotime($discussion['dateInserted'])); ?></strong> by <<?= ($discussion['PosterIsAnonymous'] ? 'em' : 'a' . ($discussion['posterFlair'] ? ' data-flair="' . $discussion['posterFlair']['text'] . '" class="flair-' . $discussion['posterFlair']['color'] . '"' : false) . ' href="' . ($isPersonalBlogPost ? URL . 'blog/' . $discussion['blogAlias'] : URL . 'u/' . $discussion['posterAlias']) . '/"') . '>' . $discussion['posterAlias'] . '</' . ($discussion['PosterIsAnonymous'] ? 'em' : 'a'); ?>><?php if (!$isPersonalBlogPost){ ?> in <a href="<?php echo $discussion['Type'] == 'BlogPost' ? URL . 'blog/' . $discussion['blogAlias'] . '/' : URL . 'discussions/' . $this->discussionCategories[ $discussion['categoryID'] ]['alias'] . '/' ?>"><?php echo $discussion['Type'] == 'BlogPost' ? $discussion['blogTitle'] : $this->discussionCategories[ $discussion['categoryID'] ]['name'] ?></a><?php } ?></span></div></div>
		</li>
	<?php } ?>
	</ul>
	<?php if($this->discussionCount > DISCUSSIONS_PER_PAGE){ ?>
	<div class="row panel">
		<?php
			$this->renderPaginationPanel(
				$this->pageNumber,
				ceil($this->discussionCount/DISCUSSIONS_PER_PAGE),
				$URLPrefix . $this->sortMode . '/',
				'/',
				false,
				'',
				false
			);
		?>
	</div>
	<?php }
	} else { ?>
	<h2 class="row band">
		<span><strong>zero</strong> discussions found</span>
	</h2>
	<div class="row formatted">
		<p>We couldn't find any results for your search.</p>
		<p>
			<a class="btn red" href="<?php echo URL; ?>"><i class="<?php echo Icon::getClass('BACKWARD'); ?>"></i>Start over</a>
		</p>
	</div>
	<?php } ?>
</div>
