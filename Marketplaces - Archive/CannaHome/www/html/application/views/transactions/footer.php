		</ul>
	</div>
	<div class="rows-30 col-4 side-bar">
		<?php if( !isset($this->isMediator) || !$this->isMediator ) { ?>
		<div class="row rows-30">
			<?php if( isset($this->listing) ) { ?>
			<ul class="pic-list">
				<li>
					<div>
						<?php if( $this->listing['image'] ) { ?>
						<div class="image" style="background-image: url('<?php echo $this->listing['image']; ?>')"></div>
						<?php } ?>
						<div class="main">
							<div>
								<div>
									<div>
										<span><?php echo $this->listing['name']; ?></span>
									</div>
								</div>
								<span><span><a target="_blank" href="<?php echo URL . 'v/' . $this->listing['vendorAlias'] . '/' ?>"><?php echo $this->listing['vendorAlias']; ?></a></span></span>
							</div>
						</div>
					</div>
				</li>
			</ul>
			<?php }
			if( isset($this->transaction) ) { ?>
			<ul class="row big-list x-small no-border">
				<li>
					<div class="aux"><div><strong class="monospace"><?php echo $this->TXID; ?></strong></div></div>
					<div class="main">
						<div><span>Order ID</span></div>
					</div>
				</li>
				<li class="border-top">
					<div class="badge <?= $this->transaction['paymentMethod']['color']; ?>"><i class="<?= Icon::getClass($this->cryptocurrency->name); ?>"></i><?= ucwords($this->cryptocurrency->name); ?></div>
					<div class="main">
						<div><span>Payment Method</span></div>
					</div>
				</li>
				<?php if($this->confirmed) {
					switch($this->transaction['escrow_enabled']){
						case TRUE:
							$typeLabel	= '<i class="' . Icon::getClass('LOCK') . '"></i>Escrow';
							$typeColor	= 'green';
						break;
						default:
							$typeLabel	= '<i class="' . Icon::getClass('EXCHANGE') . '"></i>Direct-Pay';
							$typeColor	= 'red';
					} ?>
				<li class="border-top">
					<div class="badge <?php echo $typeColor; ?>"><?php echo $typeLabel; ?></div>
					<div class="main">
						<div><span>Transaction Type</span></div>
					</div>
				</li>
				<?php }
				if($this->accepted){ ?>
				<li class="border-top">
					<div class="aux"><div><span><?php echo $this->transaction['acceptDate']; ?></span></div></div>
					<div class="main">
						<div><span>Accepted On</span></div>
					</div>
				</li>
				<?php } ?>
			</ul>
			<ul class="row big-list x-small no-border">
				<li>
					<div class="aux">
						<?php if($this->UserVendor && $this->transaction['order']['Quantity'] > 1){ ?>
						<div class="color-green">
							<strong>&times;<?php echo $this->transaction['order']['Quantity']; ?></strong>
							<?php if($this->transaction['text_quantity']) {?>
							<br>
							<span><?php echo $this->transaction['text_quantity']; ?></span>
							<?php } ?>
						</div>
						<?php } else { ?>
						<div><span><?php
						echo
							(
								isset($this->transaction['next_tx']['reship_percentage'])
									? '<span>' . $this->transaction['next_tx']['reship_percentage'] . '% &times;</span> '
									: false
							) .
							(
								$this->transaction['text_quantity']
									?
										(
											$this->transaction['order']['Quantity'] > 1
											?
												(
													'<strong>&times;' .
													$this->transaction['order']['Quantity'] .
													'</strong> (' .
													$this->transaction['text_quantity'] .
													')'
												)
											:	$this->transaction['text_quantity']
										)
									: $this->transaction['order']['Quantity']
							);
						?></span></div>
						<?php } ?>
					</div>
					<div class="main">
						<div><span>Quantity</span></div>
					</div>
				</li>
				<li class="border-top">
					<div class="aux">
						<div><span><?php echo $this->priceBreakdown['raw']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Subtotal</span></div>
					</div>
				</li>
				<?php if ($this->transaction['order']['Price']['price_shipping'] > 0) { ?>
				<li>
					<div class="aux">
						<div><span><?php echo $this->priceBreakdown['shipping'] ?></span></div>
					</div>
					<div class="main">
						<div><span>Shipping</span></div>
					</div>
				</li>
				<?php }
				if (
					isset($this->transaction['order']['Discount']) &&
					$this->transaction['order']['Discount']
				){ ?>
				<li>
					<div class="aux">
						<div><span><?php echo $this->transaction['order']['Discount'] ?></span></div>
					</div>
					<div class="main">
						<div><span>Promotional Discount</span></div>
					</div>
				</li>
				<?php }
				if (!$this->isFree){
					if (isset($this->priceBreakdown['marketplace'])) { ?>
				<li>
					<div class="aux">
						<div><span><?= $this->priceBreakdown['marketplace']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Marketplace Fees</span></div>
					</div>
				</li>
				<?php } 
				if (isset($this->priceBreakdown['network']) ) { ?>
				<li>
					<div class="aux">
						<div><span><?= $this->priceBreakdown['network']; ?></span></div>
					</div>
					<div class="main">
						<div><span><?= ucwords($this->cryptocurrency->name); ?> Fees</span></div>
					</div>
				</li>
				<?php }
				} ?>
				<li class="border-top big">
					<div class="aux">
						<?php if ($this->isFree){ ?>
						<div>FREE</div>
						<?php } else { ?>
						<div><?= $this->priceBreakdown['final']; ?><br><span><?= $this->priceBreakdown['currency']; ?></span></div>
						<?php } ?>
					</div>
					<div class="main">
						<div>Total</div>
					</div>
				</li>
			</ul>
			<?php } else { ?>
			<ul class="big-list x-small no-border">
				<li class="border-top">
					<div class="aux">
						<div>
							<span>
								<div class="rating stars">
									<?php $this->renderRating($this->listing['rating']); ?>
								</div>
							</span>
						</div>
					</div>
					<div class="main">
						<div><span>Rating (<?= $this->listing['ratingCount'] . ($this->listing['exceededMaximumVisibleRatings_listing'] ? '+' : false) . ' rating' . ($this->listing['ratingCount'] == 1 ? false : 's') ?>)</span></div>
					</div>
				</li>
				<li class="border-top">
					<div class="aux">
						<div><span><?php echo $this->listing['price_crypto'] ?></span></div>
					</div>
					<div class="main">
						<div><span>Price</span></div>
					</div>
				</li>
				<?php if( $this->listing['price_unit'] ){ ?>
				<li class="shift-up">
					<div class="aux">
						<div><span><?php echo $this->listing['price_unit']; ?></span></div>
					</div>
					<div class="main">
						<div>&nbsp;</div>
					</div>
				</li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
		<div class="row rows-15">
			<h4 style="display: flex;overflow: hidden">
				<i class="<?= Icon::getClass('USER'); ?>"></i>
				<?php if ($this->UserVendor) { ?>
				<strong style="overflow: hidden;text-overflow: ellipsis"><?= $this->transaction['buyer_alias']; ?></strong>
				<?php } else { ?>
				<a style="overflow: hidden;text-overflow: ellipsis" href="<?= URL . 'v/' . $this->listing['vendorAlias'] . '/' ?>"><?= $this->listing['vendorAlias']; ?></a>
				<?php }
				if ($isCreated = isset($this->TXID) && $this->TXID){ ?>
				<label style="flex-shrink: 0;position: relative;flex-grow: 1;text-align: right" class="small right" for="send_message"><i class="<?= Icon::getClass('ENVELOPE'); ?>"></i> Send Message</label>
				<?php } ?>
			</h4>
			<?php if($isCreated){
			$this->renderNotifications(array('Messages')); ?>
			<input id="send_message" type="checkbox" hidden>
			<div class="modal">
				<label for="send_message"></label>
				<div>
					<label for="send_message" class="close">&times;</label>
					<form method="post" class="rows-20" action="<?php echo URL . 'account/send_message/'; ?>">
						<input type="hidden" name="return" value="<?php echo 'tx/' . $this->TXID . '/' . $this->option . '/'; ?>">
						<fieldset>
							<div class="cols-15">
								<div class="col-7">
									<label class="label">Recipient's Username</label>
									<label class="text">
										<input name="recipient_alias" readonly required value="<?php echo $this->UserVendor ? $this->transaction['buyer_alias'] : $this->listing['vendorAlias']; ?>" class="prepend" type="text">
										<i class="<?php echo Icon::getClass('USER'); ?>"></i>
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
							<?php if ($this->TXID){ ?>
							<label class="text pre-textarea">
								<input value="<?= ('Order #' . $this->TXID) . ($this->paid ? ' (' . URL . 'tx/' . $this->TXID . '/)' : false); ?>" name="subject" type="hidden">	
								<input readonly value="<?= 'Order #' . $this->TXID; ?>" type="text">
							</label>
							<?php } ?>
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
			<?php } ?>
			<ul class="row big-list x-small zebra">
				<?php if ($this->isVendor) { ?>
				<li>
					<div class="aux">
						<div><span><?php echo $this->transaction['buyerPurchases']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Purchases</span></div>
					</div>
				</li>
				<?php } /*
				<li>
					<?php if ($this->isVendor) { ?>
					<div class="aux">
						<div><span><?php echo $this->transaction['buyerPurchases']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Purchases</span></div>
					</div>
					<?php } else { ?>
					<div class="aux">
						<div><span><?php echo $this->listing['vendorSales']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Sales</span></div>
					</div>
					<?php } ?>
				</li> */ ?>
				<?php if ($this->isVendor) { ?>
				<li>
					<div class="aux">
						<div><span>&#126; <?php echo $this->transaction['buyerTransacted']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Value Transacted (nominal)</span></div>
					</div>
				</li>
				<?php } ?>
				<li>
					<div class="aux">
						<div>
							<span class="rating stars">
								<?php echo $this->renderRating($this->isVendor ? $this->transaction['buyerRating'] : $this->listing['vendorRating']); ?>
							</span>
						</div>
					</div>
					<div class="main">
						<div>
							<span>Rating <?php 
								if ($this->isVendor){
									$ratingCount = $this->transaction['buyerRatingCount'];
									$commentLink = $this->transaction['buyerCommentCount'] > 0 ? URL . 'u/' . $this->transaction['buyer_alias'] . '/comments/' : false;
								} else {
									$ratingCount = $this->listing['vendorRatingCount'];
									$commentLink = $this->listing['vendorCommentCount'] > 0 ? URL . 'v/' . $this->listing['vendorAlias'] . '/comments/' : false;
								}
							echo ($commentLink ? '<a class="color-blue" target="_blank" href="' . $commentLink . '">' : '<strong>') . '(' . $ratingCount . (isset($this->listing) && $this->listing['exceededMaximumVisibleRatings_vendor'] ? '+' : false) . ' rating' . ($ratingCount == 1 ? false : 's') . ')' . ($commentLink ? '</a>' : '</strong>') ?></span>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<?php } else { ?>
		<div class="row rows-15">
			<h4>
				Vendor: 
				<a href="<?php echo URL . 'v/' . $this->listing['vendorAlias'] . '/' ?>"><?php echo $this->listing['vendorAlias']; ?></a>
			</h4>
			<ul class="row big-list x-small zebra">
				<li>
					<div class="aux">
						<div><span><?php echo $this->listing['vendorSales']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Sales</span></div>
					</div>
				</li>
				<li>
					<div class="aux">
						<div>
							<span class="rating stars">
								<?php echo $this->renderRating($this->listing['vendorRating']); ?>
							</span>
						</div>
					</div>
					<div class="main">
						<div>
							<span>Rating <?php 
							$ratingCount = $this->listing['vendorRatingCount'];
							$commentLink = $this->listing['vendorCommentCount'] > 0 ? URL . 'v/' . $this->listing['vendorAlias'] . '/comments/' : false;
									
							echo ($commentLink ? '<a class="color-blue" target="_blank" href="' . $commentLink . '">' : '<strong>') . '(' . $ratingCount . (isset($this->listing) && $this->listing['exceededMaximumVisibleRatings_vendor'] ? '+' : false) . ' rating' . ($ratingCount == 1 ? false : 's') . ')' . ($commentLink ? '</a>' : '</strong>') ?></span>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<div class="row rows-15">
			<h4>
				<strong>Buyer: <?php echo $this->transaction['buyer_alias']; ?></strong>
			</h4>
			<ul class="row big-list x-small zebra">
				<li>
					<div class="aux">
						<div><span><?php echo $this->transaction['buyerPurchases']; ?></span></div>
					</div>
					<div class="main">
						<div><span>Purchases</span></div>
					</div>
				</li>
				<li>
					<div class="aux">
						<div>
							<span class="rating stars">
								<?php echo $this->renderRating($this->transaction['buyerRating']); ?>
							</span>
						</div>
					</div>
					<div class="main">
						<div>
							<span>Rating <?php 
								$ratingCount = $this->transaction['buyerRatingCount'];
								$commentLink = $this->transaction['buyerCommentCount'] > 0 ? URL . 'u/' . $this->transaction['buyer_alias'] . '/comments/' : false;
								
							echo ($commentLink ? '<a target="_blank" href="' . $commentLink . '">' : '<strong>') . '(' . $ratingCount . ' rating' . ($ratingCount == 1 ? false : 's') . ')' . ($commentLink ? '</a>' : '</strong>') ?></span>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<?php } ?>
	</div>
</div>
