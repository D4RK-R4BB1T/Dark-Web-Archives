<?php

return;

$verified_vendors = isset($_SESSION['catalog']['verified_vendors'] ) ? true : ($this->filterPrefs ? !empty($this->filterPrefs['verified_vendors']) : false);
	
$verified_vendors = ( isset($_SESSION['catalog']) && isset($_SESSION['catalog']['verified_vendors']) ) || (!isset($_SESSION['catalog']) && ($this->filterPrefs && !empty($this->filterPrefs['verified_vendors']) ) );

$ships_to = isset($_SESSION['catalog']['ships_to']) ? $_SESSION['catalog']['ships_to'] : ($this->filterPrefs ? $this->filterPrefs['ships_to'] : false);
$ships_from = isset($_SESSION['catalog']['ships_from']) ? $_SESSION['catalog']['ships_from'] : ($this->filterPrefs ? $this->filterPrefs['ships_from'] : false);
$save_preferred = isset($_SESSION['catalog']['save_preferences']);

$URLPrefix = URL . (isset($this->isSearch) && $this->isSearch ? 'search/listings/' : 'listings/') . $this->categoryID . '/';

?><div>
    <nav>
    	<div><a href="<?php echo URL . 'listings/' . ($this->parentCategoryAlias ? $this->parentCategoryAlias . '/' : false); ?>" <?php echo ($this->categoryID == 'all' ? 'class="active"' : false); ?>>All</a></div>
    	<?php foreach( $this->subCategories as $subCategory ) { ?>
        <div><a href="<?php echo URL . 'listings/' . $subCategory['alias'] . '/' . ($this->categoryID == $subCategory['id'] ? '" class="active' : false); ?>"><?php echo $subCategory['name'] ?></a></div>
        <?php } ?>
    </nav>
	<form method="post" action="<?php echo $URLPrefix . $this->sortMode . '/'; ?>">
		<div class="left">
			<label class="label">Sort by</label>
			<div class="big-dropdown"><?php 
				
				$sortModes = array(
					'rating' => 'Rating',
					'price_asc' => 'Cheapest',
					'price_desc' => 'Priciest'
				);
				
			?>
				<span><?php echo $sortModes[$this->sortMode]; unset($sortModes[$this->sortMode]); ?></span>
				<a class="toggle">More</a>
				<ul class="dropdown">
					<?php foreach( $sortModes as $key => $sortMode ) { ?>
					<li><a href="<?php echo $URLPrefix . $key . '/' ?>" class="dropdown-link"><?php echo $sortMode ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
		<div class="right">
			<label class="label">Filter</label>
			<?php if($this->Member) { ?><input type="hidden" name="save_preferences" value="1"><?php } ?>
			<input type="hidden" name="verified_vendors" id="verified_vendors" value="1">
			<label class="select">
				<select name="ships_from">
					<option value="0" <?php echo $ships_from==0 ? 'selected' : false ?>>- Ships from -</option>
					<?php foreach ($this->continents as $continent_id => $continent_array) { ?>
					<option value="cont_<?php echo $continent_id; ?>" <?php echo $ships_from=='cont_'.$continent_id ? 'selected' : false ?>>&emsp;<?php echo $continent_array[0]; ?></option>
					<?php if(isset($continent_array[1])) foreach ($continent_array[1] as $country) { ?>
					<option value="<?php echo $country[0]; ?>" <?php echo $ships_from==$country[0] ? 'selected' : false ?>>&emsp;&emsp;<?php echo $country[1]; ?></option>
					<?php } ?>
					<?php } ?>
				</select>
			</label>
			<label class="select">
				<select name="ships_to">
					<option value="0" <?php echo $ships_to==0 ? 'selected' : false ?>>- Ships to -</option>
					<?php foreach ($this->continents as $continent_id => $continent_array) { ?>
					<option value="cont_<?php echo $continent_id; ?>" <?php echo $ships_to=='cont_'.$continent_id ? 'selected' : false ?>>&emsp;<?php echo $continent_array[0]; ?></option>
					<?php if(isset($continent_array[1])) foreach ($continent_array[1] as $country) { ?>
					<option value="<?php echo $country[0]; ?>" <?php echo $ships_to==$country[0] ? 'selected' : false ?>>&emsp;&emsp;<?php echo $country[1]; ?></option>
					<?php } ?>
					<?php } ?>
				</select>
			</label>
			<button class="btn black" type="submit">Apply</button>
			<?php if( $ships_to || $ships_from ) { ?>
			<button class="btn red" name="reset_filter" type="submit">Reset</button>
			<?php } ?>
		</div>
	</form>
</div>