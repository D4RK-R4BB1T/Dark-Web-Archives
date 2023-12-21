<div class="special-tabs">
	<div class="left rows-30">
		<nav class="row">
			<?php foreach ($this->pages as $page) { ?>
			<a <?php echo $this->current_page == $page['id'] || $this->current_page == $page['alias'] ? 'class="active"' : 'href="' .URL.'p/'.(empty($page['alias']) ? $page['id'] : $page['alias']).'/' . '"' ?>><?php echo $page['title'] ?></a>
			<?php } ?>
		</nav>
	</div>
	<div class="right">
