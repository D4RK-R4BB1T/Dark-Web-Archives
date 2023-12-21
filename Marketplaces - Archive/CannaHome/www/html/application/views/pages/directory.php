<h2>Vendor Directory</h2>
<div class="formatted">
	<?php if($letters = $this->page['data']){ ?>
	<fieldset class="sticky-panel">
		<div>
			<?php foreach($letters as $letter => $vendors){
			if(empty($vendors)){ ?>
			<a class="btn disabled"><?php echo $letter; ?></a>
			<?php } else { ?>
			<a class="btn" href="<?php echo '#' . $letter; ?>"><?php echo $letter; ?></a>
			<?php }
			} ?>
		</div>
	</fieldset>
	<fieldset class="rows-20">
		<?php foreach($letters as $letter => $vendors){
		if($vendors){ ?>
		<div class="row">
			<span class="anchor" id="<?php echo $letter; ?>"></span>
			<h5 class="band">
				<span><?php echo $letter; ?></span>
			</h5>
			<ul class='vendor-list'>
				<?php foreach($vendors as $vendor){ ?>
				<li>
					<a href="<?php echo URL . 'v/' . $vendor['Alias'] . '/'; ?>" class="<?php echo 'vendorLogo-' . $vendor['Alias']; ?>">
						<?php echo '<span>' . implode('</span><span>', $vendor['logoElements']) . '</span>'; ?>
					</a>
				</li>
				<?php } ?>
			</ul>
		</div>
		<?php }
		} ?>
	</fieldset>
	<?php } ?>
</div>
