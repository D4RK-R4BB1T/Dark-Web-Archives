<ul>
	<li<?php echo $this->checkForActiveAction($filename, 'listings') ? ' class="active"' : false ?>><a href="<?php echo URL . 'account/listings/' ?>">Listings</a></li>
	<li<?php echo $this->checkForActiveAction($filename, 'profile') ? ' class="active"' : false ?>><a href="<?php echo URL . 'account/profile/' ?>">Profile</a></li>
</ul>