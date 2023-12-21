<div class="special-tabs">
	<div class="left rows-30">
  		<div class="row rows-15">
			<div class="row rows-10">
				<?php
				if ($this->blogPostingPrivileges){
					$URL = URL . 'forum/create_post/';
		
					if( isset($this->blogAlias) && $this->blogAlias )
						$URL .= $this->blogAlias;
		
					$this->renderMemberButton(
						$URL,
						'<i class="' . Icon::getClass('RSS') . '"></i>' . ($this->UserVendor && !$this->UserMod ? 'New Vendor Update' : 'New Blog Post'),
						'row btn green wide big'
					);
					echo ' ';
				}
				
				$this->renderMemberButton(
					URL . 'forum/create/' . (
						$this->categoryID
							?	$this->categoryAlias . '/'
							:	FALSE
					),
					'<i class="' . Icon::getClass('COMMENT') . '"></i>New Discussion',
					'row btn blue wide big'
				);
				
				if ($this->hasReviewableListings){
					echo ' ';
					$this->renderMemberButton(
						URL . 'forum/create/review/',
						'<i class="' . Icon::getClass('star-half-o') . '"></i>New Product Review',
						'row btn wide big yellow'
					);
				} ?>
			</div>
			<form class="row" method="post" action="<?php echo URL . 'forum/search/'; ?>">
				<label class="text search">
					<input class="prepend" name="q" placeholder="Search Forum" type="search"<?php echo $this->searchQuery ? ' value="' . $this->searchQuery . '"' : false; ?>>
					<i class="<?= Icon::getClass('SEARCH'); ?>"></i>
					<button type="submit" class="btn arrow-right xs"></button>
				</label>
			</form>
			<?php $isOwnThread =
				isset($this->discussionID) &&
				$this->discussionID == $this->userDiscussion['ID'];
			
			$viewingOwnDiscussion =
				isset($this->discussionID) &&
				$isOwnThread;
					
			if($this->categoryID || $this->searchQuery){ ?>
			<nav class="row">
				<a href="<?php echo URL; ?>"><i class="<?= Icon::getClass('HOME'); ?>"></i>All Discussions<span><?php echo $this->totalDiscussionCount; ?></span></a>
			</nav>
			<?php } ?>
			<nav class="row">
				<?php
				
				$prevLabel = false;
				foreach ($this->discussionCategories as $key => $discussionCategory){
					if (
						$isActive =
							!$viewingOwnDiscussion &&
							$discussionCategory['ID'] == $this->categoryID
					)
						$activeCategory = &$this->discussionCategories[$key];
						
					if($prevLabel !== $discussionCategory['groupLabel'])
						echo '<h5>' . $discussionCategory['groupLabel'] . '</h5>';
						
					$prevLabel = $discussionCategory['groupLabel'];
				?>
				<a<?php echo $isActive ? ' class="active"' : FALSE ?> href="<?php echo $discussionCategory['URL']; ?>"><?php echo $discussionCategory['name'] ?><span><?php echo $discussionCategory['discussionCount'] ?></span></a>
				<?php } ?>
			</nav>
			<?php if($this->UserVendor){ ?>
			<nav class="row">
				<?php if($this->userBlog || 1 == 1) { ?>
				<a<?php echo $isOwnThread ? ' class="active"' : FALSE ?> href="<?php echo URL . 'blog/' . $this->userBlog['Alias'] . '/'; ?>">My Blog</a>
				<?php } else {
					$post =
						isset($_SESSION['comment_post'])
							? $_SESSION['comment_post']
							: FALSE;
					$feedback =
						isset($_SESSION['comment_feedback'])
							? $_SESSION['comment_feedback']
							: FALSE;
							
					unset($_SESSION['user_discussion_post'], $_SESSION['user_discussion_feedback']);
				?>
				<label for="vendor-thread">My Blog</label>
				<input type="checkbox" id="vendor-thread" hidden>
				<div class="modal" id="vendor-thread">
					<label for="vendor-thread"></label>
					<div>
						<label class="close" for="vendor-thread">&times;</label>
						<form class="rows-10" method="post" action="<?php echo URL . 'forum/create_vendor_discussion/' ?>">
							<div class="row formatted">
								<p>Being a vendor, you are entitled to your own, personal vendor-blog. This is a place where you can present yourself, your products and your business and buyers may post their reviews, questions and remarks.</p>
								<p>Create your first post (can be edited later):</p>
							</div>
							<label class="row textarea<?php echo isset($feedback['content']) ? ' invalid' : FALSE ?>">
								<textarea name="content" rows="10"><?php echo isset($post) ? $post['content'] : FALSE ?></textarea>
								
								<?php if( isset($feedback['content']) ){ ?>
								<p class="note"><?php echo $feedback['content']; ?></p>
								<?php } else { ?>
								<p class="note"><strong>Allowed tags:</strong> [b] <strong>bold text</strong> [/b], [i] <em>italicized text</em> [/i], [a=http://yourlink.com] <a>links</a> [/a] and [pgp] [/pgp] for pgp blocks or other non-formatted text.</p>
								<?php } ?>
							</label> 
							<div class="row align-right">
								<button type="submit" class="btn blue arrow-right">Create vendor thread</button>
							</div>
						</form>
					</div>
				</div>
				<?php } ?>
			</nav>
			<?php } ?>
  		</div>
  		<div class="row">
			<h5 class="band"><span>Latest Updates</span></h5>
			<ul class="x-small big-list vendor-threads">
				<?php foreach( $this->latestUpdates as $latestUpdate ){ ?>
				<li id="updates-<?php echo $latestUpdate['userAlias']; ?>">
					<?php if( $latestUpdate['icon'] ){ ?>
					<i class="<?php echo $latestUpdate['icon']; ?>"></i>
					<?php } elseif( $latestUpdate['image'] ){ ?>
					<div class="image" <?php echo 'style="background-image:url(' . $latestUpdate['image'] . ')"'; ?>></div>
					<?php } ?>
					<div class="main">
						<div><span>
							<a href="<?php echo URL . 'u/' . $latestUpdate['userAlias'] . '/' ?>"><strong><?php echo $latestUpdate['userAlias']; ?></strong></a><br>
							<a href="<?php echo $latestUpdate['URL']; ?>"><?php echo $latestUpdate['content']; ?></a><br>
							<strong><?php echo $latestUpdate['dateUpdated']; ?></strong>
						</span></div>
					</div>
				</li>
				<?php } ?>
			</ul>
		</div>
	</div>
	<div class="right rows-30">
