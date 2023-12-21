<?php
	$URLPrefix = URL . 'discussion/' . $this->discussionID . '/';
	
	$post = isset($_SESSION['comment_post']) ? $_SESSION['comment_post'] : false;
	$feedback = isset($_SESSION['comment_feedback']) ? $_SESSION['comment_feedback'] : false;
	
	unset($_SESSION['comment_post'], $_SESSION['comment_feedback']);
	
	$numberOfPages = ceil($this->commentCount/DISCUSSION_COMMENTS_PER_PAGE);
?>
<div class="rows-30">
	<h2 class="row band">
		<span><?= $this->commentCount . ' comment' . ($this->commentCount == 1 ? FALSE : 's'); ?></span>
		<div>
		<?php
		if ($this->UserMod){ ?>
		<form class="big-dropdown">
			<span><?= $activeCategory['name']; ?></span>
			<a class="toggle">More</a>
			<ul class="dropdown">
				<?php
				unset($activeCategory);
				foreach ($this->discussionCategories as $discussionCategory){ ?>
				<li><button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" formaction="<?= URL . 'forum/change_discussion_category/' . $this->discussion['ID'] . '/' . $discussionCategory['ID'] . '/'; ?>" class="dropdown-link"><?= $discussionCategory['name']; ?></button></li>
				<?php } ?>
			</ul>
		</form>
		
		<?php } 
		
		$isOriginalPoster = $this->UserAlias == $this->discussion['posterAlias'];
		/*if(
			(
				$isOriginalPoster &&
				$this->commentCount <= MAXIMUM_COMMENT_COUNT_USER_DELETABLE
			) ||
			$this->UserMod
		) { ?>
			<div class="big-dropdown">
				<span>Options</span>
				<a class="toggle">More</a>
				<ul class="dropdown">
				<?php if( $this->UserMod ) { ?>
					<li><a href="<?php echo URL . 'admin/announce_discussion/' . $this->discussion['ID'] . '/' ?>" class="dropdown-link">Announce</a></li>
					<li><a href="<?php echo URL . 'admin/close_discussion/' . $this->discussion['ID'] . '/' ?>" class="dropdown-link">Close</a></li>
					<li><a href="<?php echo URL . 'admin/sink_discussion/' . $this->discussion['ID'] . '/' ?>" class="dropdown-link">Sink</a></li>
				<?php } ?>
					<li><a href="#delete-discussion" class="dropdown-link">Delete</a></li>
				</ul>
			</div>
			<div class="modal" id="delete-discussion">
				<a href="#"></a>
				<div class="rows-10">
					<a class="close" href="#close">&times;</a>
					<p class="row">Are you sure you wish to delete this discussion?</p>
					<div class="row cols-10">
						<div class="col-6"><a href="<?php echo  URL . (
							$this->UserMod
								? 'admin/delete_discussion'
								: 'forum/delete_discussion'
						) . '/' . $this->discussion['ID'] . '/'; ?>" class="btn wide color">Yes, Delete!</a></div>
						<div class="col-6"><a href="#close" class="btn wide red color">No</a></div>
					</div>
				</div>
			</div>
		<?php } */
		$currentSort = explode('_', $this->sortMode);
		if (count($currentSort) > 1){
			$sortURL = $URLPrefix . $currentSort[0] . '_' . ($currentSort[1] == 'asc' ? 'desc' : 'asc') . '/';
			$upOrDown = $currentSort[1] == 'asc' ? 'up' : 'down';
			echo '<a class="btn minimal xs" href="' . $sortURL . '"><i class="candy-sort ' . $upOrDown . '"></i></a>';
		}
		if ($this->discussion['subscribed']) { ?>
			<a href="<?php echo URL . 'forum/unsubscribe/' . $this->discussion['ID'] . '/' . $this->pageNumber . '/'; ?>" class="btn red"><i class="<?= Icon::getClass('RSS'); ?>"></i>Unsubscribe</a>
		<?php } else { ?>
		<?php
		$this->renderMemberButton(
			URL . 'forum/subscribe/' . $this->discussion['ID'] . '/' . $this->pageNumber . '/',
			'<i class="' . Icon::getClass('RSS'). '"></i>Subscribe',
			'btn blue'
		); ?>
		<?php }
		$canDeletePost =
			$this->UserMod ||
			(
				$isOriginalPoster &&
				$this->commentCount <= MAXIMUM_COMMENT_COUNT_USER_DELETABLE
			);
			if ($canDeletePost){
			$deleteModalID = 'deleteDiscussion'; ?>
			<label class="btn red" for="<?= $deleteModalID; ?>">
				<i class="<?= Icon::getClass('TIMES', true); ?>"></i>
				Delete
			</label>
			<input id="<?= $deleteModalID; ?>" type="checkbox" hidden>
			<div class="modal">
				<label for="<?= $deleteModalID; ?>"></label>
				<div class="rows-10 formatted">
					<label for="<?= $deleteModalID; ?>" class="close">&times;</label>
					<p class="row">Are you sure you wish to delete this item?</p>
					<div class="row cols-10">
						<div class="col-6"><a href="<?= URL . 'forum/delete_discussion/' . $this->discussion['ID'] . '/'; ?>" class="btn wide">Delete It</a></div>
						<div class="col-6">
							<label for="<?= $deleteModalID; ?>" class="btn wide red">Nevermind</label>
						</div>
					</div>
				</div>
			</div>
			<?php }
			if ($this->UserMod){ ?>
			<form><button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="btn<?= $this->discussion['sink'] ? ' minimal' : false; ?>" formaction="<?= URL . 'forum/toggle_discussion_sink/' . $this->discussion['ID'] . '/'; ?>"><?= $this->discussion['sink'] ? 'Unsink' : 'Sink'; ?></button></form>
			<?php } ?>
		</div>
	</h2>
	<?php if($this->discussion['userID']){ ?>
	<form action="." method="post" class="row grey-box formatted corner-btn">
		<?php if( isset($this->forumFilter['hide_comments']) && $this->forumFilter['hide_comments'] ){ ?>
		<button type="submit" name="reset_flter" class="btn green">Show Comments</button>
		<?php } else { ?>
		<button type="submit" name="hide_comments" value="1" class="btn">Hide Comments</button>
		<?php } ?>
		<p><?php
			echo $isOriginalPoster
				? 'This is your personal thread.'
				: 'This is the personal thread of <a href="' . URL . 'u/' . $this->discussion['posterAlias'] . '/">' . $this->discussion['posterAlias'] . '</a>.'
		?></p>
	</form>
	<?php } ?>
	<ul class="row list-posts">
		<?php 
		$hasUnreadCommentsOnNextPage = false;
		foreach($this->discussion['comments'] as $i => $comment) { 
			$firstComment =
				$i == 0 &&
				$this->pageNumber == 1;
			$lastCommentOnPage = $i == count($this->discussion['comments']) - 1;
			
			$veryLastComment =
				$this->pageNumber == $numberOfPages &&
				$lastCommentOnPage;
			
			$isOriginalPost =
				(
					$upOrDown == 'up' &&
					$firstComment
				) ||
				(
					$upOrDown == 'down' &&
					$veryLastComment
				);
			
			$isPoster = $comment['isPoster'];
			
			$hasUnreadCommentsOnNextPage =
				$hasUnreadCommentsOnNextPage ||
				(
					$this->discussion['seenCommentID'] &&
					$comment['ID'] == $this->discussion['seenCommentID'] &&
					$this->pageNumber < $numberOfPages
				);
		?>
		<li>
			<span class="anchor" id="<?php echo 'comment-' . $comment['ID'] ?>"></span>
			<?php if ($lastCommentOnPage){ ?>
			<span class="anchor" id="last"></span>
			<?php } ?>
			<div class="post-header">
				<a<?= $comment['anonymousCommenterID'] === null ? ' href="' . URL . 'u' . '/' . $comment['posterAlias'] . '/' . '"' : false; ?> class="poster">
					<?php if ($comment['posterImage']) { ?>
					<figure style="background-image: url(<?php echo $comment['posterImage'] ?>)"></figure><?php }
					if ($comment['anonymousCommenterID'] !== null)
						echo '<i style="filter: hue-rotate(' . $comment['anonymousHueRotateDeg'] . 'deg)" class="' . Icon::getClass('USER') . '"></i>';
					echo $comment['posterAlias'];  ?>
				</a>
				<?php
				$this->renderFlairs(
					$comment['posterFlairs'],
					$comment['posterAlias'],
					$comment['ID']
				); ?>
				<div class="options">
					<?php if ($isPoster || $this->UserMod) { ?>
					<div class="modal" id="edit-<?php echo $comment['ID'] ?>">
						<a href="#close"></a>
						<div>
							<a class="close" href="#">&times;</a>
							<form class="rows-10" method="post" action="<?php
							echo (
								$this->UserMod
									? URL . 'admin/edit_comment'
									: $URLPrefix . 'comment/edit'
								) . '/' . $comment['ID'] . '/' ; 
							?>">
								<label class="row textarea<?php echo isset($feedback['edit-content'][  $comment['ID'] ]) ? ' invalid' : false ?>">
									<textarea rows="10" name="content"><?php echo isset($post['edit-content'][ $comment['ID'] ]) ? $post['edit-content'][ $comment['ID'] ] : $comment['rawContent'] ?></textarea>
									<?php if (isset($feedback['edit-content'][ $comment['ID'] ])) { ?>
									<p class="note"><?php echo $feedback['edit-content'][ $comment['ID'] ]; ?></p>
									<?php } ?>
								</label>
								<input type="submit" class="row btn wide" value="edit">
							</form>
						</div>
					</div>
					<?php 
					}
					if (
						!$this->discussion['closed'] ||
						$this->UserMod
					){
						if (!$isPoster){
							$this->renderMemberButton(
								$comment['anonymousCommenterID'] !== null ? 'new-comment' : $URLPrefix . 'comment/reply/' . $comment['posterAlias'] . '/' . $this->sortMode . '-' . $this->pageNumber . '/',
								'<i class="' . Icon::getClass('REPLY') . '"></i>Reply',
								'btn minimal',
								$comment['anonymousCommenterID'] !== null
							);
							echo ' ';
						}
						$this->renderMemberButton(
							$URLPrefix . 'comment/quote/' . $comment['ID'] . '/' . $this->sortMode . '-' . $this->pageNumber . '/',
							'<i class="' . Icon::getClass('QUOTE-LEFT') . '"></i>Quote',
							'btn minimal'
						);
						if ($isPoster || $this->UserMod){
							echo ' ';
							$this->renderMemberButton(
								'#edit-' . $comment['ID'],
								'<i class="' . Icon::getClass('WRITE', true) . '"></i>Edit',
								'btn minimal'
							);
						}
						
						if (
							(
								(
									$isPoster &&
									$veryLastComment
								) ||
								$this->UserMod
							) &&
							!$isOriginalPost
						){
							$deleteCommentModalID = 'delete_comment-' . $comment['ID']; ?>
							<label for="<?= $deleteCommentModalID; ?>" class="btn minimal"><i class="<?= Icon::getClass('TIMES', true); ?>"></i>Delete</label>
							<input id="<?= $deleteCommentModalID; ?>" type="checkbox" hidden>
							<div class="modal">
								<label for="<?= $deleteCommentModalID; ?>"></label>
								<div class="rows-10 formatted">
									<label for="<?= $deleteCommentModalID; ?>" class="close">&times;</label>
									<p class="row">Are you sure you wish to delete this comment?</p>
									<div class="row cols-10">
										<form class="col-6">
											<button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="btn wide" formaction="<?= URL . 'forum/delete_comment/' . $comment['ID'] . '/';  ?>">Delete It</button>
										</form>
										<div class="col-6">
											<label for="<?= $deleteCommentModalID; ?>" class="btn wide red">Nevermind</label>
										</div>
									</div>
								</div>
							</div>
						<?php }
					} ?>
				</div>
			</div>
			<div class="content">
			<?php if ($isOriginalPost) {
				if ($this->discussion['status']) { ?>
				<span class="badge <?php echo $this->discussion['color']; ?>"><?php echo $this->discussion['status']; ?></span>
				<?php } ?>
				<h6><?= $this->discussion['title'] ?></h6>
				<?php if ($listing = $this->discussion['listing']){ ?>
				<ul class="pic-list">
					<li>
						<a target="_blank" class="vendor" href="<?= 'http://' . $this->db->accessDomain . '/v/' . $listing['vendorAlias'] . '/'; ?>"><?= $listing['vendorAlias']; ?></a>
						<a<?= $listing['available'] ? '  target="_blank" href="http://' . $this->db->accessDomain . '/i/' . $listing['B36'] . '/"' : false; ?>>
							<?php
							if (!$listing['available']){ ?><div class="hint above"><span>Not Available</span></div><?php }
							if ($listing['image']){ ?><div class="image" style="background-image: url(<?= $listing['image']; ?>)"></div><?php } ?>
							<div class="main">
								<div>
									<div>
										<div>
											<span><?= $listing['name']; ?></span>
										</div>
									</div>
									<span><?= $listing['price']; ?> <span><?= $listing['price_crypto']; ?></span></span>
								</div>
							</div>
						</a>
					</li>
				</ul>
				<?php } ?>
			<?php }
				echo $comment['content'];
				
				if (
					$isOriginalPost &&
					$isVendorNominationPost = $this->discussion['status'] == FORUM_VENDOR_NOMINATION_LABEL
				)
					echo FORUM_VENDOR_NOMINATION_FOOTER;
				
				$mayUploadPicture =
					(
						$isOriginalPost &&
						$this->mayUploadPictures &&
						(
							$isPoster ||
							$this->UserMod
						)
					);
				if (
					$comment['images'] ||
					$mayUploadPicture
				){ ?>
				<<?= $mayUploadPicture ? 'form enctype="multipart/form-data" method="post" action="' . URL . 'forum/update_discussion_comment_pictures/' . $comment['ID'] . '/"' : 'div'; ?> class="picture-uploader">
					<?php
					if ($mayUploadPicture){ ?>
					<input type="hidden" name="csrf" value="<?= $this->getCSRFToken(); ?>">
					<?php }
					if ($comment['images'])
						foreach($comment['images'] as $image){ ?>
					<<?= $mayUploadPicture ? 'div' : 'a target="_blank" href="' . $image['image'] . '"'; ?> class="pic" style="background-image:url(<?= $image['thumbnail']; ?>)">
						<?php if ($mayUploadPicture){
							$deletePictureModalID = 'deletePicture-' . $image['ID']; ?>
						<label for="<?= $deletePictureModalID; ?>">Delete</label>
						<input id="<?= $deletePictureModalID; ?>" type="checkbox" hidden="">
						<div class="modal">
							<label for="<?= $deletePictureModalID; ?>"></label>
							<div class="rows-10">
								<label class="close" for="<?= $deletePictureModalID; ?>">&times;</label>
								<p class="row">Are you sure you wish to delete this picture?</p>
								<div class="row cols-10">
									<div class="col-6"><button type="submit" class="btn wide" name="delete_pic" value="<?= $image['ID']; ?>">Delete</button></div>
									<div class="col-6"><label for="delete-" class="btn wide red">Nevermind</label></div>
								</div>
							</div>
						</div>
						<a href="<?= $image['image']; ?>" target="_blank">View</a>
						<?php } ?>
					</<?= $mayUploadPicture ? 'div' : 'a'; ?>>
					<?php }
					if (
						$mayUploadPicture &&
						(
							!$comment['images'] ||
							count($comment['images']) < FORUM_MAX_IMAGES_PER_DISCUSSION_COMMENT
						)
					){
						if ($tooManyUploads = isset($_SESSION['too_many_uploads']))
							unset($_SESSION['too_many_uploads']); ?>
					<label class="input-file<?= $tooManyUploads ? ' color-red' : false; ?>">
						<span><?= $tooManyUploads ? 'You have met your daily upload limit' : 'Upload ' . ($comment['images'] ? 'another' : 'a') . ' picture, 1MB MAX'; ?></span>
						<input<?= $tooManyUploads ? ' disabled' : false; ?> name="file" type="file">
						<button<?= $tooManyUploads ? ' disabled' : false; ?> class="btn" type="submit">Upload</button>
					</label>
					<?php } ?>
				</<?= $mayUploadPicture ? 'form' : 'div'; ?>>
				<?php }
				echo $comment['posterSignature'] ? '<div class="signature">' . $comment['posterSignature'] . '</div>' : false; ?>
			</div>
			<div class="footer rows-10">
				<div class="row cols-10">
					<div class="col-4">
						Posted on: <strong<?= $comment['dateUpdated'] ? ' title="Edited on ' . $comment['dateUpdated'] . '"' : false; ?>><?= $comment['dateInserted'] . ($comment['dateUpdated'] ? '*' : false); ?></strong>
					</div>
					<div class="col-4 centered">
						<a class="permalink" href="<?= URL . 'comment/' . $comment['ID'] . '/'; ?>">Permalink</a>
					</div>
					<form class="col-4">
						<?php if ($comment['reported']){ ?>
						<button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'forum/report_comment/' . $comment['ID'] . '/'; ?>" class="pts color-red"><strong>Reported</strong> (Undo)</button>
						<?php } elseif (!$isPoster) { ?>
						<button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'forum/report_comment/' . $comment['ID'] . '/'; ?>" class="pts color-red">Report</button>
						<?php } if ( $comment['votes'] ) { ?>
						<span class="pts<?php echo $comment['votes'] < 0 ? ' red' : false ?>"><?php echo sprintf("%+d",$comment['votes']) ?></span><?php } ?>
						<?php if (!$isPoster) { 
						$this->renderMemberButton(
							URL . (
								$comment['userVote'] == 1
									? 'forum/unvote/' . $comment['ID'] . '/'
									: 'forum/vote/' . $comment['ID'] . '/up/'
							),
							'<i class="' . Icon::getClass('THUMBS_UP') . '"></i>',
							'btn xs ' . ($comment['userVote']==1 ? ' green' : 'minimal')
						); ?>
						<?php
						/*
						if ($comment['posterAlias'] == 'Nate') { ?>
						<label class="btn xs minimal" for="rejected-opinion"><i class="<?= Icon::getClass('THUMBS_DOWN'); ?>"></i></label>
						<input type="checkbox" hidden id="rejected-opinion">
						<div class="modal">
							<label for="rejected-opinion"></label>
							<div>
								<strong>Your opinion has been rejected.</strong>
							</div>
						</div>
						<?php } else $this->renderMemberButton(
							URL . (
								$comment['userVote'] == -1
									? 'forum/unvote/' . $comment['ID'] . '/'
									: 'forum/vote/' . $comment['ID'] . '/down/'
							),
							'<i class="' . Icon::getClass('THUMBS_DOWN') . '"></i>',
							'btn xs ' . ($comment['userVote']==-1 ? ' red' : 'minimal')
						); */
						} ?>
					</form>
				</div>
				<?php
				if (	$comment['reporterAliases'] &&
					$reporterAliases = explode(',', $comment['reporterAliases'])
				) { ?>
				<div class="row">
					<div class="cols-10">
						<div class="col-11">
							<strong class="color-red">Reported by</strong>
							<?php
								foreach ($reporterAliases as $o => $reporterAlias){
									echo '<a href="/u/' . $reporterAlias . '/">' . $reporterAlias . '</a>';
									if ($o < count($reporterAliases) - 1)
										echo ', ';
								}
							?>
						</div>
						<form class="col-1 align-right">
							<button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" formaction="<?= URL . 'forum/clear_discussion_comment_reports/' . $comment['ID'] . '/'; ?>" class="btn xs red">
								<i class="<?= Icon::getClass('TIMES', true) ;?>"></i>
								<div class="hint left">
									<span>Clear reports</span>
								</div>
							</button>
						</form>
					</div>
				</div>
				<?php } ?>
			</div>
		</li>
		<?php } ?>
	</ul>
	<?php if ($this->commentCount > DISCUSSION_COMMENTS_PER_PAGE){ ?>
	<div class="row centered">
		<div class="panel">
		<?php
			$this->renderPaginationPanel(
				$this->pageNumber,
				$numberOfPages,
				$URLPrefix . $this->sortMode . '/',
				'/',
				[
					false,
					$hasUnreadCommentsOnNextPage ? 'yellow' : false
				],
				'#last',
				true,
				[
					false,
					$hasUnreadCommentsOnNextPage ? '<div class="hint left"><span>Read on</span></div>Next' : false
				]
			);
			?>
		</div>
	</div>
	<?php }
	if(!$this->discussion['closed']) { ?>
	<form class="row" method="post" action="<?php echo $URLPrefix . 'comment/' ?>">
		<label class="label">Post your comment</label>
		<fieldset class="rows-20">
			<label class="row textarea<?php echo isset($feedback['content']) ? ' invalid' : false ?>">
				<textarea <?php echo isset($post['content']) ? 'autofocus' : false ?> name="content" id="new-comment" rows="6" pattern=".+"><?php echo isset($post['content']) ? $post['content'] : false ?></textarea>
				<?php if (isset($feedback['content'])) { ?>
				<p class="note"><?php echo $feedback['content'] ?></p>
				<?php } else { ?>
				<p class="note"><strong>Allowed tags:</strong> [b] <strong>bold text</strong> [/b], [i] <em>italicized text</em> [/i] and [pgp] [/pgp] for pgp blocks or other non-formatted text.</p>
				<?php } ?>
			</label>
			<?php if ($this->discussion['allowAnonymous']){ ?>
			<label class="row checkbox">
				<input<?= $this->discussion['onlyAnonymous'] ? ' checked disabled' : ($this->discussion['previouslyCommentedAnonymously'] ? ' checked' : false); ?> name="submit_anonymous" type="checkbox">
				<i></i>
				<span class="small">Submit anonymously</span>
			</label>
			<?php } ?>
			<input type="submit" class="row btn blue big" value="Submit Comment" />
		</fieldset>
	</form>
	<?php } ?>
</div>
