<?php

	$url_prefix = URL.'vendors/';

?>
<div class="rows-5">
    <div class="row box panel">
        <div class="left">
            <strong>Sort vendors by:</strong>
            <div class="big-dropdown">
                <span><?php 
                    
                    switch($this->sort_mode){
                        case 'rating':
                            echo 'Rating';
                        break;
                        case 'reputation':
                            echo 'Reputation';
                        break;
                        case 'name':
                            echo 'Name';
                        break;
                    }
                
                ?></span>
                <a class="toggle">More</a>
                <ul class="dropdown">
                  <li><a href="<?php echo $url_prefix . 'reputation/' ?>" class="dropdown-link">Reputation</a></li>
                  <li><a href="<?php echo $url_prefix . 'rating/' ?>" class="dropdown-link">Rating</a></li>
                  <li><a href="<?php echo $url_prefix . 'name/' ?>" class="dropdown-link">Name</a></li>
                </ul>
            </div>
        </div>
        <?php if ($this->vendor_count) { 
        
            $total_pages = ceil($this->vendor_count/12);
        
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
    <?php if($this->vendors) { ?>
    <ul class="row listings vendors grid">
        <?php foreach($this->vendors as $vendor) { ?>
        <li>
            <a class="listing" href="<?php echo URL . 'vendor/' . strtolower($vendor['alias']) ?>/">
                <div class="image" style="background-image:url('<?php echo empty($vendor['image']) ? URL.DEFAULT_VENDOR_PICTURE : strip_tags($vendor['image']); ?>')"></div>
                <div class="info">
                    <h3><div><span><?php echo $vendor['alias'].' ['.$vendor['reputation'].']'; ?></span></div></h3>
                    <div class="rating left">
                    	<?php if (!empty($vendor['rating'])) {
							$rating = round(($vendor['rating']*2), 0)/2;
							$full_stars = floor($rating);
							$half_stars = ($rating - 0.5)==$full_stars ? 1 : 0;
							$empty_stars = 5 - ceil($rating);
						?>
                        <div class="stars">
                        	<?php echo str_repeat('<i class="full"></i>', round($vendor['rating'], 1)).str_repeat('<i class="half"></i>', $half_stars).str_repeat('<i class="empty"></i>', $empty_stars); ?>
                        </div>
                        <?php } else { ?>
                        <em>No ratings</em>
                        <?php } ?>
                    </div>
                    <div class="btn">view profile</div>
                </div>
            </a>
        </li>
        <?php } ?>
    </ul>
    <?php } ?>
</div>