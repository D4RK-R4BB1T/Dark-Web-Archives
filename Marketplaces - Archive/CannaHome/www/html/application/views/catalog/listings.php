<?php
$isVendorListings = isset($this->vendorAlias) && $this->vendorAlias;

switch (TRUE){
	case $isVendorListings:
		$URLPrefix	= URL . 'v/' . $this->vendorAlias . '/listings/' . $this->categoryAlias . '/';
		$ULPrefix	= URL . 'v/' . $this->vendorAlias . '/listings/';
	break;
	case isset($this->isSearch) && $this->isSearch:
		$URLPrefix	= URL . 'search/listings/' . $this->categoryAlias . '/';
		$ULPrefix	= URL . 'search/listings/';
	break;
	default:
		$URLPrefix	= URL . 'listings/' . $this->categoryAlias . '/';
		$ULPrefix	= URL . 'listings/';
}

$categoryTree = $this->toUL(
	$this->listingCategories,
	0,
	$this->activeListingCategories,
	$this->categoryID,
	$ULPrefix
);

$maxPages = ceil($this->listingCount/LISTINGS_PER_PAGE);
$paginationPrefix = $URLPrefix . $this->sortMode . '/';

// Shipping Filters
if ($this->filterPreferences['ships_to'] > -1){
	$shipsTo = $this->filterPreferences['ships_to'] ?: SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $this->Locales[0]['ID'];
	list(
		$shippingType,
		$shippingID
	) = explode(
		SHIPPING_FILTER_DELIMITER,
		$shipsTo,
		2
	);
} else
	$shipsTo = FALSE;

