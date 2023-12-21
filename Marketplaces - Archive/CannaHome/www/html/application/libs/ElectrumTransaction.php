<?php

class ElectrumTransaction {
	protected $cryptocurrency;
	protected $redeemScript;
	protected $inputs;
	protected $outputs;
	protected $locktime = 0;
	protected $sequence = 4294967293;

	protected $extendedPublicKeys = [];
	protected $signatureCount;
	protected $requiredSignatureCount;
	protected $decodedRedeemscript;
	protected $multisigAddress;
	protected $isSegwit = false;
	
	function __construct(
		$cryptocurrency,
		$redeemScript,
		$inputs,
		$outputs,
		bool $segwit = false
	){
		$this->cryptocurrency = $cryptocurrency;
		$this->redeemScript = $redeemScript;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
		$this->isSegwit = $segwit;
	}
	
	private static function getArgumentString($arguments, $argumentString = ''){
		if (!is_array($arguments))
			$argumentString .= ' ' . escapeshellarg($arguments);
		elseif ($arguments){
			$argument = array_shift($arguments);
			return self::getArgumentString($arguments, self::getArgumentString($argument, $argumentString));
		}
		
		return $argumentString;
	}
	
	private static function runCommand($command, $arguments){
		return shell_exec('electrum ' . $command . self::getArgumentString($arguments));
	}
	
	public static function encodeExtendedPublicKey($extendedPublicKey, $index, $accountIndex = 0){
		return	'ff' .
			substr(BitcoinLib::base58_decode($extendedPublicKey), 0, -8) .
			RawTransaction::_flip_byte_order(BitcoinLib::padHex(BitcoinLib::hex_encode($accountIndex), 4)) .
			RawTransaction::_flip_byte_order(BitcoinLib::padHex(BitcoinLib::hex_encode($index), 4));
	}
	
	public function addExtendedPublicKey($extendedPublicKey, $index = false, $accountIndex = 0){
		if (is_array($extendedPublicKey))
			foreach ($extendedPublicKey as $parameterArray)
				$this->addExtendedPublicKey(...$parameterArray);
		else
			$this->extendedPublicKeys[] = [$extendedPublicKey, $index, $accountIndex];
		
		return $this;
	}
	
	public function getRedeemScript(){
		return $this->redeemScript;
	}
	
	public function addSignature($signatures){
		if (is_array($signatures))
			foreach ($signatures as $signature)
				$this->addSignature($signature);
		else
			$this->signatures[] = $signatures;
		
		return $this;
	}
	
	public function addLocktime($locktime){
		$this->locktime = $locktime;
		
		return $this;
	}
	
	private function parseInputs(){
		return	array_map(
				function ($input){
					$newInput = [
						'address'	=> $this->getMultisigAddress(),
						'num_sig'	=> (int) $this->getRequiredSignatureCount(),
						'prevout_hash'	=> $input['txid'],
						'prevout_n'	=> $input['vout'],
						'pubkeys'	=> $this->getPlainPublicKeys(),
						'redeem_script'	=> $this->redeemScript,
						'sequence'	=> $this->sequence,
						'signatures'	=> $input['signatures'] ?? array_fill(0, $this->getSignatureCount(), null),
						'type'		=> $this->isSegwit ? 'p2wsh-p2sh' : 'p2sh',
						'x_pubkeys'	=> $this->getExtendedPublicKeys()
					];
					
					if ($this->isSegwit)
						$newInput = array_merge(
							$newInput,
							[
								'witness' => null,
								'witness_version' => 0,
								'value'	=> $input['value']
							]
						);
						
					return $newInput;
				},
				$this->inputs
			);
	}
	
	private function parseOutputs(){
		$outputIterator = 0;
		return	array_map(
				function ($address, $value) use (&$outputIterator) {
					return [
						'address'	=> $address,
						'prevout_n'	=> $outputIterator++,
						'scriptPubKey'	=> '',
						'type'		=> 0,
						'value'		=> Cryptocurrency::toSatoshis($value)
					];
				},
				array_keys($this->outputs),
				$this->outputs
			);
	}
	
	private function getDecodedRedeemScript(){
		return $this->decodedRedeemscript ?? $this->decodedRedeemscript = RawTransaction::decode_redeem_script($this->redeemScript);
	}
	
