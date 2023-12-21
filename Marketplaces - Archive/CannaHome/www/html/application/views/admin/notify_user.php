<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Notify User</title>

</head>

<body>
	<h3>Please explain to the user, why his/her listing has been rejected</h3>
	<form method="post" action="<?php echo URL . 'admin/notify_user/' ?>">
    	<input type="hidden" name="user_id" value="<?php echo $this->recipient_id ?>" />
    	<textarea name="message" rows="10">[b]Your listing "<?php echo $this->listing_title ?>" has been rejected.[/b]


If you have any objections to this decision, please respond to this message.</textarea>
        <input type="submit" />
    </form>
</body>
</html>