<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Resolve Disputes</title>
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
	<?php if ($this->disputes) { ?>
	<table>
    	<thead>
        	<tr>
            	<th>Listing</th>
                <th>Vendor</th>
                <th>Buyer</th>
                <th>BTC</th>
                <th>Timeout</th>
                <th>Status</th>
            </tr>
        </thead>
    	<tbody>
        	<?php foreach( $this->disputes as $dispute ) { ?>
        	<tr>
            	<td><a target="_blank" href="<?php echo URL . 'listing/' . $dispute['listing_id'] . '/' ?>"><?php echo $dispute['listing_name'] ?> <span>(<?php echo $dispute['listing_category'] ?>)</span></a></td>
                <td><a target="_blank" href="<?php echo URL . 'v/' . strtolower($dispute['vendor_alias']) . '/' ?>"><?php echo $dispute['vendor_alias'] ?></a></td>
                <td><a target="_blank" href="<?php echo URL . 'u/' . strtolower($dispute['buyer_alias']) . '/' ?>"><?php echo $dispute['buyer_alias'] ?></a></td>
                <td><?php echo $dispute['value'] ?></td>
                <td><?php echo $dispute['timeout'] ?></td>
                <?php if($dispute['is_mediator']) { ?>
                <td>In progress (<a href="<?php echo URL . 'tx/' . $dispute['id'] . '/dispute/'; ?>">View</a>)</td>
                <?php } else { ?>
                <td>Not started (<a href="<?php echo URL . 'admin/start_mediation/' . $dispute['id'] . '/'; ?>">Start</a>)</td>
                <?php } ?>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php } else { ?>
    <strong>No disputed transactions to mediate</strong>
    <?php } ?>
</body>
</html>
