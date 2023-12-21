<?php

$url_prefix = URL . $filename . '/';

$feedback = Session::get('new_message_response');
Session::set('new_message_response', null);

$post = Session::get('new_message_post');
Session::set('new_message_post', null);

?>

<div class="rows-20">
	<div class="row panel">
		<div class="left">
			<?php if ($this->conversationCount > CONVERSATIONS_PER_PAGE) { ?>
			<div class="pagination">
				<?php
					$this->renderPagination(
						$this->pageNumber,
						ceil($this->conversationCount / CONVERSATIONS_PER_PAGE),
						URL . 'account/messages/'
					); 
				?>
			</div>
			<?php } elseif ($this->conversationCount > 0){ ?>
			<strong><?php echo NXS::formatNumber($this->conversationCount) . ' ongoing conversation' . ($this->conversationCount == 1 ? FALSE : 's'); ?></strong>
			<?php } else { ?>
			<strong>No ongoing conversations</strong>
			<?php } ?>
		</div>
		<div class="right btns">
			<a class="btn" href="#new-message"><i class="<?php echo Icon::getClass('PAPER_PLANE'); ?>"></i>Compose</a>
			<div class="modal" id="new-message">
				<a href="#"></a>
				<div>
					<a class="close" href="#">&times;</a>
					<form method="post" class="rows-20" action="<?php echo URL; ?>account/send_message/">
						<fieldset>
							<div class="cols-15">
								<div class="col-7">
									<label class="label">Recipient's Username</label>
									<label class="text<?php echo isset($feedback['recipient_alias']) ? ' invalid' : false ?>">
									<input name="recipient_alias" <?php echo isset($post['recipient_alias']) ? 'value="' . $post['recipient_alias'] . '"' : FALSE; ?> required class="prepend" type="text">
									<i class="<?php echo Icon::getClass('USER'); ?>"></i>
									<?php if ( isset($feedback['recipient_alias']) ){ ?>
									<p class="note"><?php echo $feedback['recipient_alias'] ?></p>
									<?php } ?>
									</label>
								</div>
								<div class="col-5">
									<label class="label">Auto-delete</label>
									<label class="select<?php echo isset($feedback['auto_delete']) ? ' invalid' : false ?>">
									<select name="auto_delete">
										<option value="0"<?php echo isset($post['auto_delete']) && $post['auto_delete']==0 ? ' selected' : false ?>>Never</option>
										<option value="30"<?php echo isset($post['auto_delete']) && $post['auto_delete']==30 ? ' selected' : false ?>>After 1 month</option>
										<option value="7" <?php echo isset($post['auto_delete']) && $post['auto_delete']==7 ? ' selected' : false ?>>After a week</option>
										<option value="14" <?php echo !isset($post['auto_delete']) || $post['auto_delete']==14 ? ' selected' : false ?>>After 2 weeks</option>
										<option value="3"<?php echo isset($post['auto_delete']) && $post['auto_delete']==3 ? ' selected' : false ?>>After 3 days</option>
										<option value="1"<?php echo isset($post['auto_delete']) && $post['auto_delete']==1 ? ' selected' : false ?>>After 1 day</option>
									</select>
									<?php if ( isset($feedback['auto_delete']) ){ ?>
									<p class="note"><?php echo $feedback['auto_delete'] ?></p>
									<?php } ?>
									</label>
								</div>
							</div>
						</fieldset>
						<?php if( !$this->canSkipCaptcha ){ ?>
						<fieldset class="rows-10">
							<div class="row captcha" style="background-image: url(<?php echo URL . 'login/showCaptcha?' . time(); ?>);"></div>
							<label class="row text<?php echo isset($feedback['captcha']) ? ' invalid' : false ?>">
								<input class="big prepend" type="text" name="captcha" placeholder="Captcha" required>
								<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
							</label>
						</fieldset>
						<?php } ?>
						<fieldset class="rows-5">
							<label class="text pre-textarea">
								<input maxlength="<?php echo MAX_LENGTH_MESSAGE_SUBJECT; ?>" type="text" placeholder="subject (optional)" name="subject"<?php echo isset($post['content']) ? ' value="' . strip_tags($post['subject']) . '"' : false; ?>>
							</label>
							<label class="row textarea<?php echo isset($feedback['content']) ? ' invalid' : false ?>">
							<textarea<?= !$this->UserMod ? ' maxlength="' . MAX_LENGTH_MESSAGE_CONTENT . '"' : false; ?> rows="5" name="content" required placeholder="Allowed tags: [b], [i], [pgp]"><?php echo isset($post['content']) ? strip_tags($post['content']) : false; ?></textarea>
							<?php if ( isset($feedback['content']) ){ ?>
							<p class="note"><?php echo $feedback['content'] ?></p>
							<?php } ?>
							</label>
							<div class="row align-right">
								<button class="btn arrow-right" type="submit">Send Message</button>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
			<?php if ($this->conversationCount > 0) { ?>
			<label for="delete-all" class="btn red"><i class="<?php echo Icon::getClass('TRASH'); ?>"></i>Delete All</label>
			<input type="checkbox" id="delete-all" hidden>
			<div class="modal">
				<label for="delete-all"></label>
				<div class="rows-10 formatted">
					<label for="delete-all" class="close">&times;</label>
					<p class="row">Are you sure you wish to delete <strong>all</strong> messages?</p>
					<div class="row cols-10">
						<form class="col-6"><button formmethod="post" type="submit" formaction="<?= URL . 'account/delete_all_messages/'; ?>" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="btn wide">Delete All</button></form>
						<div class="col-6">
							<label for="delete-all" class="btn wide red">Nevermind</label>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php $this->renderNotifications(array('Specific')); // , 'Messages' ?>
	<div class="row list-tabs messages">
		<ul>
			<?php
			if (
				$this->conversationCount > 0 &&
				$this->conversations &&
				is_array($this->conversations)
			) {
				$this->activeConversation = false;
				$this->hasImportant = false;
			
			if (!$this->isForum){	
			?>
			<style>
			@media all and (min-height: <?= 43*$this->conversationCount + 103; ?>px) {
				.list-tabs.messages > ul {
					position: sticky;
					top: 20px;
					z-index: 10;
				}
			}
			</style>
			<?php
			}
			foreach($this->conversations as $conversation){
					if (
						$isActive = strtolower($conversation['userAlias']) == strtolower($this->recipientAlias)
					){
						$this->activeConversation = $conversation;
						
						if ($conversation['hasImportant'])
							$this->hasImportant = true;
					}
						
					$reportable =
						!$conversation['isVendor'] &&
						!$conversation['isAdmin'] &&
						!$conversation['isModerator'];
			?>
			<li<?php echo $isActive ? ' class="active"' : false ?>>
				<div class="opt">
					<?php if ($reportable){ ?>
					<label for="show-chat" class="<?php echo Icon::getClass('FLAG'); ?>">
						<div class="hint left red">
							<span>Report User</span>
						</div>
					</label>
					<?php } ?>
					<label for="<?php echo 'delete-' . $conversation['userAlias'] ?>" class="<?php echo Icon::getClass('TRASH'); ?>"></label>
				</div><!--
			-------><a href="<?php
				echo
					URL .
					(
						$isActive
							? 'u/' . $conversation['userAlias']
							: (
								$conversation['earliestUnreadMessageID']
									? 'account/message/' . $conversation['earliestUnreadMessageID']
									: 'account/conversation/' . $conversation['userAlias']
							)
					) .
					'/';
				?>"><?php echo $conversation['icon'] ? '<i class="' . $conversation['icon'] . '"></i>' : FALSE; ?><?php echo $conversation['userAlias'] . ( $conversation['hasUnread'] ? ' <span>unread</span>' : false ); ?></a>
				<input type="checkbox" id="<?php echo 'delete-' . $conversation['userAlias'] ?>" hidden>
				<div class="modal">
					<label for="<?php echo 'delete-' . $conversation['userAlias'] ?>"></label>
					<div class="rows-10 formatted">
						<label for="<?php echo 'delete-' . $conversation['user_alias'] ?>" class="close">&times;</label>
						<p class="row">Are you sure you wish to delete <strong>all messages</strong> in this conversation?</p>
						<div class="row cols-10">
							<form class="col-6"><button formmethod="post" type="submit" formaction="<?= URL . 'account/delete_conversation/' . $conversation['userAlias'] . '/'; ?>" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="btn wide">Delete It</button></form>
							<div class="col-6">
								<label for="<?php echo 'delete-' . $conversation['userAlias'] ?>" class="btn wide red">Nevermind</label>
							</div>
						</div>
					</div>
				</div>
				<?php if ($reportable){ ?>
				<input type="checkbox" id="<?php echo 'report-' . $conversation['userAlias'] ?>" hidden>
				<div class="modal">
					<label for="<?php echo 'report-' . $conversation['userAlias'] ?>"></label>
					<div class="rows-10">
						<label for="<?php echo 'report-' . $conversation['userAlias'] ?>" class="close">&times;</label>
						<?php if ($this->trustedVendor){ ?>
						<p class="row">Being a trusted vendor, we trust your judgement and this user will therefore be suspended immediately.</p>
						<?php } ?>
						<p class="row">Are you sure you wish to report this user?</p>
						<label class="row label checkbox">
							<input type="checkbox" name="delete_conversation" value="1" checked>
							<i></i>
							Also delete the conversation
						</label>
						<div class="row cols-10">
							<div class="col-6"><a href="<?php echo URL . 'account/report_user/' . $conversation['userAlias'] . '/'; ?>" class="btn wide">Yes</a></div>
							<div class="col-6">
								<label for="<?php echo 'report-' . $conversation['userAlias'] ?>" class="btn wide red">Nevermind</label>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</li>
			<?php }
			}
			if (!$this->isForum){ ?>
			<li class="support<?php echo $this->isSupportPage ? ' active' : FALSE; ?>">
				<a href="<?php echo URL . 'account/support/' ?>">
					<i class="<?php echo Icon::getClass('QUESTION_MARK', true); ?>"></i>
					Support Center
				</a>
			</li>
			<?php } ?>
		</ul>
