<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio">
	<label><?php echo $order_label; ?></label>
</li>
<li>
	<a class="anchor" id="review"></a>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="checkbox">
	<label for="order_steps-<?php echo $step; ?>"><?php echo $review_label; ?></label>
	<div class="expandable">
		<?php require('review_form.php'); ?>
	</div>
</li>
<li id="pay">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" checked>
	<label><?php echo $pay_label; ?></label>
	<div class="expandable">
		<fieldset class="rows-30">
			<h2 class="color-blue">
				<?php
				switch(TRUE){
					case $this->transaction['shipped']:
						echo 'Your order has been shipped.';
					break;
					case $this->accepted:
						echo 'Your order has been accepted.';
					break;
					case $this->paid:
						echo 'Your order has been placed.';
					break;
					default:
						echo 'Your payment is being confirmed&hellip;';
				}
				?>
			</h2>
			<div class="row formatted">
				<?php
				if($this->paid){
					if ($this->escrow) { 
						if($this->accepted) { ?>
						<p>The vendor has accepted your order and it is currently being processed. Please remain patient and be aware that all orders and deliveries may be subject to delays and disruptions.</p>
						<?php } else { ?>
						<p>You will receive a notification when the vendor responds.<br>Be aware, that the vendor may not be able to accept your order. </p>
						<?php } ?>
						<p>When you have received the product, return to this page and click the button below to finalize the transaction.</p>
						<p>If, for whichever reason, the shipment does not reach you within <strong><?php echo $this->inTransitTimeoutDays; ?> days</strong>, you will be given the option to start a dispute.</p>
					<?php } else { ?>
					<p>You will receive a notification when the vendor responds. Upon vendor accept, the payment may be withdrawn directly to the vendor's <?= $this->cryptocurrency->name; ?> address and you will be asked to provide feedback on the transaction. Be aware, that the vendor may not be able to accept your order.</p>
					<p>Please do not leave feedback until you have received the product.</p>
					<p>Meanwhile, please remain patient and keep in mind that all orders and deliveries may be subject to delays and disruptions.</p>
					<?php }
				} else { ?>
				<p><strong class="color-orange">When the payment confirms on the <?= $this->cryptocurrency->name; ?> network, your order will be automatically submitted to the vendor for acceptance.</strong></p>
				<p>If you did not use <?php /*<a class="tooltip" target="_blank" href="<?php echo URL . 'p/' . PAGE_BITCOIN_MINING_FEES . '/'; ?>">*/ ?>adequate <?= $this->cryptocurrency->name; ?> fees<?php /*</a>*/ ?>, payment confirmation may be delayed significantly. If your payment does not confirm within <strong>two days</strong>, your order will be automatically rejected and your payment refunded (subject to standard transaction fees and processing delays).</p>
				<p>Neither <?php echo $this->SiteName_Short; ?> nor the vendor can or should be held accountable for delays due to long confirmation times.</p>
				<?php } ?>
			</div>
			<?php if($this->paid){ ?>
			<ul class="row big-list zebra">
				<li>
					<div class="aux">
						<div><strong><?php
							switch(TRUE){
								case $this->transaction['status'] == 'pending accept':
									echo 'Pending Accept';
								break;
								case $this->transaction['shipped']:
									echo 'In Transit';
								break;
								default:
									echo 'Accepted';
							}
						?></strong></div>
					</div>
					<div class="main">
						<div>Current Order Status:<span>
					</div>
				</li>
			</ul>
			<?php
			} elseif(
				$this->feeBumpRequirement ||
				$this->transaction['hadFeeBump']
			){ ?>
			<div class="row grey-box">
				<?php if($this->transaction['hadFeeBump']){ ?>
				<fieldset class="formatted">
					<p><strong class="color-green bigger"><?php echo $this->escrow ? 'The fee-bump process has been initiated!' : 'The fee-bump has been processed!'; ?></strong></p>
					<p>The payment deposit for this order had a very low transaction fee. We were able to initiate a fee-bump for this order using some of the excess coin deposited.</p>
					<?php if($this->escrow){ ?>
					<p>The vendor has been notified to process this fee-bump. Alternatively, you may attempt to sign the CPFP transaction yourself (advanced) by <a href="<?php echo '/transactions/prepare_transactions/' . $this->TXID .'/'; ?>">clicking here</a> and following the instructions.</p>
					<p>Once processed, you can expect to see your payment confirmed shortly after.</p>
					<?php } else { ?>
					<p>The fee-bump has been submitted and you can expect to see your payment confirmed shortly.</p>
					<?php } ?>
				</fieldset>
				<?php } /*else { ?>
				<fieldset class="formatted">
					<p><strong class="color-red bigger">Warning: Your payment deposit has a very low transaction fee!</strong></p>
					<p>The confirmation of your payment may be significantly delayed or even fail entirely, unless you take the neccessary steps to <em>bump</em> the transaction fee of your deposit.</p>
					<p>We <strong>strongly recommend</strong> you follow the instructions below to ensure a quicker payment confirmation to spare yourself and the vendor of any unnecessary delays or disruptions for this order.</p>
				</fieldset>
				<fieldset class="formatted rows-15">
					<div class="row">
						<p><strong>Step 1:</strong> Learn how to always use adequate fees so you can avoid similar occurences in the future. <a target="_blank" href="<?php echo URL . 'p/' . PAGE_BITCOIN_MINING_FEES . '/'; ?>">Click here</a> to read our quick guide on transaction fees.</p>
						<p class="color-orange">Please make sure you understand what constitutes an adequate transaction fee before you proceed to the next step!</p>
					</div>
					<div class="row rows-5">
						<label class="row label center"><strong>Step 2:</strong> Deposit an additional<b><?php echo $this->feeBumpRequirement; ?> BTC</b>to the bitcoin address below :</label>
						<label class="row text">							
							<input readonly class="big address" value="<?php echo $this->transaction['order']['DepositAddress'] ?>" type="text">
							<a class="btn xs yellow"><i class="icon-refresh"></i></a>
						</label>
						<label class="label center"><small>This will be used to bump the transaction fee of your payment via <a class="tooltip" href="#">child-pays-for-parent (CPFP)</a>.</small></label>
					</div>
				</fieldset>
				<?php }*/ ?>
			</div>
			<?php } ?>
		</fieldset>
		<?php
		if ($this->paid){
			if($this->escrow){ ?>
		<fieldset>
			<label class="label">Escrow Recovery Transaction
				<a class="tooltip inline top">What is this?</a>
				<div>
					<p>This transaction will allow you to recover your escrow in case of a disaster (e.g asteroid impact on server) or any other event causing this site to become unavailable for an extended period of time,</p>
					<p>The transaction has already been signed with our signature.</p>
					<p>However, it has been timelocked and cannot be broadcast until <strong><?php echo AUTO_FINALIZE_BUYER_DAYS ?> days</strong> after the transit period.</p>
					<p>You are advised to write this down and keep it safe until the order has been finalized.</p>
				</div>
			</label>
			<label class="pre">
				<pre contentEditable><?= $this->transaction['next_tx']['AutoFinalize']['hex']; ?></pre>
			</label>
		</fieldset>
		<fieldset class="centered rows-10">
			<?php if( $this->transaction['status'] == 'in transit' ) { ?>
			<a class="row btn big" href="#finalize-transaction">Release Funds (Finalize Transaction)</a>
			<div class="modal" id="finalize-transaction">
				<a href="#close"></a>
				<div class="rows-10">
					<a class="close" href="#close">&times;</a>
					<p class="row">Are you sure you wish to finalize this transaction?</p>
					<form class="row cols-10">
						<div class="col-6"><button formmethod="post" formaction="<?= URL . 'transactions/finalize_transaction/' . $this->TXID . '/'; ?>" class="btn wide" name="csrf" value="<?= $this->getCSRFToken(); ?>">Finalize Transaction</button></div>
						<div class="col-6"><a href="#close" class="btn wide red color">Nevermind</a></div>
					</form>
				</div>
			</div>
			<div class="row"><label class="tooltip a-like" for="show-chat">Dissatisfied with the product?</label></div>
			<?php } else { ?>
			<input type="submit" class="btn big" disabled value="Release Funds (Finalize Transaction)">
			<label><p class="note centered">The vendor has not yet accepted your order.</p></label>
			<?php } ?>
		</fieldset>
		<?php
			}
		} else { ?>
		<fieldset class="centered">
			<a class="btn big" href="<?php echo URL . 'tx/' . $this->TXID . '/finalize/#pay' ?>"><i class="<?php echo Icon::getClass('REFRESH'); ?>"></i>Reload Payment Status</a>
		</fieldset>
		<?php } ?>
	</div>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
