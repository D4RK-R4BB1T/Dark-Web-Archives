<?php 

	$URLPrefix = URL . 'account/favorites/';

?>
<div class="rows-30">
	<div class="rows-20">
		<?php if($this->listings) { ?>
		<h2 class="row band">
			<span><?php echo $this->listingCount . ' favorited listing' . ($this->listingCount > 1 ? 's' : false)?></span>
			<div>
				<label class="label">Sort by</label>
				<div class="big-dropdown">
					<span><?php
					
						$defaultSortModes = array(
							'rating'		=> 'Rating',
							'price_asc'		=> 'Price',
							'name_asc'		=> 'Name'
						);
						$allSortModes = array_merge(
							$defaultSortModes,
							array(
								'price_desc'	=> 'Price',
								'name_desc'		=> 'Name'
							)
						);
						//echo array_search($allSortModes[$this->sortMode], $defaultSortModes); die;
						echo $allSortModes[$this->sortMode];
						unset($defaultSortModes[ array_search($allSortModes[$this->sortMode], $defaultSortModes) ]);
					?></span>
					<a class="toggle">More</a>
					<ul class="dropdown">
						<?php foreach( $defaultSortModes as $key => $sortMode ) { ?>
						<li><a href="<?php echo $URLPrefix . $key . '/' ?>" class="dropdown-link"><?php echo $sortMode ?></a></li>
						<?php } ?>
					</ul>
				</div>
				<?php 
				$currentSort = explode('_', $this->sortMode);
				if( count($currentSort) > 1 ){
					$sortURL = $URLPrefix . $currentSort[0] . '_' . ($currentSort[1] == 'asc' ? 'desc' : 'asc') . '/';
					$upOrDown = $currentSort[1] == 'asc' ? 'up' : 'down';
					echo '<a class="btn minimal xs" href="' . $sortURL . '"><i class="candy-sort ' . $upOrDown . '"></i></a>';
				} ?>
			</div>
		</h2>
		<ul class="row listings grid thirds">
			<?php foreach ($this->listings as $listing) { 
				$B36 = NXS::getB36($listing['id']);
			?>
			<li>
				<a class="btn xs red" href="?do[ToggleListingFavorite]=<?php echo $listing['id'] ?>"><i class="<?php echo Icon::getClass('TIMES', true); ?>"></i></a>
				<div class="vendor"><a href="<?php echo URL . 'v/' . $listing['alias'] . '/' ?>"><?php echo $listing['alias']; ?></a></div>
				<<?php echo $listing['inactive'] ? 'div' : 'a href="' . URL . 'i/' . NXS::getB36($listing['id']) . '/"'; ?> class="listing<?php echo $listing['inactive'] ? ' inactive' : false; ?>">
					<div class="image"<?php echo $listing['image'] ? ' style="background-image:url(\'' . $listing['image'] . '\')"' : FALSE; ?>></div>
					<div class="info">
						<div class="name"><div><span><?php echo $listing['name'] ?></span></div></div>
					</div>
				</<?php echo $listing['inactive'] ? 'div' : 'a'; ?>>
				<div class="price">
					<div>
						<span class="big"><?php echo $listing['price'] ?></span> <span class="small"><?php echo $listing['price_crypto']; ?></span>
					</div>
				</div>
				<?php if ($listing['rating_count'] > 0){ ?>
				<div class="overlay">
					<div class="rating stars">
						<span>(<?php echo $listing['rating_count'] . ($listing['exceededMaximumVisibleRatings'] ? '+' : false) . ' rating' . ($listing['rating_count'] == 1 ? false : 's'); ?>)</span>
						<?php $this->renderRating($listing['rating']); ?>
					</div>
				</div>
				<?php } ?>
			</li>
			<?php } ?>
		</ul>
		<?php if ($this->listingCount > FAVORITE_LISTINGS_PER_PAGE) { ?>
		<div class="row panel">
			<?php 
				$this->renderPaginationPanel(
					$this->pageNumber,
					ceil($this->listingCount/FAVORITE_LISTINGS_PER_PAGE),
					URL . '/account/favorites/' . $this->sortMode . '/'
				);
			?>
		</div>
		<?php }
		} else { ?>
		<h2 class="row band">
			<span><strong>zero</strong> favorites</span>
		</h2>
		<?php } ?>
	</div>
</div>
