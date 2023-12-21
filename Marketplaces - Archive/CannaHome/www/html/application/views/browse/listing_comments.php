<div class="rows-30">
	<h2 class="row band">
		<span><?php echo $this->listingName; ?></span>
		<div><a href="<?php echo $url . 'i/' . $this->listingB36 . '/'; ?>" class="btn arrow-left">Back to Listing</a></div>
	</h2>
	<ul class="row list-ratings<?= count($this->comments) > 1 ? ' columns' : false; ?>">
		<?php foreach ($this->comments as $comment) { ?>
		<li>
			<div class="left">
				<div class="rating stars color-yellow">
					<?php $this->renderRating($comment['rating']); ?>
				</div>
				<date><?php echo $comment['date']; ?></date>
			</div>
			<div class="right formatted">
				<?php echo $this->nl2p($comment['content']); ?>
			</div>
		</li>
		<?php } ?>
	</ul>
	<?php if ($this->pageNumber > 1 || $this->commentCount > REVIEWS_PER_PAGE) { ?>
	<div class="row panel">
		<?php 
			$this->renderPaginationPanel(
				$this->pageNumber,
				ceil($this->commentCount / REVIEWS_PER_PAGE),
				$url . 'i/' . $this->listingB36 . '/comments/'
			);
		?>
	</div>
	<?php } ?>
</div>
