<?php
class Upload {
	function __call($filename, $arguments){
		require('browse.php');
		
		$browse = new Browse();
		return 	call_user_func_array(
				[
					$browse,
					'upload'
				],
				[$filename]
			);
	}
}
