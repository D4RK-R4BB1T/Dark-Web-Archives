<!doctype html>
<html<?= $this->AccessPrefix ? ' lang="' . $this->AccessPrefix . '"' : false?>>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php if ($this->refreshSeconds){ ?><meta http-equiv="refresh" content="<?= $this->refreshSeconds . ($this->refreshDestination ? "; url=" . $this->refreshDestination: false)?>">
	<?php } ?>
	<title><?php echo ($this->pendingActionCount > 0 ? '(' . $this->pendingActionCount . ') ' : FALSE) . $this->SiteName; ?></title>
	<link rel="shortcut icon" href="<?php echo $this->SiteFaviconPath ?>" type="image/x-icon">
	<link rel="stylesheet" href="<?php echo $this->SiteStylesheetPath; ?>" type="text/css">
	<?php
	if (
		isset($this->additionalStylesheets) &&
		$this->additionalStylesheets
	)
		foreach ($this->additionalStylesheets as $additionalStylesheet)
			echo '<link rel="stylesheet" type="text/css" href="' . $additionalStylesheet . '">'; ?>
	<style>body:after{content:'';position:fixed;top:0;right:0;bottom:0;left:0;background:#FBFBF8;z-index:90;transition:all 200ms;pointer-events:none}</style>
	<?php if (isset($this->inlineStylesheet))
		echo '<style>' . trim(preg_replace('/\s{2,}/', ' ', $this->inlineStylesheet)) . '</style>'; ?>
</head>
<body<?= $this->serverLoadStats ? ' data-info="' . $this->serverLoadStats . '"' : false; ?>>
	<?php /*if (!$this->incognitoMode){ ?><div class="spinner"><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 535 534"><path fill="#<?php echo $this->SitePrimaryColor; ?>" d="M270.5 45.5c31.6 81.3 43 170.2 32.4 256.8C344 243.3 400.7 195 465.3 163 439.2 240.4 389.7 311 322 357c60.2-23.2 126.7-28.8 190.2-18-53 36-116.5 57.7-181 57.6 39.2 10.3 76.2 30 106 57.6-57.2 1.7-115.3-13-163-45-5 28.8 1 58.2 9 85.8l-8 1.5c-12.5-27-13.5-57.6-11.3-86.7-47.6 31-105 46.4-161.6 44.3 30-27.3 66.7-47 105.8-57.5-62.8-.7-124.7-22.4-176.4-57.7 59-9.8 120.5-5.8 177.2 13.6C145 306 97.6 237.7 72.5 163c63 31.4 118.7 78 159.7 135.4-10.6-85.7 3.5-174 38.3-253z"/></svg></div><?php }*/ ?>
	<input<?php
		if(
			isset($_SESSION['recentlyChangedLocale']) ||
			(
				isset($this->expandStores) &&
				$this->expandStores
			)
		){
			unset($_SESSION['recentlyChangedLocale']);
			if ($folder !== 'front')
				echo ' checked';
		}
	?> id="expand-store-selector" name="big-selectors" hidden type="checkbox">
	<?php
	if(
		(
			$this->UserMod ||
			!isset($this->isSupportPage) ||
			$this->isSupportPage == FALSE
		)
	){
		if (
			$this->hasChats ||
			$this->hasDisputes
		){ ?>
	<input id="show-chat" hidden type="checkbox"<?php echo $this->unreadMessageCount > 0 ? ' checked' : FALSE; ?>>
	<div class="chat-box">
		<label for="show-chat"></label>
		<div>
			<?php if($this->UserMod){ ?>
			<div class="chat-info">
				<h6>Support Tickets</h6>
				<p><a href="<?php echo URL . 'account/support/'; ?>" class="btn red arrow-right">Go to support centre</a></p>
			</div>
			<form class="chat-form rows-30">
				<?php if($this->ongoingChats){ ?>
				<h4>Your Ongoing Tickets</h4>
				<ul class="row list-notfs">
					<?php 
					foreach($this->ongoingChats as $ongoingChat){
						$hasUnread = $ongoingChat['UnreadMessageCount'] > 0;
						
					?>
					<li<?= $hasUnread ? ' class="red"' : FALSE ?>>
						<a href="<?= URL . 'account/support/' . $ongoingChat['UserAlias'] . '/'; ?>">
							<?php if($hasUnread){ ?>
							<i class="<?= Icon::getClass('BELL'); ?>"></i>
							<?php } ?>
							<div>
								<div>
									<span><?= $ongoingChat['UserAlias'] . ($hasUnread ? ' <span>(' . NXS::formatNumber($ongoingChat['UnreadMessageCount']) . ' new)</span>' : FALSE); ?></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
				<?php }
				if($this->unansweredChats){ ?>
				<h4 class="row">Unanswered Tickets</h4>
				<ul class="list-notfs">
					<?php 
					foreach($this->unansweredChats as $unansweredChat){
						$hasUnread = $unansweredChat['UnreadMessageCount'] > 0; ?>
					<li<?= $hasUnread ? ' class="red"' : FALSE ?>>
						<a href="<?= URL . 'account/support/' . $unansweredChat['UserAlias'] . '/'; ?>">
							<?php if($hasUnread){ ?>
							<i class="<?= Icon::getClass('BELL'); ?>"></i>
							<?php } ?>
							<div>
								<div>
									<span><?= $unansweredChat['UserAlias'] . ($hasUnread ? ' <span>(' . NXS::formatNumber($unansweredChat['UnreadMessageCount']) . ' new)</span>' : FALSE); ?></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
				<?php }
				if($this->importantChats){ ?>
				<h4 class="row">Important Tickets</h4>
				<ul class="list-notfs">
					<?php 
					foreach($this->importantChats as $importantChat){
						$hasUnread = $importantChat['UnreadMessageCount'] > 0; ?>
					<li<?= $hasUnread ? ' class="red"' : FALSE ?>>
						<a href="<?= URL . 'account/support/' . $importantChat['UserAlias'] . '/'; ?>">
							<?php if($hasUnread){ ?>
							<i class="<?= Icon::getClass('BELL'); ?>"></i>
							<?php } ?>
							<div>
								<div>
									<span><?= $importantChat['UserAlias'] . ($hasUnread ? ' <span>(' . NXS::formatNumber($importantChat['UnreadMessageCount']) . ' new)</span>' : FALSE); ?></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
				<?php }
				if ($this->modDisputes){ ?>
				<h4 class="row">Disputes</h4>
				<ul class="list-notfs disputes">
					<?php 
					foreach($this->modDisputes as $modDispute){ ?>
					<li<?= $modDispute['hasUnreadMessages'] ? ' class="red"' : (!$modDispute['isMediator'] ? ' class="green"' : false) ?>>
						<?php if (!$modDispute['isMediator']){ ?>
						<a href="<?= URL . 'admin/start_mediation/' . $modDispute['ID'] . '/'; ?>" class="close">Join</a>
						<?php } ?>
						<a<?= $modDispute['isMediator'] ? ' href="' . URL . 'tx/' . $modDispute['Identifier'] . '/dispute/"' : false; ?>>
							<?php if ($modDispute['hasUnreadMessages']){ ?>
							<i class="<?= Icon::getClass('BELL'); ?>"></i>
							<?php } elseif (!$modDispute['isMediator']){ ?>
							<i class="<?= Icon::getClass('QUESTION', true); ?>"></i>
							<?php } ?>
							<div>
								<div>
									<span><?= $modDispute['disputeTitle'] ?></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</form>
			<?php } else { ?>
			<div class="chat-info formatted">
				<h6><?php echo isset($this->TXID) && $this->TXID ? SUPPORT_INFO_TITLE_TRANSACTIONS : SUPPORT_INFO_TITLE; ?></h6>
				<?php echo SUPPORT_INFO_BODY; ?>
			</div>
			<form class="chat-form" method="post" action="<?php echo URL . 'account/send_chat_message/'; ?>">
				<input type="hidden" name="chat_return" value="<?php echo $this->currentPath; ?>">
				<fieldset class="cols-5">
					<div class="col-8">
						<label class="textarea chat-textarea">
							<textarea required name="message" placeholder="Write message&hellip;"></textarea>
						</label>
					</div>
					<div class="col-4 rows-5">
						<?php if($this->ongoingTransactions){ ?>
						<label class="row select">
							<select name="transaction_id">
								<option<?php echo isset($this->TXID) && $this->TXID !== FALSE ? ' disabled' : ' selected'; ?>>Order ID</option>
								<?php foreach($this->ongoingTransactions as $ongoingTransaction) {
									$label =
										'#' .
										$ongoingTransaction['ID'] .
										//' &verbar; ' . 
										str_repeat('&nbsp;', 8) .
										$ongoingTransaction['SubjectAlias'] .
										' &verbar; &#579; ' .
										$ongoingTransaction['Value'] .
										' &verbar; ' .
										ucwords($ongoingTransaction['Status']);
								?>
								<option<?php echo isset($this->TXID) && $this->TXID == $ongoingTransaction['ID'] ? ' selected' : FALSE; ?> value=<?php echo $ongoingTransaction['ID']; ?>><?php echo $label; ?></option>
								<?php } ?>
							</select>
						</label>
						<?php } else { ?>
						<label class="row text">
							<input type="text" placeholder="Order ID" name="transaction_id"<?php echo isset($this->TXID) ? ' value="' . $this->TXID . '"' : false; ?> class="prepend">
							<i class="<?= Icon::getClass('HASHTAG'); ?>"></i>
						</label>
						<?php } ?>
						<button type="submit" class="row btn wide arrow-right">Send</button>
					</div>
				</fieldset>
			</form>
			<?php if ($this->chatMessages){ ?>
			<ol class="list-discussion">
				<?php if($this->chatMessageCount > CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT){ 
					$additionalMessageCount = $this->chatMessageCount - CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT;
				?>
				<li class="remark">
					<a href="<?php echo URL . 'account/support/'; ?>">
						<strong><?php echo NXS::formatNumber($additionalMessageCount); ?></strong> message<?php echo $additionalMessageCount==1 ? FALSE : 's'; ?> before this&hellip;
					</a>
				</li>
				<?php }
				foreach($this->chatMessages as $i => $message){
					$isFirstMessage =
						$i == 0 &&
						$this->chatMessageCount <= CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT &&
						$message['type'] == CHAT_MESSAGE_ENTRY_TYPE_MESSAGE &&
						$message['SenderAlias'] == $this->UserAlias;
						
					$isVeryFirstMessage =
						$isFirstMessage &&
						count($this->chatMessages) == 1;
						
				switch($message['type']){
					case CHAT_MESSAGE_ENTRY_TYPE_MESSAGE: ?>
				<li class="<?php 
					$color =
						$message['Unread'] && $message['SenderAlias'] !== $this->UserAlias
							? CHAT_MESSAGES_COLOR_UNREAD
							: $message['Color'];
					
					$class =
						'sender-' .
						$message['SenderAlias'] .
						(
							$message['Color']
								? ' ' . $color
								: FALSE
						);/* .
						(
							$message['Unread'] &&
							count($this->chatMessages) > 1
								? ' fade-in'
								: FALSE
						);*/
						
					echo $class;
				 ?>">
					<div class="meta<?php echo $message['TransactionID'] ? ' has-txid' : FALSE; ?>">
						<a class="username" href="<?php echo URL . 'u/' . $message['SenderAlias'] . '/' ?>"><?php echo $message['SenderAlias']; ?></a>
						<time><?php echo $message['date']; ?><span><?php echo $message['time']; ?></span></time>
						<?php if($message['TransactionID']){ ?>
						<a class="txid">&#35;<?php echo $message['TransactionIdentifier']; ?></a>
						<?php } ?>
						<b></b>
					</div>
					<div class="comment">
						<div>
							<div class="messages formatted">
								<?php echo $message['HTML']; ?>
							</div>
						</div>
					</div>
				</li>
				<?php break;
				/*case CHAT_MESSAGE_ENTRY_TYPE_EVENT: ?>
				<li class="remark">
					<span>
						<?php echo $message['text']; ?>
					</span>
				</li>
				<?php break; */
				}
				if ($isFirstMessage){ ?>
				<li class="remark">
					<span class="formatted"><?= SUPPORT_INFO_STATUS_CHANGED_ONGOING; ?></span>
				</li>
				<?php }
				}
				?>
			</ol>
			<?php } else { ?>
			<ol class="list-discussion">
				<li class="remark"><b></b><span class="formatted"><?= $this->UserVendor ? SUPPORT_INFO_BODY_VENDOR : SUPPORT_INFO_BODY_BUYER; ?></span></li>
			</ol>
			<?php }
			if ($isVeryFirstMessage){ ?>
			<style>
				@keyframes chatNoteAppear {
					from {
						max-height: 0;
					}
					to {
						max-height: none;
					}
				}
				.list-discussion > li:last-child {
					overflow: hidden;
					animation: chatNoteAppear .5s 2s forwards;
					max-height: 0;
				}
			</style>
			<?php }
			} ?>
		</div>
	</div>
	<label class="chat-button<?php echo $this->chatButtonColor ? ' color-' . $this->chatButtonColor : FALSE ?>" for="show-chat"></label>
	<?php } else { ?>
	<a href="<?php echo URL . 'account/support/' ?>" class="chat-button color-grey"></a>
	<?php }
	} /*else { ?>
	<div class="right-border"></div>
	<?php }*/ ?>
	<div class="container">
		<?php if ($this->localeOptions && count($this->localeOptions) > 1){ ?>
		<div class="modal wide undismissable">
			<div style="width: 620px">
				<form method="get" action=".">
					<h5 class="band bigger">
						<span>Choose your region</span>
					</h5>
					<fieldset>
						<div class="region-selector">
							<?php foreach($this->localeOptions as $i => $localeOption){ ?>
							<label>
								<input<?php echo $i==0 ? ' checked' : false; ?> name="do[ChangeUserPrefs][LocaleID]" value="<?php echo $localeOption['ID'] ?>" type="radio">
								<strong><?php echo $localeOption['Name'] ?></strong>
								<span><?php echo $localeOption['activeVendorCount'] . ' active vendor' . ($localeOption['activeVendorCount'] == 1 ? false : 's'); ?></span>
								<div style="background-image: url(<?php echo $localeOption['Flag'] ?>);"></div>
							</label>
							<?php } ?>
						</div>
					</fieldset>
					<fieldset>
						<div class="align-right">
							<button class="btn big arrow-right" type="submit">Continue</button>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
		<?php }
		if ($this->donationAddresses){ ?>
		<input type="checkbox" hidden id="donate-modal"<?php
		if (
			isset($_SESSION['newly_generated_donation_address']) &&
			$_SESSION['newly_generated_donation_address']
		){
			unset($_SESSION['newly_generated_donation_address']);
			echo ' checked';
		} ?>>
		<div class="modal wide">
			<label for="donate-modal"></label>
			<div>
				<label class="close" for="donate-modal">&times;</label>
				<fieldset class="rows-15 formatted">
					<h5 class="row band bigger"><span>Donate to Home</span></h5>
					<p class="row">If support staff has gone above and beyond the call of duty helping you with a problem, or if you just appreciate the services that Home provides, you can make a donation to Home at this address:</p>
					<div class="row switch big">
						<input<?= $this->cryptocurrencies[0]['ID'] == CURRENCY_ID_BTC ? ' checked' : false; ?> name="donation_cryptocurrency" id="donate_bitcoin" type="radio">
						<label for="donate_bitcoin"><i class="<?= Icon::getClass('BITCOIN'); ?>"></i>Bitcoin</label>
						<input<?= $this->cryptocurrencies[0]['ID'] == CURRENCY_ID_LTC ? ' checked' : false; ?> name="donation_cryptocurrency" id="donate_litecoin" type="radio">
						<label for="donate_litecoin"><i class="<?= Icon::getClass('LITECOIN'); ?>"></i>Litecoin</label>
						<div>
							<label class="text">
								<input class="big address" readonly value="<?= $this->donationAddresses[0]; ?>" type="text">
							</label>
						</div>
						<div>
							<label class="text">
								<input class="big address" readonly value="<?= $this->donationAddresses[1]; ?>" type="text">
							</label>
						</div>
					</div>
					<p class="row centered color-purple"><strong>Thank you for your generosity.</strong></p>
				</fieldset>
			</div>
		</div>
		<?php }
		if ($this->privateDomains){ ?>
		<input type="checkbox" hidden id="domains-modal"<?php echo $this->privateDomainsExpanded ? ' checked' : false; ?>>
		<div class="modal">
			<?php if (!$this->privateDomainsExpanded){ ?><label for="domains-modal"></label><?php } ?>
			<div>
				<label class="close" for="domains-modal">&times;</label>
				<fieldset class="rows-15">
					<h5 class="row band bigger"><span>Private Access URLs</span></h5>
					<div class="row formatted"><?php echo $this->privateDomainsText; ?></div>
					<div class="row rows-5">
						<?php
						foreach ($this->privateDomains as $privateDomain){ ?>
						<label class="row text">
							<input readonly value="<?php echo $privateDomain; ?>" class="big prepend" type="text">
							<span class="small">http://</span>
						</label><?php } ?>
						<label><p class="note color-red">Bookmark or save these URLs somewhere secure and <strong>use them to access the site from now on</strong>.</p></label>
					</div>
					<div class="row grey-box formatted">
						<p><strong>Do not share these with anyone!</strong></p>
						<p>If your URLs are leaked publicly, you will permanently lose access to the site.</p>
					</div>
				</fieldset>
			</div>
		</div>
		<?php }
		$this->renderNotifications(array('Urgent', 'General'), 'fixed'); ?>
		<header>
			<div class="top">
				<a href="<?= URL; ?>" class="logo">
					<h1><img alt="CannaHome" src="/assets/logos/cannahome_30.png"></h1>
				</a>
				<div class="top-menu">
					<div class="left">
						<a target="_blank" href="<?= $this->ForumURL ?>">Forum<?= $this->forumEntryCount ? '<span>' . $this->forumEntryCount . '</span>' : false; ?></a>
					</div>
					<div class="right">
						<nav>
							<a href="<?= URL . 'faq/'; ?>">FAQ</a><a href="<?= URL . 'p/' . ($this->UserVendor ? 'vendorwiki' : 'buyerwiki') . '/'; ?>">Wiki</a>
						</nav>
						<?php ob_start(); ?>
						<div class="preferences">
							<input id="preferences-toggle" type="checkbox" hidden>
							<label for="preferences-toggle"></label>
							<label for="preferences-toggle" class="preferences-toggle">
								<i class="<?= Icon::getClass('COG', true); ?>"></i>
								<?php if (count($this->Locales) > 1) { ?>
								<span><?= $this->Locales[0]['Abbreviation']; ?></span>
								<?php } if (count($this->cryptocurrencies) > 1){ ?>
								<span><?= $this->cryptocurrencies[0]['ISO']; ?></span>
								<?php } ?>
							</label>
							<div data-label="Preferences">
								<div>
									<?php
										$headerLocales = $this->Locales;
										$headerLocales[$headerLocales[0]['ID']] = $headerLocales[0];
										unset($headerLocales[0]);
										ksort($headerLocales);
										
										foreach ($headerLocales as $headerLocale){
											if ($isActiveLocale = $this->Locales[0]['ID'] == $headerLocale['ID'])
												$activeLocaleName = $headerLocale['Name'];
											echo '<a ' . ($isActiveLocale ? 'class="active"' : 'href="?do[ChangeUserPrefs][LocaleID]=' . $headerLocale['ID'] . '"') . '>' . $headerLocale['Name'] . '</a>';
										}
									?>
								</div>
								<?php if (count($this->cryptocurrencies) > 1){ ?>
								<div>
									<?php
										$headerCurrencies = $this->cryptocurrencies;
										$headerCurrencies[$this->cryptocurrencies[0]['ID']] = $headerCurrencies[0];
										unset($headerCurrencies[0]);
										ksort($headerCurrencies);
										
										foreach ($headerCurrencies as $headerCurrency)
											echo '<a ' . ($this->cryptocurrencies[0]['ID'] == $headerCurrency['ID'] ? 'class="active"' : 'href="?do[ChangeUserPrefs][CryptocurrencyID]=' . $headerCurrency['ID'] . '"') . '>' . $headerCurrency['Name'] . '</a>';
									?>
								</div>
								<?php } ?>
							</div>
						</div>
						<?php 
						$preferencesToggle = ob_get_contents(); ob_end_clean();
						if (count($this->Locales) > 1 || count($this->cryptocurrencies) > 1) 
							echo $preferencesToggle;
						?>
					</div>
				</div>
			</div>
			<div class="bottom">
				<nav>
					<div class="left">
						<div class="dropdown">
							<a href="<?= URL . 'account/' ?>"><i class="<?= Icon::getClass('USER'); ?>"></i>My Account<?= $this->NotificationCount ? '<span>' . $this->NotificationCount . '</span>' : false; ?></a>
							<ul>
								<li><a href="<?= URL . 'account/settings/'; ?>">Settings</a></li>
								<?php if($this->favoriteCount) { ?>
								<li><a href="<?= URL . 'account/favorites/'; ?>">Favorites</a></li>
								<?php } ?>
								<li><a href="<?= URL . 'account/invites/'; ?>">Invites</a></li>
								<?php if($this->UserVendor) { ?>
								<li><a href="<?= URL . 'account/profile/'; ?>">Profile</a></li>
								<li><a href="<?= URL . 'account/listings/'; ?>">Listings</a></li>
								<li><a href="<?= URL . 'account/shipping/'; ?>">Shipping</a></li>
								<?php } ?>
								<li><a href="<?= URL . 'login/logout/'; ?>">Log out</a></li>
							</ul>
						</div>
						<a href="<?= URL . 'account/messages/'; ?>"><i class="<?= Icon::getClass('ENVELOPE'); ?>"></i>Messages<?= $this->MessageCount ? '<span>' . $this->MessageCount . '</span>' : false ?></a>
						<a href="<?= URL . 'account/orders/'; ?>"><i class="<?= Icon::getClass('EXCHANGE'); ?>"></i>Orders<?= $this->TransactionCount ? '<span>' . $this->TransactionCount . '</span>' : false ?></a>
					</div>
					<div class="right">
						<label for="expand-store-selector" id="expand-store-button" class="btn big blue">View Active Vendors</label><div class="dropdown">
							<a href="<?= URL . 'listings/' ?>" class="btn big green">Browse All Products</a>
							<ul>
								<li><a href="<?= URL . 'listings/flowers/' ?>">Flowers</a></li>
								<li><a href="<?= URL . 'listings/concentrates/' ?>">Concentrates</a></li>
								<li><a href="<?= URL . 'listings/carts/' ?>">Carts</a></li>
								<li><a href="<?= URL . 'listings/edibles/' ?>">Edibles</a></li>
								<li><a href="<?= URL . 'listings/distillate/' ?>">Distillate</a></li>
								<li><a href="<?= URL . 'listings/shrooms/' ?>">Shrooms</a></li>
							</ul>
						</div>
					</div>
				</nav>
				<ul class="store-picker">
					<?php ob_start();
					if($this->vendorStores)
						foreach ($this->vendorStores as $vendorStore) { ?>
					<li>
						<a<?= !$vendorStore['Image'] ? ' class="no-logo"' : false;?> style="<?= $vendorStore['Image'] ? 'background-image:url(' . $vendorStore['Image'] . ')' : 'filter: hue-rotate(' . $vendorStore['hueRotateDeg'] . 'deg)'; ?>" href="<?= URL . 'v/' . $vendorStore['Alias'] . '/'; ?>" class="<?= 'vendorLogo-' . $vendorStore['Alias']; ?>">
							<div><?php 
								if ($vendorStore['Image'])
									echo '<span>' . $vendorStore['Alias'] . '</span>';
								else
									foreach ($vendorStore['Elements'] as $nameElement)
										echo '<span>' . $nameElement . '</span>';
							?></div>
						</a>
					</li>
					<?php } 
					$activeVendorStores = ob_get_contents();
					ob_end_clean();
					echo $activeVendorStores;
					?>
				</ul>
				<div>
					<a class="btn arrow-right disabled" <?php /*href="<?= URL . 'p/vendors/'; ?>"*/?>>View All Vendors</a>
				</div>
				<div class="breadcrumb"><?php
					if ($this->breadcrumb){ ?>
					<div>
						<?php foreach ($this->breadcrumb as $title => $page){ ?>
						<a<?=
							(isset($page['URL']) ? ' href="' . $page['URL'] . '"' : false) .
							(isset($page['shrink']) ? ' class="shrink"' : false); ?>><?php
							if (isset($page['icon'])){ ?>
							<i class="<?= $page['icon']; ?>"></i>	
							<?php }
								echo $title; ?></a>
						<?php } ?>
					</div>
					<?php } 
				?></div>
			</div>
		</header>
		<?php if( file_exists(VIEWS_PATH . $folder . '/prepend.php') ) require VIEWS_PATH . $folder . '/prepend.php'; ?>
		<section id="main" class="view-<?php echo $folder ?>">
			<?php if( file_exists(VIEWS_PATH . $folder . '/header.php') ) require VIEWS_PATH . $folder . '/header.php'; ?>
