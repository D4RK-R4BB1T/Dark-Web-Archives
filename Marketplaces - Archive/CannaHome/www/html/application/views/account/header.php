<?php
	$isMessages =
		$this->checkForActiveAction($filename, 'conversations') ||
		$this->checkForActiveAction($filename, 'support') ||
		$this->checkForActiveAction($filename, 'support_overview')
?>
<div class="side-tabs<?php echo $isMessages ? (' tab-messages' . ($this->isSupportPage ? ' support-page' : FALSE )) : FALSE ?>">
	<?php
	if(
		isset($this->collapsedView) &&
		$this->collapsedView
	){ ?>
	<input hidden type="checkbox" checked>
	<?php } ?>
	<nav>
		<?php if($this->isForum){ ?>
		<div class="group">
			<a href="<?= URL ?>">
				<i class="<?= Icon::getClass('CARET_LEFT'); ?>"></i>
				<span>Back to Forum</span>
			</a>
		</div>
		<div class="group">
			<a href="<?php echo URL . 'account/settings/' . ($this->checkForActiveAction($filename, 'settings') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('COG', true); ?>"></i>
				<span>Settings</span>
			</a>
			<a href="<?php echo URL . 'account/messages/' . ($isMessages ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('ENVELOPE'); ?>"></i>
				<span>Messages<?php echo $this->MessageCount ? '<strong>' . $this->MessageCount . '</strong>' : false ?></span>
			</a>
		</div>
		<?php } else { ?>
		<div class="group">
			<a href="<?php echo URL . 'account/' . ($this->checkForActiveAction($filename, 'overview') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('HOUSE'); ?>"></i>
				<span>Home<?php echo $this->NotificationCount ? '<strong>' . $this->NotificationCount . '</strong>' : false ?></span>
			</a>
			<a href="<?php echo URL . 'account/settings/' . ($this->checkForActiveAction($filename, 'settings') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('COG', true); ?>"></i>
				<span>Settings</span>
			</a>
		</div>
		<div class="group">
			<a href="<?php echo URL . 'account/orders/' . ($this->checkForActiveAction($filename, 'transactions') || $this->checkForActiveAction($filename, 'expired_transactions') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('EXCHANGE'); ?>"></i>
				<span>Orders<?php echo $this->TransactionCount ? '<strong>' . $this->TransactionCount . '</strong>' : false ?></span>
			</a>
			<a href="<?php echo URL . 'account/messages/' . ($isMessages ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('ENVELOPE'); ?>"></i>
				<span>Messages<?php echo $this->MessageCount ? '<strong>' . $this->MessageCount . '</strong>' : false ?></span>
			</a>
		</div>
		<?php if($this->favoriteCount) { ?>
		<div class="group">
			<a href="<?php echo URL . 'account/favorites/' . ($this->checkForActiveAction($filename, 'favorites') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('HEART', true); ?>"></i>
				<span>Favorites<?php echo '<strong>' . $this->favoriteCount . '</strong>'; ?></span>
			</a>
		</div>
		<?php } ?>
		<div class="group">
			<a href="<?php echo URL . 'account/invites/' . ($this->checkForActiveAction($filename, 'invites') || $this->checkForActiveAction($filename, 'referral_commissions') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('USERS'); ?>"></i>
				<span>Invites<?php echo !$this->UserVendor && $this->inviteCount ? ('<strong>' . $this->inviteCount . '</strong>') : FALSE; ?></span>
			</a>
		</div>
		<?php if($this->UserVendor) { ?>
		<div class="group">
			<a href="<?php echo URL . 'account/profile/' . ($this->checkForActiveAction($filename, 'profile') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('USER'); ?>"></i>
				<span>Profile</span>
			</a>
			<a href="<?php echo URL . 'account/listings/' . ($this->checkForActiveAction($filename, 'listings') || $this->checkForActiveAction($filename, 'new_listing') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('TAGS'); ?>"></i>
				<span>Listings<?php echo $this->vendorListingCount > 0 ? '<strong>' . $this->vendorListingCount . '</strong>' : FALSE; ?></span>
			</a>
			<a href="<?php echo URL . 'account/shipping/' . ($this->checkForActiveAction($filename, 'shipping') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('TRUCK'); ?>"></i>
				<span>Shipping</span>
			</a>
		</div>
		<?php }/* ?>
		<div class="group">
			<a href="<?= URL . 'account/statistics/' . ($this->checkForActiveAction($filename, 'statistics') ? '" class="active' : false) ?>">
				<i class="<?= Icon::getClass('STAR', true); ?>"></i>
				<span>Rankings</span>
			</a>
		</div>
		<?php */} ?>
	</nav>
	<div>
		<?php if( isset($_SESSION['authorize']) && $authorize = $_SESSION['authorize'] ){ 
	
		unset($_SESSION['authorize']['authorize_username'], $_SESSION['authorize']['authorize_password']);
		
		?>
		<div class="modal" id="authorize">
			<a href="#close"></a>
			<div>
				<a class="close" href="#close">&times;</a>
				<form method="post"<?php echo $authorize['action'] ? ' action="' . $authorize['action'] . '"' : false ?>>
					<fieldset class="rows-10">
						<label class="label"><?php echo $authorize['title'] ?></label>
						<label class="row text<?php echo isset($authorize['authorize_username']) ? ' invalid' : false ?>">
							<input class="prepend" name="authorize_username" readonly value="<?php $this->UserAlias; ?>">
							<i class="<?php echo Icon::getClass('USER'); ?>"></i>
							<?php if ( isset($authorize['authorize_username']) ) { ?>
							<p class="note"><?php echo $authorize['authorize_username']; ?></p>
							<?php } ?>
						</label>
						<label class="row text<?php echo isset($authorize['authorize_password']) ? ' invalid' : false ?>">
							<input type="password" autofocus tabindex="1" class="prepend" name="authorize_password" placeholder="Your Password"<?php echo isset($authorize['password']) ? ' value="' . $authorize['password'] . '"' : false; ?>>
							<i class="<?php echo Icon::getClass('LOCK'); ?>"></i>
							<?php if ( isset($authorize['authorize_password']) ) { ?>
							<p class="note"><?php echo $authorize['authorize_password']; ?></p>
							<?php } ?>
						</label>
						<?php if( $pgp = $authorize['pgp'] ){ ?>
						<div class="row">
							<label class="label">
								<a class="tooltip left">Decrypt this message with PGP</a><div><p>Your account has PGP authentication enabled. To continue, decrypt the message below to find your one-time authentication code. Paste this code in the box below.</p></div><span> to authenticate:</span>
							</label>
							<label class="textarea">
								<textarea readonly rows="15"><?php echo $authorize['message'] ?></textarea>
							</label>
						</div>
						<label class="row text<?php echo isset($authorize['authorize_code']) ? ' invalid' : false ?>">
							<input class="prepend" tabindex="2" type="text" placeholder="Authentication Code" name="authorize_code">
							<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
							<?php if ( isset($authorize['authorize_code']) ) { ?>
							<p class="note"><?php echo $authorize['authorize_code']; ?></p>
							<?php } ?>
						</label>
						<?php } ?>
						<input name="authorizing" type="submit" class="row btn wide color" value="Authorize">
					</fieldset>
				</form>
			</div>
		</div>
		<?php } ?>
