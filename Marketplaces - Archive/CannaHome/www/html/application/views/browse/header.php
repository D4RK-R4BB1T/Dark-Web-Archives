<?php
	$url = $this->AccessPrefix ? 'http://' . $this->AccessPrefix . '.' . substr(URL, 7) : URL;
	if(isset($this->vendorAlias))
		$profile_url = URL . 'v/' .$this->vendorAlias . '/';
?>
