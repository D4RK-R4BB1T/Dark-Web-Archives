<?php

$post = isset($_SESSION['pay_post']) ? $_SESSION['pay_post'] : false;
$response = isset($_SESSION['pay_response']) ? $_SESSION['pay_response'] : false;

//unset($_SESSION['pay_post']);
unset($_SESSION['pay_response'], $_SESSION['pay_post']);

?>
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
	<?php if ($this->transaction['timedOut']){ ?>
	<input id="expired_payment_window" type="checkbox" hidden checked>
	<div class="modal steps">
		<div>	
			<a class="close" href="/account/orders/">&times;</a>
			<fieldset class="rows-15 formatted">
				<h5 class="row band bigger"><span><strong class="color-red">The payment window has expired</strong></span></h5>
				<div class="row formatted">
					<p>The payment window for this order has expired and full payment was not detected on the deposit address.</p>
					<?php if ($this->transaction['hasDeposited']) { ?>
					<p>The current balance is <strong class="color-yellow"><?= $this->unconfirmedBalance; ?></strong>.</p>
					<?php } ?>
					<p>Please review your options below:</p>
				</div>
				<form class="row panel">
					<div class="left">
						<?php if ($this->transaction['hasDeposited']) { ?>
						<button name="csrf" value="<?= $this->getCSRFToken() ?>" formmethod="post" formaction="<?= URL . 'transactions/claim_order_deposit_refund/' . $this->TXID . '/' ?>" class="btn red">Claim Refund</button>
						<?php } else { ?>
						<a class="btn red arrow-left" href="/account/transactions/">Go Back</a>
						<?php } ?>
					</div>
					<div class="right">
						<button name="csrf" value="<?= $this->getCSRFToken() ?>" formmethod="post" formaction="<?= URL . 'transactions/renew_order_payment_window/' . $this->TXID . '/' ?>" class="btn left-icon"><i class="sprite-undo"></i>Renew Payment Window</button>
					</div>
				</form>
				<label class="row"><p class="note">Renewing the payment window will cause the payment amount to be re-calculated.</p></label>
			</fieldset>
		</div>
	</div>
	<?php } ?>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" checked>
	<label><?php echo $pay_label; ?></label>
	<div class="expandable">
		<?php $this->renderNotifications(array('Paying')); ?>
		<fieldset class="rows-30">
			<div class="row">
				<div class="cols-20">
					<div class="col-9 rows-10">
						<label class="row label center">Deposit <b><?php
							$priceComponents = explode(' ', $this->priceBreakdown['final']);
							echo '<span contentEditable>' . $priceComponents[0] . '</span> ' . $priceComponents[1];
						?></b> to the <?= $this->cryptocurrency->name; ?> address below :</label>
						<label class="row text">
							<input readonly type="text" class="big address<?php echo $this->depositBalance > 0 ? ' btn-right' : false ?>" value="<?= $this->transaction['order']['DepositAddress'] ?>">
						</label>
						<div class="row panel">
							<label class="<?= $this->cryptocurrency->ID == CURRENCY_ID_LTC ? 'left' : 'middle'; ?> label">Current Balance: <strong class="color-yellow"><?= $this->unconfirmedBalance; ?></strong></label>
							<?php if ($this->cryptocurrency->ID == CURRENCY_ID_LTC){
								$legacyAddress = BitcoinLib::public_key_to_address($this->transaction['redeem_script'], '05');
							?>
							<label class="right label" for="litecoin-legacy-address" style=""><span class="tooltip color-red">Having trouble sending to this address?</span></label>
							<input id="litecoin-legacy-address" type="checkbox" hidden>
							<div class="modal wide">
								<label for="litecoin-legacy-address"></label>
								<div>
									<label class="close" for="litecoin-legacy-address">&times;</label>
									<div class="rows-15">
										<h5 class="row band bigger"><span>Having trouble sending to this litecoin address?</span></h5>
										<div class="row formatted">
										<p>If your wallet gives you an error message when you try to send <strong>litecoin</strong> to this payment addresses, it means the wallet is out-of-date and does not yet support M-type addresses.</p>
										<p>You can make your payment to this address instead :</p>
									</div>
									<label class="row text">
										<input readonly class="big address" value="<?= $legacyAddress; ?>" type="text">
									</label>
										<div class="row grey-box formatted">
											<p><strong><u>Do not</u> send bitcoin to this address!</strong></p>
											<p>You must only send <strong>litecoin</strong> to this address. Bitcoin sent to this address <u>will not</u> be detected and may be impossible to recover.</p>
										</div>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
					<div class="col-3 align-right">
						<img class="qrcode" src="<?= URL . 'transactions/qr_code/' . $this->transaction['order']['DepositAddress'] . '/' . $this->transaction['order']['Price']['final_price'] . '/' . $this->cryptocurrency->name . '/' ?>">
					</div>
				</div>
			</div>
			<?php if ($this->insufficientPayment){ ?>
			<div class="row color-red grey-box formatted">
				<p><strong>Warning:</strong> You have deposited an insufficient amount! Please deposit the remaining amount before the timer runs out, or your order will be cancelled.</p>
				<p>You need to deposit an additional <strong style="display: inline-block" contentEditable><?= $this->insufficientPaymentDifference ?></strong> <?= $this->cryptocurrency->ISO ?>.</p>
			</div>
			<?php } else { ?>
			<div class="row formatted">
				<p>Send your payment to the address shown. Your unconfirmed payment will be detected within a few seconds. You <u>do not</u> need to wait for confirmations.</p>
				<p>The vendor will be notified of your pending purchase and, as soon as the payment is confirmed by the <?= $this->cryptocurrency->name; ?> network, your order will be submitted to the vendor for processing.</p>
				<p class="color-blue">If full payment is not detected within <strong><?= $this->transaction['order']['RenewedPaymentWindow'] ? PENDING_DEPOSIT_TIMEOUT_MINUTES_RENEWAL : PENDING_DEPOSIT_TIMEOUT_MINUTES ?> minutes</strong>, the order will be cancelled and any partial payment will be returned to your refund address (subject to standard transaction fees and processing delays).</p>
			</div>
			<?php } ?>
			<div class="row">
				<?php
					$this->renderCountdownTimer(
						$this->transaction['secondsRemaining'],
						($this->transaction['order']['RenewedPaymentWindow'] ? PENDING_DEPOSIT_TIMEOUT_MINUTES_RENEWAL : PENDING_DEPOSIT_TIMEOUT_MINUTES) * 60
					);
				?>
			</div>
			<ul class="row list-expandable">
				<li>
					<input id="technical-details-2" class="expand" type="checkbox" />
					<label for="technical-details-2">View Technical Details<i></i></label>
					<?php echo $technical_details; ?>
				</li>
			</ul>
		</fieldset>
		<fieldset class="centered">
			<a class="btn big" href="<?php echo URL . 'tx/' . $this->TXID . '/finalize/#pay' ?>"><i class="<?php echo Icon::getClass('REFRESH'); ?>"></i>Reload Payment Status</a>
		</fieldset>
	</div>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
