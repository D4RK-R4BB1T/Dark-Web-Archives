<?php
	$order_label	= 'Order';
	$review_label	= 'Review Order' . (
		(
			$this->option == 'review' ||
			$this->option == 'order'
		)
			? FALSE
			: '<strong>(click to expand)</strong>'
	);
	$pay_label	= 'Pay';
	$fulfill_label	= 'Fulfill Order';
	$feedback_label	= 'Provide Feedback';
	$dispute_label	= 'Settle Dispute';
	
	$step = 0;
?><div class="order-cols">
	<div class="col-8">
		<ul class="row list-expandable steps label-fill">