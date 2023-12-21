<div class="rows-20">
	<div class="row cols-5">
		<div class="col-10" style="line-height: 35px">
			<?php 
			$tableHueRotate = false;
			foreach ($this->userQueries as $i => $userQuery){
				$hueRotate = false;
				if ($i > 0){
					$hueRotate = NXS::partitionNumber($i);
					
					if ($userQuery['Title'] == $this->title)
						$tableHueRotate = $hueRotate;
				}
			?>
			<a<?= $hueRotate ? ' style="filter:hue-rotate(' . $hueRotate . 'deg)"' : false; ?> class="btn" href="<?= URL . 'account/statistics/' . $userQuery['Identifier'] . '/'; ?>"><?= $userQuery['Title']; ?></a>
			<?php } ?>
		</div>
		<div class="col-2 align-right">
			<a href="<?= URL . 'account/export_statistics/' . $this->queryIdentifier . '/' ?>" class="btn minimal">Export CSV</a>
		</div>
	</div>
	<hr>
	<div class="row rows-10">
		<strong class="row"><?= $this->title; ?></strong>
		<?php if ($this->results){ ?>
		<table class="row cool-table">
			<thead<?= $tableHueRotate ? ' style="filter:hue-rotate(' . $tableHueRotate . 'deg)"' : false; ?>>
				<tr>
					<?php foreach ($this->results[0] as $key => $value){ ?>
					<th><?= $key; ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->results as $row){ ?>
				<tr>
					<?php foreach ($row as $value){ ?>
					<td><?= $value; ?></td>
					<?php } ?>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php } else { ?>
		<div class="row">
			<img src="<?= URL . 'chart/' . $this->queryIdentifier . '/'; ?>">
		</div>
		<?php } ?>
	</div>
</div>
