<form class="top-tabs" method="post" action="/account/update_invites/<?php echo $this->type; ?>/">
	<ul>
		<li<?php echo
			$this->type == 'unclaimed'
				? ' class="active"'
				: FALSE 
			?>><a href="<?php echo URL.'account/invites/unclaimed/' ?>">Invite Codes</a></li>
		<li<?php echo $this->type=='claimed' ? ' class="active"' : false ?>><a href="<?php echo URL.'account/invites/claimed/' ?>">Invited Users</a></li>
		<?php if ($this->hasReferralCommissions){ ?>
		<li><a href="<?= URL . 'account/invites/commissions/'; ?>">Commissions</a></li>
		<?php } ?>
	</ul>
	<?php if ($this->invites) { ?>
	<ul>
		<li>
			<button class="btn" type="submit">Apply Changes</button>
		</li>
	</ul>
	<?php }
	if ($this->invites) { ?>
	<div class="rows-15">
		<?php if ($this->isAmbassador){ ?>
		<div class="row grey-box formatted">
			<p>As a <?= $this->UserVendor ? 'vendor' : '<strong>star member</strong>'; ?>, you are eligible to receive <em>referral commissions</em> on the purchases of people you've invited.</p>
			<p>When you have earned your first commission, you will see a <strong>Commissions</strong> tab above.</p>
			<p>You <u>may not</u> spam invite codes in public forums.</p>
			<?php if ($this->openRegistration){ ?>
			<hr>
			<p>Registration is currently open which means that invite codes are reusable.</p>
			<p>Use the following link to refer people to register with your invite code :</p>
			<pre contenteditable><?= $this->MainURL . 'register/' . (isset($this->invites[0]) ? $this->invites[0]['Code'] : false) ?></pre>
			<?php } ?>
		</div>
		<?php } ?>
		<table id="invites-tables" class="row cool-table" id="invites-table">
			<thead>
				<tr>
					<?php if($this->type == 'unclaimed'){ ?>
					<th>Code</th>
					<th>Comments</th>
					<th>Issued</th>
					<th>&nbsp;</th>
					<?php } else { ?>
					<th>Username</th>
					<th>Comments</th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php
				
				foreach( $this->invites as $invite ) { 
				
				?>
				<tr>
					<?php if($this->type == 'unclaimed'){ ?>
					<td>
						<input name="invite_ids[]" value="<?php echo $invite['ID']; ?>" type="hidden">
						<label class="text inline">
							<input readonly value="<?php echo $invite['Code']; ?>" type="text">
							<b></b>
						</label>
					</td>
					<td>
						<label class="text inline">
							<input maxlength="25" name="invite-<?php echo $invite['ID'] ?>_comments" value="<?php echo $invite['Comment']; ?>" type="text">
							<b></b>
						</label>
					</td>
					<td>
						<label class="checkbox">
							<input name="invite-<?php echo $invite['ID'] ?>_issued" type="checkbox"<?php echo $invite['Issued'] ? ' checked' : FALSE; ?>>
							<i></i>
						</label>
					</td>
					<td>
						<?php if($invite['Issued']){ ?>
						<button class="btn red xs" type="submit" name="invite-<?php echo $invite['ID'] ?>_retract">
							<i class="<?php echo Icon::getClass('TIMES', true); ?>"></i>
						</button>
						<?php } else { ?>
						<button class="btn xs" type="submit" name="invite-<?php echo $invite['ID'] ?>_issued">
							<i class="<?php echo Icon::getClass('CHECK'); ?>"></i>
						</button>
						<?php } ?>
					</td>
					<?php } else { ?>
					<td>
						<input name="invite_ids[]" value="<?php echo $invite['ID']; ?>" type="hidden">
						<input name="invite-<?php echo $invite['ID'] ?>_issued" value="1" type="hidden">
						<a href="<?php echo URL . 'u/' . $invite['UserAlias'] . '/'; ?>"><?php echo $invite['UserAlias']; ?></a>
					</td>
					<td>
						<label class="text inline">
							<input maxlength="25" name="invite-<?php echo $invite['ID'] ?>_comments" value="<?php echo $invite['Comment']; ?>" type="text">
							<b></b>
						</label>
					</td>
					<?php } ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="row panel">
			<?php if($this->numberOfPages > 1){ ?>
			<div class="left">
				<div class="pagination">
				<?php
					$this->renderPagination(
						$this->pageNumber,
						$this->numberOfPages,
						URL . 'account/invites/' . $this->type . '/'
					);
				?>
				</div>
			</div>
			<?php } ?>
			<div class="right">
				<button class="btn" type="submit">Apply Changes</button>
			</div>
		</div>
	</div>
	<?php } else { ?>
	<div class="content"><strong><?php echo $this->type == 'unclaimed' ? 'You have no invite codes' : 'You have not invited anyone'; ?></strong></div>
	<?php } ?>
</form>
