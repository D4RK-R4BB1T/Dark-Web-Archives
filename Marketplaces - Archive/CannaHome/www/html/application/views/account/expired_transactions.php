<div class="top-tabs">
	<ul>
		<li><a>Ongoing</a></li>
		<li><a>Finalized</a></li>
		<li class="active"><a>Expired<span><?php echo count($this->expiredTransactions); ?></span></a></li>
	</ul>
    <div>
		<form method="post" class="rows-15" action=".">
			<input type="hidden" name="csrf" value="<?= $this->getCSRFToken(); ?>">
			<div class="row formatted">
				<p>It appears you have one or more <em>expired orders</em>. These are orders that should have been finalized by now.</p>
				<p>Please select how you would like to proceed.<br>Be aware that expired orders will automatically finalize if left for <strong><?php echo NXS::formatNumber(EXPIRED_TRANSACTION_TIMEOUT_DAYS); ?> days</strong>.</p>
			</div>
			<table class="row cool-table" id="transaction-table">
				<thead>
						<tr>
							<th>#</th>
							<th>Item</th>
							<th>Value</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
				</thead>
				<tbody>
					<?php foreach ($this->expiredTransactions as $expiredTransaction) { ?>
					<tr>
						<td>
							<input type="hidden" name="txIDs[]" value="<?php echo $expiredTransaction['ID']; ?>">
							<?php echo $expiredTransaction['ID']; ?>
						</td>
						<td><strong><?php echo $expiredTransaction['name']; ?></strong></td>
						<td><?php echo $expiredTransaction['value'] . ' BTC'; ?></td>
						<td>Expired</td>
						<td style="width: 240px;">
							<div class="switch">
								<input name="<?php echo 'action-' . $expiredTransaction['ID'] ?>" checked value="finalize" id="<?php echo 'finalize-' . $expiredTransaction['ID'] ?>" type="radio">
								<label for="<?php echo 'finalize-' . $expiredTransaction['ID'] ?>">
									Finalize
									<div class="hint">
										<span>Release the funds to the vendor</span>
									</div>
								</label>
								<input <?php echo $expiredTransaction['extended'] ? 'disabled' : 'name="action-' . $expiredTransaction['ID'] . '"';?> value="extend" id="<?php echo 'extend-' . $expiredTransaction['ID'] ?>" type="radio">
								<label for="<?php echo 'extend-' . $expiredTransaction['ID'] ?>">
									Extension
									<div class="hint">
										<span><?php echo $expiredTransaction['extended'] ? 'You may only extend once' : 'Extend the transit time ' . NXS::formatNumber(EXPIRED_TRANSACTION_EXTENSION_DAYS) . ' days'; ?></span>
									</div>
								</label>
								<input name="<?php echo 'action-' . $expiredTransaction['ID'] ?>" value="dispute" type="radio" id="<?php echo 'dispute-' . $expiredTransaction['ID'] ?>" >
								<label for="<?php echo 'dispute-' . $expiredTransaction['ID'] ?>">
									Dispute
									<div class="hint">
										<span>Start a dispute</span>
									</div>
								</label>
							</div>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="row panel">
				<div class="right">
					<button class="btn blue arrow-right" type="submit">Continue</button>
				</div>
			</div>
		</form>
    </div>
</div>
