<?php 
if ( null !== Session::get('listing_feedback') ) $feedback = Session::get('listing_feedback');
if ( null !== Session::get('listing_post') ) $post = Session::get('listing_post');
Session::set('listing_feedback', null);
Session::set('listing_post', null);

// Category Tree

$tree = '<select name="category">' . PHP_EOL;

function toUL ($arr, $pass = 0, $post = false, $allowed_categories = false) {
	$html = '' . PHP_EOL;
	
	foreach ($arr as $v) {
		$html .= '<option '.(!$allowed_categories || in_array($v['ID'], $allowed_categories) ? 'value="'.$v['ID'].'"' : false).' '.( $post == $v['ID'] ? 'selected' : false ).' '.( $allowed_categories && !in_array($v['ID'], $allowed_categories) ? 'disabled' : false ).'>';
		$html .= str_repeat(str_repeat('&nbsp;', 3), $pass); // use the $pass value to create the --
		$html .= $v['Name'] . '</option>' . PHP_EOL;

		if (array_key_exists('Children', $v))
			$html.= toUL($v['Children'], $pass+1, $post, $allowed_categories);
	}

	$html.= '' . PHP_EOL;

	return $html;
}

//print_r($this->listing_attributes); die;

function listAttributes($attributes, $reputation, $name = false, $post){
	// Attributes
	$attribute_options = '';
	foreach($attributes as $type => $listing_attributes) { 
		if( $type == 'shipping' ) continue;
		$attribute_options .= '<optgroup label="'.ucfirst($type).'">';
		foreach( $listing_attributes as $listing_attribute ) {
			$attribute_options .= "<option value=".$listing_attribute['id']." ".( @constant('REPUTATION_ATTRIBUTE_'.$listing_attribute['id']) !== NULL && $reputation < constant('REPUTATION_ATTRIBUTE_'.$listing_attribute['id']) ? 'disabled' : false )." ".( $name && isset($post[$name]) && $post[$name]==$listing_attribute['id'] ? 'selected' : 'nope' )." >".$listing_attribute['attribute']."</option>";
		}
		$attribute_options .= "</optgroup>";
	}
	return $attribute_options;
}

$currencies = $this->Currencies;

usort($currencies, function ($a, $b) {
   return strcmp(
		strtolower($a['ISO']),
		strtolower($b['ISO'])
	);
});

function listCurrencies(
	$currencies,
	$name = false,
	$post = false,
	$my_currency = false
){
	$html = '';
	
	foreach($currencies as $currency){
		$is_selected =
			(
				$name && 
				isset($post[$name]) && 
				$post[$name] == $currency['ID']
			) ||
			(
				!$post && $my_currency['ID'] == $currency['ID']
			);
		$html .= '<option value="'.$currency['ID'].'" '.( $is_selected ? 'selected' : false ).'>'.$currency['ISO'].'</option>';
	}
	
	return $html;
}

$noPost = !isset($post);

if (isset($this->listing['content']))
	foreach($this->listing['content'] as $property => $value){
		if( $noPost || $property == 'images' )
		  $post[$property] = $value;
	}

if( !isset($this->allowedCategories) )
	$this->allowedCategories = false;

if( isset($post['category']) ){
	$tree .= toUL($this->listingCategories, 0, $post['category'], $this->allowedCategories);
} else {
	$tree .= toUL($this->listingCategories);
}
$tree .= '</select>';

//print_r($post); die;

//$currencies = listCurrencies($currencies);

$isImport =
	isset($this->isImport) &&
	$this->isImport;

$synchronizingStock = $this->groupingOptions['group'] && $this->groupingOptions['group'][LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN];

