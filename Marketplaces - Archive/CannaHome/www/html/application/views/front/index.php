<div class="rows-40" style="background-color:inherit;overflow: hidden;position: relative;padding: 10px 0 15px;z-index: 0;margin: -13px;">
	<div class="row rows-30">
		<h2 class="row centered band"><span>Your <strong>Home</strong> for Cannabis and Shrooms.</span></h2>
		<div class="row category-picker six-per-row">
			<a href="<?= URL . 'listings/flowers/' ?>">
				<span>Flowers</span>
				<div style="background-image:url(/assets/frontpage/cannabis.jpg)"></div>
			</a>
			<a href="<?= URL . 'listings/concentrates/' ?>">
				<span>Concentrates</span>
				<div style="background-image:url(/assets/frontpage/concentrates.jpg)"></div>
			</a>
			<a href="<?= URL . 'listings/carts/' ?>">
				<span>Carts</span>
				<div style="background-image:url(/assets/frontpage/cartride.jpg)"></div>
			</a>
			<a href="<?= URL . 'listings/edibles/' ?>">  
				<span>Edibles</span>
		   		<div style="background-image:url(/assets/frontpage/edibles.jpg)"></div>
		 	</a>
                       <a href="<?= URL . 'listings/distillate/' ?>">  
                                <span>Distillate</span>
                                <div style="background-image:url(/assets/frontpage/distillate.jpg)"></div>
                        </a>
		 	<a href="<?= URL . 'listings/shrooms/' ?>">  
				<span>Shrooms</span>
				<div style="background-image:url(/assets/frontpage/shrooms.jpg)"></div>
			</a>
		</div>
	</div>
	<div class="row rows-15">
		<h5 class="row band bigger">
			<span>Active vendors<?= count($this->Locales) > 1 ? ' in <label class="tooltip" style="display:inline-block" for="preferences-toggle"><strong>' . $activeLocaleName . '</strong></label>' : false ?></span>
		</h5>
		<ul class="store-picker">
			<?= $activeVendorStores; ?>
		</ul>
		
	</div>
	<div class="row" style="padding: 0 10px;">
		<div class="cols-15">
			<div class="col-4">
				<h5 class="band bigger">
					<span>Top Sellers</span>
				</h5>
				<ul class="pic-list">
					<?php foreach( $this->frontpageListings['bestsellers'] as $bestseller_listing ) { ?>
					<li>
						<a class="vendor" href="<?= URL . 'v/' . $bestseller_listing['vendorAlias'] . '/'; ?>"><?= $bestseller_listing['vendorAlias']; ?></a>
						<a href="<?= URL . 'i/' . $bestseller_listing['B36'] . '/'; ?>">
							<?php if($bestseller_listing['Image']){ ?>
							<div class="image" style="background-image: url('<?= $bestseller_listing['Image']; ?>')"></div>
							<?php } ?>
							<div class="main">
								<div>
									<div>
										<div>
											<span><?= $bestseller_listing['Name']; ?></span>
										</div>
									</div>
									<span><?= $bestseller_listing['price']; ?> <span><?= $bestseller_listing['price_crypto']; ?></span></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
			</div>
			<div class="col-4">
				<h5 class="band bigger">
					<span>New Arrivals</span>
				</h5>
				<ul class="pic-list">
					<?php foreach( $this->frontpageListings['new'] as $bestseller_listing ) { ?>
					<li>
						<a class="vendor" href="<?= URL . 'v/' . $bestseller_listing['vendorAlias'] . '/'; ?>"><?= $bestseller_listing['vendorAlias']; ?></a>
						<a href="<?= URL . 'i/' . $bestseller_listing['B36'] . '/'; ?>">
							<?php if($bestseller_listing['Image']) { ?>
							<div class="image" style="background-image: url('<?= $bestseller_listing['Image']; ?>')"></div>
							<?php } ?>
							<div class="main">
								<div>
									<div>
										<div>
											<span><?= $bestseller_listing['Name']; ?></span>
										</div>
									</div>
									<span><?= $bestseller_listing['price']; ?> <span><?= $bestseller_listing['price_crypto']; ?></span></span>
								</div>
							</div>
						</a>
					</li>
					<?php } ?>
				</ul>
			</div>
			<div class="col-4">
				<h5 class="band bigger">
					<span>Vendor Updates</span>
				</h5>
				<ul class="x-small big-list">
					<?php foreach( $this->latestUpdates as $latestUpdate ){ ?>
					<li id='updates-<?= $latestUpdate['userAlias']; ?>'>
						<?php if( $latestUpdate['icon'] ){ ?>
						<i class="<?= $latestUpdate['icon']; ?>"></i>
						<?php } elseif( $latestUpdate['image'] ){ ?>
						<div class="image" <?= 'style="background-image:url(' . $latestUpdate['image'] . ')"'; ?>></div>
						<?php } ?>
						<div class="main">
							<div>
								<span>
									<a href="<?= URL . 'v/' . $latestUpdate['userAlias'] . '/' ?>"><strong><?= $latestUpdate['userAlias']; ?></strong></a><br>
									<a target="_blank" href="<?= $latestUpdate['URL']; ?>"><?= $latestUpdate['content']; ?></a><br>
									<strong><?= $latestUpdate['dateUpdated']; ?></strong>
								</span>
							</div>
						</div>
					</li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>
