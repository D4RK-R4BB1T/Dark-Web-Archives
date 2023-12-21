<div class="top-tabs">
	<ul>
		<li><a href="<?= URL.'account/invites/unclaimed/' ?>">Invite Codes</a></li>
		<li><a href="<?= URL.'account/invites/claimed/' ?>">Invited Users</a></li>
		<li class="active"><a href="<?= URL . 'account/invites/commissions/'; ?>">Commissions</a></li>
	</ul>
	<div>
		<table id="commissions-table" class="cool-table">
			<thead>
				<tr>
					<th>Month</th>
					<th>Orders</th>
					<th style="">Commissions</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->referralWallets as $referralWallet){
					$withdrawModalID = 'withdraw-' . $referralWallet['ID']; ?>
				<tr>
					<td><?= $referralWallet['month']; ?></td>
					<td><?= $referralWallet['orders']; ?></td>
					<td><?= implode('<br>', $referralWallet['cryptocurrencyBalances']); ?></td>
					<td>
						<label<?= $referralWallet['isWithdrawable'] && !$referralWallet['isProcessing'] ? ' for="' . $withdrawModalID . '"' : false; ?> class="btn <?= $referralWallet['isWithdrawable'] && !$referralWallet['isProcessing'] ? 'yellow' : 'disabled'; ?>"><?php
						if (!$referralWallet['isWithdrawable'] && !$referralWallet['Withdrawn'])
							echo '<div class="hint left"><span>Not Yet</span></div>'; ?><i class="<?php
								switch (true){
									case $referralWallet['isProcessing']:
										echo ICON::getClass('ELLIPSIS-H');
										break;
									case $referralWallet['Withdrawn']:
										echo ICON::getClass('CHECK');
										break;
									default:
										echo ICON::getClass('DOLLAR', true);
								}
							?>"></i><?php
								switch (true){
									case $referralWallet['isProcessing']:
										echo 'Queued';
										break;
									case $referralWallet['Withdrawn']:
										echo 'Withdrawn';
										break;
									default:
										echo 'Withdraw';
								}
							?></label>
						<?php
						if (
							$referralWallet['isWithdrawable'] &&
							!$referralWallet['isProcessing']
						){
							if (isset($_SESSION['referral_wallet_withdrawal'])){
								$hadErrors = isset($_SESSION['referral_wallet_withdrawal']['errors'][$referralWallet['ID']]);
								$errors = $_SESSION['referral_wallet_withdrawal']['errors'][$referralWallet['ID']];
								$post = $_SESSION['referral_wallet_withdrawal']['post'];
								
								unset($_SESSION['referral_wallet_withdrawal']);
							}
							?>
						<input<?= $hadErrors ? ' checked' : false; ?> id="<?= $withdrawModalID; ?>" type="checkbox" hidden>
						<form class="modal" method="post" action="<?= URL . 'account/withdraw_referral_wallet/' . $referralWallet['ID'] . '/'; ?>">
							<label for="<?= $withdrawModalID; ?>"></label>
							<div>
								<label class="close" for="<?= $withdrawModalID; ?>">&times;</label>
								<fieldset class="rows-20">
									<h5 class="band bigger"><span>Withdraw Commissions</span></h5>
									<ul class="row big-list x-small">
										<li>
											<div class="aux">
												<div><strong><?= $referralWallet['month']; ?></strong></div>
											</div>
											<div class="main">
												<div><span>Month</span></div>
											</div>
										</li>
										<li>
											<div class="aux">
												<div><strong><?= $referralWallet['orders']; ?></strong></div>
											</div>
											<div class="main">
												<div><span>Orders</span></div>
											</div>
										</li>
										<?php foreach ($referralWallet['cryptocurrencyBalances'] as $cryptocurrencyID => $cryptocurrencyBalance){
											$cryptocurrencyName = CRYPTOCURRENCIES_NAMES[explode(' ', $cryptocurrencyBalance)[1]];
											$cryptocurrencyWithdrawn = !isset($referralWallet['withdrawableCryptocurrency'][$cryptocurrencyID]); ?>
										<li>
											<div class="aux"><div><span class="monospace"><?= ($cryptocurrencyWithdrawn ? '<s>' : false) . $cryptocurrencyBalance . ($cryptocurrencyWithdrawn ? '</s>' : false); ?></span></div></div>
											<div class="main">
												<div><span><?= $cryptocurrencyName; ?></span></div>
											</div>
										</li>
										<?php } ?>
									</ul>
									<label class="row label">Withdrawal Address<?= count($referralWallet['withdrawableCryptocurrency']) > 1 ? 'es' : false; ?></label>
									<div class="rows-5">
										<?php foreach ($referralWallet['withdrawableCryptocurrency'] as $cryptocurrencyID){
											$cryptocurrencyBalance = $referralWallet['cryptocurrencyBalances'][$cryptocurrencyID];
											
											$cryptocurrencySuffix = explode(' ', $cryptocurrencyBalance)[1];
											$cryptocurrencyName = CRYPTOCURRENCIES_NAMES[$cryptocurrencySuffix];
											$addressInputName = 'output_address-' . $cryptocurrencySuffix; ?>
										<input type="hidden" name="cryptocurrencies[]" value="<?= $cryptocurrencySuffix; ?>">
										<label class="row text<?= $hadErrors && isset($errors[$cryptocurrencySuffix]) ? ' invalid' : false; ?>">
											<input required class="big prepend" name="<?= $addressInputName; ?>" <?= isset($post[$addressInputName]) ? 'value="' . $post[$addressInputName] . '"' : 'placeholder="' . $cryptocurrencyName . ' Address"'; ?> type="text">
											<i class="<?= Icon::getClass(strtoupper($cryptocurrencySuffix)); ?>"></i>
										</label>
										<?php } ?>												
									</div>
									<div class="row cols-10">
										<div class="col-6"><button class="btn wide" type="submit" name="csrf" value="<?= $this->getCSRFToken(); ?>">Withdraw</button></div>
										<div class="col-6"><label for="<?= $withdrawModalID; ?>" class="btn wide red">Cancel</label></div>
									</div>
								</fieldset>
							</div>
						</form>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