?><div class="listing-cols">
	<div class="col-4 sidebar rows-30">
		<form class="row" method="post" action="<?php echo URL . 'search/listings/'; ?>">
			<label class="text search">
				<input placeholder="Search Listings" name="q" class="prepend" type="text"<?php echo isset($this->query) ? 'value="' . $this->query . '"' : false ?>>
				<i class="<?php echo Icon::getClass('SEARCH'); ?>"></i>
				<button type="submit" name="where" value="listings" class="btn xs"><i class="<?php echo Icon::getClass('CARET_RIGHT'); ?>"></i></button>
			</label>
		</form>
		<ul class="filters">
			<?php if (isset($this->vendorAlias) && $this->vendorAlias) { ?>
			<li>
				<a class="dismiss" href="<?php echo URL . 'listings/' . $this->categoryAlias . '/' . $this->sortMode . '/';  ?>">&times;</a><strong>Vendor:</strong> <a href="<?php echo URL . 'v/' . $this->vendorAlias . '/'; ?>"><?php echo $this->vendorAlias; ?></a>
			</li>
			<?php } /*
			if ( count($this->countries) == 1 ) { 
				$shipsTo = reset($this->countries);
				$ships_to = $shipsTo['ID']; ?>
			<li><strong>Ships to</strong> <?php echo $shipsTo['Name']; ?></li>
			<?php } elseif ( $ships_to ) { ?>
			<li>
				<button class="dismiss" type="submit" name="ships_to" value="0" form="reset_ships_to">&times;</button><strong>Ships to:</strong> <?php echo $this->countries[ $ships_to ]['Name'] ?>
				<form id="reset_ships_to" method="post" action="<?php echo $URLPrefix . $this->sortMode . '/'; ?>">
					<?php if($this->Member) { ?><input type="hidden" name="save_preferences" value="1"><?php } ?>
					<input type="hidden" name="verified_vendors" value="1">
					<input type="hidden" name="ships_to" value="0">
					<input type="hidden" name="ships_from" value="<?php echo $ships_from; ?>">
				</form>
			</li>
			<?php } if( $ships_to && $ships_from == $ships_to ) { ?>
			<li>
				<button class="dismiss" type="submit" name="ships_from" value="0"  form="reset_ships_from">&times;</button><strong>Domestic vendors only</strong>
				<form id="reset_ships_from" method="post" action="<?php echo $URLPrefix . $this->sortMode . '/'; ?>">
					<?php if($this->Member) { ?><input type="hidden" name="save_preferences" value="1"><?php } ?>
					<input type="hidden" name="verified_vendors" value="1">
					<input type="hidden" name="ships_to" value="<?php echo $ships_to; ?>">
					<input type="hidden" name="ships_from" value="0">
				</form>
			</li>
			<?php } */ ?>
		</ul>
		<?php
			$returnValue = substr(
				$URLPrefix . $this->sortMode . '/',
				strlen(URL)
			);
		?>
		<form id="no_filter" method="post" action="<?php echo URL . 'catalog/apply_listings_filter/'; ?>">
			<input type="hidden" name="payment_methods" value="0">
			<input type="hidden" name="shipping_destination" value="0">
			<input type="hidden" name="same_origin" value="<?= $this->Locales[0]['Exclusive']; ?>">
			<input type="hidden" name="return" value="<?php	echo 'listings/' . $this->categoryAlias . '/'; ?>">
		</form>
		<form class="row" id="filter" method="post" action="<?php echo URL . 'catalog/apply_listings_filter/'; ?>">
			<input type="hidden" name="return" value="<?php	echo $returnValue; ?>">
			<?php if (count($this->shippingDestinations) > 1){ ?>
			<fieldset>
				<h5 class="band">
					<span>Shipping</span>
				</h5>
				<label class="select prepend">
					<?php /*<select name="ships_to">
						<option value="0" disabled<?php echo $ships_to==0 ? ' selected' : false ?>>- Ships to -</option>
						<option disabled></option>
						<?php $topCountries = TRUE; foreach ($this->countries as $id => $country) {
						if($topCountries && !$country['Top']){ $topCountries = FALSE; ?>
						<option disabled></option>
						<?php } ?>
						<option value="<?php echo $country['ID']; ?>"<?php echo $ships_to == $id ? ' selected' : false ?>><?php echo $country['Name']; ?></option>
						<?php } ?>
					</select>
					*/ ?>
					<select name="shipping_destination">
						<option<?php echo $shipsTo === false ? ' selected' : false;?> value="-1">Anywhere</option>
						<option disabled></option>
						<?php
						
						$destinationCountry = false;
						foreach($this->shippingDestinations as $i => $shippingLocale){
							$localeIsSelected = 
								$shipsTo &&
								(
									$shippingType == SHIPPING_FILTER_PREFIX_LOCALE &&
									$shippingID == $shippingLocale['ID']
								);
							
							$shippingLocaleName = count($shippingLocale['countries']) == 1 ? $shippingLocale['countries'][0]['Name'] : $shippingLocale['Name'];
								
							if ($localeIsSelected)
								$destinationCountry = $shippingLocaleName;
						?>
						<option<?php echo $localeIsSelected ? ' selected' : false; ?> value="<?php echo SHIPPING_FILTER_PREFIX_LOCALE . SHIPPING_FILTER_DELIMITER . $shippingLocale['ID'] ?>"><?php echo $shippingLocaleName; ?></option>
						<?php if( count($shippingLocale['countries']) > 1 ){
						foreach($shippingLocale['countries'] as $country){ 
							$countryIsSelected =
								!$localeIsSelected &&
								(
									$shippingType == SHIPPING_FILTER_PREFIX_COUNTRY &&
									$shippingID == $country['ID']
								);
								
							if ($countryIsSelected)
								$destinationCountry = $country['Name'];
						?>
						<option<?php echo $countryIsSelected ? ' selected' : false; ?> value="<?php echo SHIPPING_FILTER_PREFIX_COUNTRY . SHIPPING_FILTER_DELIMITER . $country['ID'] ?>"><?php echo str_repeat('&nbsp;', 3) . $country['Name']; ?></option>
						<?php }
						}
						} ?>
					</select>
					<i class="<?php echo Icon::getClass('MAP_MARKER'); ?>"></i>
				</label>
				<?php /*if($isVendorListings && 1 == 1){*/ ?>
				<input<?php echo $this->filterPreferences['ships_from'] !== -1 ? ' checked' : false; ?> name="same_origin" type="hidden">
				<?php /*} else {
					$onlyDomesticVendors = $this->filterPreferences['ships_from'] !== -1;
				?>
				<label class="label checkbox">
					<input<?php echo $onlyDomesticVendors ? ' checked' : false; ?> name="same_origin" type="checkbox">
					<i></i>Only domestic vendors
					if (
						$onlyDomesticVendors &&
						$maxPages < 3
					){ ?>
					<div style="margin-top: -19px;transform: none;width: 100px;" class="hint left reminder"><span>Uncheck to view international</span></div>
					<?php } ?>
				</label>
				<?php }*/ ?>
			</fieldset>
			<?php }
			if (count($this->cryptocurrencies) > 1 && $this->paymentMethods){ ?>
			<fieldset>
				<h5 class="band">
					<span>Payment Methods</span>
				</h5>
				<ul class="list-expandable checkboxes lefthanded narrow">
					<?php	foreach ($this->paymentMethods as $paymentMethod){
							$inputID = 'payment_method-' . $paymentMethod['ISO'];
							$paymentMethodChecked =
								!$this->filterPreferences['cryptocurrencies'] ||
								in_array($paymentMethod['ID'], $this->filterPreferences['cryptocurrencies']);
					?>
					<li>
						<input type="hidden" name="payment_method_options[]" value="<?= $paymentMethod['ID']; ?>">
						<input<?= $paymentMethodChecked ? ' checked' : false; ?> id="<?= $inputID; ?>" name="payment_methods[]" value="<?= $paymentMethod['ID']; ?>" class="expand" type="checkbox">
						<label for="<?= $inputID; ?>"><?= $paymentMethod['Name']; ?><i></i></label>
					</li>
					<?php } ?>
				</ul>				
			</fieldset>
			<?php }
			if (count($this->shippingDestinations) > 1 || count($this->cryptocurrencies) > 1){ ?>
			<div>
				<button type="submit" class="btn">Apply</button><button form="no_filter" type="submit" class="btn red">Reset</button>
			</div>
			<?php } ?>
		</form>
		<ul class="row categories"><?php echo $categoryTree; ?></ul>
		<?php if ($this->activeListingCategories || $this->isSearch) { ?> 
		<a class="btn row wide purple arrow-left" href="<?php echo $this->isSearch ? URL . 'listings/' : $ULPrefix; ?>"><?php echo 'View All ' . ($isVendorListings ? 'Vendor\'s ' : FALSE ) . 'Listings'; ?></a>
		<?php } ?>
	</div>
	<div class="col-8 rows-20">
		<?php if($this->listings) { ?>
		<h2 class="row band">
			<span><?= $this->trueListingCount . ($this->categoryID ? ' <strong>' . strtolower($this->activeListingCategories[$this->categoryID][1]) . '</strong>' : false) . ' listing' . ($this->trueListingCount > 1 ? 's' : false)?></span>
			<div>
				<label class="label">Sort by</label><?php if($this->isSearch){ ?>
				<label class="label"><strong>Relevance</strong></label>
				<?php } else {
				$defaultSortModes = array(
					'rating'	=> 'Rating',
					'id_desc'	=> 'Newest',
					'popular'	=> 'Popular',
					'price_asc'	=> 'Price'
				);
				$allSortModes = array_merge(
					$defaultSortModes,
					array(
						'price_desc'	=> 'Price',
						'price_m_asc'	=> 'Price',
						'price_m_desc'	=> 'Price',
						'price_v_asc'	=> 'Price',
						'price_v_desc'	=> 'Price',
						'name_desc'	=> 'Name',
						'id_asc'	=> 'Newest'
					)
				);
				/* ?><div class="big-dropdown">
					<span><?php
					
					//echo array_search($allSortModes[$this->sortMode], $defaultSortModes); die;
					echo $allSortModes[$this->sortMode];
					unset($defaultSortModes[ array_search($allSortModes[$this->sortMode], $defaultSortModes) ]);
						
					?></span>
					<a class="toggle">More</a>
					<ul class="dropdown">
						<?php foreach( $defaultSortModes as $key => $sortMode ) { ?>
						<li><a href="<?php echo $URLPrefix . $key . '/' ?>" class="dropdown-link"><?php echo $sortMode ?></a></li>
						<?php } ?>
					</ul>
				</div>
				<?php */ ?><div class="options-toggle">
				<?php foreach ($defaultSortModes as $key => $sortMode) { ?><a<?= $allSortModes[$this->sortMode] == $sortMode ? ' class="active"' : false; ?> href="<?= $URLPrefix . $key . '/' ?>"><?= $sortMode ?></a><?php } ?>
				</div>
				<?php
				$currentSort = explode('_', $this->sortMode);
				/*if ($currentSort[0] == 'price'){
					$defaultPriceSortModes = [
						'price_m_asc'	=> 'per ' . BASE_DIMENSION_MASS,
						'price_v_asc'	=> 'per ' . BASE_DIMENSION_VOLUME,
						'price_asc'	=> 'Total'
					];
					$allPriceSortModes = array_merge(
						$defaultPriceSortModes,
						array(
							'price_desc'	=> 'Total',
							'price_m_desc'	=> 'per ' . BASE_DIMENSION_MASS,
							'price_v_desc'	=> 'per ' . BASE_DIMENSION_VOLUME,
						)
					);
				?>
				<div class="big-dropdown">
					<span><?php 
						echo $allPriceSortModes[$this->sortMode];
						unset($defaultPriceSortModes[ array_search($allPriceSortModes[$this->sortMode], $defaultPriceSortModes) ]);
					?></span>
					<a class="toggle">More</a>
					<ul class="dropdown">
						<?php foreach( $defaultPriceSortModes as $key => $sortMode ) { ?>
						<li><a href="<?php echo $URLPrefix . $key . '/' ?>" class="dropdown-link"><?php echo $sortMode ?></a></li>
						<?php } ?>
					</ul>
				</div>
				<?php }*/
				if (count($currentSort) > 1){
					$sortDirection = array_pop($currentSort);
					$sortURL = $URLPrefix . implode('_', $currentSort) . '_' . ($sortDirection == 'asc' ? 'desc' : 'asc') . '/';
					$upOrDown = $sortDirection == 'asc' ? 'up' : 'down';
					echo '<a class="btn minimal xs" href="' . $sortURL . '"><i class="candy-sort ' . $upOrDown . '"></i></a>';
				}
				}?>
			</div>
		</h2>
		<ul class="row listings tabular">
			<?php 
			foreach ($this->listings as $listing) {
				$hasOptions =
					$listing['GroupID'] &&
					$listing['options'] &&
					count($listing['options']) > 1;
				$hasOptionsContainer =
					$hasOptions &&
					(
						!$listing['isTrivialGroup'] ||
						count($listing['options']) > LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY_SINGLE_ROW
					)
			
			?>
			<li id="<?php echo 'listing-' . $listing['ID']; ?>">
				<?php if ($hasOptionsContainer){
					$optionsContainerID = 'options_container-' . $listing['B36']; ?>
				<input type="checkbox" hidden id="<?php echo $optionsContainerID; ?>">
				<?php } ?>
				<div class="listing">
					<div class="info">
						<a href="<?php echo URL . 'i/' . $listing['B36'] . '/' ?>" class="name"><?php echo $listing['Name']; ?></a>
						<?php
						if (
							!$isVendorListings &&
							$this->filterPreferences['ships_from'] == -1
						){ ?>
						<div class="shipping-info">
							<div class="from"><?php echo $listing['originCountry']; ?></div><?php if($destinationCountry){ ?><div class="to"><?php echo $destinationCountry; ?></div><?php } ?>
						</div>
						<?php } ?>
						<div class="rating stars">
						<?php if ($listing['ratingCount'] > 0){
							$ratingLabel = '(' . $listing['ratingCount'] . ($listing['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($listing['ratingCount'] == 1 ? FALSE : 's') . ')'; ?>
							<?php $this->renderRating($listing['averageRating']); ?><?php
							if ($listing['commentCount'] > 0) { ?><a href="<?php echo URL . 'i/' . $listing['B36'] . '/comments/' ?>"><?php echo $ratingLabel ?></a><?php 
							} else { ?><span><?php echo $ratingLabel ?></span>
							<?php } ?>
						<?php } else echo '<strong class="color-yellow">NEW!</strong>'; ?>
						</div><!--
					    ---><a class="vendor" href="<?php echo URL . 'v/' . $listing['vendorAlias'] . '/' ?>"><?php echo $listing['vendorAlias'] ?></a>
						<p><?php echo $listing['Excerpt'] ?></p>
						<?php
						if ($hasOptions){ ?>
						<div class="options">
						<?php
							if (!$hasOptionsContainer){
							foreach($listing['options'] as $option){ ?> 
							<a<?php echo $option['isActiveListing'] ? ' class="active"' : false ?> href="<?php echo URL . 'i/' . $option['B36'] . '/'; ?>">
								<?php echo $option['quantity']; 
								if (!$option['isActiveListing']){ ?>
								<div class="hint above">
									<span><?php
										echo $option['price'];
									?></span>
								</div>
								<?php } ?>
							</a>
							<?php }
							} else { ?>
							<label for="<?php echo $optionsContainerID; ?>" class="btn arrow-right">More Options Available</label>
							<?php } ?>
						</div>
						<?php } ?>
					</div>
					<a href="<?php echo URL . 'i/' . $listing['B36'] . '/'; ?>" class="image">
						<div<?php echo $listing['Image'] ? ' style="background-image:url(\'' . $listing['Image']. '\')"' : FALSE ?>></div>
						<?php if($listing['Featured']){ ?>
						<span class="banner">Featured Listing</span>
						<?php } ?>
					</a>
					<div class="price">
						<div>
							<span class="big"><?php echo $listing['price'] ?></span> <span class="small"><?php echo $listing['price_crypto'] ?></span>
						</div>
					</div>
					<div class="buttons">
					<?php 
					$this->renderMemberButton(
						URL . 'i/' . $listing['B36'] . '/',
						'<i class="' . Icon::getClass('BROWSER-M') . '"></i><div class="hint left"><span>View Details</span></div>',
						'btn xs blue'
					); 
					if ($this->UserAlias == $listing['vendorAlias']) {  ?>
						<a href="<?php echo URL . 'account/listings/edit/' . $listing['ID'] . '/' ?>" class="btn xs">
							<i class="<?php echo Icon::getClass('EDIT', true); ?>"></i>
							<div class="hint left"><span>Edit</span></div>
						</a>
					<?php } elseif (!$this->UserVendor) {
						if ($listing['paymentMethods']){
							$paymentMethodsModalID = 'select_payment_method-' . $listing['B36'];
							echo '<label class="btn xs" for="' . $paymentMethodsModalID. '"><i class="' . Icon::getClass('CART-M') . '"></i><div class="hint left"><span>Purchase</span></div></label>';
							$this->renderPaymentMethodsModal(
								$listing['paymentMethods'],
								$listing['B36'],
								'select_payment_method-' . $listing['B36']
							);
						} else
							$this->renderMemberButton(
								URL . 'order/' . $listing['B36'] . '/',
								'<i class="' . Icon::getClass('CART-M') . '"></i><div class="hint left"><span>Purchase</span></div>',
								'btn xs'
							); 
						/*if ($listing['isFavorite']) { ?>
						<a href="<?php echo '?do[ToggleListingFavorite]=' . $listing['ID'] . '#listing-' . $listing['ID']; ?>" class="btn xs minimal red">
							<i class="<?php echo Icon::getClass('HEART', true); ?>"></i>
							<div class="hint left"><span>Un-favorite</span></div>
						</a>
						<?php } else { 
							$this->renderMemberButton(
								'?do[ToggleListingFavorite]=' . $listing['ID'] . '#listing-' . $listing['ID'],
								'<i class="' . Icon::getClass('HEART', true) . '"></i><div class="hint left"><span>Add to favorites</span></div>',
								'btn xs red'
							); 
						} */ ?>
					<?php } ?>
					</div>
					<?php if (count($this->cryptocurrencies) > 1 && $listing['cryptocurrencies']){ ?>
					<div class="cryptocurrencies">
						<?php foreach($listing['cryptocurrencies'] as $cryptocurrency){ ?>
						<div class="<?= $cryptocurrency['Color']; ?>"><i class="<?= $cryptocurrency['Icon']; ?>"></i></div>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
				<?php if ($hasOptionsContainer){ ?>
				<div class="listing_options-container">
					<div class="listing_options">
						<?php foreach ($listing['options'] as $option){
							if (!$option['isActiveListing']){ ?>
						<a href="<?php echo URL . 'i/' . $option['B36'] . '/'; ?>">
							<span><?php echo $option['Name']; ?></span>
							<span class="listing_option-label"><span><?php echo $option['quantity']; ?></span></span>
							<span class="listing_option-price">
								<span class="big"><?php echo $option['price'] ?></span><span class="small"><?php echo $option['price_crypto'] ?></span>
							</span>
						</a>
						<?php } } ?>
					</div>
				</div>
				<?php } ?>
			</li>
			<?php } ?>
		</ul>
		<?php if( $this->listingCount > LISTINGS_PER_PAGE ) { ?>
		<div class="row panel">
			<?php
			$this->renderPaginationPanel(
				$this->pageNumber,
				$maxPages,
				$paginationPrefix
			);
			?>
		</div>
		<?php }
		} else { ?>
		<h2 class="row band">
			<span><strong>zero</strong> listings found</span>
		</h2>
		<div class="row formatted">
			<p>We couldn't find any <?php echo $this->filterPreferences['ships_from'] !== -1 ? 'listings that ship from <strong>' . $destinationCountry . '</strong>': 'results for your search'; ?>.</p>
			<p>
				<?php if ($this->filterPreferences['ships_from'] !== -1){ ?>
				<button form="filter" name="international_vendors" type="submit" class="btn blue big"><i class="<?php echo Icon::getClass('FLAG'); ?>"></i>Include international vendors</button>
				<?php } elseif ($this->activeListingCategories) { ?>
				<a class="btn red" href="<?php echo URL . 'listings/'; ?>"><i class="<?php echo Icon::getClass('BACKWARD'); ?>"></i>Start over</a>
				<?php } else { ?>
				<button form="no_filter" type="submit" class="btn red"><i class="<?php echo Icon::getClass('BACKWARD'); ?>"></i>Reset Filters</button>
				<?php } ?>
			</p>
		</div>
		<?php } ?>
	</div>
</div>
