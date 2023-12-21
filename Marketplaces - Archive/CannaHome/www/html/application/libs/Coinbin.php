<?php
class Coinbin {
	public static function run(
		$file,
		$method,
		$arguments,
		$json = false
	){
		$nodeJS = new NodeJS();
		
		ob_start();
		echo
			'(function(window) {' .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/crypto-min.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/crypto-sha256.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/crypto-sha256-hmac.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/ripemd160.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/aes.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/jsbn.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/ellipticcurve.js') .
			file_get_contents(LIBRARY_PATH . 'coinbin/js/coin.min.js');
		require(COINBIN_PATH . $file);
		echo '})(typeof window == "undefined" ? global : window);';
		$js = ob_get_clean();
		
		if (
			$result = $nodeJS->run(
				$js,
				$method,
				$arguments
			)
		){
			if ($json)
				return json_decode($result);
				
			$result = explode(
				',',
				str_replace(
					PHP_EOL,
					'',
					$result
				)
			);
			
			return	count($result) == 1
					? $result[0]
					: $result;
		}
				
		return false;
	}
}
