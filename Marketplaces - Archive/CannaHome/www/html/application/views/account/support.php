<?php

require('messages/header.php'); 

$URLPrefix = URL . 'account/support/' . ($this->UserMod ? $this->targetUserAlias . '/' : FALSE);
?>
		<div class="content">
			<div class="chat-box not-fixed">
				<div>
					<div class="chat-info<?= !$this->UserMod ? ' formatted' : false; ?>">
						<?php if ($this->UserMod){ ?>
						<div class="panel">
							<div class="middle">
								<a class="subject" href="<?php echo URL . 'u/' . $this->chat['SubjectUserAlias'] . '/'; ?>"><?php echo $this->chat['SubjectUserAlias']; ?></a>
							</div>
							<div class="left">
								<a class="btn arrow-left" href="<?php echo URL . 'account/support_overview/'; ?>">Return to Overview</a>
							</div>
							<div class="right">
								<div class="big-dropdown">
									<span><?php echo $this->chat['StatusTitle']; ?></span>
									<a class="toggle"></a>
									<ul class="dropdown">
										<?php foreach($this->chatStatuses as $chatStatus){
											if($this->chat['StatusID'] == $chatStatus['ID'])
												continue;
										?>
										<li>
											<a class="dropdown-link" href="<?php echo URL . 'account/change_chat_status/' . $this->chat['ID'] . '/' . $chatStatus['ID'] . '/'; ?>"><?php echo $chatStatus['Title']; ?></a>
										</li>
										<?php } ?>
									</ul>
								</div>
								<?php
								switch($this->chat['SubscriptionRole']){
									case CHAT_ROLE_SUPPORT: ?>
									<a class="btn red xs" href="<?php echo URL . 'account/toggle_chat_subscription/' . $this->chat['ID'] . '/'; ?>">
										<i class="<?= Icon::getClass('COFFEE'); ?>"></i>
										<div class="hint below">
											<span>Unsubscribe</span>
										</div>
									</a>
									<?php break;
									default: ?>
									<a class="btn xs" href="<?php echo URL . 'account/toggle_chat_subscription/' . $this->chat['ID'] . '/' . CHAT_ROLE_SUPPORT . '/'; ?>">
										<i class="<?php echo Icon::getClass('RSS'); ?>"></i>
										<div class="hint below">
											<span>Subscribe</span>
										</div>
									</a>
									<?php
								}
								?>
							</div>
						</div>
						<?php } else { ?>
						<h6><?php echo SUPPORT_INFO_TITLE; ?></h6>
						<?php echo SUPPORT_INFO_BODY; ?>
						<?php } ?>
					</div>
					<?php if(
						$this->UserMod &&
						$this->chat['SubscriptionRole'] == CHAT_ROLE_SUPPORT
					){ ?>
					<div class="mod-functions">
						<label class="btn panel-button" for="reveal-panel">Transaction Panel</label>
						<input id="reveal-panel" hidden type="checkbox"<?php echo $this->activeTransaction ? ' checked' : FALSE; ?>>
						<div class="panel-box rows-10">
							<form class="row cols-5" method="post" action="<?php echo URL . 'account/set_chat_transaction_id/' . $this->chat['ID'] . '/'; ?>">
								<div class="col-7">
									<label class="select prepend">
										<?php if($this->relevantTransactions){ ?>
										<select name="transaction_id_select">
											<option value="0">Select Transaction (<?php echo NXS::formatNumber(count($this->relevantTransactions)); ?> total)</option>
											<?php
											$activeSelectTX = FALSE;
											foreach($this->relevantTransactions as $relevantTransaction){ ?>
											<option <?php 
												if(
													$this->activeTransaction &&
													$this->activeTransaction['ID'] == $relevantTransaction['ID']
												){
													echo 'selected ';
													$activeSelectTX = TRUE;
												}
											?>value="<?php echo $relevantTransaction['ID']; ?>"><?php
												echo
													'#' .
													$relevantTransaction['Identifier'] .
													' &verbar; ' .
													$relevantTransaction['SubjectAlias'] .
													' &verbar; ' .
													$relevantTransaction['Value'] .
													' &verbar; ' .
													ucwords($relevantTransaction['Status']);
											?></option>
											<?php } ?>
										</select>
										<?php } else { ?>
										<select disabled>
											<option>Zero Transactions</option>
										</select>
										<?php } ?>
										<i class="<?php echo Icon::getClass('EXCHANGE'); ?>"></i>
									</label>
								</div>
								<label class="col-1 label centered">or</label>
								<div class="col-3">
									<label class="text">
										<input class="prepend" name="transaction_id_specify" placeholder="<?php echo $this->activeTransaction && $activeSelectTX == FALSE ? $this->activeTransaction['Identifier'] : 'TXID'; ?>" type="text">
										<i class="<?php echo Icon::getClass('HASHTAG'); ?>"></i>
									</label>
								</div>
								<div class="col-1 align-right">
									<button class="btn xs" type="submit">
										<i class="<?php echo Icon::getClass('FORWARD'); ?>"></i>
									</button>
								</div>
							</form>
							<?php if($this->activeTransaction){ 
								$justSetChatTransactionID =
									(
										isset($_SESSION['justSetChatTransactionID']) &&
										$_SESSION['justSetChatTransactionID']
									)
										? $_SESSION['justSetChatTransactionID']
										: FALSE;
								
								unset($_SESSION['justSetChatTransactionID']);
							?>
							<ul class="row list-expandable">
								<li>
									<input id="transaction-details" class="expand" type="checkbox"<?php echo $justSetChatTransactionID ? ' checked' : FALSE; ?>>
									<label for="transaction-details">
										Transaction Details
										<i></i>
									</label>
									<div class="expandable">
										<fieldset>
											<ul class="big-list x-small">
												<li>
													<div class="aux">
														<div><span class="monospace"><?php echo $this->activeTransaction['ID'] . '<br>' . $this->activeTransaction['Identifier']; ?></span></div>
													</div>
													<div class="main">
														<div><span>Transaction IDs</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><strong><?php echo ucwords($this->activeTransaction['Status']); ?></strong></div>
													</div>
													<div class="main">
														<div><span>Status</span></div>
													</div>
												</li>
												<li>
													<?php if (!$this->activeTransaction['MultiSigAddress']){ ?>
													<div class="badge yellow">Unconfirmed</div>
													<?php } elseif ($this->activeTransaction['Escrow']){ ?>
													<div class="badge green">Escrow</div>
													<?php } else { ?>
													<div class="badge red">Direct-Pay</div>
													<?php } ?>
													<div class="main">
														<div><span>Transaction Type</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><span><?= $this->activeTransaction['Value']; ?></span></div>
													</div>
													<div class="main">
														<div><span>Value</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><span><?php echo $this->activeTransaction['Timeout']; ?></span></div>
													</div>
													<div class="main">
														<div><span>Timeout</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><a target="_blank" href="<?php echo URL . 'i/' . NXS::getB36($this->activeTransaction['ListingID']) . '/' ?>"><?php echo $this->activeTransaction['ListingName']; ?></a></div>
													</div>
													<div class="main">
														<div><span>Listing</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><a target="_blank" href="<?php echo URL . 'v/' . $this->activeTransaction['VendorAlias'] . '/' ?>"><?php echo $this->activeTransaction['VendorAlias']; ?></a></div>
													</div>
													<div class="main">
														<div><span>Vendor</span></div>
													</div>
												</li>
												<li>
													<div class="aux">
														<div><a target="_blank" href="<?php echo URL . 'u/' . $this->activeTransaction['BuyerAlias'] . '/' ?>"><?php echo $this->activeTransaction['BuyerAlias']; ?></a></div>
													</div>
													<div class="main">
														<div><span>Buyer</span></div>
													</div>
												</li>
											</ul>
										</fieldset>
										<?php
										if (
											$this->activeTransaction['MultiSigAddress'] ||
											$this->activeTransaction['vendorAddress'] ||
											$this->activeTransaction['buyerAddress']
										){ ?>
										<fieldset class="rows-10">
											<?php
											if ($this->activeTransaction['MultiSigAddress']){ ?>
											<div class="row cols-10">
												<label class="col-3 label">Deposit Address</label>
												<div class="col-9">
													<div class="text">
														<input readonly value="<?php echo $this->activeTransaction['MultiSigAddress']; ?>" type="text">
														<a class="btn" target="_blank" href="<?= $this->activeTransaction['blockExplorerPrefix'] . $this->activeTransaction['MultiSigAddress'] . $this->activeTransaction['blockExplorerSuffix']; ?>">?</a>
													</div>
												</div>
											</div>
											<?php }
											if ($this->activeTransaction['vendorAddress']){ ?>
											<div class="row cols-10">
												<label class="col-3 label">Vendor Address</label>
												<div class="col-9">
													<div class="text">
														<input readonly value="<?php echo $this->activeTransaction['vendorAddress']; ?>" type="text">
														<a class="btn" target="_blank" href="<?= $this->activeTransaction['blockExplorerPrefix'] . $this->activeTransaction['vendorAddress'] . $this->activeTransaction['blockExplorerSuffix']; ?>">?</a>
													</div>
												</div>
											</div>
											<?php }
											if ($this->activeTransaction['buyerAddress']){ ?>
											<div class="row cols-10">
												<label class="col-3 label">Buyer Address</label>
												<div class="col-9">
													<div class="text">
														<input readonly value="<?php echo $this->activeTransaction['buyerAddress']; ?>" type="text">
														<a class="btn" target="_blank" href="<?= $this->activeTransaction['blockExplorerPrefix'] . $this->activeTransaction['buyerAddress'] . $this->activeTransaction['blockExplorerSuffix']; ?>">?</a>
													</div>
												</div>
											</div>
											<?php } ?>
										</fieldset>
										<?php }
										if($this->activeTransaction['RedeemScript']){ ?>
										<fieldset class="rows-10">
											<div class="row">
												<label class="label">Redeem Script</label>
												<label class="pre">
													<pre contentEditable><?php echo $this->activeTransaction['RedeemScript']; ?></pre>
												</label>
											</div>
											<?php
											if(
												$this->activeTransaction['publicKeys'] &&
												$this->activeTransaction['publicKeys']['buyer']
											){ ?>
											<div class="row cols-10">
												<label class="col-2 label">Buyer:</label>
												<div class="col-10">
													<label class="textarea">
														<textarea readonly rows="1" style="resize:none"><?php echo $this->activeTransaction['publicKeys']['buyer']; ?></textarea>
													</label>
												</div>
											</div>
											<?php } ?>
											<div class="row cols-10">
												<label class="col-2 label">Vendor:</label>
												<div class="col-10">
													<label class="textarea">
														<textarea readonly rows="1" style="resize:none"><?php echo $this->activeTransaction['publicKeys']['vendor'] ?></textarea>
													</label>
												</div>
											</div>
											<div class="row cols-10">
												<label class="col-2 label">Market:</label>
												<div class="col-10">
													<label class="textarea">
														<textarea readonly="" rows="1" style="resize:none"><?php echo $this->activeTransaction['publicKeys']['marketplace']; ?></textarea>
													</label>
												</div>
											</div>
										</fieldset>
										<?php } ?>
									</div>
								</li>
							</ul>
							<?php } ?>
						</div>
					</div>
					<?php } ?>
					<form class="chat-form" method="post" action="<?php echo URL . 'account/send_chat_message/' . ($this->UserMod ? $this->chat['ID'] . '/' : FALSE); ?>">
						<fieldset class="rows-5">
							<?php if($this->UserMod){ ?>
							<label class="row text inline">
								<input type="hidden" name="initial_note" value="<?php echo $this->chat['LatestNote']; ?>">
								<input name="note" placeholder="Subject, Comments, Notes (for internal use only)" class="prepend" value="<?php echo $this->chat['LatestNote']; ?>" type="text">
								<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
								<b></b>
							</label>
							<?php } ?>
							<div class="row cols-5">
								<div class="col-9">
									<label class="textarea chat-textarea">
										<textarea<?php echo $this->UserMod ? FALSE : ' required'; ?> name="message" placeholder="Write message&hellip;"></textarea>
									</label>
								</div>
								<div class="col-3 rows-5">
									<?php if($this->UserMod){ ?>
									<label class="row select prepend">
										<?php if ($this->modUsernames){ ?>
										<select name="sender">
											<?php foreach($this->modUsernames as $modUsername){ ?>
											<option<?= $modUsername == $this->UserAlias ? ' selected' : false ?>><?= $modUsername; ?></option>
											<?php } ?>
										</select>
										<?php } else { ?>
										<select disabled>
											<option><?php echo $this->UserAlias; ?></option>
										</select>
										<?php } ?>
										<i class="<?= Icon::getClass('USER'); ?>"></i>
									<?php } else {
										/*if($this->ongoingTransactions){ ?>
										<select name="transaction_id">
											<option selected disabled>Order ID</option>
											<?php foreach($this->ongoingTransactions as $ongoingTransaction) {
												$label =
													'#' .
													$ongoingTransaction['ID'] .
													' &verbar; ' . 
													$ongoingTransaction['SubjectAlias'] .
													' &verbar; &#579; ' .
													$ongoingTransaction['Value'] .
													' &verbar; ' .
													ucwords($ongoingTransaction['Status']);
											?>
											<option value=<?php echo $ongoingTransaction['ID']; ?>><?php echo $label; ?></option>
											<?php } ?>
										</select>
										<?php } else { ?>
										<select disabled>
											<option selected>Order ID</option>
										</select>
										<?php } */ ?>
									<label class="row text">
										<input placeholder="Order ID" name="transaction_id" class="prepend" type="text">
										<i class="<?php echo Icon::getClass('HASHTAG'); ?>"></i>
										<?php } ?>
									</label>
									<button class="row btn wide arrow-right" type="submit">Send</button>
								</div>
							</div>
						</fieldset>
					</form>
					<ol class="list-discussion">
						<?php if($this->chat['messages']){
						foreach($this->chat['messages'] as $i => $message){
							$isFirstMessage =
								$i == 0 &&
								$this->supportPageNumber == $this->numberOfPages &&
								$message['type'] == CHAT_MESSAGE_ENTRY_TYPE_MESSAGE &&
								$message['SenderAlias'] == $this->UserAlias &&
								!$this->UserMod;
								
							$isVeryFirstMessage =
								$isFirstMessage &&
								count($this->chatMessages) == 1;
						
						switch($message['type']){
							case CHAT_MESSAGE_ENTRY_TYPE_MESSAGE:
								$isEditable = $message['RawContent'] !== FALSE;
								
								$color = $message['Color'];
								$class =
									'sender-' .
									$message['SenderAlias'] .
									(
										$message['Color']
											? ' ' . $color
											: FALSE
									) .
									/*(
										$message['Unread'] &&
										count($this->chatMessages) > 1
											? ' fade-in'
											: FALSE
									) .*/
									(
										$isEditable
											? ' is-editable'
											: FALSE
									);
									
								if($this->UserMod){ 
									$deleteMessage_modalID = 'delete_message-' . $message['ID'];
									$editMessage_checkboxID = 'edit_message-' . $message['ID'];
								}
							?>
						<li id="message-<?php echo $message['ID']; ?>" class="<?php echo $class; ?>">
							<?php if($this->UserMod){ ?>
							<input type="checkbox" class="edit_message_toggle" hidden id="<?php  echo $editMessage_checkboxID; ?>">
							<?php } ?>
							<div class="<?php 
							
							$metaClass =
								'meta' .
								(
									$message['TransactionID']
										? ' has-txid'
										: FALSE
								) .
								(
									$this->UserMod
										? ' has-btns'
										: FALSE
								);
							
							echo $metaClass;
							?>">
								<a class="username" href="<?php echo URL . 'u/' . $message['SenderAlias'] . '/' ?>"><?php echo $message['SenderAlias']; ?></a>
								<time><?php echo $message['date']; ?><span><?php echo $message['time']; ?></span></time>
								<?php if($message['TransactionID']){ ?>
								<a class="txid">&#35;<?php echo $message['TransactionIdentifier']; ?></a>
								<?php }
								if($this->UserMod){ ?>
								<div class="btns">
									<label for="<?php echo $deleteMessage_modalID; ?>" class="btn xs color-red">
										<i class="<?php echo Icon::getClass('TIMES', true); ?>"></i>
										<div class="hint below">
											<span>Delete</span>
										</div>
									</label>
									<label for="<?php echo $editMessage_checkboxID; ?>" class="btn xs">
										<i class="<?= Icon::getClass('EDIT', true); ?>"></i>
										<div class="hint below">
											<span>Edit</span>
										</div>
									</label>
								</div>
								<input id="<?php echo $deleteMessage_modalID; ?>" hidden type="checkbox">
								<div class="modal">
									<label for="<?php echo $deleteMessage_modalID; ?>"></label>
									<div class="rows-10">
										<label for="<?php echo $deleteMessage_modalID; ?>" class="close">&times;</label>
										<p class="row">Are you sure you wish to delete this message?</p>
										<div class="row cols-10">
											<div class="col-6"><a href="<?php echo URL . 'account/delete_chat_message/' . $message['ID'] . '/'; ?>" class="btn wide color">Delete It</a></div>
											<div class="col-6">
												<label for="<?php echo $deleteMessage_modalID; ?>" class="btn wide red color">Nevermind</label>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
								<b></b>
							</div>
							<div class="comment">
								<div<?= $message['status'] ? ' data-status="' . $message['status'] . '"' : false; ?>>
									<?php if($isEditable){ ?>
									<form method="post" action="<?php echo URL . 'account/edit_chat_message/' . $message['ID'] . '/'; ?>" class="messages editable rows-5">
										<input type="hidden" name="redirect" value="<?php echo $URLPrefix . $this->supportPageNumber . '/#message-' . $message['ID']; ?>">
										<label class="row textarea">
											<textarea name="content"><?php echo $message['RawContent']; ?></textarea>
										</label>
										<div class="row align-right">
											<button type="submit" class="btn">Save</button>
											<label for="<?php echo $editMessage_checkboxID; ?>" class="btn red">Cancel</button>
										</div>
									</form>
									<?php } ?>
									<div class="messages formatted">
										<?php echo $message['HTML']; ?>
									</div>
								</div>
							</div>
						</li>
						<?php break;
						case CHAT_MESSAGE_ENTRY_TYPE_EVENT:
							if(
								$this->UserMod &&
								(
									!isset($this->chat['messages'][$i + 1]) ||
									$this->chat['messages'][$i + 1]['type'] !== CHAT_MESSAGE_ENTRY_TYPE_EVENT
								)
							){?>
							<li class="remark">
								<span>
									<?php echo $message['text']; ?>
								</span>
							</li>
						<?php	}
						break;
						case CHAT_MESSAGE_ENTRY_TYPE_NOTE: ?>
						<li class="remark">
							<span>
								<strong><?php echo $message['AuthorAlias']; ?></strong> added a remark:<br>&ldquo;<?php echo $message['Note']; ?>&rdquo;
							</span>
						</li>
						<?php break;
						}
						
						if ($isFirstMessage){ ?>
						<li class="remark">
							<span class="formatted"><?= SUPPORT_INFO_STATUS_CHANGED_ONGOING; ?></span>
						</li>
						<?php }
						
						}
						} ?>
					</ol>
					<?php 
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
							animation: chatNoteAppear .5s 4s forwards;
							max-height: 0;
						}
					</style>
					<?php }
					if($this->numberOfPages > 1){ ?>
					<div class="panel">
						<?php
						$this->renderPaginationPanel(
							$this->supportPageNumber,
							$this->numberOfPages,
							$URLPrefix
						);
						?>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
<?php require('messages/footer.php'); ?>
