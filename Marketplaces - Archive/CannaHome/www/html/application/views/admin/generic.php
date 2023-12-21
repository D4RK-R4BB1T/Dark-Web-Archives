<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title><?= $this->title ?></title>
<style>
	body {
		font-family: sans-serif;
		font-weight: 100;
	}
	
	table {
		width: 100%;
		border-collapse: collapse;
	}
	td, th {
		padding: 10px;
		border-width: 2px 0;
		line-height: 1.3;
	}
	th {
		text-align: left;
		font-size: 20px;
	}
	/*tr {
		height: 100px;
	}*/
	
	tbody tr:nth-child(2n) {
		background: #F5F5F5;
	}
</style>
</head>

<body>
	<h1 id="top"><?= $this->title; ?></h1>
	<table>
		<thead>
			<tr>
				<?php foreach ($this->results[0] as $column => $value){ ?>
				<th><?php echo $column; ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach($this->results as $row){ ?>
			<tr>
				<td><?php echo rtrim(implode('</td><td>', $row),'<td>'); ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</body>
</html>
