<?php 

$inactiveOrNope =
	!$this->isVendor &
	(
		$this->transaction['status'] == 'rejected' ||
		$this->transaction['status'] == 'refunded'
	)
		? 'nope'
		: 'inactive';

if (!$this->UserVendor) { 

	if ($this->confirmed) { ?>
<li class="finished">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio">
	<a><?php echo $order_label; ?></a>
</li>
<?php } else { 

$post = array(
	'quantity'		=> $this->transaction['order']['Quantity'],
	'comments'		=> $this->transaction['order']['Comments'],
	'address'		=> $this->transaction['order']['Address'],
	'shipping'		=> $this->transaction['order']['ShippingID'],
	'payment_method'	=> $this->cryptocurrency->ISO
);

?>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label for="order_steps-<?php echo $step; ?>"><?php echo $order_label; ?><strong>(click to edit)</strong></label>
	<div class="expandable">
		<form method="post" action="<?php echo URL . 'transactions/edit_transaction/' . $this->TXID . '/'; ?>">
			<?php require('order_form.php'); ?>
		</form>
	</div>
</li>
<?php } ?>
<?php } ?>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" checked>
	<label<?php echo $this->confirmed ? FALSE : ' for="order_steps-' . $step . '"'; ?>><?php echo $review_label; ?></label>
	<div class="expandable">
		<?php $this->renderNotifications(array('Transactions')); 
		require('review_form.php'); ?>
	</div>
</li>
<?php if (!$this->UserVendor) { ?>
<li class="<?php echo $inactiveOrNope; ?>">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $pay_label; ?></label>
</li>
<li class="<?php echo $inactiveOrNope; ?>">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
<?php } elseif ($this->feedbackGiven) { ?>
<li class="finished">
	<input class="expand" type="radio" name="order-steps">
	<label><?php echo $fulfill_label; ?></label>
</li>
<li class="finished">
	<input class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
<?php } else { ?>
<li class="<?php echo $inactiveOrNope; ?>">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $fulfill_label; ?></label>
</li>
<li class="<?php echo $inactiveOrNope; ?>">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps">
	<label><?php echo $feedback_label; ?></label>
</li>
<?php } 

unset($_SESSION['respond_response'], $_SESSION['confirm_post'], $_SESSION['confirm_response']);

?>
