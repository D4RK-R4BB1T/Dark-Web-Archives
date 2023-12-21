<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Reports</title>
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
	<?php if ($this->commentReports) { ?>
	<h1>Reported Comments</h1>
	<table>
    	<thead>
        	<tr>
				<th>CommentID</th>
            	<th>Poster Alias</th>
                <th>Reports</th>
				<th>Content</th>
            </tr>
        </thead>
    	<tbody>
        	<?php foreach( $this->commentReports as $commentReport) { ?>
            <tr>
            	<td><a target="_blank" href="<?php echo $this->ForumURL . 'comment/' . $commentReport['ID'] . '/'; ?>"><?php echo $commentReport['ID']; ?></a></td>
				<td><a target="_blank" href="<?php echo URL . 'u/' . $commentReport['alias'] . '/'; ?>"><?php echo $commentReport['alias']; ?></a></td>
                <td><?php echo $commentReport['reportCount']; ?></td>
                <td>
					<textarea rows="10"><?php echo $commentReport['content']; ?></textarea>
				</td>
            </tr>
            </form>
            <?php } ?>
        </tbody>
    </table>
    <?php }
	if( $this->userReports ){ ?>
	<table>
    	<thead>
        	<tr>
				<th>Reported Alias</th>
            	<th>Reports</th>
				<th>Action</th>
            </tr>
        </thead>
    	<tbody>
        	<?php foreach( $this->userReports as $userReport) { ?>
            <tr>
            	<td><a target="_blank" href="<?php echo URL . 'u/' . $userReport['alias'] . '/'; ?>"><?php echo $userReport['alias']; ?></a></td>
                <td><?php echo $userReport['reportCount']; ?></td>
                <td>
					<form action="<?php echo URL . 'admin/ban_user/' . $userReport['ID'] . '/' ?>">
						<input type="submit" value="Ban">
					</form>
				</td>
            </tr>
            </form>
            <?php } ?>
        </tbody>
    </table>
	<?php } ?>
</body>
</html>