<?php if (file_exists(VIEWS_PATH . $folder . '/footer.php')) require VIEWS_PATH . $folder . '/footer.php'; ?>
		</section>
		<footer>
			<div>
				<div>
					<h7><i class="<?= Icon::getClass('HOME'); ?>"></i>Your <strong>Home</strong> for Cannabis and Shrooms</h7>
				</div>
				<div class="exchange-rate"><?php echo $this->oneCryptocurrency ?: FALSE; ?></div>
				<div>
					<?php echo !$this->UserMod ? '<label for="show-chat" class="btn red"><i class="' . Icon::getClass('QUESTION_MARK', true) . '"></i>Contact Support</label>' : FALSE;
					if ($this->privateDomains){ ?>
					<label for="domains-modal" class="btn blue"><i class="<?php echo Icon::getClass('KEY', true); ?>"></i>Private URLs</label>
					<?php }
					if ($this->donationAddresses){ ?>
					<label for="donate-modal" class="btn"><i class="<?php echo Icon::getClass('HEART', true); ?>"></i>Donate</label>
					<?php } else { ?>
					<a class="btn" href='?do[GenerateDonationAddress]'><i class="<?php echo Icon::getClass('HEART', true); ?>"></i>Donate</a>
					<?php } //$this->SiteLogo; ?>
				</div>
			</div>
		</footer>
	</div>
	<?php if ($this->javascripts){
		foreach ($this->javascripts as $javascript) { ?>
	<script src="<?php echo $javascript; ?>"></script>
		<?php }
	} ?>
</body>
</html>
