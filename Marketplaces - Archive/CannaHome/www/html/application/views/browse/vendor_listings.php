<div class="rows-30">
	<h2 class="row band">
		<span><?php echo $this->vendorAlias; ?></span>
		<div><a href="<?php echo $profile_url; ?>" class="btn arrow-left white">Back to Vendor Profile</a></div>
	</h2>
	<ul class="row listings grid">
		<?php foreach( $this->listings as $listing ) { ?>
		<li>
			<a class="listing" href="<?php echo $url . 'i/' . NXS::getB36($listing['id']) . '/'; ?>">
				<div class="image" style="background-image:url('<?php echo $listing['image']; ?>')"></div>
				<div class="info">
					<div class="name"><div><span><?php echo $listing['name']; ?></span></div></div>
					<div class="price">
						<div>
							<?php if( $this->UserCurrency['ISO'] !== 'BTC' ) { ?>
							<span class="big"><?php echo $listing['price']; ?></span> <span class="small"><?php echo $listing['price_btc']; ?></span>
							<?php } else { ?>
							<span class="big"><?php echo $listing['price_btc']; ?></span>
							<?php } ?>
						</div>
					</div>
				</div>
			</a>
			<?php if( $listing['rating_count'] > 0 ){ ?>
			<div class="overlay">
				<div class="rating stars alt">
					<span>(<?php echo $listing['rating_count']; ?> ratings)</span>
					<?php $this->renderRating($listing['rating']); ?>
				</div>
			</div>
			<?php } ?>
		</li>
		<?php } ?>
	</ul>
	<div class="row pagination centered">
	<?php 
		$this->renderPagination(
			$this->pageNumber,
			ceil($this->listingCount / LISTINGS_PER_PAGE),
			$profile_url . '/listings/'
		);
	?>
	</div>
</div>