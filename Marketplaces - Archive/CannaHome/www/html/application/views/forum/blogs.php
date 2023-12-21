<?php
	$URLPrefix = URL . 'blogs/';
	
	$numberOfPages = ceil($this->blogs['PostCount']/BLOG_POSTS_PER_PAGE);
?>
<div class="rows-30">
	<h2 class="row band">
		<span>Blog Posts</span>
		<div>
		<?php
			$currentSort = explode('_', $this->sortMode);
			if( count($currentSort) > 1 ){
				$sortURL = $URLPrefix . $currentSort[0] . '_' . ($currentSort[1] == 'asc' ? 'desc' : 'asc') . '/';
				$upOrDown = $currentSort[1] == 'asc' ? 'up' : 'down';
				echo '<a class="btn minimal xs" href="' . $sortURL . '"><i class="candy-sort ' . $upOrDown . '"></i></a>';
			}
			
			/*
			
			$hasPostingPrivileges = $this->blogPostingPrivileges && array_key_exists($this->blog['ID'], $this->blogPostingPrivileges);
			
			if($hasPostingPrivileges){ ?>
			<a class="btn green" href="<?php echo URL . 'forum/create_post/' . $this->blog['Alias'] . '/'; ?>">
				<i class="<?php echo Icon::getClass('WRITE'); ?>"></i>
				New Entry
			</a>
			<?php } */ ?>
		</div>
	</h2>
	<ul class="row list-posts">
		<?php
			$i = 0;
			foreach($this->blogs['BlogPosts'] as $blogPost) { 
				$firstComment =
					$i == 0 &&
					$this->pageNumber == 1;
				$veryLastComment =
					$this->pageNumber == $numberOfPages &&
					$i == count($this->blogs['blogPosts']) - 1;
				
				$isTopPost = //$isOriginalPost =
					(
						$upOrDown == 'up' &&
						$firstComment
					) ||
					(
						$upOrDown == 'down' &&
						$veryLastComment
					);
				
				$isPoster = $blogPost['PosterAlias'] == $this->UserAlias;
		?>
		<li>
			<span class="anchor" id="<?php echo 'post-' . $blogPost['ID'] ?>"></span>
			<div class="post-header">
				<a href="<?php echo URL . 'u' . '/' . $blogPost['PosterAlias'] . '/' ?>" class="poster">
					<?php if ( $blogPost['PosterImage'] ) { ?>
					<figure style="background-image: url(<?php echo $blogPost['PosterImage'] ?>)"></figure>
					<?php } ?>
					<?php echo $blogPost['PosterAlias']; ?>
				</a>
				<div class="options">
					<?php
					if (
						$blogPost['Closed'] == FALSE ||
						$this->UserMod
					){ ?>
					<a class="btn minimal" href="<?= '/forum/comment_blog_post/' .  $blogPost['ID'] . '/'; ?>">
						<i class="<?= Icon::getClass('COMMENT'); ?>"></i>
						Comment
					</a>
					<?php }
					if (
						$isPoster ||
						$this->UserMod
					){ ?>
					<a class="btn minimal" href="<?= URL . 'post/' . $blogPost['ID'] . '/#edit-' . $blogPost['ID']; ?>">
						<i class="<?= Icon::getClass('WRITE', true); ?>"></i>
						Edit
					</a>
					<?php }
					if (
						$this->UserMod ||
						$isPoster
					){
						$deleteModalID = 'deletePost-' . $blogPost['ID']; ?>
					<label class="btn minimal" for="<?= $deleteModalID; ?>">
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
								<div class="col-6"><a href="<?= URL . 'forum/delete_blog_post/' . $blogPost['ID'] . '/'; ?>" class="btn wide">Delete It</a></div>
								<div class="col-6">
									<label for="<?= $deleteModalID; ?>" class="btn wide red">Nevermind</label>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="content">
				<?php if($blogPost['Status']) { ?>
				<span class="badge"><?php echo $blogPost['Status']; ?></span>
				<?php }
				if($blogPost['Title']){ ?>
				<h6><a href="<?php echo URL . 'post/' . $blogPost['ID'] . '/'; ?>"><?php echo $blogPost['Title'] ?></a></h6>
				<p class="subtitle">Posted on: <?php echo $blogPost['DateInserted']; ?></p>
				<?php } ?>
				<?php echo $blogPost['HTML']; ?>
				<?php echo $blogPost['PosterSignature'] ? '<div class="signature">' . $blogPost['PosterSignature'] . '</div>' : false; ?>
			</div>
			<div class="footer tall">
				<div class="cols-10">
					<div class="col-4">
						Posted on: <strong><?php echo $blogPost['DateInserted']; ?></strong>
					</div>
					<div class="col-4">
						<?php if($blogPost['CommentCount'] > 1){
						echo
							'<strong>' .
							NXS::formatNumber($blogPost['CommentCount']) .
							'</strong> comment' .
							(
								$blogPost['CommentCount'] == 1
									? FALSE
									: 's'
							);
						} else { ?>
						&nbsp;
						<?php } ?>
					</div>
					<div class="col-4">
						<?php
						if($blogPost['CommentCount'] > 0)
							$this->renderMemberButton(
								'/post/' .  $blogPost['ID'] . '/#comments',
								'<i class="' . Icon::getClass('COMMENT') . '"></i>See Comments',
								'btn minimal'
							);
						?>
					</div>
				</div>
			</div>
			<?php if($blogPost['Comments']) { ?>
			<ol class="list-discussion blog-comments">
				<?php
				if($blogPost['CommentCount'] > BLOG_POSTS_COMMENTS_COUNT){
				?>
				<li class="prev-in-chain">
					<a href="<?php echo URL . 'post/' . $blogPost['ID'] . '/#comments'; ?>">
						<strong><?php
						$commentCountBeforeThis = ($blogPost['CommentCount'] - BLOG_POSTS_COMMENTS_COUNT);
						
						echo NXS::formatNumber($commentCountBeforeThis); ?></strong> comment<?php echo $commentCountBeforeThis == 1 ? FALSE : 's'; ?> before this&hellip;
					</a>
				</li>
				<?php } ?>
				<?php foreach($blogPost['Comments'] as $comment){ ?>
				<li class="poster-<?php echo $comment['CommenterAlias']; ?>">
					<?php if($comment['CommenterImage']){ ?>
					<div class="avatar">
						<div style="background-image: url(<?php echo $comment['CommenterImage']; ?>)"></div>
					</div>
					<?php } ?>
					<div class="meta<?php echo $this->UserMod ? ' mod-controls' : FALSE; ?>">
						<a class="username" href="<?php echo URL . 'u/' . $comment['CommenterAlias'] . '/'; ?>"><?php echo $comment['CommenterAlias']; ?></a>
						<time><?php echo $comment['DateInserted']; ?></time>
						<?php if($comment['Score'] != 0) { ?>
						<div class="score"><?php echo $comment['Score']; ?></div>
						<?php } ?>
						<div class="btns">
							<a href="<?php echo URL . 'forum/reply_blog_post_comment/' . $comment['ID'] . '/'; ?>" class="btn xs color-blue">
								<i class="<?php echo Icon::getClass('REPLY'); ?>"></i>
								<div class="hint left">
									<span>Reply</span>
								</div>
							</a>
							<?php 
							/*if($this->UserMod){ ?>
							<a href="<?php echo URL . 'forum/edit_blog_post_comment/' . $comment['ID'] . '/'; ?>" class="btn xs color-purple">
								<i class="<?php echo Icon::getClass('WRITE'); ?>"></i>
								<div class="hint below">
									<span>Edit</span>
								</div>
							</a>
							<a href="<?php echo URL . 'forum/delete_blog_post_comment/' . $comment['ID'] . '/'; ?>" class="btn xs color-red">
								<i class="<?php echo Icon::getClass('TIMES'); ?>"></i>
								<div class="hint below">
									<span>Delete</span>
								</div>
							</a>
							<?php }*/ ?>
						</div>
						<?php /* Voting ARROWS
						<div class="arrows">
							<a class="up<?php echo $comment['Vote'] == 1 ? ' voted' : FALSE; ?>" href="<?php echo URL . 'forum/upvote_blog_post_comment/' . $blogPost['ID'] . '/'; ?>"></a>
							<a class="down<?php echo $comment['Vote'] == -1 ? ' voted' : FALSE; ?>" href="<?php echo URL . 'forum/unvote_blog_post_comment/' . $blogPost['ID'] . '/'; ?>"></a>
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
				<?php } ?>
			</ol>
			<?php } ?>
		</li>
		<?php $i++; } ?>
	</ul>
	<?php if( $this->blogs['PostCount'] > BLOG_POSTS_PER_PAGE ){ ?>
	<div class="row panel">
		<?php
			$this->renderPaginationPanel(
				$this->pageNumber,
				$numberOfPages,
				$URLPrefix . $this->sortMode . '/'
			);
		?>
	</div>
	<?php } ?>
</div>
