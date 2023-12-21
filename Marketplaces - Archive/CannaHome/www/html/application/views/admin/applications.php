<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Approve Listings</title>
<style>
	table {
		width: 100%;
		border-collapse: collapse;
	}
	td {
		padding: 20px;
		border-width: 2px 0;	
	}
	
	tr {
		height: 100px;
	}
	
	tbody tr:nth-child(2n) {
		background: #F5F5F5;
	}
</style>
</head>

<body>
	<?php if ($this->applications) { ?>
	<table>
    	<thead>
        	<tr>
            	<th>Alias</th>
                <th>Endorsements</th>
				<th>Prior Applications</th>
                <th>Application</th>
                <th>Policy</th>
				<th>Action</th>
            </tr>
        </thead>
    	<tbody>
        	<?php foreach( $this->applications as $application) { ?>
            <tr>
            	<td><?php echo $application['alias'] ?></td>
                <td>
					<?php echo '<strong>' . $application['buyerEndorsements'] . '</strong> buyer endorsement' . (count($application['buyerEndorsements']) == 1 ?: 's'); ?><br>
					<?php echo '<strong>' . $application['vendorEndorsements'] . '</strong> vendor endorsement' . (count($application['buyerEndorsements']) == 1 ?: 's'); ?>
				</td>
				<td><?php echo $application['applicationAttempts']; ?></td>
                <td><?php echo $application['application'] ?></td>
                <td><?php echo $application['policy'] ?></td>
                <td>
                	<form action="<?php echo URL . 'admin/respond_application/' . $application['userID'] . '/'; ?>" method="post">
                        <select name="action">
                            <option value="approve">Approve</option>
                            <option value="reject">Reject</option>
							<option value="blacklist">Blacklist</option>
                        </select>
                        <button type="submit">Submit</button>
                    </form>
                </td>
            </tr>
            </form>
            <?php } ?>
        </tbody>
    </table>
    <?php } else { ?>
    <strong>No applications to review</strong>
    <?php } ?>
</body>
</html>