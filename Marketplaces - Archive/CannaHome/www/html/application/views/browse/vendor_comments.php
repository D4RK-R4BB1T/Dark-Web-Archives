<div class="rows-30">
	<h2 class="row band">
		<span><?php echo $this->vendorAlias; ?></span>
		<div><a href="<?php echo $profile_url; ?>" class="btn arrow-left">Back to Profile</a></div>
	</h2>
	<?php if ($this->ratingBreakdown){ ?>
	<div class="row grey-box">
		<div class="cols-30">
			<div class="col-6 rows-20">
				<h5 class="row band bigger"><span><strong>Ratings Summary</strong></span></h5>
				<table class="row horizontal-bar-chart">
					<tbody>
						<?php foreach ($this->ratingBreakdown as $rating => $ratingPercentage){ ?>
						<tr>
							<td>
								<div class="rating stars color-yellow">
									<?php $this->renderRating($rating); ?>
								</div>
							</td>
							<td>
								<b style="width:<?= $ratingPercentage; ?>%"></b>
							</td>
							<td><?=	$ratingPercentage > 0 &&
								$ratingPercentage < 1
									? '< 1'
									: NXS::formatDecimal($ratingPercentage, 0); ?>%</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php if ($this->ratingAttributeBreakdown){ ?>
			<div class="col-6 rows-20">
				<h5 class="row band bigger"><span><strong>Buyers Particularly Liked</strong></span></h5>
				<table class="row horizontal-bar-chart">
					<tbody>
						<?php foreach ($this->ratingAttributeBreakdown as $ratingAttribute => $ratingPercentage){ ?>
						<tr>
							<td><?= $ratingAttribute; ?></td>
							<td>
								<b style="width:<?= $ratingPercentage; ?>%"></b>
							</td>
							<td><?=	$ratingPercentage > 0 &&
								$ratingPercentage < 1
									? '< 1'
									: NXS::formatDecimal($ratingPercentage, 0); ?>%</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
	<ul class="row list-ratings<?= count($this->comments) > 1 ? ' columns' : false; ?>">
		<?php foreach( $this->comments as $comment ) { ?>
		<li>
			<div class="left">
				<div class="rating stars color-yellow">
					<?php $this->renderRating($comment['rating']); ?>
				</div>
				<date><?php echo $comment['date']; ?></date>
				<?php if( $comment['listing'] ) { ?>
				<small><?php 
					if($comment['b36'])
						echo '<a target="_blank" href="' . URL . 'i/' . $comment['b36'] . '/">';
					echo $comment['listing'];
					if($comment['b36'])
						echo '</a>';
				?></small>
				<?php } ?>
			</div>
			<div class="right formatted">
				<?php echo $this->nl2p($comment['content']); ?>
			</div>
		</li>
		<?php } ?>
	</ul>
	<?php if( $this->commentCount > REVIEWS_PER_PAGE ) { ?>
	<div class="row panel">
		<?php 
			$this->renderPaginationPanel(
				$this->pageNumber,
				ceil($this->commentCount / REVIEWS_PER_PAGE),
				$profile_url . 'comments/'
			);
		?>
	</div>
	<?php } ?>
</div>
