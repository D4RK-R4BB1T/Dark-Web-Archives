<?php
	
	$post = isset($_SESSION['feedback_post']) ? $_SESSION['feedback_post'] : false;
	$response = isset($_SESSION['feedback_response']) ? $_SESSION['feedback_response'] : false;
	
	unset($_SESSION['feedback_post']);
	unset($_SESSION['feedback_response']);

if (!$this->isVendor) { ?>
<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio">
	<label><?php echo $order_label; ?></label>
</li>
<?php } ?>
<li>
	<a class="anchor" id="review"></a>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="checkbox">
	<label for="order_steps-<?php echo $step; ?>"><?php echo $review_label; ?></label>
	<div class="expandable">
		<?php require('review_form.php'); ?>
	</div>
</li>
<?php if (!$this->isVendor) { ?>
<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $pay_label; ?></label>
</li>
<?php } else { ?>
<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $fulfill_label; ?></label>
</li>
<?php } ?>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" checked>
	<label><?php echo $feedback_label; ?></label>
	<div class="expandable">
		<form method="post" action="<?php echo URL . 'transactions/rate_transaction/' . $this->TXID . '/' ?>">
			<fieldset class="rows-30">
				<div class="row formatted">
					<h2 class="color-blue">Your transaction is complete.</h2>
					<p><?php echo $this->isVendor ? 'How was your experience with this buyer? Please rate this buyer.' : 'How was your experience with this vendor? Please rate this vendor.'; ?></p>
					<?php if( !$this->isVendor ){ ?>
					<p>You may amend your feedback up to <strong><?php echo PENDING_FEEDBACK_DAYS ?> days</strong> after the transaction is complete.</p>
					<?php } ?>
				</div>
				<div class="row rows-30">
					<h5 class="row band bigger"><span>Rate your overall experience with this order</span></h5>
					<?php if (!$this->isVendor) { ?>
					<div class="row rating label stars<?= isset($response['transaction_rating']) ? ' invalid' : false ?>" data-label="Pick a rating between 1 and 5">
						<?php for($i = 5; $i > 0; $i--){ ?>
						<input required type="radio" name="transaction_rating" value="<?php echo $i; ?>" id="<?php echo 'transaction_rating-' . $i; ?>" <?php echo isset($post['transaction_rating']) ? ( $post['transaction_rating'] == $i ? 'checked' : false ) : ($this->transactionRating == $i ? 'checked' : false) ?>>
						<label for="<?php echo 'transaction_rating-' . $i; ?>"></label>
						<?php } ?>
						<div class="rating-attributes">
							<input<?= $this->attributeID ? ' checked' : false; ?> type="checkbox" hidden id="toggle-rating_attributes">
							<div>
								<label class="btn" for="toggle-rating_attributes"><i class="<?= Icon::getClass('THUMBS_UP'); ?>"></i>Give A Compliment?</label>
							</div>
							<div class="attributes">
								<label>
									<p class="note">What was particularly <strong data-positive="good" data-negative="unsatisfactory"></strong> about this order?<br>(optional)</p>
								</label>
								<div>
									<input name="rating_attribute" id="rating_attribute-none" value="0" type="radio" hidden>
									<?php foreach ($this->ratingAttributes as $ratingAttribute){
										$inputID = 'rating_attribute-' . $ratingAttribute['ID'];
										$isChecked =
											(
												isset($response['rating_attribute']) &&
												$response['rating_attribute'] == $ratingAttribute['ID']
											) ||
											(
												!isset($response['rating_attribute']) &&
												$ratingAttribute['ID'] == $this->attributeID
											)
									?>
									<input<?= $isChecked ? ' checked' : false; ?> type="radio" hidden name="rating_attribute" id="<?= $inputID; ?>" value="<?= $ratingAttribute['ID']; ?>">
									<label for="<?= $inputID; ?>">
										<i class="<?= str_replace('icon', 'sprite', $ratingAttribute['icon']) . '-l'; ?>"></i>
										<span><?= $ratingAttribute['Name']; ?></span>
									</label>
									<?php } ?>
									<label for="rating_attribute-none"></label>
								</div>
							</div>
						</div>
					</div>
					<div class="row rows-10">
						<label class="row label">Comments:</label>
						<label class="textarea<?php echo isset($response['transaction_comment']) ? ' invalid' : false ?>">
							<textarea rows="5" name="transaction_comments" maxlength="500" placeholder="Please provide comments on your experience, the quality of the product, shipping and stealth."><?php echo isset($post['transaction_comment']) ? $post['transaction_comment'] : $this->transactionComments ?></textarea>
							<?php if( isset($response['transaction_comment']) ) { ?>
							<p class="note"><?php echo $response['transaction_comment'] ?></p>
							<?php } ?>
						</label>
						<?php if ($this->subscribeVendorToggleState)
							echo '<input type="hidden" name="is_following_vendor" value="1">'; ?>
						<label class="row checkbox">
							<input<?= $this->subscribeVendorToggleState || $this->subscribeVendorToggleState === NULL ? ' checked' : false; ?> name="follow_vendor" type="checkbox">
							<i></i>
							<span class="small">Subscribe to forum updates from this vendor.</span>
						</label>
					</div>
					<?php } else { ?>
					<div class="row switch big colorful">
						<input id="overall-plus" name="overall" value="5" type="radio" <?php echo isset($post['overall']) ? ($post['overall'] == '1' ? 'checked' : false) : 'checked' ?> />
						<label for="overall-plus"><i class="<?php echo Icon::getClass('THUMBS-UP-M'); ?>"></i>Positive</label>
						<input id="overall-minus" name="overall" value="0" type="radio" <?php echo isset($post['overall']) && $post['overall'] == '-1' ? 'checked' : false ?> />
						<label for="overall-minus"><i class="<?php echo Icon::getClass('THUMBS-DOWN-M'); ?>"></i>Negative</label>
					</div>
					<?php } ?>
				</div>
			</fieldset>
			<fieldset>
				<label>
					<input type="submit" class="btn big blue" value="Submit Feedback">
				</label>
			</fieldset>
		</form>
	</div>
</li>
