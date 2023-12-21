<input type="hidden" name="listing_id" value="<?= $this->listingID; ?>">
<fieldset>
	<div class="cols-5">
		<label class="col-5 label">Quantity</label>
		<?php 
		
		$quantitySelect = !empty($post['quantity']) ? $post['quantity'] : $this->listing['Quantity_Minimum'];
		
		if($this->listing['Quantity_Minimum'] == 1){ ?>
		<div class="col-3">
			<label class="select<?php echo isset($feedback['quantity_select']) ? ' invalid' : false ?>">
				<select name="quantity_select">
					<?php if ($quantitySelect > ORDER_QUANTITY_DROPDOWN_OPTIONS_QUANTITY){ ?>
					<option selected>&nbsp;</option>
					<?php }
					
					for(
						$i = $this->listing['Quantity_Minimum'];
						(
							$i < $this->listing['Quantity_Minimum'] + ORDER_QUANTITY_DROPDOWN_OPTIONS_QUANTITY &&
							$i <= $this->listing['stock']
						);
						$i++
					){
						echo '<option ' . ($quantitySelect == $i ? ' selected' : false) . ' value="' . $i . '">' . $i . ' ' . ( $this->listing['quantity'] == 1 ? ( $i == 1 ? $this->listing['unit_singular'] : $this->listing['unit_plural'] ) : '(' . ($this->listing['quantity'] * $i) . ' ' . $this->listing['unit_plural'] . ')' ) . '</option>';
					}
					
					if( $feedback['quantity_select'] ) { ?>
					<p class="note"><?php echo $feedback['quantity_select'] ?></p>
					<?php } ?>
				</select>
			</label>
		</div>
		<label class="col-1 label centered">or</label>
		<?php } else { ?>
		<div class="col-4">&nbsp;</div>
		<?php } ?>
		<div class="col-3">
			<div class="text">
				<input type="hidden" name="quantity_per_unit" value="<?= $this->listing['quantity']; ?>">
				<input<?php echo $this->listing['Quantity_Minimum'] > 1 || $quantitySelect > ORDER_QUANTITY_DROPDOWN_OPTIONS_QUANTITY ? ' value="' . $quantitySelect * $this->listing['quantity'] . '"' : FALSE; ?> type="number" class="suffix" max="<?php echo $this->listing['stock']*$this->listing['quantity']; ?>" min="<?php echo $this->listing['quantity'] * $this->listing['Quantity_Minimum']; ?>" step="<?php echo $this->listing['quantity']; ?>" name="quantity_specify">
				<span><?php echo $this->listing['unit_plural']; ?></span>
				<?php if( $feedback['quantity_specify'] ) { ?>
				<p class="note"><?php echo $feedback['quantity_specify'] ?></p>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php /*
	<label class="row label">Comments</label>
	<label class="textarea">
		<textarea rows="4" name="comments"><?php echo isset($post['comments']) ? $post['comments'] : false ?></textarea>
	</label>
	*/ ?>
</fieldset>
<fieldset class="rows-20">
	<div class="row rows-10">
		<label class="row label">Shipping Address &amp; Notes</label>
		<label class="textarea">
			<textarea placeholder="You are strongly advised to encrypt all sensitive information using the vendor's PGP public key below" rows="5" required name="address"><?php echo $post['address'] ? $post['address'] : false ?></textarea>
		</label>
		<?php if ($this->vendorPGP){ ?>
		<ul class="row list-expandable">
			<li>
				<input type="checkbox" class="expand" id="vendor-pgp" />
				<label for="vendor-pgp">View Vendor's PGP Public Key<i></i></label>
				<div class="expandable">
					<label class="textarea pgp">
						<textarea readonly spellcheck="false" rows="<?php echo substr_count($this->vendorPGP, PHP_EOL) + 1; ?>"><?php echo $this->vendorPGP; ?></textarea>
					</label>
				</div>
			</li>
		</ul>
		<?php } ?>
	</div>
	<?php if ($this->shipping['shippingOptions']){ ?>
	<label class="row label">Shipping</label>
	<ul class="list-expandable radios lefthanded">
		<?php 	foreach ($this->shipping['shippingOptions'] as $key => $shippingOption) {
				$shippingMethodSelected = 
					(
						!$post &&
						$key == 0
					) ||
					(
						isset($post['shipping']) &&
						$post['shipping'] == $shippingOption['ID']
					);
		?>
		<li>
			<input<?= $shippingMethodSelected ? ' checked' : false; ?> id="<?php echo 'shipping_option-' . ($key + 1); ?>" name="shipping" value="<?php echo $shippingOption['ID']; ?>" class="expand" type="radio">
			<label for="<?php echo 'shipping_option-' . ($key + 1); ?>"><?php echo $shippingOption['name']; ?><i></i>
				<?php if ($shippingOption['price_crypto'] == ZERO_PRICE_TEXTUAL_REPLACEMENT) { ?>
				<strong class="color-blue"><?= $shippingOption['price_crypto']; ?></strong>
				<?php } else { ?>
				<strong><span class="small"><?php echo $shippingOption['price']; ?></span> <?php echo $shippingOption['price_crypto']; ?></strong>
				<?php } ?>
			</label>
		</li>
		<?php } ?>
	</ul>
	<?php } ?>
</fieldset>
<?php
if (
	$this->paymentMethods &&
	count($this->paymentMethods) > 1
){ ?>
<fieldset>
	<label class="row label">Payment Method</label>
	<div class="switch big">
		<?php foreach($this->paymentMethods as $paymentMethod){
			$inputID = 'payment_method-' . $paymentMethod['ISO'];
			$paymentMethodSelected =
				(
					!$post &&
					$paymentMethod['selected']
				) ||
				(
					isset($post['payment_method']) &&
					$post['payment_method'] == $paymentMethod['ISO']
				);
		?>
		<input<?= $paymentMethodSelected ? ' checked' : false; ?> id="<?= $inputID ?>" name="payment_method" value="<?= $paymentMethod['ISO'] ?>" type="radio">
		<label for="<?= $inputID ?>"><i class="<?= $paymentMethod['Icon'] ?>-m"></i><?= $paymentMethod['Name'] ?></label>
		<?php } ?>
	</div>
</fieldset>
<?php } ?>
<fieldset>
	<div class="align-right">
		<button type="submit" class="btn big arrow-right">Continue</button>
	</div>
</fieldset>
