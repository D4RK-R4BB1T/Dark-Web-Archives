<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Invite Codes</title>
<style>
	table {
		width: 100%;
		border-collapse: collapse;
	}
	td {
		padding: 20px;
		border-width: 2px 0;	
	}
	
	tbody tr:nth-child(2n) {
		background: #F5F5F5;
	}
	
	tr {
		height: 100px;
	}
	
</style>
</head>

<body>
	<form method="post" action="<?php echo URL . 'admin/generate_invite_codes/' ?>">
		<?php if ($this->invites) { 
		foreach( $this->invites as $group => $invites ) { ?>
		<label><?php echo $group; ?></label>
		<fieldset>
			<textarea rows="<?php echo count($invites); ?>"><?php echo implode(PHP_EOL, $invites); ?></textarea>
		</fieldset>
		<?php }
		} ?>
		<fieldset>
			<label>Generate Codes</label>
			<input type="text" name="quantity" placeholder="quantity">
			<select name="type">
				<option value="market">Market</option>
				<option value="buyer">Buyer</option>
			</select>
			<input type="submit">
		</fieldset>
	</form>
</body>
</html>
