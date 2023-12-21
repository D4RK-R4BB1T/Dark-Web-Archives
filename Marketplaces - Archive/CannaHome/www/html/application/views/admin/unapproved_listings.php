<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
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
	
	table tbody tr td:nth-child(3) {
		background-size: cover;
		width: 200px;
	}
</style>
</head>

<body>
	<?php if ($this->listings) { ?>
	<table>
    	<thead>
        	<tr>
            	<th>Category</th>
                <th>Vendor</th>
                <th>Image</th>
                <th>Title</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
    	<tbody>
        	<?php foreach( $this->listings as $listing) { ?>
            <tr>
            	<td><?php echo $listing['category'] ?></td>
                <td><a href="<?php echo URL . 'vendor/' . $listing['vendor'] ?>" target="_blank"><?php echo $listing['vendor'] ?></a></td>
                <td style="background-image:url(<?php $listing['image'] ?>)"></td>
                <td><?php echo $listing['title'] ?></td>
                <td><?php echo $listing['description'] ?></td>
                <td>
                	<form action="<?php echo URL.'admin/update_listing/' ?>" method="post">
                        <select name="action">
                            <option value="1">Approve</option>
                            <option value="-1">Reject</option>
                        </select>
                        <button name="info" value="<?php echo $listing['vendor_id'] . '-' . $listing['id'] . '-' . $listing['title'] ?>" type="submit">Submit</button>
                    </form>
                </td>
            </tr>
            </form>
            <?php } ?>
        </tbody>
    </table>
    <?php } else { ?>
    <strong>No listings to review</strong>
    <?php } ?>
</body>
</html>