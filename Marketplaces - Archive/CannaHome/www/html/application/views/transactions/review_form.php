<?php
if (
	$showPromoBox = 
		!$this->UserVendor &&
		!$this->confirmed
){
	$promoFeedback = false;
	if ( isset($_SESSION['promoFeedback']) ) {
		$promoFeedback = $_SESSION['promoFeedback'];
		unset($_SESSION['promoFeedback']);
	}
?>
<form id="promo_form" method="post" action="<?php echo URL . 'transactions/apply_promo/' . $this->TXID . '/'; ?>"></form>
<?php } ?>
<form method="post" action="<?php echo $this->isVendor ? URL . 'transactions/respond_transaction/' . $this->TXID . '/' : URL . 'transactions/confirm_transaction/' . $this->TXID . '/'; ?>">
	<fieldset>
		<table class="order-table">
			<thead>
				<tr>
					<th>Item</th>
					<th>Quantity</th>
					<th>Subtotal</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $this->transaction['listing_name'] ?></td>
					<td><?php
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
												'<strong' .
												(
													$this->isVendor
														? ' class="big-quantity"'
														: FALSE
												) .
												'>&times;' .
												$this->transaction['order']['Quantity'] .
												'</strong> (' .
												$this->transaction['text_quantity'] .
												')'
											)
										: $this->transaction['text_quantity']
									)
								: $this->transaction['order']['Quantity']
						);
					?></td>
					<td><?= $this->priceBreakdown['raw'] ?></td>
				</tr>
				<tr>
					<td colspan="2">Shipping<br><span><?php echo $this->transaction['order']['Shipping'] ?></span></td>
					<td><?= $this->priceBreakdown['shipping'] ?></td>
				</tr>
				<?php
				if (
					$hasDiscount =
						isset($this->transaction['order']['Discount']) &&
						$this->transaction['order']['Discount']
				){
				?>
				<tr>
					<td colspan="2">Promotional Discount<br><span><?= $this->transaction['order']['PromoCode']; ?></span></td>
					<td class="color-orange"><?= $this->isFree ? '<big><strong>FREE</strong></big>' : '&ndash; ' . $this->transaction['order']['Discount']; ?></td>
				</tr>
				<?php }
				if (!$this->isFree){
					if (isset($this->priceBreakdown['marketplace'])) { ?>
				<tr>
					<td colspan="2">Platform Fees <span>(<?php
						echo $this->transaction['vendor_commission']
							? NXS::formatDecimal(
								$this->transaction['vendor_commission']/10,
								1
							)
							: MARKETPLACE_FEE*100 ?>%)</span></td>
					<td class="color-red">&ndash; <?php echo $this->priceBreakdown['marketplace']; ?></td>
				</tr>
				<?php } 
				if (isset($this->priceBreakdown['network'])) { ?>
				<tr>
					<td colspan="2">
						<?= $this->cryptocurrency->name; ?> Fees
						<?php if($this->cryptocurrencyFeeLevelOptions){ ?>
						<b></b>
						<div class="big-dropdown smaller">
							<span><?php echo $this->cryptocurrencyFeeLevelOptions[$this->cryptocurrencyFeeLevel]; unset($this->cryptocurrencyFeeLevelOptions[$this->cryptocurrencyFeeLevel]); ?></span>
							<a class="toggle">More</a>
							<ul class="dropdown">
								<?php foreach($this->cryptocurrencyFeeLevelOptions as $feeLevel => $feeDescription){ ?>
								<li><a href="?do[ChangeUserPrefs][CryptocurrencyFeeLevel]=<?php echo $feeLevel; ?>#review" class="dropdown-link"><?php echo $feeDescription; ?></a></li>
								<?php } ?>
							</ul>
						</div>
						<?php } ?>
						<br>
						<span class="small">(Based on current fee estimates)</span>
					</td>
					<td class="color-red">&ndash; <?php echo $this->priceBreakdown['network']; ?></td>
				</tr>
				<?php }
				} ?>
				<tr>
					<td></td>
					<td>Total</td>
					<?php if($this->isFree){ ?>
					<td><strong class="color-yellow"><big>FREE</big><strong></strong></strong></td>
					<?php } else { ?>
					<td><?= $this->priceBreakdown['final'] ?><br><span><?= $this->priceBreakdown['currency']; ?></span></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
		<?php
		if(
			$showPromoBox &&
			$hasDiscount == false
		){ ?>
		<div class="cols-5 promo-box">
			<div class="col-4">
				<label class="row checkbox label">
					<input class="expand"<?php echo $promoFeedback ? ' checked' : false; ?> type="checkbox"><i></i>I have a promo code
					<div class="text expandable<?php echo $promoFeedback ? ' invalid' : false; ?>">
						<input form="promo_form" required placeholder="Promo Code" name="promo_code" type="text">
						<button type="submit" class="btn" form="promo_form">Apply</button>
						<?php if($promoFeedback){ ?>
						<p class="note"><?php echo $promoFeedback; ?></p>
						<?php } ?>
					</div>
				</label>
			</div>
		</div>
		<?php } ?>
	</fieldset>
	<?php
	if(
		(
			$this->transaction['order']['Address'] ||
			$this->transaction['order']['Comments']
		) &&
		(
			(
				$this->transaction['timedOut'] == FALSE &&
				(
					$this->transaction['status'] == 'pending deposit' ||
					$this->transaction['status'] == 'pending accept' ||
					$this->transaction['status'] == 'in transit' ||
					$this->transaction['status'] == 'expired' ||
					(
						$this->transaction['status'] == 'pending feedback' &&
						$this->UserVendor
					)
				)
			) ||
			$this->transaction['status'] == 'in dispute'
		)
	){ ?>
	<fieldset>
		<?php if( $this->transaction['order']['Address'] && $this->transaction['order']['Comments'] ) { ?>
		<div class="cols-15">
			<div class="col-6">
			 <label class="label">Comments</label>
			 <label class="pre">
				 <pre><?php echo $this->transaction['order']['Comments']; ?></pre>
			 </label>
			</div>
			<div class="col-6">
				<label class="label">Shipping Address &amp; Notes</label>
				<label class="pre">
					<pre><?php echo $this->transaction['order']['Address']; ?></pre>
				</label>
			</div>
		</div>
		<?php } elseif ( $this->transaction['order']['Address'] ) { ?>
		<label class="label">Shipping Address &amp; Notes</label>
		<label class="pre">
			<pre contentEditable class="background"><?php echo $this->transaction['order']['Address']; ?></pre>
		</label>
		<?php } else { ?>
		<label class="label">Comments</label>
		 <label class="pre">
			 <pre><?php echo $this->transaction['order']['Comments']; ?></pre>
		 </label>
		<?php } ?>
	</fieldset>
	<?php } ?>
	<?php if ( $this->confirmed && !$this->isFree ) {
	ob_start(); ?>
	<fieldset>
		<label class="label">For your records
			<a class="tooltip inline top">What is this?</a>
			<div>
				<p>These are data that may be useful to you as a <?php echo $this->isVendor ? 'vendor' : 'buyer'; ?>.</p>
				<p>You are advised to write this down and keep it safe until the order has been finalized.</p>
			</div>
		</label>
		<label class="textarea">
			<textarea class="break-all" readonly rows="8">### TRANSACTION <?php echo $this->TXID ?> ###

Listing:
&nbsp;&nbsp;&nbsp;&nbsp;"<?php echo $this->listing['name']; ?>"

Quantity:
&nbsp;&nbsp;&nbsp;&nbsp;<?php

echo	$this->transaction['order']['Quantity']
	.	(
			$this->transaction['text_quantity']
				? '
&nbsp;&nbsp;&#126; ' . $this->transaction['text_quantity']
				: FALSE
		)
	.	(
			isset($this->transaction['next_tx']['reship_percentage'])
				? '
&nbsp;&nbsp;&nbsp;&nbsp;' . $this->transaction['next_tx']['reship_percentage'] . '% reship'
				: FALSE
		); ?>


Cryptocurrency: 
&nbsp;&nbsp;&nbsp;&nbsp;<?= $this->cryptocurrency->name; ?> (<?= $this->cryptocurrency->ISO; ?>)

Total:
&nbsp;&nbsp;&nbsp;&nbsp;<?php
	
	echo
		$this->priceBreakdown['final'] . '
&nbsp;&nbsp;' . $this->transaction['price_currency']; ?>


Transaction type:
&nbsp;&nbsp;&nbsp;&nbsp;"<?php
switch($this->transaction['escrow_enabled']){
	case TRUE:
		echo 'Escrow"';
	break;
	default:
		echo 'Direct-Pay"';
} if( $this->transaction['escrow_enabled'] ){ ?>
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo ($this->transaction['signee_count'] - 1) . '&#124;' . $this->transaction['signee_count'] ?>
<?= $this->transaction['isSegwit'] ? '&nbsp;&nbsp;&nbsp;&nbsp;Segwit' : false; ?>
<?php } if( $this->isVendor ) { ?>


Shipping Address and Notes:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo preg_replace('/\n/', '\n&nbsp;&nbsp;&nbsp;&nbsp;', $this->transaction['order']['Address']); ?>
<?php if($this->transaction['order']['Comments']){ ?>


Comments:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo preg_replace('/\n/', '\n&nbsp;&nbsp;&nbsp;&nbsp;', $this->transaction['order']['Comments']); ?>
<?php } } else { ?>


Vendor:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $this->listing['vendorAlias']; ?>
<?php } ?>


Deposit Address:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $this->transaction['multisig_address']; ?>
<?php if( $this->transaction['escrow_enabled'] ){ ?>

Redeem Script:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $this->transaction['redeem_script']; ?><?php if($this->isVendor && $this->accepted && !$this->finalized) { ?>


Escrow Recovery Transaction:
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $this->transaction['next_tx']['AutoFinalize']['hex']; } } ?></textarea>
		</label>
	</fieldset><?php $your_records = ob_get_contents(); ob_end_clean(); ?>
	<fieldset>
		<ul class="list-expandable">
			<li>
				<input id="technical-details" class="expand" type="checkbox" />
				<label for="technical-details">View Technical Details<i></i></label>
				<?php ob_start(); ?>
				<div class="expandable">
					<fieldset class="rows-10">
						<?php if ($this->UserVendor){ ?>
						<div class="row">
							<label class="label">Deposit Address</label>
							<label class="text">
								<input type="text" readonly value="<?php echo $this->transaction['multisig_address'] ?>" />
							</label>
						</div>
						<?php } ?>
						<div class="row">
							<label class="label">Redeem Script</label>
							<label class="pre">
								<pre contentEditable><?php echo $this->transaction['redeem_script'] ?></pre>
							</label>
						</div>
						<?php 
						if( isset($this->transaction['next_tx']['PublicKey_Buyer']) ) {?>
						<div class="row cols-10">
							<label class="col-2 label">Buyer:</label>
							<div class="col-10">
								<label class="textarea">
									<textarea readonly rows="<?php echo substr($this->transaction['next_tx']['PublicKey_Buyer'], 0, 2) == '04' ? 2 : 1; ?>" style="resize:none"><?php echo $this->transaction['next_tx']['PublicKey_Buyer']; ?></textarea>
								</label>
							</div>
						</div>
						<?php } ?>
						<div class="row cols-10">
							<label class="col-2 label">Vendor:</label>
							<div class="col-10">
								<label class="textarea">
									<textarea readonly rows="<?php echo substr($this->transaction['next_tx']['PublicKey_Vendor'], 0, 2) == '04' ? 2 : 1; ?>" style="resize:none"><?php echo $this->transaction['next_tx']['PublicKey_Vendor']; ?></textarea>
								</label>
							</div>
						</div>
						<div class="row cols-10">
							<label class="col-2 label">Marketplace:</label>
							<div class="col-10">
								<label class="textarea">
									<textarea readonly rows="<?php echo substr($this->transaction['next_tx']['PublicKey_Marketplace'], 0, 2) == '04' ? 2 : 1; ?>" style="resize:none"><?php echo $this->transaction['next_tx']['PublicKey_Marketplace']; ?></textarea>
								</label>
							</div>
						</div>
						<?php if ($this->transaction['isSegwit']){ ?>
						<div class="row cols-10">
							<label class="col-2 label">Segwit:</label>
							<label class="col-10"><label class="label"><strong>Enabled</strong></label></label>
						</div>
						<?php } ?>
					</fieldset>
					<?php
					echo $this->UserVendor ? FALSE : $your_records; ?>
				</div>
				<?php $technical_details = ob_get_contents(); ob_end_clean(); echo $technical_details; ?>
			</li>
		</ul>
	</fieldset>
	<?php } else {
		$your_records = $technical_details = false;
	}
	
	if (!$this->accepted && !$this->rejected){
		if (!$this->isVendor) {
			if (!$this->confirmed){ ?>
	<fieldset>
		<ul class="row list-expandable">
			<li>
				<input type="checkbox" class="expand" id="returns-refund" />
				<label for="returns-refund">Refund &amp; Escrow Policy<i></i></label>
				<div class="expandable formatted"><?php echo $this->vendor['policy']; ?></div>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<div class="cols-15">
			<div class="col-9">
				<div class="checkbox">
					<input type="radio" checked />
					<i></i>
					<span class="small">I have read and agree to comply with the vendor's <label class="inline a-like" for="returns-refund">refund &amp; escrow policy</label>.</span>
				</div>
			</div>
			<div class="col-3 align-right">
				<?php if ($this->isFree){ ?>
				<input hidden name="skip_return_address_validation" value="1">
				<input hidden name="escrow_option" value="off">
				<button type="submit" class="btn arrow-right">Confirm Order</button>
				<?php } else { ?>
				<label class="btn arrow-right" for="confirm_order-1">Continue</label>
				<input type="radio" name="confirm_order-step" value="0" id="confirm_order-0" hidden>
				<input type="radio" name="confirm_order-step" value="1" id="confirm_order-1" hidden<?php echo !empty($_SESSION['confirm_response']['failedStep']) && $_SESSION['confirm_response']['failedStep'] == 1 ? ' checked' : FALSE; ?>>
				<div class="modal steps">
					<div class="rows-15">
						<label class="close" for="confirm_order-0">&times;</label>
						<h5 class="row band bigger"><span>Select Transaction Type</span></h5>
						<div class="row switch">
							<?php if($this->transaction['AllowFE']) { ?>
							<input id="no_escrow" name="escrow_option" value="off" type="radio"<?php
								echo
									(
										!empty($_SESSION['confirm_post']['escrow_option']) &&
										$_SESSION['confirm_post']['escrow_option'] == 'off'
									) ||
									(
										empty($_SESSION['confirm_post']['escrow_option']) &&
										empty($this->publicKey)
									)
										? ' checked'
										: FALSE; 
							?>>
							<label for="no_escrow">Direct-Pay</label>
							<?php } ?>
							<input id="escrow" name="escrow_option" value="on" type="radio"<?php
								echo
									!$this->transaction['AllowFE'] ||
									(
										!empty($_SESSION['confirm_post']['escrow_option']) &&
										$_SESSION['confirm_post']['escrow_option'] == 'on'
									) ||
									(
										empty($_SESSION['confirm_post']['escrow_option']) &&
										$this->publicKey
									)
										? ' checked'
										: FALSE; 
							?>>
							<label for="escrow">Multi-sig Escrow</label>
							<?php if($this->transaction['AllowFE']) { ?>
							<div class="rows-15">
								<div class="formatted row">
									<p>The vendor will be able to withdraw the payment immediately.</p>
									<p>You should only choose this option if you trust the vendor fully.</p>
									<p><?php echo $this->SiteName_Short ?> cannot be held accountable for undelivered shipments, unsatisfactory product quality or any other incidents.</p>
								</div>
								<hr>
								<div class="row panel">
									<div class="left">
										<label for="confirm_order-0" class="btn red arrow-left">Go Back</label>
									</div>
									<div class="right">
										<label for="confirm_order-2" class="btn arrow-right">Continue</label>
									</div>
								</div>
							</div>
							<?php } ?>
							<div class="rows-10">
								<div class="row formatted">
									<p>The vendor will not be able to withdraw transaction funds until you have received the product and finalized the transaction.</p>
									<p>In the event of an undelivered shipment, a dispute may be started after <strong><?php echo $this->inTransitTimeoutDays; ?> days</strong>.</p>
								</div>
								<label class="row label">Master public key<a class="tooltip inline top" href="/p/<?= PAGE_MULTISIG_SETUP ?>/" target="_blank">Need help setting up multisig?</a><div><p>Click here to view our guide on setting up your <em><?= $this->cryptocurrency->name; ?> public key</em> for multisig escrow.</p></div></label>
								<div class="text rows-15<?= isset($_SESSION['confirm_response']['signing_public_key']) ? ' invalid' : false ?>">
									<input class="row" required title="A public key typically starts with 'xpub', 'ypub' or 'zpub'" type="text" name="signing_public_key" value="<?= !empty($_SESSION['confirm_post']['signing_public_key']) ? $_SESSION['confirm_post']['signing_public_key'] : $this->publicKey; ?>" pattern="<?= REGEX_CRYPTOCURRENCY_EXTENDED_PUBLIC; ?>" placeholder="xpub, ypub or zpub"<?= isset($_SESSION['confirm_response']['signing_public_key']) ? ' autofocus' : FALSE; ?>>
									<?php if( isset($_SESSION['confirm_response']['signing_public_key']) ) { ?>
									<p class="note"><?php echo $_SESSION['confirm_response']['signing_public_key']; ?></p>
									<?php }/* else { ?>
									<p class="note display-invalid color-red"><strong>This does not appear to be a valid public key.</strong></p>
									<?php }*/ ?>
									<hr>
									<div class="row panel display-invalid">
										<div class="left">
											<label for="confirm_order-0" class="btn red arrow-left">Go Back</label>
										</div>
										<div class="right">
											<button type="submit" class="btn arrow-right">Continue</button>
										</div>
									</div>
									<div class="row panel display-valid">
										<div class="left">
											<label for="confirm_order-0" class="btn red arrow-left">Go Back</label>
										</div>
										<div class="right">
											<label for="confirm_order-2" class="btn arrow-right">Continue</label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<input type="radio" name="confirm_order-step" value="2" id="confirm_order-2" hidden<?php echo !empty($_SESSION['confirm_response']['failedStep']) && $_SESSION['confirm_response']['failedStep'] == 2 ? ' checked' : FALSE; ?>>
				<div class="modal steps">
					<div class="rows-15">
						<h5 class="row band bigger"><span>Provide A Refund Address</span></h5>
						<div class="row formatted">
							<p>Before you make your payment, we need an address from <a class="tooltip" target="_blank" href="<?php echo URL . 'p/' . PAGE_BITCOIN_WALLETS . '/'; ?>">your personal <?= $this->cryptocurrency->name; ?> wallet</a>.</p>
							<p>If the vendor cannot accept your order, your payment will be refunded to this address.</p>
						</div>
						<label class="row label"><?= $this->cryptocurrency->name; ?> refund address</label>
						<div class="rows-15 text<?php echo isset($_SESSION['confirm_response']['return_address']) ? ' invalid' : false ?>">
							<input class="row prepend" name="return_address" required value="<?php echo !empty($_SESSION['confirm_post']['return_address']) ? $_SESSION['confirm_post']['return_address'] : $this->returnAddress; ?>" type="text" pattern="<?php echo REGEX_CRYPTOCURRENCY_ADDRESS; ?>"<?php echo isset($_SESSION['confirm_response']['return_address']) ? ' autofocus' : FALSE; ?>>
							<i class="<?= Icon::getClass($this->cryptocurrency->name); ?>"></i>
							<?php if( isset($_SESSION['confirm_response']['return_address']) ) { ?>
							<p class="note"><?php echo $_SESSION['confirm_response']['return_address']; ?></p>
							<?php } ?>
							<hr>
							<div class="row panel display-invalid">
								<div class="left">
									<label for="confirm_order-1" class="btn red arrow-left">Go Back</label>
								</div>
								<div class="right">
									<button type="submit" class="btn arrow-right">Continue</button>
								</div>
							</div>
							<div class="row panel display-valid">
								<div class="left">
									<label for="confirm_order-1" class="btn red arrow-left">Go Back</label>
								</div>
								<div class="right">
									<label for="confirm_order-3" class="btn arrow-right">Continue</label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<input type="radio" name="confirm_order-step" value="3" id="confirm_order-3" hidden>
				<div class="modal steps">
					<div class="rows-15">
						<h5 class="row band bigger"><span>Get Ready to Pay</span></h5>
						<div class="row formatted">
							<p>On the next page you will be presented with a <strong><?= $this->cryptocurrency->name; ?></strong> deposit address.</p>
							<p>Before you continue, make sure you are ready to send the full amount: <strong><?php echo $this->priceBreakdown['full']; ?></strong>.</p>
							<p>For fast payment confirmation, make sure you use <?php /*<a class="tooltip" target="_blank" href="<?= URL . 'p/' . PAGE_BITCOIN_MINING_FEES . '/'; ?>">*/?>adequate network fees<?php /*</a>*/ ?>.</p>
						</div>
						<hr>
						<div class="row panel">
							<div class="left">
								<label for="confirm_order-2" class="btn red arrow-left">Go Back</label>
							</div>
							<div class="right">
								<button formnovalidate type="submit" class="btn arrow-right">Continue to Payment</button>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	<?php	} } elseif(!$this->accepted && $this->transaction['status'] !== 'rejected' && $this->transaction['status'] !== 'refunded' ) { ?>
	<fieldset class="centered" id="vendorAction">
		<button type="submit" name="action" class="btn big blue" value="accept_order">Accept Order</button>
		<a href="#reject-order" class="btn big red">Reject Order</a>
		<div class="modal" id="reject-order">
			<a href="#"></a>
			<div class="rows-10">
				<a class="close" href="#">&times;</a>
				<p class="row">Are you sure you wish to reject this order?</p>
				<div class="row cols-10">
					<div class="col-6"><button type="submit" name="action" class="btn wide red" value="reject_order">Reject Order</button></div>
					<div class="col-6"><a href="#" class="btn wide">Nevermind</a></div>
				</div>
			</div>
		</div>
	</fieldset>
	<?php	}
		}
		
		unset($_SESSION['confirm_post'], $_SESSION['confirm_response']);
		
		?>
</form>
