<?php
	$URLPrefix = URL . 'post/' . $this->blogPost['ID'] . '/';
	
	$numberOfPages = ceil($this->blogPost['CommentCount']/BLOG_COMMENTS_PER_PAGE);
	
	$post = isset($_SESSION['blog_post_comment_post']) ? $_SESSION['blog_post_comment_post'] : false;
	$feedback = isset($_SESSION['blog_post_comment_feedback']) ? $_SESSION['blog_post_comment_feedback'] : false;

	unset(
		$_SESSION['blog_post_comment_post'],
		$_SESSION['blog_post_comment_feedback']
	);
	
	$isPoster = $this->blogPost['PosterAlias'] == $this->UserAlias;
	
	$canComment =
		$this->UserMod ||
		$this->blogPost['Closed'] == FALSE;
?>
<div class="rows-30">
	<h2 class="row band">
		<span><a href="<?php echo URL . 'blog/' . $this->blogPost['BlogAlias'] . '/'; ?>"><?php echo $this->blogPost['BlogTitle']; ?></a></span>
		<div>
		<?php
			$currentSort = explode('_', $this->sortMode);
			if( count($currentSort) > 1 ){
				$sortURL = $URLPrefix . $currentSort[0] . '_' . ($currentSort[1] == 'asc' ? 'desc' : 'asc') . '/#comments';
				$upOrDown = $currentSort[1] == 'asc' ? 'up' : 'down';
				echo '<a class="btn minimal xs" href="' . $sortURL . '"><i class="candy-sort ' . $upOrDown . '"></i></a>';
			}?>
			<a class="btn <?php echo $this->blogPost['Subscribed'] ? 'red' : 'blue'; ?>" href="<?php echo URL . 'forum/toggle_subscription/blog_post/' . $this->blogPost['ID'] . '/' . $this->sortMode . '/' . $this->pageNumber . '/'; ?>">
				<i class="<?= Icon::getClass('RSS'); ?>"></i>
				<?= $this->blogPost['Subscribed'] ? 'Unsubscribe' : 'Subscribe'; ?>
			</a>
			<?php 
			
			$hasPostingPrivileges = $this->blogPostingPrivileges && array_key_exists($this->blogPost['BlogID'], $this->blogPostingPrivileges);
			if($hasPostingPrivileges){ ?>
			<a class="btn green" href="<?php echo URL . 'forum/create_post/' . $this->blogPost['BlogAlias'] . '/'; ?>">
				<i class="<?= Icon::getClass('WRITE', true); ?>"></i>
				New Entry
			</a>
			<?php } 
			$canDeletePost =
				$this->UserMod ||
				$isPoster;
			if ($canDeletePost){
				$deleteModalID = 'deletePost'; ?>
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
						<div class="col-6"><a href="<?= URL . 'forum/delete_blog_post/' . $this->blogPost['ID'] . '/'; ?>" class="btn wide">Delete It</a></div>
						<div class="col-6">
							<label for="<?= $deleteModalID; ?>" class="btn wide red">Nevermind</label>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</h2>
	<ul class="row list-posts">
		<li>
			<span class="anchor" id="<?php echo 'post-' . $this->blogPost['ID'] ?>"></span>
			<div class="post-header">
				<a href="<?php echo URL . 'u' . '/' . $this->blogPost['PosterAlias'] . '/' ?>" class="poster">
					<?php if ( $this->blogPost['PosterImage'] ) { ?>
					<figure style="background-image: url(<?php echo $this->blogPost['PosterImage'] ?>)"></figure><?php }
					echo $this->blogPost['PosterAlias']; ?>
				</a>
				<?php
				$this->renderFlairs($this->blogPost['PosterFlairs'], $this->blogPost['PosterAlias']); ?>
				<div class="options">
					<?php 
					if ($canComment && !$isPoster){ ?>
					<label for="new-comment" class="btn minimal"><i class="<?php echo Icon::getClass('COMMENT'); ?>"></i>Comment</label>
					<?php }
					if ($isPoster || $this->UserMod) { ?>
					<a href="#edit-<?php echo $this->blogPost['ID'] ?>" class="btn minimal"><i class="<?php echo Icon::getClass('WRITE', true); ?>"></i>Edit</a>
					<?php } ?>
				</div>
			</div>
			<input type="checkbox" id="edit-<?php echo $this->blogPost['ID'] ?>" hidden>
			<form method="post" action="<?php echo URL . 'forum/edit_blog_post/' . $this->blogPost['ID'] . '/'; ?>" class="content editable rows-5">
				<label class="text big blend-in">
					<input type="text" placeholder="Title" pattern="<?php echo REGEX_BLOG_POST_TITLE; ?>" name="title" value="<?php echo $this->blogPost['Title']; ?>">
				</label>
				<label class="row textarea blend-in">
					<textarea rows="15" name="content" required><?php echo $this->blogPost['RawContent']; ?></textarea>
				</label>
				<div class="row align-right">
					<button class="btn green" type="submit">Save</button>
					<a class="btn red" href="#">Cancel</a>
				</div>
			</form>
			<div class="content">
				<?php if($this->blogPost['Status']) { ?>
				<span class="badge"><?php echo $this->blogPost['Status']; ?></span>
				<?php }
				if($this->blogPost['Title']) { ?>
				<h6><?php echo $this->blogPost['Title'] ?></h6>
				<p class="subtitle">Posted on: <?php echo $this->blogPost['DateInserted']; ?></p>
				<?php } ?>
				<?php echo $this->blogPost['HTML']; ?>
				<?php echo $this->blogPost['PosterSignature'] ? '<div class="signature">' . $this->blogPost['PosterSignature'] . '</div>' : false; ?>
			</div>
			<div class="footer tall">
				<div class="cols-10">
					<div class="col-4">
						Posted on: <strong><?php echo $this->blogPost['DateInserted']; ?></strong>
					</div>
					<div class="col-4">
						<?php if($this->blogPost['CommentCount'] > 1){
						echo
							'<strong>' .
							NXS::formatNumber($this->blogPost['CommentCount']) .
							'</strong> comment' .
							(
								$this->blogPost['CommentCount'] == 1
									? FALSE
									: 's'
							);
						} else { ?>
						&nbsp;
						<?php } ?>
					</div>
					<div class="col-4">
						<?php
						$this->renderMemberButton(
							URL . 'blog/' . $this->blogPost['BlogAlias'] . '/',
							'<i class="' . Icon::getClass('RSS') . '"></i>View All Posts in Series',
							'btn minimal'
						);
						?>
					</div>
				</div>
			</div>
			<?php if($this->blogPost['Comments']) { ?>
			<ol id="comments" class="list-discussion blog-comments">
				<?php
				if ($this->pageNumber > 1) { ?>
				<li class="prev-in-chain">
					<a href="<?= $URLPrefix; ?>"><strong><?= NXS::formatNumber($this->blogPost['CommentCount'] - count($this->blogPost['Comments'])); ?></strong> comments <?= $this->sortMode == 'id_desc' ? 'after' : 'before'; ?> this&hellip;</a>
				</li>
				<?php }
				$editableComments = [];
				$hasUnreadCommentsOnNextPage = false;
				foreach($this->blogPost['Comments'] as $comment){
					$isCommenter = $comment['CommenterAlias'] == $this->UserAlias;
					
					$hasUnreadCommentsOnNextPage =
						$hasUnreadCommentsOnNextPage ||
						(
							$this->blogPost['SeenCommentID'] &&
							$comment['ID'] == $this->blogPost['SeenCommentID'] &&
							$numberOfPages > $this->pageNumber
						);
				?>
				<li id="comment-<?php echo $comment['ID'] ?>" class="poster-<?php echo $comment['CommenterAlias'] . ' ' . $comment['CommenterColor']; ?>">
					<?php if($comment['CommenterImage']){ ?>
					<div class="avatar">
						<div style="background-image: url(<?php echo $comment['CommenterImage']; ?>)"></div>
					</div>
					<?php } ?>
					<div class="meta<?php echo $this->UserMod ? ' mod-controls' : FALSE; ?>">
						<a<?php echo $comment['CommenterFlair'] ? ' data-flair="' . $comment['CommenterFlair']['text'] . '"' : false; ?> class="username<?php echo $comment['CommenterFlair'] ? ' flair-' . $comment['CommenterFlair']['color'] : false; ?>" href="<?php echo URL . 'u/' . $comment['CommenterAlias'] . '/'; ?>"><?php echo $comment['CommenterAlias']; ?></a>
						<time><?= $comment['DateInserted']; ?></time>
						<?php if($comment['Score'] != 0) { ?>
						<div class="score"><?php echo $comment['Score']; ?></div>
						<?php } ?>
						<div class="btns">
							<?php
							if ($canComment){ ?>
							<a href="<?php echo URL . 'forum/reply_blog_post_comment/' . $comment['ID'] . '/'; ?>" class="btn xs color-blue">
								<i class="<?php echo Icon::getClass('REPLY'); ?>"></i>
								<div class="hint below">
									<span>Reply</span>
								</div>
							</a><a href="<?php echo URL . 'forum/quote_blog_post_comment/' . $comment['ID'] . '/'; ?>" class="btn xs color-green">
								<i class="<?php echo Icon::getClass('QUOTE-LEFT'); ?>"></i>
								<div class="hint below">
									<span>Quote</span>
								</div>
							</a><?php }
							if($comment['RawContent']){
								$editableComments[] = $comment; ?><a href="<?php echo '#edit-' . $comment['ID']; ?>" class="btn xs color-purple">
								<i class="<?php echo Icon::getClass('WRITE', true); ?>"></i>
								<div class="hint below">
									<span>Edit</span>
								</div>
							</a><?php } ?>
						</div>
						<?php /* Voting ARROWS
						<div class="arrows">
							<a class="up<?php echo $comment['Vote'] == 1 ? ' voted' : FALSE; ?>" href="<?php echo URL . 'forum/upvote_blog_post_comment/' . $this->blogPost['ID'] . '/'; ?>"></a>
							<a class="down<?php echo $comment['Vote'] == -1 ? ' voted' : FALSE; ?>" href="<?php echo URL . 'forum/unvote_blog_post_comment/' . $this->blogPost['ID'] . '/'; ?>"></a>
						</div> */ ?>
						<b></b>
					</div>
					<div class="comment">
						<div>
							<div class="messages">
								<div class="formatted"><?php echo $comment['HTML']; ?></div>
							</div>
						</div>
					</div>
				</li>
				<?php }	 ?>
			</ol>
			<?php } ?>
		</li>
	</ul>
	<?php 
	if($editableComments){ 
		foreach($editableComments as $comment){ ?>
	<div class="modal" id="edit-<?php echo $comment['ID']; ?>">
		<a href="#"></a>
		<div>
			<a class="close" href="#">&times;</a>
			<form class="rows-10" method="post" action="<?php echo URL . 'forum/edit_blog_post_comment/' . $comment['ID'] . '/' ?>">
				<label class="row textarea">
					<textarea rows="10" name="content-<?php echo $comment['ID']; ?>"><?php echo isset($post['content-' . $comment['ID']]) ? $post['content-' . $comment['ID']] : $comment['RawContent']; ?></textarea>
				</label>
				<input class="row btn wide" value="edit" type="submit">
			</form>
		</div>
	</div>
		<?php }
	}
	if ($this->blogPost['CommentCount'] > BLOG_COMMENTS_PER_PAGE){
		$hasPagination = TRUE ?>
	<div class="panel">
		<?php
		$this->renderPaginationPanel(
			$this->pageNumber,
			$numberOfPages,
			$URLPrefix . $this->sortMode . '/',
			'/#comments',
			[
				false,
				$hasUnreadCommentsOnNextPage ? 'yellow' : false
			]
		);
		?>
	</div>
	<?php }
	
	if($canComment) {
	$hasMarginTop = $hasPagination || $this->blogPost['Comments'] == FALSE;
	
	?>
	<form<?php echo $hasMarginTop ? ' class="row"' : FALSE; ?> method="post" action="<?php echo URL . 'forum/post_blog_post_comment/' . $this->blogPost['ID'] . '/'; ?>">
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
			<input type="submit" class="row btn blue big" value="Submit Comment" />
		</fieldset>
	</form>
	<?php } ?>
</div>
