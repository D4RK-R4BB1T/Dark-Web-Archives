<?php require('messages/header.php'); ?>
		<div class="content rows-15">
			<?php if ($this->conversationCount > 0){ ?>
			<div class="row box panel">
				<div class="left">
					<?php switch($this->userRole){
						case 'admin': ?>
					<label class="label color-blue"><strong>Admin</strong></label></div>
					<?php break;
						case 'moderator': ?>
					<label class="label color-purple"><strong>Mod</strong></label></div>
					<?php break;
						case 'vendor':
						case 'customer' ?>
					<label class="label color-green"><strong><?= $this->userRole == 'customer' ? 'Has ordered from you' : 'Vendor'; ?></strong></label></div>
					<?php break;
						default: ?>
					<label class="label color-red"><strong><?= $this->UserVendor ? 'Has not ordered from you' : 'Not a vendor'; ?></strong></label></div>
				<?php } ?>
				<div class="middle">
					<?php if($this->hasImportant){ ?>
					<div class="switch messages-switch colorful">
						<?php if($this->importantOnly){ ?>
						<input type="checkbox">
						<a href="<?php echo URL . 'account/conversation/' . $this->recipientAlias . '/all/'; ?>">All</a>
						<input type="checkbox" checked>
						<a>Important</a>
						<?php } else { ?>
						<input type="checkbox" checked>
						<a>All</a>
						<input type="checkbox">
						<a href="<?php echo URL . 'account/conversation/' . $this->recipientAlias . '/important/'; ?>">Important</a>
						<?php } ?>
					</div>
					<?php } elseif($this->messageCount > MESSAGES_PER_PAGE){
					if (
						$this->messagePage > 1 &&
						$this->messageCount > 0 &&
						$this->messages[0]['new']
					){ ?>
					<a href="<?= URL . 'account/conversation/' . $this->recipientAlias . '/'; ?>" class="btn xs yellow">
						<i class="<?= Icon::getClass('CARET-LEFT'); ?>"></i>
						<div class="hint left"><span>Read on</span></div>
					</a>
					<?php } ?>
					<div class="pagination">
						<?php
							$this->renderPagination(
								$this->messagePage,
								ceil($this->messageCount / MESSAGES_PER_PAGE),
								URL . 'account/conversation/' . $this->recipientAlias . '/' . $this->conversationMode . '/'
							); 
						?>
					</div>
					<?php } ?>
				</div>
				<div class="right"><label class="btn" for="subject"><i class="<?= Icon::getClass('REPLY'); ?>"></i>New</label></div>
			</div>
			<?php if($this->recipientPGP){ ?>
			<label class="btn panel-button" for="reveal-panel">View PGP Public Key</label>
			<input type="checkbox" id="reveal-panel" hidden>
			<div class="panel-box textarea">
				<textarea readonly rows="<?php echo substr_count($this->recipientPGP, PHP_EOL) + 1; ?>"><?php echo $this->recipientPGP; ?></textarea>
			</div>
			<?php }
			if($this->messageCount > 0) { ?>
			<ul class="list-posts">
				<?php foreach($this->messages as $message){
					$classes = [];
					if($message['is_sender'])
						$classes[] = 'lowlight';
					elseif($message['new'])
						$classes[] = 'highlight';
						
					if($message['important'])
						$classes[] = 'red';
				?>
				<li<?php echo $classes ? ' class="' . implode(' ', $classes) . '"' : false; ?>>
					<span class="anchor"<?= ' id="message-' . $message['id'] . '"'; ?>></span>
					<div class="message-btns">
						<?php if(!$message['is_sender']){ ?>
						<a href="<?= URL . 'account/toggle_message_important/' . $message['id'] . '/' . ($this->importantOnly ? 'important/' : false); ?>" class="btn <?php echo $message['important'] ? 'red' : 'minimal'; ?> xs">
							<i class="<?= Icon::getClass('STAR', true); ?>"></i>
							<?php if (!$message['important']){ ?>
							<div class="hint left"><span>Mark Important</span></div>
							<?php } ?>
						</a>
						<?php } ?>
					</div>
					<h5 class="band"><span><?php echo $message['content']['Date'] ? $message['content']['Date'] : 'Ancient'; ?></span></h5>
					<div class="content formatted"> <?php echo $message['content']['Message'] ?> </div>
					<small><?php echo !$message['is_sender'] ? '<a href="' . URL . 'u/' . $this->recipientAlias . '/">' . $this->recipientAlias . '</a>' : $this->UserAlias ?></small>
				</li>
				<?php } ?>
			</ul>
			<?php } 
			if($this->messageCount > MESSAGES_PER_PAGE){ ?>
			<div class="row box panel">
				<?php
					$this->renderPaginationPanel(
						$this->messagePage,
						ceil($this->messageCount / MESSAGES_PER_PAGE),
						URL . 'account/conversation/' . $this->recipientAlias . '/' . $this->conversationMode . '/'
					); 
				?>
			</div>
			<?php } ?>
			<form class="row" method="post" id="reply" action="<?php echo URL; ?>account/send_message/">
				<input type="hidden" name="recipient_alias" value="<?php echo $this->recipientAlias; ?>">
				<input type="hidden" name="is_reply" value="1">
				<?php if( !$this->canSkipCaptcha ){ ?>
				<fieldset class="rows-5 narrower">
					<div class="row captcha" style="background-image: url(<?php echo URL . 'login/showCaptcha?' . time(); ?>);"></div>
					<label class="row text<?php echo isset($feedback['captcha']) ? ' invalid' : false ?>">
						<input class="big prepend" type="text" name="captcha" placeholder="Captcha" required>
						<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
					</label>
				</fieldset>
				<?php } ?>
				<fieldset class="rows-5">
					<label class="text pre-textarea">
						<input maxlength="<?= MAX_LENGTH_MESSAGE_SUBJECT; ?>" type="text" placeholder="subject (optional)" id="subject" name="subject"<?php echo isset($post['content']) ? ' value="' . strip_tags($post['subject']) . '"' : false; ?>>
					</label>
					<label class="row textarea<?php echo isset($feedback['content']) ? ' invalid' : false ?>">
					<textarea<?= !$this->UserMod ? ' maxlength="' . MAX_LENGTH_MESSAGE_CONTENT . '"' : false; ?> rows="5" name="content" required placeholder="Allowed tags: [b], [i], [pgp]"><?php echo isset($post['content']) ? strip_tags($post['content']) : false; ?></textarea>
					<?php if ( isset($feedback['content']) ){ ?>
					<p class="note"><?php echo $feedback['content'] ?></p>
					<?php } ?>
					</label>
					<div class="row cols-10">
						<div class="col-6">
							<div class="cols-5">
								<label class="col-4 label">Auto-delete:</label>
								<div class="col-5">
									<label class="select<?php echo isset($feedback['auto_delete']) ? ' invalid' : false ?>">
									<select name="auto_delete">
										<option value="0"<?php echo isset($post['auto_delete']) && $post['auto_delete']==0 ? ' selected' : false ?>>Never</option>
										<option value="30"<?php echo isset($post['auto_delete']) && $post['auto_delete']==30 ? ' selected' : false ?>>1 month</option>
										<option value="14" <?php echo !isset($post['auto_delete']) || $post['auto_delete']==14 ? ' selected' : false ?>>2 weeks</option>
										<option value="7"<?php echo isset($post['auto_delete']) && $post['auto_delete']==7 ? ' selected' : false ?>>1 week</option>
										<option value="3"<?php echo isset($post['auto_delete']) && $post['auto_delete']==3 ? ' selected' : false ?>>3 days</option>
										<option value="1"<?php echo isset($post['auto_delete']) && $post['auto_delete']==1 ? ' selected' : false ?>>1 day</option>
									</select>
									<?php if ( isset($feedback['auto_delete']) ){ ?>
									<p class="note"><?php echo $feedback['auto_delete'] ?></p>
									<?php } ?>
									</label>
								</div>
							</div>
						</div>
						<div class="col-3">&nbsp;</div>
						<div class="col-3">
							<button class="row btn wide arrow-right" type="submit">Send</button>
						</div>
					</div>
				</fieldset>
			</form>
			<?php } else { ?>
			
			<?php } ?>
		</div>
<?php require('messages/footer.php'); ?>
