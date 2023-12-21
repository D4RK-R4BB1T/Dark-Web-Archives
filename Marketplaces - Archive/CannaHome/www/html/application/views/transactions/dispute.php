<?php
	
	$post = $_SESSION['dispute_post'];
	$response = $_SESSION['dispute_response'];
	
	unset($_SESSION['dispute_post'], $_SESSION['dispute_response']);

?>
<?php 

if(!$this->isMediator) {

if(!$this->isVendor) { ?>
<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio">
	<label><?php echo $order_label; ?></label>
</li>
<?php } ?>
<li>
	<a class="anchor" id="review"></a>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="checkbox">
	<label for="order_steps-<?php echo $step; ?>"><?php echo $review_label; ?></label>
	<div class="expandable">
		<?php require('review_form.php'); ?>
	</div>
</li>
<?php } ?>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" checked>
	<label for="order_steps-<?php echo $step; ?>"><?php echo $dispute_label; ?></label>
	<div class="expandable">
		<?php $this->renderNotifications(array('Dispute')); ?>
		<?php if ($this->disputeMessages) { ?>
		<fieldset class="rows-15">
			<div class="row box panel">
				<?php if( $this->disputeMessageCount > DISPUTE_MESSAGES_PER_PAGE ) { ?>
				<div class="left">
					<div class="pagination">
						<?php
						$this->renderPagination(
							$this->pageNumber,
							ceil($this->disputeMessageCount/DISPUTE_MESSAGES_PER_PAGE),
							URL . 'tx/' . $this->TXID . '/dispute/'
						);
						?>
					</div>
				</div>
				<?php } ?>
				<div class="right">
					<?php if( $this->transaction['time_remaining'] !== '0 minutes' ) { ?>
					<?php if( !$this->isMediator && strtotime($this->transaction['timeout']) < strtotime('+' . ( IN_DISPUTE_TIMEOUT_DAYS - CALL_MEDIATOR_DAYS ) . ' DAYS') ){ ?>
					<a class="btn color" href="<?php echo URL . 'transactions/call_mediator/' . $this->TXID . '/'; ?>">Call Mediator</a>
					<?php } ?>
					<?php } else { ?>
					<a class="btn disabled">A mediator has been called</a>
					<?php } ?>
				</div>
			</div>
			<ol class="row list-discussion">
				<?php foreach($this->disputeMessages as $dispute_message){ ?>
				<?php if ($dispute_message['my_message']['Type'] == 'proposal') {
					$isVerdict =
						$dispute_message['my_message']['Proposal']['Type'] == 'refund' &&
						$dispute_message['my_message']['Proposal']['Content']['isVerdict'];
				?>
				<li class="proposal <?php echo $dispute_message['is_sender'] ? 'self' : 'other' ?>">
					<?php if( $dispute_message['sender_image'] ) { ?>
					<div class="avatar">
						<div style="background-image: url(<?php echo $dispute_message['sender_image'] ?>)"></div>
					</div>
				  <?php } ?>
					<div class="terms">
						<span><?= $isVerdict ? 'Decision' : 'Proposal'; ?></span>
						<?php
							switch($dispute_message['my_message']['Proposal']['Type']){
								case 'refund':
									echo $dispute_message['my_message']['Proposal']['Value'] . '% refund to buyer';
								break;
								case 'reship':
									echo $dispute_message['my_message']['Proposal']['Value'] . '% reship to buyer';
								break;
							}
						?>
						<?php
						
						$notYetSigned =
							$dispute_message['my_message']['Proposal']['Type'] == 'refund' &&
							!isset($dispute_message['my_message']['Proposal']['Content']['complete']) &&
							(
								$this->isMediator ||
								(
									!$dispute_message['my_message']['Proposal']['Content']['isVerdict'] &&
									!$dispute_message['is_sender']
								)
							);
						
						if( 
							$dispute_message['is_sender'] &&
							(
								$dispute_message['my_message']['Proposal']['Type'] == 'reship' ||
								isset($dispute_message['my_message']['Proposal']['Content']['complete']) ||
								(
									$isVerdict &&
									$this->isMediator
								)
							)
						) { ?>
						<a href="#withdraw-proposal-<?php echo $dispute_message['id'] ?>" class="btn">Cancel</a>
						<?php } else { ?>
						<a class="btn<?php echo ($this->isMediator && $dispute_message['my_message']['Proposal']['Type'] == 'reship') || $notYetSigned ? ' disabled' : '" href="#accept-proposal-' . $dispute_message['id'] . false; ?>">
							<?= $dispute_message['is_sender'] || ($dispute_message['my_message']['Proposal']['Content']['isVerdict'] && !$this->isMediator) ? 'Sign' : 'Accept';
							if ($notYetSigned){ ?>
							<div class="hint above"><span>Not yet signed</span></div>
							<?php } ?>
						</a>
						<?php } ?>
						<time><?= $dispute_message['sender_alias']; ?> &bull; <?= $dispute_message['my_message']['Time'] ?> UTC</time>
						<?php
						if (
							$dispute_message['my_message']['Proposal']['Type'] == 'refund' &&
							(
								$dispute_message['is_sender'] ||
								(
									$dispute_message['my_message']['Proposal']['Content']['isVerdict'] &&
									!$this->isMediator
								)
							)
						) {
						$this->renderTransactionSignModal(
							"accept-proposal-" . $dispute_message['id'],
							URL . 'transactions/sign_proposal/' . $dispute_message['id'] . '/',
							[$dispute_message['my_message']['Proposal']['Content']['hex']],
							$this->cryptocurrency,
							(
								$this->UserVendor
									? $this->transaction['vendorExtendedPublicKey']
									: $this->transaction['buyerExtendedPublicKey']
							),
							'signed_transaction',
							isset($response['signed_transaction']) ? $response['signed_transaction'] : false,
							URL . 'p/' . PAGE_TRANSACTION_SIGNING_TUTORIAL . '/'
						);
									
						} else { ?>
						<div class="modal" id="accept-proposal-<?php echo $dispute_message['id'] ?>">
							<a href="#close"></a>
							<div class="rows-10">
								<a class="close" href="#close">&times;</a>
								<p class="row">Are you sure you wish to accept the following proposal: <strong><?php
									switch($dispute_message['my_message']['Proposal']['Type']){
										case 'refund':
											echo $dispute_message['my_message']['Proposal']['Value'] . '% refund to buyer (' . (100 - $dispute_message['my_message']['Proposal']['Value']) . '% to vendor)';
										break;
										case 'reship':
											echo $dispute_message['my_message']['Proposal']['Value'] . '% reship to buyer';
										break;
									}
								?></strong> ?</p>
								<div class="row cols-10">
									<form class="col-6"><button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'transactions/accept_proposal/' . $dispute_message['id'] . '/' ?>" class="btn wide">Accept Proposal</button></form>
									<div class="col-6"><a href="#close" class="btn wide red color">Nevermind</a></div>
								</div>
							</div>
						</div>
						<?php }
						if ($dispute_message['is_sender']) { ?>
						<div class="modal" id="withdraw-proposal-<?php echo $dispute_message['id'] ?>">
							<a href="#close"></a>
							<div class="rows-10">
								<a class="close" href="#close">&times;</a>
								<p class="row">Are you sure you wish to retract the following proposal: <strong><?php
									switch($dispute_message['my_message']['Proposal']['Type']){
										case 'refund':
											echo $dispute_message['my_message']['Proposal']['Value'] . '% refund to buyer (' . (100 - $dispute_message['my_message']['Proposal']['Value']) . '% to vendor)';
										break;
										case 'reship':
											echo $dispute_message['my_message']['Proposal']['Value'] . '% reship to buyer';
										break;
									}
								?></strong> ?</p>
								<div class="row cols-10">
									<div class="col-6"><a href="<?php echo URL . 'transactions/withdraw_proposal/' . $dispute_message['id'] . '/' ?>" class="btn wide color">Cancel Proposal</a></div>
									<div class="col-6"><a href="#close" class="btn wide red color">Nevermind</a></div>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>
				</li>
				<?php } else { ?>
				<li class="<?php echo $dispute_message['is_sender'] ? 'self' : 'other' ?>">
					<?php if( $dispute_message['sender_image'] ) { ?>
					<div class="avatar">
						<div style="background-image: url(<?php echo $dispute_message['sender_image'] ?>)"></div>
					</div>
					<?php } ?>
					<div class="messages<?php echo isset($dispute_message['my_message']['PGP']) && $dispute_message['my_message']['PGP'] ? ' pgp' : false ?>">
						<pre><?php echo $dispute_message['my_message']['Message'] ?></pre>
						<time><?php echo $dispute_message['sender_alias']; ?> &bull; <?php echo $dispute_message['my_message']['Time'] ?> UTC</time>
					</div>
				</li>
				<?php } ?>
				<?php } ?>
			  </ol>
		</fieldset>
		<?php } else { ?>
		<fieldset class="formatted">
			<?php if ($this->isMediator) { ?>
			<p>This transaction was not finalized and a dispute has been started. The buyer and vendor were not able to reach an agreement, so a mediator has been called in to assist in the dispute.</p>
			<?php } else { ?>
			<p>The transaction was not finalized and a dispute has been started. You and the <?php echo $this->isVendor ? 'buyer' : 'vendor'; ?> are given <strong><?php echo IN_DISPUTE_TIMEOUT_DAYS ?> days</strong> to negotiate resolutions between yourselves before a mediator is called in to assist in the dispute.</p>
			<p>We ask that you try to keep the discussion relevant and constructive, and that you maintain a civil and respectful tone throughout.</p>
			<?php if (IN_DISPUTE_TIMEOUT_DAYS > CALL_MEDIATOR_DAYS){ ?><p>If, after <strong><?php echo CALL_MEDIATOR_DAYS ?> days</strong>, you feel that the negotiations have not advanced appropriately, you may request mediator assistance ahead of time.</p><?php } ?>
			<?php } ?>
		</fieldset>
		<?php }  ?>
		<fieldset>
			<form class="rows-10" method="post" action="<?php echo URL . 'transactions/send_message/' . $this->TXID . '/' ?>">
				<label class="row textarea">
					<label class="label">
						Send Messages &amp; Propose Solutions
						<a class="tooltip inline top">EXPLAIN</a>
						<div>
							<p><strong>Refund</strong>: the given percentage of the order total is returned to the buyer. The rest is transferred to the vendor.</p>
							<p><strong>Reship</strong>: the given percentage of the order quantity will be re-shipped to the buyer at no charge. The funds will remain in escrow until the buyer has received the reshipment and finalized the transaction. This may result in futher disputes.</p>
						</div>
					</label>
					<textarea name="message" rows="5"><?php echo isset($post['message']) ? $post['message'] : false ?></textarea>
				</label>
				<div class="row cols-10">
					<?php if (!$this->isMediator) { ?>
					<div class="col-3">
						<a class="btn wide" href="#release-funds"><i class="<?= Icon::getClass('CHECK'); ?>"></i><?php echo $this->isVendor ? 'Issue Refund' : 'Finalize Order'; ?></a>
						<div class="modal" id="release-funds">
							<a href="#"></a>
							<div class="rows-10">
								<a class="close" href="#">&times;</a>
								<p class="row">Are you sure you wish to release the escrow funds in their entirety to <strong>the <?php echo $this->isVendor ? 'buyer' : 'vendor'; ?></strong>?</p>
								<div class="row cols-10">
									<div class="col-6"><button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'transactions/' . ($this->isVendor ? 'refund_transaction' : 'finalize_transaction') . '/' . $this->TXID . '/'; ?>" class="btn wide"><i class="<?= Icon::getClass('CHECK'); ?>"></i>Release Funds</button></div>
									<div class="col-6"><a class="btn wide red color" href="#">Nevermind</a></div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-2">
						<label class="label centered">or</label>
					</div>
					<div class="col-3">
						<label class="select">
							<select name="proposal_type">
								<option disabled<?php echo !isset($post['proposal_type']) || empty($post['proposal_type']) ? ' selected' : false ?>>Select Proposal</option>
								<option value="refund"<?php echo isset($post['proposal_type']) && $post['proposal_type'] == 'refund' ? ' selected' : false ?>>Refund</option>
								<option value="reship"<?php echo isset($post['proposal_type']) && $post['proposal_type'] == 'reship' ? ' selected' : false ?>>Reship</option>
							</select>
							<i></i>
						</label>
					</div>
					<div class="col-2">
						<label class="text<?php echo isset($response['percentage']) ? ' invalid' : false ?>">
							<input name="percentage" placeholder="0&ndash;100" pattern="100|\d{1,2}" class="append" type="text" <?php echo isset($post['percentage']) ? 'value="' . $post['percentage'] . '"' : false ?>>
							<span>%</span>
							<?php if (isset($response['percentage'])){ ?>
							<p class="note"><?php echo $response['percentage']; ?></p>
							<?php } ?>
						</label>
					</div>
					<?php } else { ?>
					<div class="col-3">
						<a class="btn wide red" href="#issue-refund"><i class="<?= Icon::getClass('REPLY'); ?>"></i>Refund to <strong>Buyer</strong></a>
						<div class="modal" id="issue-refund">
							<a href="#"></a>
							<div class="rows-10">
								<a class="close" href="#">&times;</a>
								<p class="row">Are you sure you wish to release the transaction funds in their entirety to <strong>the buyer</strong>, <em><?= $this->transaction['buyer_alias']; ?></em>?</p>
								<div class="row cols-10">
									<div class="col-6"><button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'transactions/refund_transaction/' . $this->TXID . '/'; ?>" class="btn wide"><i class="<?= Icon::getClass('CHECK'); ?>"></i>Release Funds</button></div>
									<div class="col-6"><a class="btn wide red color" href="#">Nevermind</a></div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-3">
						<a class="btn wide" href="#finalize-transaction"><i class="<?= Icon::getClass('EDIT', true); ?>"></i>Give to <strong>Vendor</strong></a>
						<div class="modal" id="finalize-transaction">
							<a href="#"></a>
							<div class="rows-10">
								<a class="close" href="#">&times;</a>
								<p class="row">Are you sure you wish to release the transaction funds in their entirety to <strong>the vendor</strong>, <em><?= $this->listing['vendorAlias']; ?></em>?</p>
								<div class="row cols-10">
									<div class="col-6"><button name="csrf" value="<?= $this->getCSRFToken(); ?>" formmethod="post" formaction="<?= URL . 'transactions/finalize_transaction/' . $this->TXID . '/'; ?>" class="btn wide"><i class="<?= Icon::getClass('CHECK'); ?>"></i>Release Funds</button></div>
									<div class="col-6"><a class="btn wide red color" href="#">Nevermind</a></div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-1">&nbsp;</div>
					<div class="col-3">
						<div class="toggle">
							<input name="proposal_type" value="refund" id="toggle-verdict" type="checkbox">
							<label class="checkbox" for="toggle-verdict">
								<i></i>
							</label>
							<label class="text">
								<input name="percentage" placeholder="0&ndash;100" pattern="100|\d{1,2}" class="append" type="text" <?= isset($post['percentage']) ? 'value="' . $post['percentage'] . '"' : false ?>>
								<span>%</span>
								<p class="note">Refund to buyer</p>
							</label>
						</div>
					</div>
					<?php } ?>
					<div class="col-2 align-right"><button type="submit" class="arrow-right btn blue">Send</button></div>
				</div>
			</form>
		</fieldset>
	</div>
</li>
<?php if( !$this->isMediator ) { ?>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
<?php } ?>
