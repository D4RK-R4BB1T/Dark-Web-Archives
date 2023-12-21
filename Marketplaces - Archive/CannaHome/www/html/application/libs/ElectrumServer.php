<?php
class ElectrumServer {
	const TIMEOUT = ELECTRUM_SERVER_REQUEST_TIMEOUT;
	const RESPONSE_LENGTH = ELECTRUM_SERVER_REQUEST_RESPONSE_LENGTH;
	
	const FEE_ESTIMATES_SATOSHIS_CONVERSION = 1e8;
	const FEE_ESTIMATES_NEXT_BLOCK_COEFFICIENT = 1.5;
	const FEE_ESTIMATES_MINIMUM_FEE_SATOSHIS = 10000;
	
	public static function sendRequest(
		$serverAddress,
		$serverPort,
		$method,
		$params = [],
		$associative = false,
		$requestID = false,
		&$response = null
	){
		$requestID = $requestID ?: uniqid();
		$fp = &$_POST['electrumServer-' . $serverAddress . ':' . $serverPort];
		
		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		
		if (
			$fp = 	$fp
				/*?: pfsockopen(
					$serverAddress,
					$serverPort,
					$errno,
					$errstr,
					self::TIMEOUT
				)*/
				?: @stream_socket_client(
					$serverAddress . ':' . $serverPort,
					$errno,
					$errstr,
					self::TIMEOUT,
					STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
					$context
				)
		){
			stream_set_timeout($fp, self::TIMEOUT);
			$body =
				json_encode(
					[
						'id' => $requestID,
						'method' => $method,
						'params' => $params
					]
				) .
				PHP_EOL;
			fwrite(
				$fp,
				$body
			);
			
			if (
				!feof($fp) &&
				(
					$response = json_decode(
						fgets($fp),
						$associative
					)
				) &&
				(
					(
						$associative
							? $response['id']
							: $response->id
					) == $requestID
				)
			)
				return $associative ? $response['result'] : $response->result;
		}
		
		return false;
	}
	
	public static function estimateFee(
		$serverAddress,
		$serverPort,
		$feeLevel
	){
		return self::sendRequest(
			$serverAddress,
			$serverPort,
			'blockchain.estimatefee',
			[$feeLevel]
		);
	}
	
	public static function getBlockHeight(
		$serverAddress,
		$serverPort
	){
		if (
			(
				$response = self::sendRequest(
					$serverAddress,
					$serverPort,
					'blockchain.headers.subscribe'
				)
			) &&
			(
				is_numeric($response->block_height) ||
				is_numeric($response->height)
			)
		)
			return
				is_numeric($response->block_height)
					? $response->block_height
					: $response->height;
		
		return	self::sendRequest(
				$serverAddress,
				$serverPort,
				'blockchain.numblocks.subscribe'
			);
	}
	
	public static function broadcastTransaction(
		$serverAddress,
		$serverPort,
		$hex,
		&$result = null
	){
		if (
			$result = self::sendRequest(
				$serverAddress,
				$serverPort,
				'blockchain.transaction.broadcast',
				[$hex]
			)
		)
			return preg_match(REGEX_CRYPTOCURRENCY_TRANSACTION_HASH, $result) ?: false;
		
		return false;
	}
	
	public static function getAddressBalance(
		$serverAddress,
		$serverPort,
		$address,
		&$unconfirmedBalance = 0
	){
		if (
			(
				$result = self::sendRequest(
					$serverAddress,
					$serverPort,
					'blockchain.scripthash.get_balance',
					[NXS::addressToScriptHash($address)]
				)
			) ||
			(
				$result = self::sendRequest(
					$serverAddress,
					$serverPort,
					'blockchain.address.get_balance',
					[$address]
				)
			)
		){
			$unconfirmedBalance = (float) $result->unconfirmed / 1e8;
			$confirmedBalance = (float) $result->confirmed / 1e8;
			
			return $confirmedBalance;
		}
		
		return false;
	}
	
	public static function getAddressUnspentOutputs(
		$serverAddress,
		$serverPort,
		$address
	){
		if (
			$result = self::sendRequest(
				$serverAddress,
				$serverPort,
				'blockchain.scripthash.listunspent',
				[NXS::addressToScriptHash($address)],
				true
			)
		)
			return $result;
		
		return self::sendRequest(
			$serverAddress,
			$serverPort,
			'blockchain.address.listunspent',
			[$address],
			true
		);
	}
	
	public static function getAddressHistory(
		$serverAddress,
		$serverPort,
		$address
	){
		if (
			$result = self::sendRequest(
				$serverAddress,
				$serverPort,
				'blockchain.scripthash.get_history',
				[NXS::addressToScriptHash($address)],
				true
			)
		)
			return $result;
		
		return self::sendRequest(
			$serverAddress,
			$serverPort,
			'blockchain.address.get_history',
			[$address],
			true
		);
	}
	
	public static function getTransaction(
		$serverAddress,
		$serverPort,
		$transactionID,
		&$result = null
	){
		if (
			$result = self::sendRequest(
				$serverAddress,
				$serverPort,
				'blockchain.transaction.get',
				[$transactionID]
			)
		)
			return preg_match(REGEX_CRYPTOCURRENCY_RAW_TRANSACTION, $result) ? $result : false;
		
		return false;
	}
}
