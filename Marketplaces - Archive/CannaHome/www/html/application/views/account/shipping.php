<?php $this->renderNotifications(array('Shipping')); ?>
<div class="rows-30">
	<div class="row panel">
		<div class="left">
			<strong><?php echo $this->shippingOptions ? ucwords(NXS::formatNumber(count($this->shippingOptions))) . ' shipping option' . (count($this->shippingOptions) == 1 ? false : 's') : 'No shipping options'; ?></strong>
		</div>
		<div class="right">
			<?php if( $this->newShippingOption ) { ?>
			<button class="btn" form="shipping_form" name="save_and_insert"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>New Option</button>
			<?php } else { ?>
			<a class="btn" href="<?php echo URL . 'account/shipping/new/' ?>"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>New Option</a>
			<?php } ?>
		</div>
	</div>
	<form class="row rows-15" id="shipping_form" method="post" action="<?php echo URL . 'account/update_shipping_options/' ?>">
		<?php if( $this->shippingOptions ) {?>
		<ul class="row list-expandable trashcans">
			<?php $i = 0; foreach($this->shippingOptions as $shipping_option) { $id = $shipping_option['ID'] ? $shipping_option['ID'] : 'new'; ?>
			<li>
				<input id="<?php echo 'shipping_option-' . $id ?>" name="enable_shipping[]" value="<?php echo $id ?>" class="expand" type="checkbox" checked>
				<div class="alt-label">
					<div class="cols-5">
						<div class="col-4">
							<label class="text inline">
								<input type="text" maxlength="50" name="<?php echo 'shipping_option-' . $id . '_name' ?>" value="<?php echo $shipping_option['Name'] ?>" placeholder="Title" />
								<b></b>
							</label>
						</div>
						<div class="col-1">&nbsp;</div>
						<label class="col-1 label">Price</label>
						<div class="col-2">
							<div class="text select">
								<select class="monospace" name="<?php echo 'shipping_option-' . $id . '_currency'; ?>">
									<?php foreach($this->Currencies as $currency){
										echo '<option value="' . $currency['ID'] . '"' . ($currency['ID'] == $shipping_option['CurrencyID'] ? ' selected' : false) . '>' . $currency['ISO'] . '</option>';
									} ?>
								</select>
								<input class="monospace" name="<?php echo 'shipping_option-' . $id . '_price'; ?>" value="<?php echo $shipping_option['Price']; ?>" type="text">
							</div>
						</div>
						<div class="col-1">&nbsp;</div>
						<label class="col-1 label">Transit</label>
						<div class="col-2">
							<label class="select">
								<select name="<?php echo 'shipping_option-' . $id . '_transit_days'; ?>">
									<?php
										$transit_options = array(5, 7, 10, 14, 21);
										foreach($transit_options as $transit_option){
									?>
									<option <?php echo ($transit_option == $shipping_option['TransitDays'] ? 'selected ' : false) . 'value="' . $transit_option . '"' ?>><?php echo $transit_option ?> days</option>
									<?php } ?>
								</select>
							</label>
						</div>
					</div>
				</div>
				<label for="<?php echo 'shipping_option-' . $id; ?>">
					<i></i>
				</label>
				<div class="expandable">
					<label class="text">
						<input type="text" name="<?php echo 'shipping_option-' . $id . '_description' ?>" maxlength="100" placeholder="Description" value="<?php echo $shipping_option['Description'] ?>">
					</label>
				</div>
			</li>
			<?php } ?>
		</ul>
		<input type="submit" class="row btn big blue" value="Save Changes" />
		<?php } ?>
	</form>
</div>