$this->renderNotifications(array('Specific')); ?>
<div class="rows-20">
	<?php if ($isImport){?>
	<div class="row grey-box">
		<strong>Note:</strong> when you copy a listing, the new listing is automatically grouped together with the listing that is being copied. If you wish to create a listing for a distinct product, uncheck <em>Group similar listings</em> below.
	</div>
	<?php } ?>
	<form class="row rows-30" action="<?php echo isset($this->targetListing) ? ( $this->targetListing ? URL.'account/edit_listing/' . $this->targetListing['id'] . '/' : URL.'account/new_listing/' ) : (isset($this->listing) && $this->listing ? URL.'account/edit_listing/' . $this->listing['id'] . '/' : URL.'account/new_listing/') ?>" method="post" enctype="multipart/form-data">
		<div class="row">
			<fieldset>
				<div class="cols-15">
					<div class="col-4">
						<label class="label">Title</label>
						<label class="text<?php echo isset($feedback['name']) ? ' invalid' : false ?>">
							<input<?= isset($post['canChangeTitle']) && !$post['canChangeTitle'] ? ' readonly' : false; ?> type="text"<?php echo !isset($this->listing) ? ' autofocus' : false; ?> name="name" pattern=".{5,100}" required <?php echo isset($post['name']) ? 'value="'.$post['name'].'"' : false; ?>>
							<?php if ( isset($feedback['name']) ) { ?>
							<p class="note"><?php echo $feedback['name'] ?></p>
							<?php } ?>
						</label>
					</div>
					<div class="col-2">
						<label class="label">Category</label>
						<label class="select<?php echo isset($feedback['category']) ? ' invalid' : false ?>">
							<?php echo $tree ?>
						</label>
					</div>
					<div class="col-1"></div>
					<div class="col-3">
						<label class="label">Quantity <span>per</span> Unit</label>
						<div class="text select<?php echo isset($feedback['quantity']) ? ' invalid' : false ?>">
							<input class="monospace" type="text" pattern="\d{1,4}(?:\.\d{1,2})?" name="quantity" required value="<?php echo isset($post['quantity']) ? $post['quantity'] : 1; ?>">
							<select style="width:120px" name="unit">
								<?php foreach ($this->units as $unit) {
									$disabledUnit = $synchronizingStock && $unit['DimensionID'] !== $post['unitDimensionID'];
								?>
								<option<?= ($disabledUnit ? ' disabled' : false) . ' value="' . $unit['id'] . '"' . ($unit['id'] == $post['unit'] ? ' selected' : false) ?>><?php echo $unit['name'] . ' (' . $unit['abbreviation'] . ')'; ?></option>
								<?php } ?>
							</select>
							<?php if ( isset($feedback['quantity']) ) { ?>
							<p class="note"><?php echo $feedback['quantity'] ?></p>
							<?php } ?>
						</div>
					</div>
					<div class="col-2">
						<label class="label">Price <span>per</span> Unit</label>
						<label class="text select<?php echo isset($feedback['price']) ? ' invalid' : false ?>">
							<input class="monospace" type="text" name="price" required <?php
								echo
									(
										isset($post['price'])
											? 'value="'.$post['price'].'" '
											: FALSE
									) .
									(
										isset($feedback['price'])
											? 'autofocus'
											: FALSE
									); 
							?>/>
							<select class="monospace" name="currency" >
								<?php echo listCurrencies($currencies, 'currency', $post, $this->UserCurrency) ?>
							</select>
							<?php if ( isset($feedback['price']) ) { ?>
							<p class="note"><?php echo $feedback['price'] ?></p>
							<?php } ?>
						</label>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<div class="cols-15">
					<div class="col-6">
						<?php
						if ($isImport) { ?>
						<input type="hidden" name="excerpt_original" value="<?php echo $post['excerpt']; ?>">
						<input type="hidden" name="description_original" value="<?php echo $post['description']; ?>">
						<?php } ?>
						<label class="textarea pre-textarea">
							<textarea rows="3" placeholder="Product Summary (optional)" name="summary"><?php echo isset($post['summary']) ? $post['summary'] : false; ?></textarea>
						</label>
						<label class="textarea">
							<textarea rows="8" required placeholder="Product Description" name="description"><?php echo isset($post['description']) ? $post['description'] : false; ?></textarea>
						</label>
					</div>
					<div class="col-1"></div>
					<div class="col-5 rows-15">
						<label class="row checkbox label">
							<input type="checkbox" name="listing_active"<?php echo !isset($post) || !empty($post['listing_active']) ? ' checked' : false ?>><i></i>Accept Orders (Active)
						</label>
						<label class="row checkbox label">
							<input type="checkbox" name="listing_visible"<?php echo !isset($post) || !empty($post['listing_visible']) ? ' checked' : false ?>><i></i>Visible on Marketplace
						</label>
						<div class="row cols-5">
							<label class="col-5 label"><a class="tooltip left">Inventory</a><div><p>This is how many units of the product you have available for purchase.</p><p>This number is automatically decremented with each placed order.</p><p>When the number reaches zero, the listing is automatically disabled.</p></div></label>
							<div class="col-7">
								<?php if ($synchronizingStock){ ?>
								<input type="hidden" name="stock" value="<?php echo isset($post['stock']) ? $post['stock'] : 50; ?>">
								<input type="hidden" name="group_stock" value="1">
								<small>You must deselect <em>Same Stock</em> from group settings to manage individual listing stock.</small>
								<?php } else { ?>
								<label class="text<?php echo isset($feedback['stock']) ? ' invalid' : false ?>">
									<input type="text" pattern="\d+" required name="stock" value="<?php echo isset($post['stock']) ? $post['stock'] : 50; ?>">
									<?php if ( isset($feedback['stock']) ) { ?>
									<p class="note"><?php echo $feedback['stock'] ?></p>
									<?php } ?>
								</label>
								<?php } ?>
							</div>
						</div>
						<div class="row cols-5">
							<label class="col-5 label"><a class="tooltip left">Minimum Order</a><div><p>This is the lowest number of units that can be ordered at a time.</p></div></label>
							<div class="col-7">
								<label class="text<?php echo isset($feedback['quantity_minimum']) ? ' invalid' : false ?>">
									<input type="number" min="1" required name="quantity_minimum" value="<?php echo isset($post['quantity_minimum']) ? $post['quantity_minimum'] : 1; ?>">
									<?php if ( isset($feedback['quantity_minimum']) ) { ?>
									<p class="note"><?php echo $feedback['quantity_minimum'] ?></p>
									<?php } ?>
								</label>
							</div>
						</div>
						<?php if ($this->listingPaymentMethods) { ?>
						<div class="row cols-5">
							<label class="col-5 label">Cryptocurrencies</label>
							<div class="col-7">
								<ul class="list-expandable checkboxes lefthanded narrow">
									<?php 	foreach ($this->listingPaymentMethods as $paymentMethod){
											$inputID = 'payment_method-' . $paymentMethod['ID'];
											$paymentMethodChecked =
												(
													$noPost &&
													$paymentMethod['Enabled']
												) ||
												(
													!$noPost &&
													in_array(
														$paymentMethod['ID'],
														$post['payment_methods']
													)
												);
									?>
									<li>
										<input<?= $paymentMethodChecked ? ' checked' : false; ?> id="<?= $inputID; ?>" class="expand" name="payment_methods[]" value="<?= $paymentMethod['ID']; ?>" type="checkbox">
										<label for="<?= $inputID; ?>"><?= $paymentMethod['Name']; ?><i></i></label>
									</li>
									<?php } ?>
								</ul>
							</div>
						</div>
						<?php } ?>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<span class="anchor" id="picture-options"></span>
				<div class="cols-15">
					<div class="col-9">
						<div class="picture-uploader">
							<?php 
						
							if(!empty($post['images'])) foreach($post['images'] as $image) { ?>
							<input type="hidden" name="listing-image-ids[]" value="<?php echo $image['ID']; ?>">
							<div class="pic" style="background-image:url(<?php echo $image['Image'] ?>)">
							<?php if( !$image['Primary'] ){ ?>
								<label for="<?php echo 'deletePicture-' . $image['ID']; ?>">Delete</label>
								<input type="checkbox" id="<?= 'deletePicture-' . $image['ID']; ?>" hidden>
								<div class="modal">
									<label for="<?php echo 'deletePicture-' . $image['ID']; ?>"></label>
									<div class="rows-10">
										<label class="close" for="<?php echo 'deletePicture-' . $image['ID']; ?>">&times;</label>
										<p class="row">Are you sure you wish to delete this picture?</p>
										<div class="row cols-10">
											<div class="col-6"><button type="submit" class="btn wide" name="delete_pic" value="<?php echo $image['ID'] ?>">Delete</button></div>
											<div class="col-6"><label for="<?= 'deletePicture-' . $image['ID']; ?>" class="btn wide red">Nevermind</label></div>
										</div>
									</div>
								</div>
								<button type="submit" name="make_pic_primary" value="<?php echo $image['ID'] ?>">Primary</button>
							<?php } ?>
							</div>
							<?php }
							$imageCount = isset($post['images']) && $post['images'] ? count($post['images']) : 0;
							if( $imageCount < 4 ) { ?>
							<label class="input-file">
								<span><?php echo 'Upload ' . ($imageCount > 0 ? 'another' : 'a') . ' picture'  ?></span>
								<input name="file" type="file">
							</label>
							<?php } ?>
						</div>
					</div>
					<div class="col-3 align-right rows-10">
						<label class="row"><p class="note align-right"><?php echo LISTING_IMAGE_WIDTH_DISPLAYED . '&times;' . LISTING_IMAGE_HEIGHT_DISPLAYED ?>, 1MB MAX</p></label>
						<button <?php echo $imageCount < LISTING_IMAGES_MAX ? 'type="submit" name="return" value="picture-options"' : 'disabled' ?> class="row btn"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>Add Picture</button>
					</div>
				</div>
			</fieldset>
		</div>
		<hr>
		<div class="row cols-15">
			<div class="col-6 rows-15">
				<div class="row">
					<label class="label">Ships From:</label>
					<label class="select">
						<select name="ships_from">
							<?php 
						
							if( !isset($post['ships_from']) && $this->defaultShipsFrom )
								$post['ships_from'] = $this->defaultShipsFrom;
						
							$i = 0;
						
							foreach ($this->continents as $continent_id => $continent_array) { 
								$i++;
							?>
							<option disabled><?php echo $continent_array['name'] ?></option>
							<?php if(isset($continent_array['countries'])) foreach ($continent_array['countries'] as $country) { ?>
							<option value="<?php echo $country['id']; ?>" <?php echo isset($post['ships_from']) && $post['ships_from']==$country['id'] ? 'selected' : false ?>>&nbsp;&nbsp;&nbsp;<?php echo $country['name']; ?></option>
							<?php }
							if ($i < count($this->continents)){ ?>
							<option disabled></option>
							<?php }
							} ?>
						</select>
					</label>
				</div>
				<?php if( $this->shippingOptions ) { 
					$previouslySelectedShippingOptions = [];
				?>
				<label class="row label">Shipping Options</label>
				<ul class="list-expandable checkboxes lefthanded">
					<?php foreach( $this->shippingOptions as $shipping_option ) { 
						if (
							$wasSelected =
								isset($post['shipping_options']) &&
								in_array($shipping_option['ID'], $post['shipping_options'])
						)
							$previouslySelectedShippingOptions[] = $shipping_option['ID'];
					?>
					<li>
						<input id="<?php echo 'shipping_option-' . $shipping_option['ID'] ?>" name="shipping_options[]" value="<?php echo $shipping_option['ID']; ?>" <?php echo !isset($post['shipping_options']) || $wasSelected ? ' checked' : false ?> class="expand" type="checkbox">
						<label for="<?php echo 'shipping_option-' . $shipping_option['ID'] ?>"><?php echo $shipping_option['Name'] ?><i></i><strong><?php echo $shipping_option['EURPrice'] ?></strong></label>
					</li>
					<?php } ?>
				</ul>
				<?php 
				if($isImport && $previouslySelectedShippingOptions){ 
					foreach ($previouslySelectedShippingOptions as $shippingOption){ ?>
				<input type="hidden" name="original_shipping_options[]" value="<?php echo $shippingOption; ?>">
				<?php }
					}
				} else { ?>
				<div class="row">
					<label><p class="note"><strong>You have not added any shipping options. <a target="_blank" href="<?php echo URL . 'account/shipping/' ?>">Add shipping options</a>.</strong></p></label>
				</div>
				<?php } ?>
			</div>
			<div class="col-1"></div>
			<div class="col-5">
				<label class="label">Ships To:</label>
				<div class="rows-10">
					<?php foreach ($this->continents as $continent_id => $continent_array) { ?>
					<label class="row checkbox label">
						<input type="checkbox" class="expand" name="ships_to_continent[]" value="<?php echo $continent_id; ?>" <?php echo !isset($post['ships_to_continent']) || in_array($continent_id, $post['ships_to_continent']) ? 'checked' : false ?>><i></i><?php echo $continent_array['name']; ?>
						<?php if(isset($continent_array['countries'])) { ?>
						<div class="expandable xl">
							<?php foreach ($continent_array['countries'] as $country) { ?>
							<label class="checkbox">
								<input type="checkbox" name="ships_to_country[]" value="<?php echo $country['id']; ?>" <?php echo !isset($post['ships_to_continent']) || !in_array($continent_id, $post['ships_to_continent']) || in_array($country['id'], $post['ships_to_country']) ? 'checked' : false ?>><i></i><?php echo $country['name']; ?>
							</label>
							<?php } ?> 
						</div>
						<?php } ?>
					</label>
					<?php } ?>
				</div>
			</div>
		</div>
		<hr>
		<div class="row rows-5">
			<label class="label row">Other Options</label>
			<?php if($this->groupingOptions){ 
			if($this->groupingOptions['group']){ ?>
			<input type="hidden" name="group_id" value="<?php echo $this->groupingOptions['group']['ID']; ?>">
			<?php } ?>
			<span class="anchor" id="grouping-options"></span>
			<ul class="list-expandable checkboxes lefthanded">
				<li>
					<input id="toggle-enable_grouping" name="enable_grouping"<?php echo $this->groupingOptions['group'] || $isImport ? ' checked' : FALSE; ?> class="expand" type="checkbox">		
					<label for="toggle-enable_grouping">Group similar listings<i></i></label>
					<div class="expandable cols-30">                            
						<div class="col-8 rows-15">
							<label class="row label">Add/Remove Listings to Group</label>
							<ul class="list-expandable checkboxes lefthanded">
								<?php foreach($this->groupingOptions['listings'] as $listing){
									$toggleID = 'group_listing-' . $listing['ID'];
								?>
								<li>
									<input id="<?php echo $toggleID; ?>" name="group_listings[]" value="<?php echo $listing['ID'] ?>"<?php echo $listing['inGroup'] || $_POST['import_listing'] == $listing['ID'] ? ' checked' : FALSE ?> class="expand" type="checkbox">
									<div class="alt-label">
										<div class="cols-5">
											<div class="col-9">
												<small><?php echo $listing['Name']; ?></small>
											</div>
											<div class="col-3">
												<div class="text inline">
													<input maxlength="<?php echo LISTING_GROUP_LABEL_MAX_LENGTH; ?>" name="<?php echo $toggleID . '-label'; ?>"<?php echo $listing['Label'] ? ' value="' . $listing['Label'] . '"' : false; ?> type="text" placeholder="<?php echo $listing['quantityLabel']; ?>" class="small">
													<b></b>
												</div>
											</div>
										</div>
									</div>
									<label for="<?php echo $toggleID; ?>"><i></i></label>
								</li>
								<?php } ?>
							</ul>
						</div>
						<div class="col-4 rows-20">
							<div class="row cols-5">
								<label class="col-5 label">
									<a class="tooltip left">Label (optional)</a>
									<div>
										<p>A unique label to distinguish this listing from the other listings in the group.</p>
										<p>If left empty, the quantity will be used.</p>
									</div>
								</label>
								<div class="col-7">
									<label class="text inline">
										<input maxlength="<?php echo LISTING_GROUP_LABEL_MAX_LENGTH; ?>"<?php echo !$isImport && !empty($post['group_label']) ? ' value="' . $post['group_label'] . '"' : false; ?> name="group_label" type="text" class="small">
										<b></b>
									</label>
								</div>
							</div>
							<div class="row">
								<label class="label">Group Synchronization Settings</label>
								<div class="rows-5">
									<label class="row checkbox label">
										<input disabled checked type="checkbox">
										<i></i>Same Category
									</label>
									<label class="row checkbox label">
										<input disabled checked type="checkbox">
										<i></i>Same Ships From &amp; Ships To
									</label>
									<label class="row checkbox label">
										<input name="group_sync_stock"<?php echo $this->groupingOptions['group'] && $this->groupingOptions['group'][LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN] ? ' checked' : FALSE ?> type="checkbox">
										<i></i>
										<a class="tooltip left">Same Stock</a>
										<div><p>Stock is managed for the entire group from Listing overview page.</p><p>Stock settings for individual listings are disabled.</p></div>
									</label>
									<label class="row checkbox label">
										<input name="group_sync_images"<?php echo !$this->groupingOptions['group'] || $this->groupingOptions['group'][LISTING_GROUP_SETTING_SYNCHRONIZE_IMAGES_DB_COLUMN] ? ' checked' : FALSE ?> type="checkbox">
										<i></i>Same Images
									</label>
									<label class="row checkbox label">
										<input name="group_sync_descriptions"<?php echo !$this->groupingOptions['group'] || $this->groupingOptions['group'][LISTING_GROUP_SETTING_SYNCHRONIZE_DESCRIPTIONS_DB_COLUMN] ? ' checked' : FALSE ?> type="checkbox">
										<i></i>Same Descriptions
									</label> 
									<label class="row checkbox label">
										<input name="group_sync_shipping"<?php echo !$this->groupingOptions['group'] || $this->groupingOptions['group'][LISTING_GROUP_SETTING_SYNCHRONIZE_SHIPPING_DB_COLUMN] ? ' checked' : FALSE ?> type="checkbox">
										<i></i>Same Shipping Options
									</label>
									<?php /*
									<label class="row checkbox label">
										<input disabled type="checkbox">
										<i></i>Same Promotional Codes
									</label> */ ?>
								</div>
							</div>
						</div>
					</div>
				</li>
			</ul>
			<?php } ?>
			<span class="anchor" id="promo-options"></span>
			<ul class="row list-expandable checkboxes lefthanded">
				<li>
					<input<?php echo ($post['promo_code_ids'] || isset($post['enable_promos'])) ? ' checked' : false; ?> id="toggle-enable_promos" name="enable_promos" class="expand" type="checkbox">		
					<label for="toggle-enable_promos">Promotional Discount Codes<i></i></label>
					<div class="expandable rows-10">                            
						<ul class="row list-expandable trashcans">		
							<?php if($post['promo_code_ids']) {
								foreach($post['promo_code_ids'] as $promoCodeID){ ?>
							<li>
								<input id="<?php echo 'promo_code-' . $promoCodeID; ?>" name="promo_code_ids[]" value="<?php echo $promoCodeID; ?>" class="expand" checked type="checkbox">
								<div class="alt-label">
									<div class="cols-5">
										<div class="col-3">
											<label class="text inline">
												<input placeholder="<?php echo LISTING_PROMOTIONAL_CODE_PLACEHOLDER; ?>" maxlength="<?php echo LISTING_PROMOTIONAL_CODE_LENGTH_MAX; ?>" name="<?php echo 'promo_code-' . $promoCodeID . '-code'; ?>" value="<?php echo $post['promo_code-' . $promoCodeID . '-code'] ?>" type="text">
												<b></b>
											</label>
										</div>
						
										<label class="col-3 label align-right">Discount</label>
										<div class="col-2">
											<div class="text select">
												<select class="monospace" name="<?php echo 'promo_code-' . $promoCodeID . '-currency'; ?>">
													<option value="0"<?php echo $post['promo_code-' . $promoCodeID . '-currency'] == NULL ? ' selected' : FALSE; ?>>%</option>
													<?php echo listCurrencies(
														$currencies,
														'promo_code-' . $promoCodeID . '-currency',
														$post,
														$this->UserCurrency
													); ?>
												</select>
												<input class="monospace" name="<?php echo 'promo_code-' . $promoCodeID . '-discount'; ?>" value="<?php echo $post['promo_code-' . $promoCodeID . '-discount'] ?>" min="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_MIN; ?>" max="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_MAX; ?>" step="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_STEP; ?>" type="number">
											</div>
										</div>
										<label class="col-3 label align-right">Quantity Remaining</label>
										<div class="col-1">
											<label class="text">
												<input type="number" name="<?php echo 'promo_code-' . $promoCodeID . '-quantity'; ?>" value="<?php echo $post['promo_code-' . $promoCodeID . '-quantity'] ?>" min="<?php echo LISTING_PROMOTIONAL_CODE_QUANTITY_MIN; ?>" max="<?php echo LISTING_PROMOTIONAL_CODE_QUANTITY_MAX; ?>" step="1">
											</label>
										</div>
									</div>
								</div>
								<label for="<?php echo 'promo_code-' . $promoCodeID; ?>">
									<i></i>
								</label>
							</li>
							<?php }
							} ?>
							<li>
								<input id="promo_code-new" name="promo_code-new" class="expand" checked type="checkbox">
								<div class="alt-label">
									<div class="cols-5">
										<div class="col-3">
											<label class="text inline">
												<input placeholder="<?php echo LISTING_PROMOTIONAL_CODE_PLACEHOLDER; ?>" maxlength="<?php echo LISTING_PROMOTIONAL_CODE_LENGTH_MAX; ?>" name="promo_code-new-code" type="text">
												<b></b>
											</label>
										</div>
						
										<label class="col-3 label align-right">Discount</label>
										<div class="col-2">
											<div class="text select">
												<select class="monospace" name="promo_code-new-currency">
													<option value="0">%</option>
													<?php echo listCurrencies(
														$currencies,
														false,
														false,
														$this->UserCurrency
													) ?>
												</select>
												<input class="monospace" name="promo_code-new-discount" min="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_MIN; ?>" max="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_MAX; ?>" step="<?php echo LISTING_PROMOTIONAL_CODE_DISCOUNT_STEP; ?>" type="number">
											</div>
										</div>
										<label class="col-3 label align-right">Quantity Remaining</label>
										<div class="col-1">
											<label class="text">
												<input type="number" value="1" name="promo_code-new-quantity" min="<?php echo LISTING_PROMOTIONAL_CODE_QUANTITY_MIN; ?>" max="<?php echo LISTING_PROMOTIONAL_CODE_QUANTITY_MAX; ?>" step="1">
											</label>
										</div>
									</div>
								</div>
								<label for="promo_code-new">
									<i></i>
								</label>
							</li>
						</ul>
						<div class="row panel">
							<div class="left">
								<button class="btn" type="submit" name="return" value="promo-options"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>Add Promotional Code</button>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<input class="row btn big blue" name="submit" type="submit" value="<?php echo isset($this->listing) ? 'Save Listing' : 'Create Listing' ?>">
	</form>
</div>
