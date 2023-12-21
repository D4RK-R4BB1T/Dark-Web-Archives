<?php

$URLPrefix = URL . 'account/listings/';

?>
<?php $this->renderNotifications(array('Specific')); ?>
<div class="rows-30">
	<div class="row panel">
		<div class="left">
			<strong><?= $this->listingCount > 0 ? ucwords(NXS::formatNumber($this->listingCount)) . ' listing' . ($this->listingCount == 1 ? false : 's') : 'No listings'; ?></strong>
		</div>
		<div class="right">
			<a class="btn blue" href="<?php echo $URLPrefix . 'new/' ?>"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>New Listing</a>
			<?php if( $this->listingCount > 0 ) { ?>
			<?php if( $this->listingInactiveCount == $this->listingCount ) {  ?>
				<a class="btn" href="<?php echo $URLPrefix . 'reactivate/all/' ?>"><i class="<?php echo Icon::getClass('PLAY'); ?>"></i>Reactivate All</a>
			<?php } else { ?>
				<label class="btn red" for="deactivate-all"><i class="<?php echo Icon::getClass('PAUSE'); ?>"></i>Deactivate All</label>
				<input type="checkbox" id="deactivate-all" hidden>
				<div class="modal">
					<label for="deactivate-all"></label>
					<div class="rows-10">
						<label class="close" for="deactivate-all">&times;</label>
						<p class="row">Are you sure you wish to deactivate all listings?</p>
						<div class="row cols-10">
							<div class="col-6"><a class="btn wide" href="<?php echo $URLPrefix . 'deactivate/all/' ?>">Yes, Deactivate All!</a></div>
							<div class="col-6"><label for="deactivate-all" class="btn wide red">Nevermind</label></div>
						</div>
					</div>
				</div>
			<?php }
			} ?>
			<a class="btn <?php echo $this->hideArchivedListings ? 'green' : 'red' ?>" href="?do[ChangeUserPrefs][ShowArchivedListings]"><i class="<?php echo Icon::getClass('TRASH'); ?>"></i><?php echo $this->hideArchivedListings ? 'Show' : 'Hide' ?> Deleted</a>
		</div>
	</div>
	<?php if ($this->listingCount > 0){ ?>
	<form method="post" id="batch_actions">
		<input type="hidden" name="page" value="<?php echo $this->pageNumber ?>">
		<input type="hidden" name="sort" value="<?php echo $this->sortMode ?>">
	</form>
	<form class="row rows-15" method="post" action="<?php echo URL . 'account/update_listings/'; ?>">
		<input type="hidden" name="page" value="<?php echo $this->pageNumber ?>">
		<input type="hidden" name="sort" value="<?php echo $this->sortMode ?>">
		<table class="row cool-table" id="listing-table">
			<thead>
				<tr>
					<th><a href="<?php echo $URLPrefix . ($this->sortMode == 'id_asc' ? 'id_desc' : 'id_asc') . '/'; ?>">#<?php 
								
						switch($this->sortMode){
							case 'id_asc':
								echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
							break;
							case 'id_desc':
								echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
							break;
						}
					
					?></a></th>
					<th><a href="<?php echo $URLPrefix . ($this->sortMode == 'name_asc' ? 'name_desc' : 'name_asc') . '/'; ?>">Product<?php 
								
						switch($this->sortMode){
							case 'name_asc':
								echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
							break;
							case 'name_desc':
								echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
							break;
						}
					
					?></a></th>
					<th><a href="<?php echo $URLPrefix . ($this->sortMode == 'price_asc' ? 'price_desc' : 'price_asc') . '/'; ?>">Price<?php 
								
						switch($this->sortMode){
							case 'price_asc':
								echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
							break;
							case 'price_desc':
								echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
							break;
						}
					
					?></a></th>
					<th><a href="<?php echo $URLPrefix . ($this->sortMode == 'stock_asc' ? 'stock_desc' : 'stock_asc') . '/'; ?>">Stock<?php 
								
						switch($this->sortMode){
							case 'stock_asc':
								echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
							break;
							case 'stock_desc':
								echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
							break;
						}
					
					?></a></th>
					<th>Visible</th>
					<th>Active</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->listings as $i => $listing) {
				
				$B36 = strtoupper(NXS::getB36($listing['id']));
				
				if ($listing['archived']){ ?>
				<tr>
					<td>
						<input type="hidden" name="listing_ids[]" value="<?php echo $listing['id']; ?>">
						<span class="monospace"><?php echo $B36; ?></span>
						<?php if($listing['rating_count']) { ?>
						<div class="notice">
							<i class="<?php echo Icon::getClass('STAR', true); ?> color-green"></i>
							<div class="hint">
								<span>New Rating</span>
							</div>
						</div>
						<?php }
						if($listing['unanswered_questions']) { ?>
						<div class="notice">
							<a href="<?php echo URL . 'i/' . $B36 . '/#questions'; ?>" target="_blank" class="atypical color-blue"><i class="<?php echo Icon::getClass('QUESTION_MARK', true); ?>"></i></a>
							<div class="hint">
								<span>New question</span>
							</div>
						</div>
						<?php } ?>
					</td>
					<td>
						<label class="text inline">
							<input disabled value="<?php echo $listing['name']; ?>" type="text">
							<b></b>
						</label>
					</td>
					<td>
						<div class="text select disabled">
							<select disabled class="monospace">
								<?php foreach($this->Currencies as $currency){
									echo '<option value="' . $currency['ID'] . '"' . ($currency['ISO'] == $listing['currency'] ? ' selected' : false) . '>' . $currency['ISO'] . '</option>';
								} ?>
							</select>
							<input class="monospace" disabled value="<?php echo $listing['price']; ?>" type="text">
						</div>
					</td>
					<td>
						<label class="text">
							<input disabled class="monospace" <?php echo 'value="' . $listing['stock'] . '" required'; ?> type="text">
						</label>
					</td>
					<td>
						<label class="checkbox">
							<input disabled <?php echo 'name="listing-' . $listing['id'] . '_visible"' . (!$listing['stealth'] ? ' checked' : false) ?> type="checkbox">
							<i></i>
						</label>
					</td>
					<td>
						<label class="checkbox">
							<input disabled <?php echo 'name="listing-' . $listing['id'] . '_active"' . (!$listing['inactive'] ? ' checked' : false) ?> type="checkbox">
							<i></i>
						</label>
					</td>
					<td class="align-right">
						<a href="<?php echo URL.'account/listings/unarchive/'.$listing['id'].'/' ?>" class="btn xs">
							<i class="<?php echo Icon::getClass('UNDO'); ?>"></i>
							<div class="hint left">
								<span>Restore</span>
							</div>
						</label>
					</td>
				</tr>
				<?php } else { ?>
				<tr>
					<td>
						<input type="hidden" name="listing_ids[]" value="<?php echo $listing['id']; ?>">
						<input type="hidden" name="<?php echo 'listing-' . $listing['id'] . '_quantity_minimum' ?>" value="<?php echo $listing['Quantity_Minimum']; ?>">
						<?php if ($listing['groupID'])
							echo '<b style="filter: hue-rotate(' . $listing['groupHue'] . 'deg)"></b>'; ?>
						<a class="monospace" href="<?php echo URL . 'i/' . $B36 . '/'; ?>"><?php echo $B36; ?></a>
						<?php if($listing['rating_count']) { ?>
						<div class="notice">
							<i class="<?php echo Icon::getClass('STAR', true); ?> color-green"></i>
							<div class="hint">
								<span>New Rating</span>
							</div>
						</div>
						<?php } 
						if($listing['unanswered_questions']) { ?>
						<div class="notice">
							<a href="<?php echo URL . 'i/' . $B36 . '/#questions'; ?>" target="_blank" class="atypical color-blue"><i class="<?php echo Icon::getClass('QUESTION_MARK', true); ?>"></i></a>
							<div class="hint">
								<span>New question</span>
							</div>
						</div>
						<?php } 
						if($listing['hasActivePromos']) { ?>
						<div class="notice">
							<i class="<?php echo Icon::getClass('GIFT'); ?> color-orange"></i>
							<div class="hint">
								<span>Active Promotions</span>
							</div>
						</div>
						<?php } ?>
					</td>
					<td>
						<?php if ($listing['editableTitle']){ ?>
						<label class="text inline">
							<input maxlength="100" name="<?php echo 'listing-' . $listing['id'] . '_name' ?>" value="<?php echo $listing['name']; ?>" type="text">
							<b></b>
						</label>
						<?php } else { ?>
						<input type="hidden" name="<?= 'listing-' . $listing['id'] . '_name' ?>" value="<?= $listing['name']; ?>">
						<strong><?= $listing['name']; ?></strong>
						<?php } ?>
					</td>
					<td>
						<div class="text select">
							<select class="monospace" name="<?php echo 'listing-' . $listing['id'] . '_currency' ?>" >
								<?php foreach($this->Currencies as $currency){
									echo '<option value="' . $currency['ID'] . '"' . ($currency['ISO'] == $listing['currency'] ? ' selected' : false) . '>' . $currency['ISO'] . '</option>';
								} ?>
							</select>
							<input class="monospace" name="<?php echo 'listing-' . $listing['id'] . '_price' ?>" value="<?php echo $listing['price']; ?>" type="text">
						</div>
					</td>
					<td>
						<?php if (
							$groupedWithPriorListing =
								$listing['groupID'] &&
								isset($this->listings[$i - 1]) &&
								$this->listings[$i - 1]['groupID'] == $listing['groupID'] &&
								$listing['unitID']
						)
							echo '<small>N/A</small>';
						else { ?>
						<label class="text select<?= $listing['stock'] == 0 ? ' invalid' : false; ?>">
							<select<?= !$listing['unitID'] ? ' disabled' : false; ?> class="monospace"<?= $listing['unitID'] ? ' name="listing-' . $listing['id'] . '_stock_unit"' : false; ?>><?php
								if ($listing['unitID'])
									foreach($this->units[$listing['dimensionID']] as $unit)
										echo '<option value="' . $unit['id'] . '"' . ($unit['id'] == $listing['unitID'] ? ' selected' : false) . '>' . $unit['abbreviation'] . '</option>';
							?></select>
							<input class="monospace" name="<?= 'listing-' . $listing['id'] . '_stock' ?>" <?= 'value="' . $listing['stock'] . '" required'; ?> <?= /*$listing['unitID'] ?*/ 'type="text"' /*: 'type="number" min="0"'*/; ?>>
						</label>
						<?php } ?>
					</td>
					<td>
						<label class="checkbox">
							<input <?php echo 'name="listing-' . $listing['id'] . '_visible"' . (!$listing['stealth'] ? ' checked' : false) ?> type="checkbox">
							<i></i>
						</label>
					</td>
					<td>
						<label class="checkbox">
							<input <?php echo 'name="listing-' . $listing['id'] . '_active"' . (!$listing['inactive'] ? ' checked' : false) ?> type="checkbox">
							<i></i>
						</label>
					</td>
					<td>
						<a href="<?php echo URL . 'account/listings/edit/' . $listing['id'] . '/' ?>" class="btn blue xs">
							<i class="<?php echo Icon::getClass('EDIT', true); ?>"></i>
							<div class="hint above">
								<span>Edit</span>
							</div>
						</a><!--
					---><a href="<?php echo URL . 'account/listings/copy/' . $listing['id'] . '/' ?>" class="btn xs">
							<i class="<?php echo Icon::getClass('COPY'); ?>"></i>
							<div class="hint above">
								<span>Copy</span>
							</div>
						</a><!--
					---><label for="<?php echo 'delete-' . $listing['id']; ?>" class="btn red xs">
							<i class="<?php echo Icon::getClass('TRASH'); ?>"></i>
							<div class="hint above">
								<span>Delete</span>
							</div>
						</label>
						<input type="checkbox" id="<?php echo 'delete-' . $listing['id']; ?>" hidden>
						<div class="modal">
							<label for="<?php echo 'delete-' . $listing['id']; ?>"></label>
							<div class="rows-10">
								<label class="close" for="<?php echo 'delete-' . $listing['id']; ?>">&times;</label>
								<p class="row">Are you sure you wish to delete this listing?</p>
								<div class="row cols-10">
									<div class="col-6"><a class="btn wide" href="<?php echo URL.'account/listings/archive/'.$listing['id'].'/' ?>">Delete</a></div>
									<div class="col-6"><label for="<?php echo 'delete-' . $listing['id']; ?>" class="btn wide red">Nevermind</label></div>
								</div>
							</div>
						</div>
					</td>
				</tr>
				<?php } } ?>
			</tbody>
		</table>
		<div class="row panel">
			<?php if( $this->pageNumber > 1 || $this->listingCount > VENDORS_LISTINGS_PER_PAGE ) { ?>
			<div class="middle">
				<div class="pagination">
				<?php
					$this->renderPagination(
						$this->pageNumber,
						ceil($this->listingCount/VENDORS_LISTINGS_PER_PAGE),
						$URLPrefix . $this->sortMode . '/'
					);
				?>
				</div>
			</div>
			<?php } ?>
			<div class="left">
				<?php /*
				<div class="big-dropdown">
					<span>With Selected</span>
					<a class="toggle">More</a>
					<ul class="dropdown">
						<li><label for="delete-selected">Delete</label></li>
					</ul>
				</div>
				<input type="checkbox" id="delete-selected" hidden>
				<div class="modal" id="<?php 'delete-' . $listing['id']; ?>">
					<label for="delete-selected"></label>
					<div class="rows-10">
						<label class="close" for="delete-selected">&times;</label>
						<p class="row">Are you sure you wish to delete the selected listings and all associated reviews?</p>
						<div class="row cols-10">
							<div class="col-6">
								<button class="btn wide color" form="batch_actions" formaction="<?php echo URL . 'account/delete_listings/'; ?>">Delete</button>
							</div>
							<div class="col-6">
								<label for="delete-selected" class="btn wide red color">Nevermind</label>
							</div>
						</div>
					</div>
				</div>
				*/ ?>
			</div>
			<div class="right">
				<button class="btn" type="submit"><i class="<?= Icon::getClass('SAVE'); ?>"></i>Apply Changes</button>
			</div>
		</div>
	</form>
	<div class="row grey-box">
		<?php /*<fieldset>Remember to <a target="_blank" class="tooltip" href="/p/grouping/">group same-product listings</a> to maximize their visiblity in search results.</fieldset>*/ ?>
		<fieldset> Use the <strong>Copy</strong> button to create listings for the same product. Grouping will be configured automatically.</fieldset>
	</div>
	<?php } ?>
</div>