	private function getSignatureCount(){
		return $this->signatureCount ?? $this->signatureCount = $this->getDecodedRedeemScript()['n'];
	}
	
	private function getRequiredSignatureCount(){
		return $this->requiredSignatureCount ?? $this->requiredSignatureCount = $this->getDecodedRedeemScript()['m'];
	}
	
	private function getPlainPublicKeys(){
		return $this->getDecodedRedeemScript()['keys'];
	}
	
	private function getMultisigAddress(){
		return $this->multisigAddress ?? $this->multisigAddress = $this->cryptocurrency->encodeRedeemscript($this->redeemScript);
	}
	
	private function getExtendedPublicKeys(){
		return	!$this->extendedPublicKeys
			? $this->getPlainPublicKeys()
			: array_map(
				function ($publicKey) {
					if (
						$extendedPublicKeys = array_filter(
							$this->extendedPublicKeys,
							function ($extendedPublicKeyArray) use ($publicKey) {
								list(
									$extendedPublicKey,
									$keyIndex,
									$accountIndex
								) = $extendedPublicKeyArray;
							
								return	NXS::deriveBIP32PublicKey(
										$keyIndex,
										$extendedPublicKey,
										$accountIndex . '/'
									) == $publicKey;
							}
						)
					){
						return 	self::encodeExtendedPublicKey(...array_pop($extendedPublicKeys));
					}
						
					return $publicKey;
				},
				$this->getPlainPublicKeys()
			);
	}
	
	private function jsonEncode(){
		return json_encode([
			'inputs'	=> $this->parseInputs(),
			'lockTime'	=> $this->locktime,
			'outputs'	=> $this->parseOutputs(),
			'partial'	=> true,
			'segwit_ser'	=> $this->isSegwit,
			'version'	=> 2
		]);
	}

	public function serialize(){
		if ($result = json_decode(self::runCommand('serialize', $this->jsonEncode())))
			return $result->hex;
		
		return false;
	}
	
	public static function deserialize($cryptocurrency, $rawTransaction){
		if (
			$deserializedTransaction = json_decode(self::runCommand(
				'deserialize',
				$rawTransaction
			), true)
		){
			$inputs = array_map(
				function ($input){
					return [
						'txid' => $input['prevout_hash'],
						'vout' => $input['prevout_n'],
						'signatures' => $input['signatures']
					];
				},
				$deserializedTransaction['inputs']
			);
			
			$outputs = [];
			foreach ($deserializedTransaction['outputs'] as $output)
				$outputs[$output['address']] = Cryptocurrency::fromSatoshis($output['value']);
			
			return new self (
				$cryptocurrency,
				$deserializedTransaction['inputs'][0]['redeem_script'] ?? $deserializedTransaction['inputs'][0]['witness_script'],
				$inputs,
				$outputs,
				$deserializedTransaction['segwit_ser']
			);
		}
			
		throw new Exception('Could not deserialize transaction');
	}
	
	public function sign($privateKey){
		return self::signTransaction($this->cryptocurrency, $this->serialize(), $privateKey);
	}
	
	public static function signTransaction($cryptocurrency, $transaction, $privateKey){
		if (
			$result = json_decode(
				self::runCommand(
					'signtransaction',
					[
						$transaction,
						'--privkey',
						$privateKey
					]
				)
			)
		)
			return $result->hex;
		
		return	false;
	}
	
	/**
	public static function isSigned($rawTransaction){
		if (
			$deserializedTransaction = json_decode(self::runCommand(
				'deserialize',
				$rawTransaction
			), true)
		)
			return 	count(array_filter(
					$deserializedTransaction['inputs'],
					function ($input){
						return	count(array_filter(
							$input['signatures'],
							function ($signature){
								return !is_null($signature);
							}
						)) == $input['num_sig'];
					}
				)) == count($deserializedTransaction['inputs']);
		
		return false;
	}*/
	
	public function isSigned($signatures = null){
		$requiredSignatures = $signatures ?? $this->getRequiredSignatureCount();
		return count(array_filter(
				$this->inputs,
				function ($input) use ($requiredSignatures){
					return	count(array_filter(
						$input['signatures'],
						function ($signature){
							return !is_null($signature);
						}
					)) == $requiredSignatures;
				}
			)) == count($this->inputs);
	}
}
