<?php
	$is_vendor = strtolower($this->listing['vendor']['alias']) == strtolower($this->UserAlias);

	$post = isset($_SESSION['answer_post']) ? $_SESSION['answer_post'] : false;
	$response = isset($_SESSION['answer_response']) ? $_SESSION['answer_response'] : false;

?>
<div class="rows-30">
	<div class="row cols-20 product">
		<div class="col-5">
			<figure><?php if($this->listing['listing']['images']) {
				for($i = 0; $i < count($this->listing['listing']['images']); $i++){ ?>
				<input name="thumb-pic" id="pic-<?php echo $i; ?>" type="radio"<?php echo $i == count($this->listing['listing']['images']) - 1 ? ' checked' : false ?>>
				<?php } ?>
				<?php foreach($this->listing['listing']['images'] as $image){ ?>
				<a target="_blank" href="<?php echo $image['big']; ?>" style="background-image: url('<?php echo $image['small']; ?>')"></a>
				<?php }
				if( count($this->listing['listing']['images']) > 1 ){ ?>
				<ul class="thumbs">
					<?php foreach ($this->listing['listing']['images'] as $key => $image) {  
					?><li><label for="pic-<?php echo $key; ?>" style="background-image: url(<?php echo $image['small']; ?>);"></label></li><?php 
					} ?>
				</ul>
				<?php }
			} ?></figure>
		</div>
		<div class="col-7 rows-10">
			<div class="row rows-20">
				<div class="row">
					<h2><?php echo $this->listing['listing']['name'] ?></h2>
					<a href="<?php echo $profile_url; ?>"><?php echo $this->listing['vendor']['alias']; ?></a>
				</div>
				<div class="row">
					<div class="price">
						<span class="big"><?php echo $this->listing['listing']['price'] . ($this->listing['listing']['perUnit'] ? ' / ' . $this->listing['listing']['perUnit'] : false); ?></span> <span class="small"><?php echo $this->listing['listing']['minimumQuantity'] ?: $this->listing['listing']['price_crypto']; ?></span>
					</div>
				</div>
				<p class="row summary"><?= $this->listing['listing']['summary']; ?></p>
				<?php if ($this->listing['listing']['shippingAvailability'] !== true){ ?>
				<div class="row grey-box color-red" style="display: inline-block">This listing <u>does not</u> ship to <strong><?= $this->listing['listing']['shippingAvailability']; ?></strong>.</div>
				<?php } ?>
			</div>
			<div class="rows-10">
				<div class="row cols-5">
					<div class="col-6">
						<ul class="row big-list">
							<li>
								<div class="aux">
									<div>
										<div class="rating stars color-yellow">								
											<?php $this->renderRating($this->listing['listing']['rating']); ?>
										</div>
									</div>
								</div>
								<div class="main">
									<div>Rating<?php 
							
									$hasComments = $this->listing['listing']['commentCount'] > 0;
									$ratingLabel =
										$hasComments
											? 'a href="' . URL . 'i/' . $this->listingB36 . '/comments/' . '"'
											: 'strong';
							
									echo $this->listing['listing']['rating_count'] > 0 ? ' <span><' . ($ratingLabel) . '>(' . $this->listing['listing']['rating_count'] . ($this->listing['listing']['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($this->listing['listing']['rating_count'] == 1 ? false : 's') . ')</' . ($hasComments ? 'a' : 'strong') . '></span>' : false; ?></div>
								</div>
							</li>
						</ul>
					</div>
				</div>
				<?php if ($this->listing['vendor']['alias'] == $this->UserAlias) { ?>
				<a href="<?php echo URL . 'account/listings/edit/' . $this->listingID . '/'; ?>" class="row btn big wide">
					<i class="<?= Icon::getClass('EDIT', true); ?>"></i>Edit Listing
				</a>
				<?php } elseif (!$this->UserVendor) { ?>
				<div class="row cols-15">
					<div class="col-7">
						<?php
						if ($this->listing['paymentMethods']){
							echo '<label class="btn big wide" for="select-currency"><i class="' . Icon::getClass('CART') . '"></i> Order Product</label>';
							$this->renderPaymentMethodsModal(
								$this->listing['paymentMethods'],
								$this->listingB36,
								'select-currency'
							);
						} else 
							$this->renderMemberButton(
								URL . 'order/' . $this->listingB36 . '/',
								'<i class="' . Icon::getClass('CART') . '"></i> Order Product',
								'btn big wide ' . ($disable_transaction ? 'disabled' : FALSE)
							); ?>
						<label><p class="note">Click this button to proceed to the checkout page.<br>A unique, single-use deposit address will be generated once you've confirmed the order details.</p></label>
					</div>
					<div class="col-5">
						<?php if ($this->listing['listing']['favorite']) { ?>
						<a href="?do[ToggleListingFavorite]=<?php echo $this->listingID; ?>" class="btn big wide minimal red">
							<i class="<?php echo Icon::getClass('TIMES', true); ?>"></i>Un-favorite
						</a>
						<?php } else { ?>
						<?php
						$this->renderMemberButton(
							'?do[ToggleListingFavorite]=' . $this->listingID,
							'<i class="' . Icon::getClass('HEART', true) . '"></i>Add to favorites',
							'btn big wide red'
						); ?>
						<?php } ?>
					</div>
				</div>
				<?php }
				if($this->listing['options']){ ?>
				<div class="options-container">
					<div id="options" class="anchor"></div>
					<h5 class="band"><span>Also Available</span></h5>
					<div class="listing_options">
						<?php foreach($this->listing['options'] as $option){ ?>
						<a href="<?php echo URL . 'i/' . $option['B36'] . '/'; ?>">
							<span><?php echo $option['Name']; ?></span>
							<?php if ($option['label']){ ?>
							<span class="listing_option-label"><span><?php echo $option['label']; ?></span></span>
							<?php } else { ?><span></span><?php } ?>
							<span class="listing_option-price">
								<span class="big"><?php echo $option['price'] ?></span><span class="small"><?php echo $option['price_crypto'] ?></span>
							</span>
						</a>
						<?php } ?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="row cols-20">
		<div class="col-5">
			<?php if( $this->listing['listing']['featured_comments'] ){ ?>
			<h4>
				Reviews
				<a class="small right" href="<?php echo $url . 'i/' . $this->listingB36 . '/comments/'; ?>">View All</a>
			</h4>
			<ul class="row list-ratings">
				<?php foreach( $this->listing['listing']['featured_comments'] as $featured_comment ){ ?>
				<li>
					<div class="left">
						<div class="rating stars color-yellow">
							<?php $this->renderRating($featured_comment['rating']); ?>
						</div>
						<date><?php echo $featured_comment['date']; ?></date>
					</div>
					<div class="right formatted">
						<?php echo $this->nl2p($featured_comment['content']); ?>
					</div>
				</li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
		<div class="col-7">
			<div class="top-tabs">
				<input id="description" name="listing-tab" type="radio">
				<input id="shipping" name="listing-tab" type="radio">
				<input id="questions" name="listing-tab" type="radio">
				<ul>
					<li><a href="#description">Description</a></li>
					<li><a href="#shipping">Shipping</a></li>
					<li><a href="#questions">Questions<?php
						echo $this->listing['listing']['questions']
							? '<span>' . count($this->listing['listing']['questions']) . '</span>'
							: FALSE;
					?></a></li>
				</ul>
				<div class="formatted">
					<?php echo $this->listing['listing']['description']; ?>
				</div>
				<div class="rows-20">
					<div class="row cols-15">
						<label class="col-6 label big">Ships from:</label>
						<label class="col-6 label big"><span><?php echo $this->listing['listing']['shipping']['ships_from']['continent'] ? ( ( $this->listing['listing']['shipping']['ships_from']['country'] ? $this->listing['listing']['shipping']['ships_from']['country'] . ', ' : false ) . $this->listing['listing']['shipping']['ships_from']['continent']) : 'Undeclared'; ?></span></label>
					</div>
					<ul class="row big-list zebra">
						<?php foreach( $this->listing['listing']['shipping']['shipping_options'] as $shipping_option ) { ?>
						<li>
							<div class="aux">
								<div><?php
									if ($shipping_option['price_crypto'] == ZERO_PRICE_TEXTUAL_REPLACEMENT)
										echo $shipping_option['price_crypto'];
									else
										echo $shipping_option['price'] . '<br><span>' . $shipping_option['price_crypto'] . '</span>';
								?></div>
							</div>
							<div class="main">
								<div><?php echo $shipping_option['name']; ?><?php echo $shipping_option['description'] ? '<br><span>' . $shipping_option['description'] . '</span>' : false; ?></div>
							</div>
						</li>
						<?php } ?>
					</ul>
					<ul class="row list-expandable">
						<li>
							<input id="returns-refund-policy" class="expand" type="checkbox" checked>
							<label for="returns-refund-policy">Refund &amp; Escrow Policy<i></i></label>
							<div class="expandable formatted">                            
								<?php print_r($this->listing['vendor']['policy']); ?>
							</div>
						</li>
					</ul>
				</div>
				<div class="rows-20">
					<?php $this->renderNotifications(array('Specific')); ?>
					<?php if( $this->listing['listing']['questions'] ) { ?>
					<ul class="row list-expandable w-ellipses">
						<?php $i=1; foreach( $this->listing['listing']['questions'] as $question ){ 
						if ( $is_vendor || !empty($question['html']) ){ ?>
						<li>
							<input id="<?php echo 'faq-' . $question['id']; ?>" class="expand" type="checkbox">
							<label for="<?php echo 'faq-' . $question['id']; ?>"><?php echo $question['title'] . ( empty($question['html']) ? ' <span>unanswered</span>' : false ) ?><i></i></label>
							<div class="expandable formatted">                            
								<?php echo $question['html'];
								if ( $is_vendor ) { echo !empty($question['html']) ? '<hr><p></p>' : false;
								?>
								<div class="cols-10">
									<label class="col-6 label">
										Vendor Controls:
									</label>
									<div class="col-3">
										<label for="<?php echo 'answer-question-' . $question['id']; ?>" class="btn wide"><?php echo empty($question['html']) ? 'Answer' : 'Edit' ?></label>
									</div>
									<div class="col-3">
										<label for="<?php echo 'delete-question-' . $question['id'];  ?>" class="btn wide red">Delete</label>
									</div>
								</div>
								<?php } ?>
							</div>
							<?php if( $is_vendor ) { ?>
							<input type="checkbox" hidden id="<?php echo 'answer-question-' . $question['id'] . ($_GET['modal'] == 'answer-question-' . $question['id'] ? '" checked="checked' : false); ?>">
							<div class="modal">
								<label for="<?php echo 'answer-question-' . $question['id']; ?>"></label>
								<div>
									<label class="close" for="<?php echo 'answer-question-' . $question['id']; ?>">×</label>
									<form class="rows-10" method="post" action="<?php echo URL . 'account/answer_question/' . $question['id'] . '/' ?>">
										<div class="row cols-5">
											<div class="col-8">
												<label class="label">Question:</label>
												<label class="text">
													<input name="question" pattern=".{3,100}" type="text" value="<?php echo $post ? '' . $post['question'] : $question['title'] ?>">
												</label>
											</div>
											<div class="col-4">
												<label class="label">Sort</label>
												<label class="select<?php echo isset($response['sort']) ? ' invalid' : false; ?>">
													<select name="sort">
														<?php for( $o=1; $o <= count($this->listing['listing']['questions']); $o++ ) { ?>
														<option value="<?php echo $o . ($i == $o ? '" selected="selected' : false) ?>"><?php echo $o; ?></option>
														<?php } ?>
													</select>
													<i></i>
												</label>
											</div>
										</div>
										<label class="row textarea">
											<textarea rows='5' name="answer"><?php echo $post ? $post['question'] : $question['raw'] ?></textarea>
											<?php /*?><p class="note"><strong>Allowed tags:</strong> [b] <strong>bold text</strong> [/b], [i] <em>italicized text</em> [/i], [a=http://yourlink.com] <a>links</a> [/a] and [pgp] [/pgp] for pgp blocks or other non-formatted text.</p><?php */?>
										</label>
										<div class="row">
											<input class="btn wide" type="Submit" value="Submit" />
										</div>
									</form>
								</div>
							</div>
							<input type="checkbox" id="<?php echo 'delete-question-' . $question['id'];  ?>" hidden>
							<div class="modal">
								<label for="<?php echo 'delete-question-' . $question['id'];  ?>"></label>
								<div class="rows-10">
									<label class="close" for="<?php echo 'delete-question-' . $question['id'];  ?>">×</label>
									<p class="row">Are you sure you wish to delete this question?</p>
									<div class="row cols-10">
										<div class="col-6"><a href="<?php echo URL . 'account/delete_question/' . $question['id'] . '/' ?>" class="btn wide">Delete</a></div>
										<div class="col-6"><label for="<?php echo 'delete-question-' . $question['id'];  ?>" class="btn wide red">Nevermind</label></div>
									</div>
								</div>
							</div>
							<?php } ?>
						</li>
						<?php } $i++; } ?>
					</ul>
					<?php } else { ?>
					<strong class="row">No Questions</strong>
					<?php } ?>
					<?php if( $this->Member ) { ?>
					<div class="row">
						<form class="cols-10" method="post" action="<?php echo URL . 'account/ask_question/' . $this->listingID . '/'; ?>">
							<?php echo $this->AccessPrefix ? '<input type="hidden" name="prefix" value="' . $this->AccessPrefix . '">' : false ?>
							<label class="col-3 label">Ask a question:</label>
							<div class="col-6">
								<label class="text">
									<input maxlength="100" type="text" name="question">
								</label>
							</div>
							<div class="col-3">
								<button class="btn wide arrow-right" type="Submit">Ask</button>
							</div>
						</form>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
	<?php if( $this->listing['relatedListings'] ){ ?>
	<div class="row">
		<h3 class="band">
			<span>Other listings by the vendor</span>
			<?php //if( count($this->listing['relatedListings']) >= RELATED_LISTINGS_PER_PAGE ) { ?>
			<div><a class="btn arrow-right" href="<?php echo $profile_url . 'listings/'; ?>">View All</a></div>
			<?php //} ?>
		</h3>
		<ul class="row listings grid">
			<?php foreach ($this->listing['relatedListings'] as $relatedListing) { 
				$expandOptionsToggleID = 'expand_options-' . $relatedListing['ID'];
			?>
			<li>
				<?php if($relatedListing['options']){ ?>
				<input type="checkbox" id="<?php echo $expandOptionsToggleID; ?>" hidden>
				<?php } ?>
				<a class="listing" href="<?php echo $url . 'i/' . $relatedListing['B36'] . '/'; ?>">
					<div class="image"<?php echo $relatedListing['Image'] ? ' style="background-image:url(\'' . $relatedListing['Image'] . '\')"' : FALSE; ?>></div>
					<div class="info">
						<div class="name"><div><span><?php echo $relatedListing['Name'] ?></span></div></div>
					</div>
				</a>
				<div class="price">
					<div>
						<span class="big"><?php echo $relatedListing['price'] ?></span> <span class="small"><?php echo $relatedListing['price_crypto']; ?></span>
					</div><?php
						//if($relatedListing['options'])
						//	echo '<label for="' . $expandOptionsToggleID . '" class="btn">more options</label>';
					?>
				</div>
				<?php if ($relatedListing['ratingCount'] > 0){ ?>
				<div class="overlay">
					<div class="rating stars">
						<span>(<?php echo $relatedListing['ratingCount'] . ($relatedListing['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($relatedListing['ratingCount'] == 1 ? false : 's'); ?>)</span>
						<?php $this->renderRating($relatedListing['averageRating']); ?>
					</div>
				</div>
				<?php } 
				if ($relatedListing['options']){/* ?>
				<div class="options-overlay">
					<label for="<?php echo $expandOptionsToggleID; ?>"></label>
					<div>
						<?php foreach($relatedListing['options'] as $i => $option){ 
						if(
							$relatedListing['groupMemberCount'] > LISTINGS_GRID_OPTIONS_MAX_QUANTITY &&
							$i == (LISTINGS_GRID_OPTIONS_MAX_QUANTITY - 1)
						){ ?>
						<a class="more-btn" href="<?php echo URL . 'i/' . $relatedListing['B36'] . '/#options'; ?>">
							<div>View All Options <i class="<?php echo Icon::getClass('CARET_RIGHT'); ?>"></i></div>
						</a>
						<?php } else { ?>
						<a href="<?php echo URL . 'i/' . $option['B36'] . '/'; ?>">
							<div><span><?php echo $option['Name']; ?></span></div>
							<div>
								<span class="big"><?php echo $option['price'] ?></span><span class="small"><?php echo $option['price_crypto'] ?></span>
							</div>
						</a>
						<?php } } ?>
					</div>
				</div> */ ?>
				<div class="options">
				<?php
				foreach ($relatedListing['options'] as $i => $option)
					echo	'<a href="' . URL . 'i/' . $option['B36'] . '/">
							<div>
								<span>' . ($relatedListing['trivialOptions'] ? ($option['label'] ?: $option['altLabel']) : $option['Name']) . '</span>
								<span><b>' . $option['price'] . '</b></span>
							</div>
						</a>'; ?>
				</div>
				<?php } ?>
			</li>
			<?php } ?>
		</ul>
	</div>
	<?php } ?>
</div>
