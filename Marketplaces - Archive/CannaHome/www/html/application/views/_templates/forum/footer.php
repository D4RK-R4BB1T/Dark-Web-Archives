<?php if( file_exists(VIEWS_PATH . $folder . '/footer.php') ) require VIEWS_PATH . $folder . '/footer.php'; ?>
		</section>
		<footer><?php /*
			<div>
				<div class="cols-30">
					<div class="col-9">
						<div class="links">
							<?php if($this->FooterPages) foreach( $this->FooterPages as $footer_page ){ 
								if( isset($footer_page['for']) ) { ?>
							<label for="<?php echo $footer_page['for']; ?>"><?php echo $footer_page['title'] ?></label>
							<?php } else { ?>
							<a href="<?php echo $footer_page['url'] ?>" target="<?php echo $footer_page['target'] ?>"><?php echo $footer_page['title'] ?></a>
							<?php } } ?>
						</div>
					</div>
					<div class="col-3 logo">
						<?php echo $this->SiteLogo; ?>
					</div>
				</div>
			</div>
		*/ ?></footer>
	</div>
</body>
</html>
