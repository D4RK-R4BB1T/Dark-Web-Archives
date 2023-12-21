<?php
	
$URLPrefix = URL . 'account/orders/' . ($this->type == 'finalized' ? $this->type : 'ongoing') . '/';

if (isset($_SESSION['pending_transactions'])){ 
	$pendingTransactions = $_SESSION['pending_transactions']['transactions'];
	$commands = $_SESSION['pending_transactions']['commands'];
	
	$signingPublicKey = explode('-', $_SESSION['pending_transactions']['signingPublicKey']);
	$signingPublicKeyCryptocurrency = $signingPublicKey[0];
	$signingPublicKeyPrefix = $signingPublicKey[1];
	$signingPublicKey = $signingPublicKey[2];
	
	$cryptocurrency = $_SESSION['pending_transactions']['cryptocurrency'];
	
	$response = false;
	if (isset($_SESSION['sign_response']['signed_transactions']))
		$response = $_SESSION['sign_response']['signed_transactions'];
	if (isset($_SESSION['sign_post']))
		$post = $_SESSION['sign_post'];
	
	unset(
		$_SESSION['sign_response'],
		$_SESSION['sign_post']
	);

	$sendModals = [];

	if ($pendingTransactions)
		$this->renderTransactionSignModal(
			"sign-transactions",
			URL . 'transactions/sign_transactions/' . $cryptocurrency . '/',
			array_map(
				function($array){
					return $array['hex'];
				},
				$pendingTransactions
			),
			$cryptocurrency,
			$signingPublicKey,
			'signed_transactions',
			$response,
			URL . 'p/' . PAGE_TRANSACTION_SIGNING_TUTORIAL . '/'
		);
} else {
$this->renderNotifications(array('Transactions')); 
} ?>
<fieldset>
	<div class="top-tabs">
		<ul>
			<li<?php echo
				$this->type == 'buy' || $this->type == 'sell'
					? ' class="active"'
					: FALSE 
				?>><a href="<?php echo URL.'account/orders/ongoing/' ?>">Ongoing</a></li>
			<?php if($this->incipientOrderCount){ ?>
			<li<?php echo $this->type=='incipient' ? ' class="active"' : false ?>><a href="<?php echo URL.'account/orders/incipient/' ?>">Unconfirmed<span><?php echo $this->incipientOrderCount; ?></span></a></li>
			<?php } ?>
			<li<?php echo $this->type=='finalized' ? ' class="active"' : false ?>><a href="<?php echo URL.'account/orders/finalized/' ?>">Finalized</a></li>
		</ul>
		<ul>
			<li>
				<?php
				if (
					$this->UserVendor &&
					$this->type == 'sell'
				){ ?>
				<div class="switch colorful">
					<input<?= !$this->advancedView ? ' checked' : false; ?> type="radio">
					<a<?= $this->advancedView ? ' href="?do[ChangeUserPrefs][AdvancedOrderView]"' : false; ?>>Simple</a>
					<input<?= $this->advancedView ? ' checked' : false; ?> type="radio">
					<a<?= !$this->advancedView ? ' href="?do[ChangeUserPrefs][AdvancedOrderView]"' : false; ?>>Advanced</a>
				</div>
				<?php }
				if ($this->advancedView){ ?>
				<div class="big-dropdown">
					<span><strong><?= $this->ordersPerPage; ?></strong> orders per page</span>
					<a class="toggle">More</a>
					<ul class="dropdown">
						<?php
						foreach (ORDER_VIEW_ADVANCED_DEFAULT_ITEMS_PER_PAGE_OPTIONS as $perPageOption){
							if ($perPageOption == $this->ordersPerPage)
								continue;
						?>
						<li><a href="?do[ChangeUserPrefs][AdvancedOrdersPerPage]=<?= $perPageOption; ?>" class="dropdown-link"><strong><?= $perPageOption; ?></strong> orders per page</a></li>
						<?php } ?>
					</ul>
				
				</div>
				<button type="submit" form="batch_process" class="btn" for=""><i class="<?= Icon::getClass('SAVE'); ?>"></i>Apply Changes</button>
				<?php } ?>
			</li>
		</ul>
		<?php if ($this->transactions) { ?>
		<form method="post" id="batch_actions">
			<input type="hidden" name="page" value="<?= $this->pageNumber ?>">
			<input type="hidden" name="sort" value="<?= $this->sortMode ?>">
		</form>
		<<?= $this->advancedView ? 'form id="batch_process" method="post" action="' . URL . 'transactions/update_transactions/"' : 'div'; ?> class="rows-15">
			<?php if ($this->advancedView){
				$this->renderNotifications(['Messages']); ?>
			<input type="hidden" name="current_page" value="<?= $this->pageNumber; ?>">
			<?php }
			ob_start(); ?>
			<table class="row cool-table" id="transaction-table">
				<thead>
					<tr>
						<?php if ($this->advancedView){ ?>
						<th colspan="2">Details</th>
						<th>Address &amp; Notes</th>
						<th>&nbsp;</th>
						<?php } else { ?>
						<th><a href="<?php echo $URLPrefix.($this->sortMode == 'id_asc' ? 'id_desc' : 'id_asc').'/'; ?>">#<?php 
									
							switch($this->sortMode){
								case 'id_asc':
									echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
								break;
								case 'id_desc':
									echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
								break;
							}
								
						?></a></th>
						<?php if($this->type != 'incipient'){ ?><th><a href="<?php echo $URLPrefix.($this->sortMode == 'date_asc' ? 'date_desc' : 'date_asc').'/'; ?>">Date<?php 
									
							switch($this->sortMode){
								case 'date_asc':
									echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
								break;
								case 'date_desc':
									echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
								break;
							}
								
						} ?></a></th>
						<th><a href="<?php echo $URLPrefix.($this->sortMode == 'listing_asc' ? 'listing_desc' : 'listing_asc').'/'; ?>">Item<?php 
									
										switch($this->sortMode){
											case 'listing_asc':
												echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
											break;
											case 'listing_desc':
												echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
											break;
										}
								
									?></a></th>
						<th><a href="<?php echo $URLPrefix.($this->sortMode == 'value_asc' ? 'value_desc' : 'value_asc').'/'; ?>">Amount<?php 
									
										switch($this->sortMode){
											case 'value_asc':
												echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
											break;
											case 'value_desc':
												echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
											break;
										}
								
									?></a></th>
						<th><a href="<?php echo $URLPrefix.($this->sortMode == 'alias_asc' ? 'alias_desc' : 'alias_asc').'/'; ?>"><?php 
								
										echo $this->UserVendor ? 'Buyer' : 'Vendor';
								
										switch($this->sortMode){
											case 'alias_asc':
												echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
											break;
											case 'alias_desc':
												echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
											break;
										}
								
									?></a></th>
						<th><?= $this->type == 'finalized' ? 'Rating' : 'Status'; ?></th>
						<th>&nbsp;</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
				
					$hasSignable = FALSE;
					$hasPendingFeedback = false;
					
					foreach ($this->transactions as $transaction ) { 
						switch($transaction['status']){
							case 'Expired':
								$timeoutDescription = 'Until auto-finalize';
							break;
							case 'In Transit':
								$timeoutDescription = 'Until ' . ($this->UserVendor ? 'buyer' : 'you') . ' may dispute';
							break;
							default:
								$timeoutDescription = false;
						}
					
						$highlightedType = FALSE;
						if (isset($_GET, array_keys($_GET)[1]))
							switch( array_keys($_GET)[1] ){
								case 'unsuccessfulBroadcast':
									$highlightedType =
										$transaction['failedBroadcast'];
								break;
								default:
									$highlightedType =
										strcasecmp(
											array_keys($_GET)[1],
											str_replace(
												' ',
												'',
												$transaction['status']
											)
										) == 0;
							}
						
						$hasPendingFeedback =
							$transaction['status'] == 'Pending Feedback' &&
							(
								$transaction['shipped'] ||
								$transaction['escrow'] == TRUE
							) &&
							!$transaction['finished']
								?: $hasPendingFeedback;
						
						$unwithdrawnFunds =
							$this->UserVendor &&
							$transaction['status'] == 'Pending Feedback' &&
							!$transaction['withdrawn'];
						$unsignedReject =
							(
								!$transaction['withdrawn'] &&
								(
									$transaction['status'] == 'Rejected' ||
									$transaction['status'] == 'Refunded'
								)
							);
					
						$recentlyFailed =
							$transaction['status'] == 'Pending Deposit' &&
							$transaction['hasDeposited'] &&
							$transaction['timeout'] == FALSE;
					
						$processing =
							$transaction['processing'] &&
							(
								(
									$this->UserVendor &&
									$transaction['status'] == 'Pending Feedback'
								) ||
								(
									!$this->UserVendor &&
									(
										$transaction['status'] == 'Pending Deposit' ||
										$transaction['status'] == 'Rejected' ||
										$transaction['status'] == 'Refunded'
									)
								)
							);
						$signable =
							$unwithdrawnFunds ||
							$unsignedReject ||
							$transaction['failedBroadcast'] ||
							$transaction['hasPendingCPFP'];
					
						if($signable){
							$signLabel = 
								$unsignedReject
									? ($this->UserVendor ? 'Process' : 'Claim') . ' Refund'
									: 'Withdraw';
							$hasSignable = TRUE;
						}
					
						if(
							!$transaction['shipped'] &&
							(
								$transaction['status'] == 'In Transit' ||
								(
									$transaction['escrow'] == FALSE &&
									$transaction['status'] == 'Pending Feedback'
								)
							)
						
						)
							$transaction['status'] = 'Accepted';
						
						if($transaction['status'] == 'Pending Deposit'){
							if($transaction['timeout'] == FALSE)
								$transaction['status'] = 'Unpaid';
							
							if($transaction['hasPaid'])
								$transaction['status'] = 'Pending Confirmation';
						}
						
						$timedOut =
							$transaction['timeout'] === false &&
							$transaction['status'] !== 'In Transit' &&
							$transaction['status'] !== 'Accepted' &&
							$transaction['status'] !== 'In Dispute' &&
							!$transaction['canExtendPaymentWindow'];
					
					ob_start();
					if ($unwithdrawnFunds){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('DOLLAR', true); ?> color-green"></i>
						<div class="hint">
							<span>Funds available</span>
						</div>
					</div>
					<?php }
					if (
						$unsignedReject ||
						$recentlyFailed
					){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('DOLLAR', true); ?> color-red"></i>
						<div class="hint">
							<span>Pending return</span>
						</div>
					</div>
					<?php }
					if ($transaction['status'] == 'In Dispute') { ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('GAVEL'); ?> color-purple"></i>
						<div class="hint">
							<span>In dispute</span>
						</div>
					</div>
					<?php }
					if ($transaction['failedBroadcast']){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('TIMES', true); ?> color-red"></i>
						<div class="hint">
							<span>Failed <?php echo $this->type == 'incipient' ? 'CPFP' : 'broadcast'; ?></span>
						</div>
					</div>
					<?php } elseif ($processing){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('ELLIPSIS_HORIZONTAL'); ?> color-blue"></i>
						<div class="hint">
							<span>Processing</span>
						</div>
					</div>
					<?php }
					if($transaction['hasPromo']){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('GIFT'); ?> color-yellow"></i>
						<div class="hint">
							<span>Promotional Discount</span>
						</div>
					</div>
					<?php }
					if($transaction['hasPendingCPFP']){ ?>
					<div class="notice">
						<i class="<?php echo Icon::getClass('CLOCK-O'); ?> color-yellow"></i>
						<div class="hint">
							<span>Needs CPFP Signature</span>
						</div>
					</div>
					<?php }
					$notices = ob_get_contents();
					ob_clean();
					
					switch (true){
						case ($transaction['status'] == 'In Dispute'):
							$rowColor = TRANSACTIONS_COLOR_CODE_STATE_IN_DISPUTE;
							break;
						case $unsignedReject:
							$rowColor = TRANSACTIONS_COLOR_CODE_STATE_PENDING_REFUND;
							break;
						case $unwithdrawnFunds:
							$rowColor = TRANSACTIONS_COLOR_CODE_STATE_PENDING_WITHDRAW;
							break;
						case ($transaction['status'] == 'Pending Accept'):
							$rowColor = TRANSACTIONS_COLOR_CODE_STATE_PENDING_ACCEPT;
							break;
						default:
							$rowColor = false;
					}
					
					if (
						$boldStatus =
							$this->advancedView ||
							$transaction['statusChanged']
					)
						echo '<strong>';
					if (
						$this->UserVendor &&
						(
							$transaction['status'] == 'Rejected' ||
							$transaction['status'] == 'Refunded'
						)
					)
						echo
							!$transaction['hasPaid']
								? 'Failed Payment'
								: 'Pending Refund';
					elseif (
						!$this->UserVendor &&
						$transaction['escrow'] == FALSE &&
						$transaction['status'] == 'Pending Feedback' &&
						$transaction['shipped'] &&
						(
							!$transaction['finished'] ||
							$transaction['statusChanged']
						)
					)
						echo 'Shipped';
					else
						echo
							$transaction['finished']
								? 'Finalized'
								: $transaction['status'];
					
					if ($boldStatus)
						echo '</strong>';
					
					if(
						$transaction['timeout'] && 
						$transaction['status'] !== 'Rejected' &&
						$transaction['status'] !== 'Refunded' &&
						$transaction['status'] !== 'Pending Feedback' &&
						(
							$transaction['status'] !== 'Accepted' ||
							$transaction['escrow'] == TRUE
						) &&
						!$unwithdrawnFunds
					){
						echo '<br>';
						if(
							$transaction['minsRemaining'] < 60 &&
							!$this->UserVendor
						)
							$this->renderCountdownClock($transaction['minsRemaining'] * 60);
						else
							echo
								'<small>' .
								(
									$timeoutDescription
										? '<a class="tooltip">'
										: false
								) .
								$transaction['timeout'] . ' left' . 
								(
									$timeoutDescription
										? '<div class="hint"><span>' . $timeoutDescription . '</span></div></a>'
										: false
								) .
								'</small>';
					}
					$statusColumn = ob_get_contents();
					ob_end_clean();
					
					$itemColumn = '<a href="' . URL . 'i/' . NXS::getB36($transaction['listing_id']) . '/">' . $transaction['listing_name'] . '</a>';
					
					$identifierColumn =
						$notices .
						($timedOut ? '<s style="display:inline-block">' : '<span style="display:inline-block">') .
						$transaction['identifier'] .
						($timedOut ? '</s>' : '</span>');
						
					if ($this->advancedView){
						$simpleRow =
							$unsignedReject ||
							$timedOut;
							
					$sendMessageModalID = 'send_message-' . $transaction['identifier'];
					
					$rowClasses = [];
					if ($simpleRow)
						$rowClasses[] = 'simple';
					//if ($highlightedType)
					//	$rowClasses[] = 'highlight';
					if ($rowColor)
						array_push(
							$rowClasses,
							'color',
							$rowColor
						);
					?>
					<tr id="order-<?= $transaction['identifier']; ?>"<?= $rowClasses ? ' class="' . rtrim(implode(' ', $rowClasses)) . '"' : false; ?>>
						<td>ID</td>
						<td><?= $identifierColumn; ?></td>
						<?php if ($simpleRow){ ?>
						<td></td>
						<?php } else { ?>
						<td rowspan="11">
							<div class="textarea">
								<textarea readonly><?= $transaction['decrypted']['order']['Address']; ?></textarea>
							</div>
						</td>
						<?php } ?>
						<td>
							<div>
								<label class="btn purple xs" for="<?= $sendMessageModalID; ?>">
									<i class="<?= Icon::getClass('ENVELOPE'); ?>"></i>
									<div class="hint above"><span>Send PM</span></div>
								</label>
								<?php
								ob_start(); ?>
								<input type="checkbox" hidden id="<?= $sendMessageModalID; ?>">
								<div class="modal wide">
									<label for="<?= $sendMessageModalID; ?>"></label>
									<div>
										<label for="<?= $sendMessageModalID; ?>" class="close">&times;</label>
										<form target="_blank" method="post" class="rows-20" action="<?= URL . 'account/send_message/'; ?>">
											<fieldset>
												<div class="cols-15">
													<div class="col-7">
														<label class="label">Recipient's Username</label>
														<label class="text">
															<input name="recipient_alias" readonly required value="<?= $transaction['alias']; ?>" class="prepend" type="text">
															<i class="<?= Icon::getClass('USER'); ?>"></i>
														</label>
													</div>
													<div class="col-5">
														<label class="label">Auto-delete</label>
														<label class="select">
															<select name="auto_delete">
																<option value="0">Never</option>
																<option value="30">After 1 month</option>
																<option value="14" selected>After 2 weeks</option>
																<option value="7">After 1 week</option>
																<option value="3">After 3 days</option>
																<option value="1">After 1 day</option>
															</select>
														</label>
													</div>
												</div>
											</fieldset>
											<fieldset class="rows-10">
												<label class="text pre-textarea">
													<input value="<?= 'Order #' . $transaction['identifier'] . ' (' . URL . 'tx/' . $transaction['identifier'] . '/)'; ?>" name="subject" type="hidden">	
													<input readonly value="Order #<?= $transaction['identifier']; ?>" type="text">
												</label>
												<label class="row textarea">
													<textarea maxlength="<?= MAX_LENGTH_MESSAGE_CONTENT; ?>" required rows="5" name="content" placeholder="Allowed tags: [b], [i], [pgp]"></textarea>
												</label>
												<div class="row align-right">
													<button class="btn arrow-right" type="submit">Send Message</button>
												</div>
											</fieldset>
										</form>
									</div>
								</div>
								<?php
								$sendModals[] = ob_get_contents();
								ob_end_clean();
								if ($signable){ ?>
								<a href="<?= URL . 'transactions/prepare_transactions/' . ($transaction['identifier'] ?: $transaction['id']) . '/'; ?>" class="btn <?= $unsignedReject ? 'red' : 'yellow'; ?>"><i class="<?= Icon::getClass('DOLLAR', true); ?>"></i><?= $signLabel; ?></a>
								<?php }
								if (
									$hasRecoveryTransaction =
										$transaction['escrow'] &&
										(
											$transaction['status'] == 'In Transit' ||
											$transaction['status'] == 'Accepted' ||
											$transaction['status'] == 'In Dispute'
										) &&
										$transaction['decrypted']['next_tx']['AutoFinalize']['hex']
								){
									$recoveryTransactionModalID = 'recovery_transaction-' . $transaction['identifier'];
								?>
								<label for="<?= $recoveryTransactionModalID; ?>" class="btn xs">
									<i class="<?= ICON::getClass('LOCK'); ?>"></i>
									<div class="hint above"><span>Recovery Transaction</span></div>
								</label>
								<input id="<?= $recoveryTransactionModalID; ?>" type="checkbox" hidden="">
								<div class="modal wide">
									<label for="<?= $recoveryTransactionModalID; ?>"></label>
									<div>
										<label class="close" for="<?= $recoveryTransactionModalID; ?>">&times;</label>
										<fieldset class="rows-15">
											<h5 class="row band bigger"><span><strong>#<?= $transaction['identifier']; ?></strong>: Escrow Recovery Transaction</span></h5>
											<label class="pre">
												<pre contentEditable><?= $transaction['decrypted']['next_tx']['AutoFinalize']['hex']; ?></pre>
												<p class="note">The transaction has already been signed with our signature.</p>
												<p class="note">However, it has been timelocked and cannot be broadcast until <strong>30 days</strong> after the transit period.</p>
											</label>
										</fieldset>
									</div>
								</div>
								<?php } ?>
								<a<?= !$timedOut ? ' href="' . URL . 'tx/' . $transaction['identifier'] . '/"' : false; ?> class="btn <?= $timedOut ? 'disabled' : 'blue'; ?> arrow-right"><?= 'View' . (!$signable ? ' Order Details' : false); ?></a>
							</div>
						</td>
					</tr>
					<?php if ($simpleRow)
						echo str_repeat('<tr></tr>', 10);
					else { ?>
					<tr>
						<td>Date</td>
						<td><?= $transaction['datePaid']; ?></td>
						<td rowspan="10">
							<div class="rows-20 order-selections">
								<input type="hidden" name="transactions[]" value="<?= $transaction['identifier']; ?>">
								<?php
								$transactionOptionInputName = 'transaction_options-' . $transaction['identifier'] . '[]';
							
								if ($transaction['status'] == 'Pending Accept'){
									$responseIDPrefix = 'respond_transaction-' . $transaction['identifier'];
									$responseID_accept = $responseIDPrefix . '-accept';
									$responseID_reject = $responseIDPrefix . '-reject';
									$responseID_skip = $responseIDPrefix . '-skip';
									?>
								<input type="hidden" name="<?= $transactionOptionInputName; ?>" value="respond">
								<ul class="row list-expandable lefthanded radios">
									<li>
										<input id="<?= $responseID_accept; ?>" class="expand" name="<?= $responseIDPrefix; ?>" type="radio" value="accept">
										<label class="nowrap" for="<?= $responseID_accept; ?>">Accept Order<i class="<?= Icon::getClass('CHECK-M'); ?>"></i></label>
										<b></b>
									</li>
									<li>
										<input id="<?= $responseID_reject; ?>" class="expand" name="<?= $responseIDPrefix; ?>" type="radio"  value="reject">
										<label class="nowrap" for="<?= $responseID_reject; ?>">Reject Order<i class="<?= Icon::getClass('TIMES', true); ?>"></i></label>
										<b></b>
									</li>
									<li>
										<input checked id="<?= $responseID_skip; ?>" class="expand" name="<?= $responseIDPrefix; ?>" type="radio">
										<label class="nowrap" for="<?= $responseID_skip; ?>">No Action<i class="<?= Icon::getClass('ELLIPSIS-H-M'); ?>"></i></label>
										<b></b>
									</li>
								</ul>
								<?php }
								if (
									$transaction['status'] == 'Accepted' ||
									$transaction['status'] == 'In Transit'
								){
									$markShippedID = 'mark_shipped-' . $transaction['identifier'];
									if (
										$shipToggleEnabled =
											$transaction['status'] == 'Accepted' ||
											$transaction['decrypted']['unshipAllowed']
									){ ?>
								<input type="hidden" name="<?= $transactionOptionInputName; ?>" value="mark_shipped">
								<?php }
									if ($transaction['shipped']){ ?>
								<input type="hidden" name="<?= $markShippedID . '-unship'; ?>">
								<?php } ?>
								<ul class="row list-expandable lefthanded checkboxes">
									<li>
										<input<?= (!$shipToggleEnabled ? ' disabled' : false) . ($transaction['shipped'] ? ' checked' : false); ?> id="<?= $markShippedID; ?>" class="expand" name="<?= $markShippedID; ?>" type="checkbox">
										<label class="nowrap" for="<?= $markShippedID; ?>">Mark Shipped<i></i></label>
										<b></b>
									</li>
								</ul>
								<?php } 
								if ( 
									$transaction['status'] == 'Pending Feedback' &&
									!$transaction['finished'] 
								){
									$feedbackIDPrefix = 'rate_transaction-' . $transaction['identifier'];
									$feedbackID_positive = $feedbackIDPrefix . '-positive';
									$feedbackID_negative = $feedbackIDPrefix . '-negative';
								?>
								<input type="hidden" name="<?= $transactionOptionInputName; ?>" value="rate_transaction">
								<ul class="row list-expandable lefthanded radios">
									<li>
										<input id="<?= $feedbackID_positive; ?>" class="expand" name="<?= $feedbackIDPrefix; ?>" type="radio" value="positive">
										<label class="nowrap" for="<?= $feedbackID_positive; ?>">Rate Positively<i class="<?= Icon::getClass('THUMBS-UP-M'); ?>"></i></label>
										<b></b>
									</li>
									<li>
										<input id="<?= $feedbackID_negative; ?>" class="expand" name="<?= $feedbackIDPrefix; ?>" type="radio" value="negative">
										<label class="nowrap" for="<?= $feedbackID_negative; ?>">Rate Negatively<i class="<?= Icon::getClass('THUMBS-DOWN-M'); ?>"></i></label>
										<b></b>
									</li>
								</ul>
								<?php } ?>
							</div>
						</td>
					</tr>
					<tr>
						<td>Buyer</td>
						<td>
							<a href="<?= URL. 'u/' . $transaction['alias'] . '/'; ?>"><?= $transaction['alias'] ?></a>
							<label for="expand-<?= $transaction['identifier']; ?>-buyer" class="expand-toggle"></label>
						</td>
					</tr>
					<tr class="narrow">
						<td>Purchases</td>
						<td><?= $transaction['decrypted']['buyerPurchases']; ?></td>
					</tr>
					<tr class="narrow">
						<td>Transacted</td>
						<td>&#126; <?= $transaction['decrypted']['buyerTransacted']; ?></td>
					</tr>
					<tr class="narrow">
						<td>Rating</td>
						<td><?= number_format($transaction['decrypted']['buyerRating'], 1); ?> / 5</td>
					</tr>
					<tr>
						<td>Item</td>
						<td><?= $itemColumn; ?></td>
					</tr>
					<tr>
						<td>Quantity</td>
						<td><?=
							$transaction['decrypted']['text_quantity']
								?
									(
										$transaction['decrypted']['order']['Quantity'] > 1
										?
											(
												'<strong' .
												(
													$this->isVendor
														? ' class="big-quantity"'
														: FALSE
												) .
												'>&times;' .
												$transaction['decrypted']['order']['Quantity'] .
												'</strong> (' .
												$transaction['decrypted']['text_quantity'] .
												')'
											)
										: $transaction['decrypted']['text_quantity']
									)
								: $transaction['decrypted']['order']['Quantity']
						?></td>
					</tr>
					<tr>
						<td>Shipping</td>
						<td><em><?= $transaction['decrypted']['order']['Shipping']; ?></em></td>
					</tr>
					<tr>
						<td>Total</td>
						<td><strong><?= $transaction['decrypted']['priceBreakdown']['final']; ?></strong><br><small><?= $transaction['decrypted']['priceBreakdown']['currency']; ?></small></td>
					</tr>
					<tr>
						<td>Status</td>
						<td><?= $statusColumn; ?></td>
					</tr>
					<?php }
					} else { ?>
					<tr<?= $highlightedType ? ' class="highlight"' : FALSE;?>>
						<td><?= $identifierColumn; ?></td>
						<?php if($this->type != 'incipient'){ ?><td><?php echo $transaction['datePaid']; ?></td><?php } ?>
						<td><?php
						
							if(
								$transaction['listing_inactive'] &&
								!$this->UserVendor
							)
								echo '<strong>' . $transaction['listing_name'] . '</strong>';
							else
								echo $itemColumn;
						
							if($transaction['quantity'] > 1)
								echo ' <strong>&times;' . $transaction['quantity'] . '</strong>';
							?></td>
						<td><?php echo $transaction['value'] ?></td>
						<td><a href="<?php echo URL. ($this->type == 'sell' ? 'u' : 'v') . '/' . $transaction['alias'] . '/'; ?>"><?php echo $transaction['alias'] ?></a></td>
						<td><?php
							if ($this->type == 'finalized'){ ?>
							<div class="rows-10">
								<div class="row rating stars color-yellow">
									<?php $this->renderRating($transaction['vendorRating']); ?>
								</div>
								<?php if ($transaction['attributeName']){ ?>
								<div class="row"><strong class="<?= $transaction['vendorRating'] <= 4 ? 'color-red' : 'color-green'; ?>"><i class="<?= $transaction['vendorRating'] <= 4 ? Icon::getClass('THUMBS-DOWN') : Icon::getClass('THUMBS-UP'); ?>"></i><?= '&ensp;' . str_replace('<br>', ' ', $transaction['attributeName']) . ' '; ?></strong></div>
								<?php }
								if ($transaction['ratingComments']){ ?>
								<div class="row grey-box small"><?= $transaction['ratingComments']; ?></div>
								<?php } ?>
							</div>
							<?php } else
								echo $statusColumn; ?></td>
						<td>
							<?php
								if(
									$signable &&
									(
										$transaction['status'] !== 'Pending Deposit' ||
										$transaction['hasPendingCPFP']
									)
								){
						
							?><!--
						---><a href="<?php echo URL . 'transactions/prepare_transactions/' . ($transaction['identifier'] ?: $transaction['id']) . '/'; ?>" class="btn <?= ($unsignedReject ? 'red' : 'yellow') . ($unsignedReject ? false : ' xs'); ?>">
								<i class="<?= Icon::getClass('DOLLAR', true); ?>"></i><?php
									if ($unsignedReject)
										echo $signLabel;
									else { ?>
								<div class="hint above">
									<span><?= $signLabel; ?></span>
								</div>
								<?php } ?>
							</a><!--
						---><?php }
						if (!$timedOut)
							switch($transaction['status']){
								case 'Pending Feedback': 
								?><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/' . ($this->UserVendor && $transaction['finished'] ? 'review' : 'feedback') . '/'; ?>" class="btn <?= $this->UserVendor ? 'blue' : 'green'; ?> xs">
										<i class="<?php echo Icon::getClass($this->UserVendor ? 'BROWSER' : 'STAR-HALF-O'); ?>"></i>
										<div class="hint above">
											<span><?= $this->UserVendor ? 'View' : 'Rate'; ?></span>
										</div>
									</a><!--
							---><?php break;
								case 'In Dispute': ?><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/dispute/'; ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a><!--
							---><?php break;
								case 'Accepted':
									if( $this->type == 'sell'){ ?><!--
								---><a href="<?php echo URL . 'transactions/toggle_shipped/' . $transaction['identifier'] . '/overview/'; ?>" class="btn xs">
										<i class="<?php echo Icon::getClass('PLANE'); ?>"></i>
										<div class="hint above">
											<span>Mark Shipped</span>
										</div>
									</a><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/' . ($this->UserVendor ? 'fulfill/' : false); ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a><!--
							---><?php } else {
							?><!--
								---><a href="<?= URL . 'tx/' . $transaction['identifier'] . '/' . ($transaction['escrow'] ? false : 'feedback/'); ?>" class="btn <?= $transaction['escrow'] ? 'blue' : 'green'; ?> xs">
										<i class="<?php echo Icon::getClass($transaction['escrow'] ? 'BROWSER' : 'STAR-HALF-O'); ?>"></i>
										<div class="hint above">
											<span><?= $transaction['escrow'] ? 'View' : 'Rate'; ?></span>
										</div>
									</a><!-- 
								---><?php } break;
								case 'In Transit': ?><!--
									<?php if( $this->type == 'sell' || $transaction['escrow'] == FALSE ) { ?>
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/' . ($this->UserVendor ? 'fulfill/' : false); ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a><!--
								---><?php } else { ?><!--
								---><a href="<?php echo '#finalize-' . $transaction['identifier']; ?>" class="btn xs">
										<i class="<?php echo Icon::getClass('CHECK'); ?>"></i>
										<div class="hint above">
											<span>Finalize</span>
										</div>
									</a><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/finalize/'; ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a>
									<div class="modal" id="finalize-<?= $transaction['identifier']; ?>">
										<a href="#"></a>
										<div class="rows-10">
											<a class="close" href="#">&times;</a>
											<p class="row">Are you sure you wish to finalize this order?</p>
											<form class="row cols-10">
												<div class="col-6"><button formmethod="post" name="csrf" value="<?= $this->getCSRFToken(); ?>" formaction="<?= URL . 'transactions/finalize_transaction/' . $transaction['identifier'] . '/' ?>" class="btn wide">Finalize Order</button></div>
												<div class="col-6"><a href="#" class="btn wide red">Nevermind</a></div>
											</form>
										</div>
									</div>
									<?php } ?>
								<?php break;
								case 'Pending Accept': 
									if( $this->type == 'sell' ) { ?><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/review/#reject-order'; ?>" class="btn red xs">
										<i class="<?php echo Icon::getClass('TIMES', true); ?>"></i>
										<div class="hint above">
											<span>Reject</span>
										</div>
									</a><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/review/'; ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a>
									<div class="modal" id="reject-<?php echo $transaction['identifier']; ?>">
										<a href="#"></a>
										<div class="rows-10">
											<a class="close" href="#">&times;</a>
											<p class="row">Are you sure you wish to reject this order?</p>
											<div class="row cols-10">
												<div class="col-6"><form action="<?php echo URL . 'transactions/respond_transaction/' . $transaction['identifier'] . '/' ?>" method="post"><button type="submit" name="action" class="btn wide" value="reject_order">Reject Order</button></form></div>
												<div class="col-6"><a href="#" class="btn wide red">Nevermind</a></div>
											</div>
										</div>
									</div>
									<?php } else { ?><!--
								---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/finalize/'; ?>" class="btn blue xs">
										<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
										<div class="hint above">
											<span>View</span>
										</div>
									</a><!--
								---><?php } 
								break;
								case 'Pending Confirmation':
								case 'Pending Deposit':
								case 'Unpaid':
									if($this->type !== 'incipient'){ ?><a href="<?= URL . 'tx/' . $transaction['identifier'] . '/pay/#pay'; ?>" class="btn <?= $transaction['canExtendPaymentWindow'] ? 'green' : 'blue' ?> xs">
										<i class="<?= Icon::getClass($transaction['canExtendPaymentWindow'] ? 'UNDO' : 'BROWSER'); ?>"></i>
										<div class="hint above">
											<span><?= $transaction['canExtendPaymentWindow'] ? 'Renew' : 'View' ?></span>
										</div>
									</a><?php } ?>
								<?php break;
								case 'Refunded':
								case 'Rejected':
								case 'Unpaid':
								break;
								default: ?><!--
							---><a href="<?php echo URL . 'tx/' . $transaction['identifier'] . '/'; ?>" class="btn blue xs">
									<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
									<div class="hint above">
										<span>View</span>
									</div>
								</a>
								<?php break;
							}
							if (
								$transaction['status'] == 'Pending Feedback' &&
								$this->UserVendor &&
								!$transaction['finished'] &&
								!$timedOut
							){?>
								<form method="post" action="<?php echo URL . 'transactions/rate_transaction/' . $transaction['identifier'] . '/' ?>"><!--
								---><button class="btn xs" type="submit" name="overall" value="5">
										<i class="<?= Icon::getClass('THUMBS_UP'); ?>"></i>
										<div class="hint above">
											<span>Positive</span>
										</div>
									</button><!--
								---><button class="btn red xs" type="submit" name="overall" value="0">
										<i class="<?= Icon::getClass('THUMBS_DOWN'); ?>"></i>
										<div class="hint above">
											<span>Negative</span>
										</div>
									</button>
								</form>
							<?php } ?>
						</td>
					</tr>
					<?php }
				} ?>
				</tbody>
			</table>
			<?php
			$orderTable = ob_get_contents();
			ob_end_clean();
			
			if ($this->advancedView)
				foreach ($this->transactions as $transaction)
					echo '<input id="expand-' . $transaction['identifier'] . '-buyer" type="checkbox" hidden>';
			echo $orderTable;
			if (
				$this->transactionCount > $this->ordersPerPage ||
				(
					$this->UserVendor &&
					(
						$hasPendingFeedback ||
						$this->transactionCount > $this->ordersPerPage
					)
				) ||
				$this->advancedView
			){ ?>
			<div class="row panel">
				<?php
				if (!$this->UserVendor)
					$this->renderPaginationPanel(
						$this->pageNumber,
						ceil($this->transactionCount/$this->ordersPerPage),
						$URLPrefix . $this->sortMode . '/'
					);
				elseif ($this->transactionCount > $this->ordersPerPage) {?>
				<div class="middle">
					<div class="pagination">
						<?php
						$this->renderPagination(
							$this->pageNumber,
							ceil($this->transactionCount/$this->ordersPerPage),
							$URLPrefix . $this->sortMode . '/'
						);
						?>
					</div>
				</div>
				<?php }
				if (
					$this->UserVendor &&
					(
						$hasPendingFeedback ||
						$this->transactionCount > $this->ordersPerPage
					)
				){ ?>
				<div class="left">
					<label class="label">Bulk Actions:</label>
					<div class="big-dropdown">
						<span>For All Orders</span>
						<a class="toggle">More</a>
						<ul class="dropdown">
							<li><a href="<?= URL . 'transactions/rate_all_transactions_positively/'; ?>" class="dropdown-link">Rate Positively</a></li>
						</ul>
					</div>
				</div>
				<?php }
				if ($this->advancedView){ ?>
				<div class="right">
					<button type="submit" class="btn"><i class="<?= Icon::getClass('SAVE'); ?>"></i>Apply Changes</button>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
		</<?= $this->advancedView ? 'form' : 'div'; ?>>
		<?php 
		if ($sendModals)
			echo implode(PHP_EOL, $sendModals);
		} else { ?>
		<div class="content"><strong>No <?php
		echo
			$this->type == 'finalized'
				? (
					(
						$this->type == 'finalized'
							? 'recent, '
							: FALSE
					) .
					'finalized'
				)
				: 'ongoing'; 
		?> transactions</strong></div>
		<?php } ?>
	</div>
</fieldset>
<?php
if (
	$this->UserVendor &&
	(
		$hasSignable ||
		$this->transactionCount > $this->ordersPerPage
	)
){ ?>
<fieldset class="panel">
	<div class="left">
		<label class="label">Withdraw Funds:&nbsp;</label>
		<?php foreach ($this->cryptocurrencies as $cryptocurrency){ ?>
		<a href="<?= URL . 'transactions/prepare_transactions_cryptocurrency/' . $cryptocurrency['ID'] . '/'; ?>" class="btn yellow"><i class="<?= Icon::getClass($cryptocurrency['Name']); ?>"></i><?= $cryptocurrency['Name'] ?></a>
		<?php } ?>
	</div>
	<div class="right">
		<label class="label">
			<a class="tooltip left">Transaction Priority</a>
			<div>
				<p>How quickly would you like your withdrawals to confirm?</p><p>Quicker confirmation requires higher miner fees.</p>
			</div>
		</label>
		<div class="big-dropdown">
			<span><?php echo $this->cryptocurrencyFeeLevelOptions[$this->cryptocurrencyFeeLevel]; unset($this->cryptocurrencyFeeLevelOptions[$this->cryptocurrencyFeeLevel]); ?></span>
			<a class="toggle">More</a>
			<ul class="dropdown">
				<?php foreach($this->cryptocurrencyFeeLevelOptions as $feeLevel => $feeDescription){ ?>
				<li><a href="?do[ChangeUserPrefs][CryptocurrencyFeeLevel]=<?php echo $feeLevel; ?>" class="dropdown-link"><?php echo $feeDescription; ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
</fieldset>
<?php } ?>
