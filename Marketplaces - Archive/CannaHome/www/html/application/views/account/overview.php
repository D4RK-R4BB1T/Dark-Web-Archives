<?php 

$greetings = [
	'Hi',
	'Wassup',
        'Hey'
];
$greeting = $greetings[mt_rand(0, count($greetings) - 1)];

$afterGreetings = [
	'Great to see you!',
	'Thanks for dropping in!',
	'Good to see you again.'
//	'Vote Pete in 2020!'
];
$afterGreeting = $afterGreetings[mt_rand(0, count($afterGreetings) - 1)];

//$emoji = FRONTPAGE_GREETING_EMOJIS[mt_rand(0, count(FRONTPAGE_GREETING_EMOJIS) - 1)];

?>
<div class="content rows-30">
	<div class="row header">
		<h2 id="greeting">
			<span><?php echo $greeting . ' ' . (empty($this->UserAlias) || (isset($_SESSION['new_user']) && $_SESSION['new_user']) ? 'there' : $this->UserAlias) . '. ' . $afterGreeting; ?></span>
			<?php /*?><div>
				<div class="big-dropdown">
					<span>User Actions</span>
					<a class="toggle">More</a>
					<ul class="dropdown">
					  <li><button type="submit" form="filter" name="sort" value="price_asc" class="dropdown-link">Delete Account</button></li>
					  <li><button type="submit" form="filter" name="sort" value="price_desc" class="dropdown-link">Generate Backup</button></li>
					</ul>
				</div>
			</div><?php */?>
		</h2>
	</div>
	<div class="row cols-20">
		<div class="col-6 rows-40" id="notifications-column">
			<?php if ($this->UserVendor){ ?>
			<input<?php echo $this->enableLiveUpdates ? ' checked' : false; ?> type="checkbox" hidden id="toggle-live_updates">
			<input<?php echo $this->enableLiveUpdates ? false : ' disabled'; ?>  type="checkbox" hidden id="toggle-notifications">
			<?php } ?>
			<h3 class="band">
				<span>Notifications</span>
				<?php if ($this->UserVendor){ ?>
				<div style="display: none">
					<label class="btn <?php echo $this->enableLiveUpdates ? 'red' : 'green' ?>" for="toggle-live_updates">
						<i class="<?= Icon::getClass('RSS'); ?>"></i><?php echo $this->enableLiveUpdates ? 'Disable' : 'Enable' ?> Live Updates
					</label>
					<label class="btn xs <?php echo $this->enableLiveUpdates ? 'green' : 'disabled' ?>" for="toggle-notifications">
						<i class="<?= Icon::getClass('BELL'); ?>"></i>
					</label>
				</div>
				<?php } ?>
			</h3>
			<?php
			if (
				!$this->renderNotifications(
					['Dashboard'],
					'list',
					[
						'<ul id="dashboard-notfs" class="row list-notfs">',
						'</ul>'
					]
				)
			){ ?>
			<strong class="row">No New Notifications</strong>
			<?php }
			if ($this->forumEntries){ ?>
			<h3 class="row band">
				<span>Forum Posts</span>
				<div>
					<?php if ($this->UserVendor) { ?><a href="<?= $this->ForumURL . 'forum/create_post/'; ?>" target="_blank" class="btn yellow"><i class="<?= Icon::getClass('RSS'); ?>"></i>Post Update</a><?php } ?>
					<a href="<?= $this->ForumURL ?>" target="_blank" class="btn arrow-right">View Forum</a>
				</div>
			</h3>
			<ul class="big-list zebra x-small">
				<?php foreach ($this->forumEntries as $discussion){
					if ($discussion['PosterIsAnonymous'])
						$discussion['posterAlias'] = '<em>Anonymous</em>';
					
					$entryClass = rtrim(
						'poster-' . strip_tags($discussion['posterAlias']) . ' ' .
						($discussion['highlighted'] ? ' highlight ' : false) .
						$discussion['color']
					);
					$isPersonalBlogPost =
						$discussion['Type'] == 'BlogPost' &&
						$discussion['blogTitle'] == $discussion['posterAlias'];
					?>
				<li class="<?= $entryClass; ?>">
					<?php if ($discussion['posterImage']) { ?>
					<div class="image" style="background-image:url(<?php echo $discussion['posterImage'] ?>)"></div>
					<?php }
					if ($discussion['dismissableNotificationID']){ ?>
					<a class="dismiss" href="?do[DismissForumNotification]=<?= $discussion['dismissableNotificationID']; ?>"></a>
					<?php } ?>
					<div class="main">
						<div>
							<a target="_blank" href="<?php
								if (
									$discussion['reportedCommentID'] ||
									(
										$discussion['newEntries'] &&
										$discussion['seenCommentID']
									)
								)
									echo
										URL .
										'go/forum/' .
										(
											$discussion['Type'] == 'BlogPost'
												? 'forum/blog_post_comment'
												: 'comment'
										) .
										'/' .
										($discussion['reportedCommentID'] ?: $discussion['seenCommentID']) .
										'/';
								else
									echo
										URL .
										'go/forum/' .
										(
											$discussion['Type'] == 'BlogPost'
												? 'post'
												: 'discussion'
										) . '/' . 
										$discussion['ID'] . '/';
							?>"><?= $discussion['title'] ?></a><br>
							<span>Posted on <strong><?= date('F j Y', strtotime($discussion['dateInserted'])); ?></strong> by <a<?= $isPersonalBlogPost ? ' target="_blank"' : false; ?> class="color-blue" href="<?=
								$isPersonalBlogPost
									? URL . 'go/forum/blog/' . $discussion['blogAlias'] . '/'
									: !$discussion['PosterIsAnonymous'] ? URL . 'u/' . $discussion['posterAlias'] . '/' : '#' ?>"><?= $discussion['posterAlias']; ?></a></span>
						</div>
					</div>
				</li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
		<div class="col-6">
			<h3 class="band">
				<span>Your Stats</span>
				<div>
					<a class="btn" href="<?php echo URL . ($this->UserVendor ? 'v' : 'u') . '/' . $this->UserAlias . '/'; ?>">View Profile</a>
				</div>
			</h3>
			<?php if($this->showStats){ ?>
			<ul class="big-list zebra">
				<li>
					<div class="aux">
						<div>
							<div class="rating stars">
								<?php $this->renderRating($this->userStats['rating']); ?>
								[<?php echo NXS::formatDecimal($this->userStats['rating']); ?>]
							</div>
						</div>
					</div>
					<div class="main">
						<div>Rating<?php if ($this->userStats['ratingCount'] > 0) { ?>
							<br><span>
							<?php echo ($this->userStats['commentCount'] > 0 ? '<a href="' . URL . ($this->UserVendor ? 'v' : 'u') . '/' . $this->UserAlias . '/comments/"' : '<span' ) . '>'; ?>(<?php echo $this->userStats['ratingCount'] . ' rating' . ($this->userStats['ratingCount'] == 1 ? false : 's') ?>)<?php echo '</' . ($this->userStats['commentCount'] > 0 ? 'a' : 'span') . '>'; ?></span>
							<?php } ?>
						</div>
					</div>
				</li>
				<?php if ($this->UserVendor) { ?>
				<li>
					<div class="aux">
						<div><?php echo $this->userStats['salesCount'] ?></div>
					</div>
					<div class="main">
						<div>Sales</div>
					</div>
				</li>
				<?php } elseif($this->userStats['ratingCount']) { ?>
				<li>
					<div class="aux">
						<div><?php echo $this->userStats['ratingCount'] ?></div>
					</div>
					<div class="main">
						<div>Purchases</div>
					</div>
				</li>
				<?php } if ($this->userStats['followersCount'] > 0) { ?>
				<li>
					<div class="aux">
						<div><?php echo $this->userStats['followersCount'] ?></div>
					</div>
					<div class="main">
						<div>Followers</div>
					</div>
				</li>
				<?php } 
				if ($fundsInEscrow = $this->userStats['fundsInEscrow']){ ?>
				<li>
					<div class="aux">
						<div>
							<?php
								foreach ($fundsInEscrow['cryptocurrencies'] as $cryptocurrencies)
									echo $cryptocurrencies['formatted'] . '<br>';
							?><span><?= $fundsInEscrow['total']; ?></span>
						</div>
					</div>
					<div class="main">
						<div>Funds in Escrow</div>
					</div>
				</li>
				<?php } if( $this->distinctions ) { ?>
				<li>
					<ul class="awards grayscale">
						<?php foreach( $this->distinctions as $distinction ) { ?>
						<li class="<?php echo $distinction['style']; ?>">
							<i class="<?php echo $distinction['icon'] ?>"></i>
							<div class="hint">
								<span><?php echo $distinction['name']; ?></span>
							</div>
						</li>
						<?php } ?>
					</ul>
				</li>
				<?php } ?>
			</ul>
			<?php } else { ?>
			<div class="blur-view">
				<div><a class="btn" href="<?php echo URL . 'account/overview/stats/'; ?>">Load Statistics</a></div>
				<ul class="big-list zebra">
					<li>
						<div class="aux">
							<div>
								<div class="rating stars">
									<i class="full"></i><i class="full"></i><i class="full"></i><i class="full"></i><i class="full"></i>								[4.89]
								</div>
							</div>
						</div>
						<div class="main">
							<div>Rating<br><span><strong>(123 ratings)</strong></span></div>
						</div>
					</li>
					<li>
						<div class="aux">
							<div>420</div>
						</div>
						<div class="main">
							<div>Sales</div>
						</div>
					</li>
					<li>
						<div class="aux">
							<div>420</div>
						</div>
						<div class="main">
							<div>Followers</div>
						</div>
					</li>
					<li>
						<div class="aux">
							<div>1.2345 BTC<br><span>$1235.67</span></div>
						</div>
						<div class="main">
							<div>Funds in Escrow</div>
						</div>
					</li>			
				</ul>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php if( !$this->UserVendor && 1 == 3 ) { ?>
	<hr>
	<div class="row">
		<h3 class="band">
			<span>Listings that may interest you</span>
			<div>
				<a class="btn arrow-right blue" href="<?php echo URL . 'listings/'; ?>">View All Listings</a>
			</div>
		</h3>
		<?php if( $this->OverviewListings ) { ?>
		<ul class="row listings grid">
			<?php foreach( $this->OverviewListings as $overview_listing ) { ?>
			<li>
				<div class="vendor"><a href="<?php echo URL . 'v/' . $overview_listing['alias'] . '/' ?>"><?php echo $overview_listing['alias']; ?></a></div>
				<a class="listing" href="<?php echo URL . 'i/' . NXS::getB36($overview_listing['id']) . '/'; ?>">
					<div class="image"<?php echo $overview_listing['image'] ? ' style="background-image:url(\'' . $overview_listing['image'] . '\')"' : FALSE; ?>></div>
					<div class="info">
						<div class="name"><div><span><?php echo $overview_listing['name'] ?></span></div></div>
					</div>
				</a>
				<div class="price">
					<div>
						<?php if( $this->UserCurrency['ISO'] !== 'BTC' ) { ?>
						<span class="big"><?php echo $overview_listing['price'] ?></span> <span class="small"><?php echo $overview_listing['price_btc']; ?></span>
						<?php } else { ?>
						<span class="big"><?php echo $overview_listing['price_btc'] ?></span>
						<?php } ?>
					</div>
				</div>
				<?php if( $overview_listing['rating_count'] > 0 ){ ?>
				<div class="overlay">
					<div class="rating stars color-yellow">
						<span>(<?php echo $overview_listing['rating_count'] . ' rating' . ($overview_listing['rating_count'] == 1 ? FALSE : 's'); ?>)</span>
						<?php $this->renderRating($overview_listing['rating']); ?>
					</div>
				</div>
				<?php } ?>
			</li>
			<?php } ?>
		</ul>
		<?php } ?>
	</div>
	<?php } ?>
</div>
