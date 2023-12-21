<div class="cols-30">
    <div class="col-8 rows-5">
    	<div class="row box panel">
            <div class="left">
                <strong>Sort results by:</strong>
                <div class="big-dropdown">
                    <span><?php 
					
						$url_prefix = $this->storefront_url($this->vendor['alias']) . 'listings/';
						
						switch($this->sort_mode){
							case 'rating':
								echo 'Rating';
							break;
							case 'price_asc':
								echo 'Low Price';
							break;
							case 'price_desc':
								echo 'High Price';
							break;
							case 'name':
								echo 'Name';
							break;
						}
					
					?></span>
                    <a class="toggle">More</a>
                    <ul class="dropdown">
                      <li><a href="<?php echo $url_prefix . 'rating/' ?>" class="dropdown-link">Rating</a></li>
                      <li><a href="<?php echo $url_prefix . 'price_asc/' ?>" class="dropdown-link">Low Price</a></li>
                      <li><a href="<?php echo $url_prefix . 'price_desc/' ?>" class="dropdown-link">High Price</a></li>
                      <li><a href="<?php echo $url_prefix . 'name/' ?>" class="dropdown-link">Name</a></li>
                    </ul>
                </div>
            </div>
            <?php if ($this->vendor['listing_count'] > 0) { 
			
				$total_pages = ceil($this->vendor['listing_count']/5);
			
			?>
			<div class="right">
				<div class="pagination">
					<?php if ($this->page_number > 3) { ?><a href="<?php echo $url_prefix . $this->sort_mode . '/' . '1/' ?>">1</a><span class="ellipsis" href="#">&hellip;</span><?php } ?>
                    <?php if ($this->page_number > 2) { ?><a href="<?php echo $url_prefix . $this->sort_mode . '/' . ($this->page_number-2) .'/' ?>"><?php echo $this->page_number-2 ?></a><?php } ?>
                    <?php if ($this->page_number > 1) { ?><a href="<?php echo $url_prefix . $this->sort_mode . '/' . ($this->page_number-1) .'/' ?>"><?php echo $this->page_number-1 ?></a><?php } ?>
                    <span class="current"><?php echo $this->page_number ?></span>
                    <?php if ($total_pages - $this->page_number >= 1) { ?><a href="<?php echo $url_prefix . $this->sort_mode . '/' . ($this->page_number+1) .'/' ?>"><?php echo $this->page_number+1 ?></a><?php } ?>
                    <?php if ($total_pages - $this->page_number >= 2) { ?><a href="<?php echo $url_prefix . $this->sort_mode . '/' . ($this->page_number+2) .'/' ?>"><?php echo $this->page_number+2 ?></a><?php } ?>
                    <?php if ($total_pages - $this->page_number >= 3) { ?><span class="ellipsis" href="#">&hellip;</span><a href="<?php echo $url_prefix . $this->sort_mode . '/' . $total_pages ?>/"><?php echo $total_pages ?></a><?php } ?>
                </div>
			</div>
			<?php } ?>
        </div>
        <ul class="row listings list">
        	<?php foreach( $this->vendor['listings'] as $listing ) { ?>
            <li>
                <div class="image" style="background-image:url('<?php echo $listing['image']; ?>')"></div>
                <div class="info">
                    <div class="left">
                        <div class="title">
                            <h3><div><span><?php echo $listing['name'] ?></span></div></h3>
                            <h4><?php 
							
							foreach( $listing['categories'] as $category ) {
								$categories[] = $category['name'];
							}
							
							echo implode(' <span>/</span> ', $categories);
							unset($categories);
							
							?></h4>
                        </div>
                        <p><?php echo $listing['description']; ?></p>
                    </div>
                    <div class="right">
                        <?php if (!empty($listing['rating'])) {
							$rating = round(($listing['rating']*2), 0)/2;
							$full_stars = floor($rating);
							$half_stars = ($rating - 0.5)==$full_stars ? 1 : 0;
							$empty_stars = 5 - ceil($rating);
						?>
						<div class="rating">
							<div class="stars"><?php echo str_repeat('<i class="full"></i>', round($listing['rating'], 1)).str_repeat('<i class="half"></i>', $half_stars).str_repeat('<i class="empty"></i>', $empty_stars); ?></div>
                            <div><a href="<?php echo $url . 'listing/' . $listing['id'] . '/comments/'; ?>"><?php echo $listing['rating_count'].' Rating'.($listing['rating_count']==1 ? false : 's') ?></a></div>
						</div>
						<?php } else { ?>
						<div class="rating stars"><em>NEW!</em></div>
						<?php } ?>
                        <div class="options">
                        	<div class="big"><?= $listing['price']; ?></div>
                        	<div class="small"><?php echo $listing['price_crypto']; ?></div>
                        	<a href="<?php echo $url . 'listing/' . $listing['id'] . '/' ?>" class="btn">View More</a>
                        </div>
                    </div>
                </div>
            </li>
            <?php } ?>
        </ul>
    </div>
    <div class="rows-15 col-4 side-bar">
        <div class="row">
            <div class="main">
            	<?php if ( $this->vendor['image'] ) { ?>
                <a href="<?php echo $this->vendor['image'] ?>" target="_blank" class="image" style="background-image: url('<?php echo $this->vendor['image'] ?>')"></a>
                <?php } ?>
                <h3><?php echo $this->vendor['alias'] ?></h3>       
                <div class="rows-10">
                    <ul class="row big-list x-small border">
                        <li>
                        	<div class="aux">
                            	<div class="color-<?php 
									
									switch(true){
										case ($this->vendor['reputation']>=REPUTATION_PTS_GREEN):
											echo 'green';
										break;
										case ($this->vendor['reputation']>=REPUTATION_PTS_YELLOW):
											echo 'yellow';
										break;
										case ($this->vendor['reputation']<REPUTATION_PTS_YELLOW):
											echo 'red';
										break;
									}
									
								?>"><?php echo $this->vendor['reputation'] ?> pts</div>
                            </div>
                            <div class="main">
                                <div><span>Reputation</span></div>
                            </div>
                        </li>
                        <li>
                            <div class="aux">
                            <?php if (!empty($this->vendor['rating'])) { ?>
                                <div><span><a href="<?php echo $this->storefront_url($this->vendor_alias) . 'comments/' ?>"><?php echo $this->vendor['rating_count'] ?> Rating<?php echo $this->vendor['rating_count']==1 ? '' : 's' ?></a></span></div>
                            <?php } else { ?>
                            	<div><span>No Ratings</span></div>
                            <?php } ?>
                            </div>
                            <div class="main">
                                <div>
                                    <?php if (!empty($this->vendor['rating'])) {
										$rating = round(( $this->vendor['rating']*2), 0)/2;
										$full_stars = floor($rating);
										$half_stars = ($rating - 0.5)==$full_stars ? 1 : 0;
										$empty_stars = 5 - ceil($rating);
									?>
									<div class="rating stars">
										<?php echo str_repeat('<i class="full"></i>', round( $this->vendor['rating'], 1)).str_repeat('<i class="half"></i>', $half_stars).str_repeat('<i class="empty"></i>', $empty_stars); ?>
									</div>
									<?php } else { ?>
                                    <div class="rating stars">
                                    	<?php echo str_repeat('<i class="empty"></i>', 5); ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <a class="row btn wide color" href="<?php echo $this->storefront_url($this->vendor['alias']); ?>"><i class="fa-user"></i> View Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
