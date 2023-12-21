<?php
class ElectrumDaemon {
	private static function sendRequest(
		$cryptocurrencyID,
		$method,
		$params = [],
		$requestID = false,
		$associative = false,
		&$fullResponse = null
	){
		switch($cryptocurrencyID){
			case CURRENCY_ID_BTC:
				$address = ELECTRUM_DAEMON_RPC_ADDRESS;
				$port = ELECTRUM_DAEMON_RPC_PORT;
			break;
			case CURRENCY_ID_LTC:
				$address = ELECTRUM_LTC_DAEMON_RPC_ADDRESS;
				$port = ELECTRUM_LTC_DAEMON_RPC_PORT;
			break;
		}
		
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			[
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => 'http://user:4Mdsm93JQ45Sf3PHaQlIFw==@' . $address . ':' . $port,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => json_encode(
					[
						'id' => $requestID ?: uniqid(),
						'method' => $method,
						'params' => $params
					]
				),
				CURLOPT_TIMEOUT => ELECTRUM_DAEMON_REQUEST_TIMEOUT
			]
		);
		
		$response = curl_exec($curl);
		curl_close($curl);
		
		if (
			$response &&
			$fullResponse = json_decode($response, $associative)
		)
			return $associative ? $fullResponse['result'] : $fullResponse->result;
		
		return false;
	}
	
	public static function getTransaction(
		$cryptocurrencyID,
		$transactionHash
	){
		if (
			$result = self::sendRequest(
				$cryptocurrencyID,
				'gettransaction',
				[$transactionHash]
			)
		)
			return $result->hex;
		
		return false;
	}
	
	public static function getWalletBalance(
		$cryptocurrencyID,
		&$unconfirmedBalance = 0
	){
		if (
			$result = self::sendRequest(
				$cryptocurrencyID,
				'getbalance'
			)
		){
			if (isset($result->unconfirmed))
				$unconfirmedBalance = $result->unconfirmed;
		
			return $result->confirmed;
		}
		
		return false;
	}
	
	public static function deserialize(
		$cryptocurrencyID,
		$rawTransaction
	){
		return self::sendRequest(
			$cryptocurrencyID,
			'deserialize',
			[$rawTransaction],
			false,
			true
		);
	}
	
	public static function getAddressHistory(
		$cryptocurrencyID,
		$address
	){
		return self::sendRequest(
			$cryptocurrencyID,
			'getaddresshistory',
			[$address],
			false,
			true
		);
	}
	
	public static function getAddressBalance(
		$cryptocurrencyID,
		$address,
		&$unconfirmedBalance = 0
	){
		if (
			$result = self::sendRequest(
				$cryptocurrencyID,
				'getaddressbalance',
				[$address]
			)
		){
			$unconfirmedBalance = (float) $result->unconfirmed;
			$confirmedBalance = (float) $result->confirmed;
		
			return $confirmedBalance;
		}
		
		return false;
	}
	
	public static function getAddressUnspentOutputs(
		$cryptocurrencyID,
		$address
	){
		return self::sendRequest(
			$cryptocurrencyID,
			'getaddressunspent',
			[$address],
			false,
			true
		);
	}
	
	public static function broadcastTransaction(
		$cryptocurrencyID,
		$hex,
		&$response = null
	){
		$response = self::sendRequest(
			$cryptocurrencyID,
			'broadcast',
			[$hex]
		);
		
		return
			$response &&
			preg_match(REGEX_CRYPTOCURRENCY_TRANSACTION_HASH, $response[1]);
	}
}
