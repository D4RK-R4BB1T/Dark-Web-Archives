<?php

$post = isset($_SESSION['refund_post']) ? $_SESSION['refund_post'] : false;
$response = isset($_SESSION['refund_response']) ? $_SESSION['refund_response'] : false;

//unset($_SESSION['pay_post']);
unset($_SESSION['refund_response']);

?>
<li>
	<a class="anchor" id="review"></a>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="checkbox">
	<label for="order_steps-<?php echo $step; ?>"><?php echo $review_label; ?></label>
	<div class="expandable">
		<?php require('review_form.php'); ?>
	</div>
</li>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" checked>
	<label><?php echo $fulfill_label; ?></label>
	<div class="expandable">
		<fieldset class="formatted">
			<h2 class="color-blue">You have accepted the order.</h2>
			<?php if($this->transaction['escrow_enabled']){ ?>
			<p>The buyer has been instructed to finalize the transaction as soon as the product has been received. If, after <strong><?php echo $this->transaction['order']['EscrowTimeout']; ?> days</strong>, the buyer has not yet finalized this order, they will be given the option to dispute. If the buyer does not react, the order will auto-finalize.</p>
			<p>Please verify that the multisig deposit address (<a target="_blank" href="<?php echo SUPPORT_TRANSACTION_PANEL_BLOCK_EXPLORER_URL_PREFIX_ADDRESS . $this->transaction['multisig_address']; ?>"><?php echo $this->transaction['multisig_address']; ?></a>) has received sufficient payment and that the payment has reached an appropriate number of confirmations.</a></p>
			<?php } else { ?>
			<p>Please ensure that you can withdraw the payment and allow the withdrawal to reach an appropriate number of confirmations.</p>
			<?php } ?>
			<p>After the order has been processed, please click <em>"mark shipped"</em> below to notify the buyer.</p>
		</fieldset>
		<fieldset class="cols-10" id="vendorAction">
			<div class="col-4">
				<a class="btn arrow-left big" href="<?php echo URL . 'account/transactions/'; ?>">Back to Orders</a>
				<?php if( $this->nextOrderHREF ){ ?>
				<a class="btn big arrow-right xs" href="<?php echo $this->nextOrderHREF; ?>">
					<div class="hint above">
						<span>Next Order</span>
					</div>
				</a>
				<?php } ?>
			</div>
			<div class="col-8 align-right">
				<?php
				if (
					$this->transaction['escrow_enabled'] == FALSE &&
					$this->transaction['withdrawn'] == FALSE
				) { ?>
				<a class="btn big yellow" href="<?php echo URL . 'transactions/withdraw_transaction/' . $this->TXID . '/'; ?>">
					<i class="<?php echo Icon::getClass($this->cryptocurrency->name); ?>"></i>
					Withdraw Funds
				</a>
				<?php } if($this->transaction['shipped']) { ?>
				<a <?= $this->transaction['unshipAllowed'] ? 'class="btn big minimal" href="' . URL . 'transactions/toggle_shipped/' . $this->TXID . '/details/"' : 'class="btn big disabled"'; ?>>
					<i class="<?php echo Icon::getClass('PLANE'); ?>"></i>
					Un-mark Shipped
				</a>	
				<?php 
				if (
					$this->transaction['escrow_enabled'] == FALSE &&
					$this->transaction['withdrawn'] == TRUE
				) { ?>
				<a class="btn big arrow-right" href="<?php echo URL . 'tx/' . $this->TXID . '/feedback/'; ?>">Feedback</a>
				<?php } } else { ?>	
				<a class="btn big blue" href="<?php echo URL . 'transactions/toggle_shipped/' . $this->TXID . '/details/' ?>">
					<i class="<?php echo Icon::getClass('PLANE'); ?>"></i>
					Mark Shipped
				</a>
				<?php }
				if ($this->transaction['escrow_enabled']) { ?>
				<label class="btn big red" for="refund_order_modal">Issue Refund</label>
				<input type="checkbox" hidden id="refund_order_modal">
				<div class="modal">
					<label for="refund_order_modal"></label>
					<div class="rows-10">
						<p class="row">Are you sure you wish to issue a full refund?</p>
						<form class="row cols-10">
							<div class="col-6"><button formmethod="post" formaction="<?= URL . 'transactions/refund_transaction/' . $this->TXID . '/' ?>" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="btn wide">Refund Order</button></div>
							<div class="col-6"><label for="refund_order_modal" href="#close" class="btn wide red">Nevermind</label></div>
						</form>
					</div>
				</div>
				<?php } ?>
			</div>
		</fieldset>
		<?php if ($this->transaction['escrow_enabled']) { ?>
		<fieldset>
			<label class="label">Escrow Recovery Transaction
				<a class="tooltip inline top">What is this?</a>
				<div>
					<p>This transaction will allow you to get your escrow in case of a disaster (e.g asteroid strike destroys server) or any other event causing this site to become unavailable for an extended period of time.</p>
					<p>The transaction has already been signed with our signature.</p>
					<p>However, it has been timelocked and cannot be broadcast until <strong><?php echo AUTO_FINALIZE_VENDOR_DAYS ?> days</strong> after the transit period.</p>
					<p>You are advised to write this down and keep it safe until the order has been finalized.</p>
					<p>For more detailed instructions on how to use Escrow Recovery Transaction, see the FAQ link.</p>
				</div>
			</label>
			<label class="pre">
				<pre contentEditable><?php echo $this->transaction['next_tx']['AutoFinalize']['hex']; ?></pre>
			</label>
		</fieldset>
		<?php } echo $your_records; ?>
	</div>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
