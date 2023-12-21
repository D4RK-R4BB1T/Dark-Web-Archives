<div class="side-tabs wider">
	<nav>
		<?php foreach( $this->categories as $category ){ ?>
		<a href="<?php echo URL.'faq/'.($category['alias'] ? $category['alias'] : $category['id']).'/' . ($this->categoryID == $category['id'] ? '" class="active' : false) ?>">
			<i class="<?php echo $category['icon']; ?>"></i>
			<?php echo ucfirst($category['name']); ?>
		</a>
		<?php } ?>
	</nav>
	<div>
		<?php if ($this->CategoryID) { ?>
		<div class="row header">
			<h3><?php echo $this->FAQs['category']['name']; ?></h3>
			<h4><?php echo $this->FAQs['category']['description']; ?></h4>
		</div>
		<?php }
		if( count($this->FAQs['faqs']) > 0 ){ 
		
		$isSingular = count($this->FAQs['faqs']) == 1;
		
		?>
		<ul class="row list-expandable label-fill">
			<?php foreach ($this->FAQs['faqs'] as $faq) { ?>
			<li>
				<span class="anchor" id="<?php echo $faq['id'] ?>"></span>
				<input id="faq-<?php echo $faq['id'] ?>" class="expand" type="checkbox"<?php echo $isSingular ? ' checked' : FALSE; ?>>
				<label for="faq-<?php echo $faq['id'] ?>"><?php echo $faq['title'] ?><i></i></label>
				<div class="expandable rows-15">                            
					<div class="row formatted">
						<?php echo $faq['content'] ?>
					</div>
				</div>
			</li>
			<?php } ?>
		</ul>
		<?php } ?>
	</div>
</div>
