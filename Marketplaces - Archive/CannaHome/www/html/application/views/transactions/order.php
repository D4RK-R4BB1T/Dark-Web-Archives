<?php

$post = isset($_SESSION['order_post']) ? $_SESSION['order_post'] : FALSE;
$feedback = isset($_SESSION['order_response']) ? $_SESSION['order_response'] : FALSE;

unset($_SESSION['order_post'], $_SESSION['order_response']);

?>
<li>
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" checked>
	<label for="order_steps-<?php echo $step; ?>"><?php echo $order_label; ?></label>
	<div class="expandable">
		<form method="post" action="<?php echo $this->isEditing ? URL . 'transactions/edit_transaction/' . $this->TXID . '/' : URL . 'transactions/create_transaction/' . $this->listingID . '/'; ?>">
			<?php require('order_form.php'); ?>
		</form>
	</div>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" />
	<label><?php echo $review_label; ?></label>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" />
	<label><?php echo $pay_label; ?></label>
</li>
<li class="inactive">
	<input id="order_steps-<?php echo ++$step; ?>" class="expand" type="radio" name="order-steps" />
	<label><?php echo $feedback_label; ?></label>
</li>