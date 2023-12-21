<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title><?=
	isset($this->aggregateData['Funds In Escrow'])
		? explode('<br>', $this->aggregateData['Funds In Escrow'])[2] . ' In Escrow'
		: $this->aggregateData['Users Online'] . ' users online';
?></title>
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
	
	.menu > a {
		display: block;
	}
</style>
</head>

<body>
	<label><input id="auto_reload" type="checkbox"> Enable Auto-Reload</label>
	<h1 id="top">Admin Dashboard (<?= $this->loadTime; ?>)</h1>
	<table>
		<tbody>
			<?php foreach( $this->aggregateData as $datum => $value) { ?>
			<tr>
				<td><?php echo $datum ?></td>
				<td><?php echo $value ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<h2>Graphs</h2>
	<img src="/admin/stacked_graph/">
	<img src="/admin/show_graph/users_online/">
	<h2>Tabular Data</h2>
	<div class="menu">
	<?php 
	foreach($this->tabularData as $datum => $table){
		$ID = str_replace(' ', '_', strtolower($datum));
	
		echo "<a href='#" . $ID . "'>" . $datum . "</a> ";
	}
	foreach($this->tabularData as $datum => $table){
		$ID = str_replace(' ', '_', strtolower($datum));
	?>
	</div>
	<h3 id="<?php echo $ID; ?>"><?php echo $datum; ?> <a href="#top">(go to top)</a></h3>
	<table>
		<thead>
			<tr>
				<?php foreach( $table[0] as $column => $value ){ ?>
				<th><?php echo $column; ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach($table as $row){ ?>
			<tr>
				<td><?php echo rtrim(implode('</td><td>', $row),'<td>'); ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } ?>
<script>
var refresh_rate = <?php echo ADMIN_ANALYTICS_AUTO_REFRESH_SECONDS; ?>;
var last_user_action = 0;
var has_focus = false;
var lost_focus_count = 0;
var focus_margin = 10; // If we lose focus more then the margin we want to refresh


function reset() {
    last_user_action = 0;
    console.log("Reset");
}

function windowHasFocus() {
    has_focus = true;
}

function windowLostFocus() {
    has_focus = false;
    lost_focus_count++;
    console.log(lost_focus_count + " <~ Lost Focus");
}

setInterval(function () {
    last_user_action++;
    refreshCheck();
}, 1000);

function refreshCheck() {
	var focus = window.onfocus;
	if (
		(
			document.getElementById('auto_reload').checked &&
			last_user_action >= refresh_rate &&
			!has_focus &&
			document.readyState == "complete"
		)
	) {
		window.location.reload(); // If this is called no reset is needed
		reset(); // We want to reset just to make sure the location reload is not called.
	}
}
window.addEventListener("focus", windowHasFocus, false);
window.addEventListener("blur", windowLostFocus, false);
window.addEventListener("click", reset, false);
window.addEventListener("mousemove", reset, false);
window.addEventListener("keypress", reset, false);
</script>
</body>
</html>
