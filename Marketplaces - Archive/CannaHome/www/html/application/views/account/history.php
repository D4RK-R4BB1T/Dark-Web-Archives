<?php 

	$url_prefix = $url_prefix = URL.$filename.'/';

?>
<div class="content rows-30">
	<?php if($this->logging) { ?>
	<div class="row header">
        <h3>User Activity</h3>
        <h4>From here you can see your recent activities and their effect on your reputation. You can disable activity logging in your <a href="<?php echo URL ?>account/settings/#general">account settings</a>.</h4>
    </div>
    <hr />
    <div class="row box panel">
        <div class="left">
            Sort Results by:
            <div class="big-dropdown">
                <span><?php echo $this->sortmode=='asc' ? 'Date (oldest first)' : 'Date (newest first)' ?></span>
                <a class="toggle">More</a>
                <ul class="dropdown">
                    <li><a href="<?php echo $url_prefix . $this->page_number ?>/desc/" class="dropdown-link">Date (newest first)</a></li>
                    <li><a href="<?php echo $url_prefix . $this->page_number ?>/asc/" class="dropdown-link">Date (oldest first)</a></li>
                </ul>
            </div>
        </div>
        <?php if ($this->activity_count) { 
			
			$total_pages = ceil($this->activity_count/5);
		
		?>
        <div class="right">
            <div class="pagination white">
                <?php if ($this->page_number > 3) { ?><a href="<?php echo $url_prefix . '1/' . $this-> sortmode . '/' ?>">1</a><span class="ellipsis" href="#">&hellip;</span><?php } ?>
                <?php if ($this->page_number > 2) { ?><a href="<?php echo $url_prefix . ($this->page_number-2) .'/' . $this-> sortmode . '/'  ?>"><?php echo $this->page_number-2 ?></a><?php } ?>
				<?php if ($this->page_number > 1) { ?><a href="<?php echo $url_prefix . ($this->page_number-1) .'/' . $this-> sortmode . '/' ?>"><?php echo $this->page_number-1 ?></a><?php } ?>
                <span class="current"><?php echo $this->page_number ?></span>
                <?php if ($total_pages - $this->page_number >= 1) { ?><a href="<?php echo $url_prefix . ($this->page_number+1) .'/' . $this-> sortmode . '/' ?>"><?php echo $this->page_number+1 ?></a><?php } ?>
                <?php if ($total_pages - $this->page_number >= 2) { ?><a href="<?php echo $url_prefix . ($this->page_number+2) .'/' . $this-> sortmode . '/' ?>"><?php echo $this->page_number+2 ?></a><?php } ?>
                <?php if ($total_pages - $this->page_number >= 3) { ?><span class="ellipsis" href="#">&hellip;</span><a href="<?php echo $url_prefix . $total_pages ?>/"><?php echo $total_pages ?></a><?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php if ( $this->activities ) { ?>
    <ul class="row big-list big border">
    	<?php foreach ($this->activities as $activity) { 
			$timestamp = strtotime($activity['Date']);
		?>
        <li>
            <div class="calendar">
                <div class="month"><?php echo date('M', $timestamp) ?></div>
                <div class="date"><?php echo date('j', $timestamp) ?></div> 
                <div class="day"><?php echo date('D', $timestamp) ?></div>                               
            </div>
            <i class="<?php echo 'fa-' . $activity['Icon'] . ' color-'.$activity['Color']; ?>"></i>
            <?php if (isset($activity['Meta'])) { ?>
            <?php /*?><div class="meta">
            	<?php foreach ($activity['Meta'] as $meta) { ?>
                <div><?php echo $meta[0] ?><br><span><?php echo $meta[1] ?></span></div>
                <?php } ?>
            </div><?php */?>
            <?php } ?>
            <?php if ( $activity['ReputationChange'] !== 0 ) { ?>
            <div class="badge<?php echo $activity['ReputationChange'] < 0 ? ' red' : false ?>">
                <?php echo sprintf("%+d",$activity['ReputationChange']); ?> point<?php echo abs($activity['ReputationChange']) > 1 ? 's' : false  ?>
            </div>
            <?php } ?>
            <div class="main">
                <div><?php echo $activity['Title']; ?><br><span><?php echo $activity['Subtitle']; ?></span></div>
            </div>
        </li>
        <?php } ?>
    </ul>
    <?php } else { ?>
    <div class="row box">
    	<strong>No Activity</strong>
    </div>
    <?php } ?>
    <?php } else { ?>
    <div class="row header">
        <h3>User Activity (disabled)</h3>
        <h4>You have disabled activity logging. You can enable activity logging in your <a href="<?php echo URL ?>account/settings/#general">account settings</a>.</h4>
    </div>
    <?php } ?>
</div>