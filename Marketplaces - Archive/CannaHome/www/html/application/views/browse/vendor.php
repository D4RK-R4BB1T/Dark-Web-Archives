<div class="rows-30 vendor-<?php echo $this->vendor['alias']; ?>">
	<div class="row vendor-box rows-15">
		<div class="row">
			<?php if($this->vendor['image']) { ?>
			<img src="<?php echo $this->vendor['image']; ?>">
			<?php } ?>
			<div class="main-infos">
				<h2><?php echo $this->vendor['alias']; ?></h2>
				<?php if($this->vendor['categories']){ ?>
				<p><?php echo implode(', ', $this->vendor['categories']) . ' vendor'; ?></p>
				<?php } if($this->vendor['is_vendor']){ ?>
				<div class="rating stars <?php echo $this->vendor['rating'] > 1  ? 'color-yellow' : FALSE ?>">
					<?php
						if( ($this->vendor['rating_count'] > 0) * ($this->vendor['rating'] > 0) ){
							$displayRating = number_format($this->vendor['rating'], 2);
							$this->renderRating($this->vendor['rating']);
							echo ' [' . $displayRating . ']';
							if (
								$this->vendor['commentCount'] > 0 &&
								!$this->isForum
							)
								$link = 'a href="' . ($profile_url . 'comments/') . '"';
							else
								$link = false;
							echo '<' . ($link ? $link : 'strong') . '>(' . $this->vendor['rating_count'] . ($this->vendor['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($this->vendor['rating_count'] == 1 ? FALSE : 's') . ')</' . ($link ? 'a' : 'strong') . '>';
						} else
							echo '<strong>No Ratings</strong>';
					 ?>
				</div>
				<?php } ?>
			</div>
			<div class="corner">
				<ul class="zebra x-small big-list">
					<?php /* if( $this->vendor['listing_count'] ) { ?>
					<li>
						<div class="aux">
							<div><span class="color-blue"><?php echo $this->vendor['listing_count'] ?></span></div>
						</div>
						<div class="main">
							<div><span>Listings</span></div>
						</div>
					</li>
					<?php } if( $this->vendor['sell_count'] > 0 ) { ?>
					<li>
						<div class="aux">
							<div><span class="color-blue"><?php echo $this->vendor['sell_count'] ?></span></div>
						</div>
						<div class="main">
							<div><span>Sales</span></div>
						</div>
					</li>
					<?php } } */ ?>
					<li>
						<div class="aux">
							<div><span><?php echo $this->vendor['lastSeen'] ?></span></div>
						</div>
						<div class="main">
							<div><span>Last Seen</span></div>
						</div>
					</li>
					<?php if ($this->vendor['postCount'] > 0) { ?>
					<li>
						<div class="aux">
							<div><span><?= $this->vendor['postCount'] ?></span></div>
						</div>
						<div class="main">
							<div><span><?= $this->vendor['is_vendor'] ? '<a class="tooltip" target="_blank" href="/go/forum/blog/' . $this->vendor['alias'] . '/">' : false ?>Forum Posts<?= $this->vendor['is_vendor'] ? '</a>' : false ?></span></div>
						</div>
					</li>
					<?php } if( $this->vendor['followerCount'] > 0 ) { ?>
					<li>
						<div class="aux">
							<div><span><?php echo $this->vendor['followerCount'] ?></span></div>
						</div>
						<div class="main">
							<div><span>Followers</span></div>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php
					if(
						$this->vendor['is_vendor'] &&
						$this->vendor['relatedListings']
					){ ?>
				<a href="<?php echo $profile_url . 'listings/' ?>" class="btn wide purple arrow-right"><i class="<?php echo Icon::getClass('LIST'); ?>"></i>View All Vendor's Listings</a>
				<?php }
				elseif (
					$this->vendor['is_vendor'] &&
					$this->isForum
				){ ?>
				<a target="_blank" href="<?= 'http://' . $this->db->accessDomain . '/v/' . $this->vendor['alias'] . '/'; ?>" class="btn wide green arrow-right"><i class="<?php echo Icon::getClass('CART'); ?>"></i>View Market Profile</a>
				<?php } ?>
			</div>
		</div>
		<?php if( $this->vendor['update'] ) {
			$target = $this->isForum
				? FALSE
				: 'target="_blank" ';
			 ?>
		<div class="row grey-box formatted">
			<div class="buttons">
				<a <?php echo $target ?>class="btn" href="<?php echo $this->ForumURL . 'forum/comment_blog_post/' . $this->vendor['update']['ID'] . '/' ?>">
					<i class="<?php echo Icon::getClass('COMMENT'); ?>"></i>
					Comment
				</a><a <?php echo $target ?>class="btn" href="<?php echo $this->ForumURL . 'blog/' . $this->vendor['update']['BlogAlias'] . '/' ?>">
					<i class="<?php echo Icon::getClass('RSS'); ?>"></i>
					View all updates
				</a>
			</div>
			<div class="formatted">
				<?php echo $this->vendor['update']['content'] ?>
			</div>
		</div>
		<?php } ?>
	</div>
	<div class="row special-tabs">
		<?php if( !empty($this->vendor['sections']) ) { foreach($this->vendor['sections'] as $section) { ?>
		<input id="<?php echo $section['anchor']; ?>" name="vendor-section" type="radio">
		<?php } } ?>
		<input hidden type="radio" id="send-pm" name="tab">
		<div class="left rows-20">
			<?php if( !empty($this->vendor['sections']) ) { ?>
			<nav class="row" data-title="Vendor's Profile Pages">
				<?php  foreach($this->vendor['sections'] as $section) { ?>
				<a href="#<?php echo $section['anchor']; ?>"><?php echo $section['name']; ?></a>
				<?php } ?>
			</nav>
			<?php } else { ?>
			<nav class="row">
				<a>Send PM</a>
			</nav>
			<?php } if(
			    $this->vendor['is_vendor'] &&
			    $this->vendor['alias'] !== $this->UserAlias
			){ ?>
			<div class="row cols-5">
				<div class="col-6">
					<?php $this->renderMemberButton(
						'#send-pm',
						'<i class="' . Icon::getClass('ENVELOPE') . '"></i>Send PM',
						'btn wide blue'
					); ?>
				</div>
				<div class="col-6">
					<?php $this->renderMemberButton(
						'?do[ToggleUserSubscription]=' . $this->vendor['alias'],
						'<i class="' . Icon::getClass('RSS') . '"></i>' . ($this->vendor['isFollowing'] ? 'Un-follow' : 'Follow'),
						'btn wide ' . ($this->vendor['isFollowing'] ? 'minimal' : 'red')
					); ?>
				</div>
			</div>
			<?php } 
			if ($this->UserMod){ ?>
			<div class="row cols-5">
				<div class="col-6">
					<a class="btn <?php echo $this->vendor['banned'] ? 'minimal' : 'yellow'; ?>" href="<?php echo URL . 'admin/toggle_user_banned/' . $this->vendor['alias'] . '/'; ?>"><?php echo ($this->vendor['banned'] ? 'Unban' : 'Ban') . ' user'; ?></a>
				</div>
			</div>
			<?php } if ($this->vendor['pgp'] ) { ?>
			<ul class="row list-expandable">
				<li>
					<input id="pgp-public-key" class="expand" type="checkbox">
					<label for="pgp-public-key">PGP Public Key<i></i></label>
					<div class="expandable">                            
						<label class="textarea pgp">
							<textarea rows="10" spellcheck="false"><?php echo $this->vendor['pgp']; ?></textarea>
						</label><?php ?>
					</div>
				</li>
			</ul>
			<?php }
			if( $this->vendor['distinctions'] ){
			?>
			<ul class="row awards">
				<?php foreach($this->vendor['distinctions'] as $distinction){ ?><!--
			    --><li class="<?php echo $distinction['Color'] . ' ' . $distinction['Style']; ?>"><i class="<?php echo $distinction['Icon']; ?>"></i><div class="hint"><span><?php echo $distinction['Name']; ?></span></div></li><!--
			    --><?php } ?>
			</ul>
			<?php } ?>
		</div>
		<div class="right">
			<div class="contents">
				<?php if( !empty($this->vendor['sections']) ) { foreach($this->vendor['sections'] as $section) { ?>
				<div class="formatted" style="word-wrap:break-word"><?php echo $section['content']; ?></div>
				<?php } }
				
				ob_start(); ?>
				<form method="post" action="<?php echo URL; ?>account/send_message/">
					<input type="hidden" name="recipient_alias" value="<?php echo $this->vendor['alias']; ?>">
					<fieldset class="rows-10">
						<label class="text pre-textarea">
							<input type="text" placeholder="Subject (optional)" name="subject">
						</label>
						<label class="row textarea">
							<textarea rows="9" name="content" required></textarea>
						</label>
						<div class="row cols-10">
							<label class="col-3 label">Auto-delete :</label>
							<div class="col-4">
								<label class="select">
									<select name="auto_delete">
										<option value="0">Never</option>
										<option value="30">1 month</option>
										<option value="14" selected>2 weeks</option>
										<option value="7">1 week</option>
										<option value="3">3 days</option>
										<option value="1">1 day</option>
									</select>
									<i></i>
								</label>
							</div>
							<div class="col-5 align-right">
								<button class="row btn arrow-right" type="submit">Send Message</button>
							</div>
						</div>
					</fieldset>
				</form>
				<?php $message_form = ob_get_contents(); ob_end_clean();
				
				if($this->vendor['pgp']) {?>
				<div class="cols-10">
					<div class="col-6">
						<?php echo $message_form; ?>
					</div>
					<div class="col-6">
						<label class="textarea" spellcheck="false">
							<textarea rows="15"><?php echo $this->vendor['pgp']; ?></textarea>
						</label>
					</div>
					
				</div>
				<?php } else echo '<div>' . $message_form . '</div>'; ?>
			</div>
		</div>
	</div>
	<?php
	
	$showTabs =
		!$this->isForum &&
		$this->vendor['is_vendor'] &&
		(
			$this->vendor['relatedListings'] ||
			$this->vendor['featured_comments']
		);
	
	if($showTabs) { ?>
	<div class="row">
		<div class="top-tabs">
			<?php if( $this->vendor['relatedListings'] ){ ?>
			<input id="listings-tab" name="other-tabs" type="radio" checked>
			<?php } ?>
			<input id="comments-tab" name="other-tabs" type="radio">
			<ul>
				<?php if( $this->vendor['relatedListings'] ){ ?>
				<li><label for="listings-tab">Listings</label></li>
				<?php } ?>
				<li><label for="comments-tab">Ratings</label></li>
			</ul>
			<?php 
			$hasManyListings = $this->vendor['relatedListings'] && $this->vendor['listing_count'] > count($this->vendor['relatedListings']);
			$hasManyComments = $this->vendor['featured_comments'] && $this->vendor['commentCount'] > count($this->vendor['featured_comments']);
			if($hasManyListings || $hasManyComments){ ?>
			<ul>
				<?php //if($hasManyListings) { ?>
				<li><a href="<?php echo $profile_url . 'listings/' ?>" class="btn arrow-right">View All Listings</a></li>
				<?php //} else echo '<li></li>';
				if($hasManyComments) { ?>
				<li><a href="<?php echo $profile_url . 'comments/' ?>" class="btn arrow-right">View All Ratings</a></li>
				<?php } else echo '<li></li>'; ?>
			</ul>
			<?php }
			if( $this->vendor['relatedListings'] ){ ?>
			<div class="rows-20">
				<ul class="row listings grid">
					<?php foreach( $this->vendor['relatedListings'] as $relatedListing ) { 
						$expandOptionsToggleID = 'expand_options-' . $relatedListing['ID'];
					?>
					<li>
						<?php if($relatedListing['options']){ ?>
						<input type="checkbox" id="<?php echo $expandOptionsToggleID; ?>" hidden>
						<?php } ?>
						<a class="listing" href="<?php echo $url . 'i/' . $relatedListing['B36'] . '/'; ?>">
							<div class="image"<?php echo $relatedListing['Image'] ? ' style="background-image:url(\'' . $relatedListing['Image'] . '\')"' : FALSE; ?>></div>
							<div class="info">
								<div class="name"><div><span><?php echo $relatedListing['Name'] ?></span></div></div>
							</div>
						</a>
						<div class="price">
							<div>
								<span class="big"><?php echo $relatedListing['price'] ?></span> <span class="small"><?php echo $relatedListing['price_crypto']; ?></span>
							</div><?php
								#if($relatedListing['options'])
								#	echo '<label for="' . $expandOptionsToggleID . '" class="btn">more options</label>';
							?>
						</div>
						<?php if ($relatedListing['ratingCount'] > 0){ ?>
						<div class="overlay">
							<div class="rating stars">
								<span>(<?php echo $relatedListing['ratingCount'] . ($relatedListing['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($relatedListing['ratingCount'] == 1 ? false : 's'); ?>)</span>
								<?php $this->renderRating($relatedListing['averageRating']); ?>
							</div>
						</div>
						<?php } 
						if ($relatedListing['options']){
						/* ?>
						<div class="options-overlay">
							<label for="<?php echo $expandOptionsToggleID; ?>"></label>
							<div>
								<?php foreach($relatedListing['options'] as $i => $option){ 
								if(
									$relatedListing['groupMemberCount'] > LISTINGS_GRID_OPTIONS_MAX_QUANTITY &&
									$i == (LISTINGS_GRID_OPTIONS_MAX_QUANTITY - 1)
								){ ?>
								<a class="more-btn" href="<?php echo URL . 'i/' . $relatedListing['B36'] . '/#options'; ?>">
									<div>View All Options <i class="<?php echo Icon::getClass('CARET_RIGHT'); ?>"></i></div>
								</a>
								<?php } else { ?>
								<a href="<?php echo URL . 'i/' . $option['B36'] . '/'; ?>">
									<div><span><?php echo $option['Name']; ?></span></div>
									<div>
										<span class="big"><?php echo $option['price'] ?></span><span class="small"><?php echo $option['price_crypto'] ?></span>
									</div>
								</a>
								<?php } } ?>
							</div>
						</div> */ ?>
						<div class="options">
						<?php
						foreach ($relatedListing['options'] as $i => $option)
							echo	'<a href="' . URL . 'i/' . $option['B36'] . '/">
									<div>
										<span>' . ($relatedListing['trivialOptions'] ? ($option['label'] ?: $option['altLabel']) : $option['Name']) . '</span>
										<span><b>' . $option['price'] . '</b></span>
									</div>
								</a>'; ?>
						</div>
						<?php } ?>
					</li>
					<?php } ?>
				</ul>
				<?php //if($this->vendor['listing_count'] > count($this->vendor['relatedListings']) ) { ?>
				<div class="row centered">
					<a href="<?php echo $profile_url . 'listings/' ?>" class="btn arrow-right">View All Listings</a>
				</div>
				<?php //} ?>
			</div>
			<?php } ?>
			<div class="rows-20">
				<?php if( $this->vendor['featured_comments'] ){ ?>
				<ul class="row list-ratings columns">
					<?php foreach( $this->vendor['featured_comments'] as $featured_comment ) { ?>
					<li>
						<div class="left">
							<div class="rating stars color-yellow">
								<?php $this->renderRating($featured_comment['rating']); ?>
							</div>
							<date><?php echo $featured_comment['date']; ?></date>
							<?php if( $featured_comment['listing'] ) { ?>
							<small><?php echo $featured_comment['listing']; ?></small>
							<?php } ?>
						</div>
						<div class="right formatted">
							<?php echo $this->nl2p($featured_comment['comment']); ?>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php if($this->vendor['commentCount'] > count($this->vendor['featured_comments']) ) { ?>
				<div class="row centered">
					<a href="<?php echo $profile_url . 'comments/' ?>" class="btn arrow-right">View All Ratings</a>
				</div>
				<?php }
				} else { ?>
				<strong>This <?php echo $this->vendor['is_vendor'] ? 'vendor' : 'user'; ?> has not yet received any ratings.</strong>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
