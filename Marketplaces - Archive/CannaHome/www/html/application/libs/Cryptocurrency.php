<?php

class Cryptocurrency {
	const SATOSHIS_PER_UNIT = 1e8;
	
	public $ID;
	public $ISO;
	public $name;
	public $XEUR;
	public $smallestIncrement;
	public $prefixPublic;
	public $prefixPrivate;
	public $prefixScriptHash;
	
	private $decimalPlaces;
	private $roundingCoefficient;
	
	function __construct(
		$ID,
		$ISO,
		$name,
		$XEUR,
		$decimalPlaces,
		$prefixPublic,
		$prefixPrivate,
		$prefixScriptHash,
		$bech32HRP
	){
		$this->ID = $ID;
		$this->ISO = $ISO;
		$this->name = $name;
		$this->XEUR = $XEUR;
		$this->decimalPlaces = $decimalPlaces;
		$this->prefixPublic = $prefixPublic;
		$this->prefixPrivate = $prefixPrivate;
		$this->prefixScriptHash = $prefixScriptHash;
		$this->bech32HRP = $bech32HRP;
		
		$this->roundingCoefficient = 10 ** $this->decimalPlaces;
		$this->smallestIncrement = 10 ** (-1 * $this->decimalPlaces);
	}
	
	public static function toSatoshis($value){
		return floor($value * self::SATOSHIS_PER_UNIT);
	}
	
	public static function fromSatoshis($satoshis){
		return $satoshis / self::SATOSHIS_PER_UNIT;
	}
	
	public function formatValue(
		$value,
		$appendISO = false,
		$zeroReplacement = false
	){
		if (
			$zeroReplacement &&
			$value == 0
		)
			return $zeroReplacement;
		
		$formattedValue = number_format($value, $this->decimalPlaces);
		if ($appendISO)
			$formattedValue = $this->appendISO($formattedValue);
		
		return $formattedValue;
	}
	
	public function convertPrice($euros){
		return $this->formatValue($euros * $this->XEUR);
	}
	
	public function formatPrice(
		$euros,
		$zeroReplacement = ZERO_PRICE_TEXTUAL_REPLACEMENT
	){
		$value = $this->convertPrice($euros);
		return $this->formatValue(
			$value,
			true,
			$zeroReplacement
		);
	}
	
	public function appendISO($value){
		return $value . ' ' . $this->ISO;
	}
	
	public function appendName(
		$value,
		$lowercase = true
	){
		$name = $lowercase ? strtolower($this->name) : $this->name;
		return $value . ' ' . $name;
	}
	
	public function parseValue(
		$value,
		$roundDown = false
	){
		return
			(
				$roundDown
					? floor($value * $this->roundingCoefficient)
					: ceil($value * $this->roundingCoefficient)
			) /
			$this->roundingCoefficient;
	}
	
	public function validateAddress($address){
		return	strlen($address) < 44 &&
			$this->validateAddressWithCoinbin($address) == "1";
	}
	
	private function validateAddressWithCoinbin($address){
		return	Coinbin::run(
				'validate_address.js',
				'validateAddress',
				[
					$this->prefixPublic,
					$this->prefixScriptHash,
					$this->bech32HRP,
					$address
				]
			);
	}
	
	public function bech32EncodePublicKey($publicKey){
		return	Coinbin::run(
				'bech32_encode.js',
				'bech32EncodePublicKey',
				[
					$this->bech32HRP,
					$publicKey
				]
			);
	}
	
	public function createMultisigAddress(
		$signaturesRequired,
		$publicKeys,
		$segwit = false
	){
		asort($publicKeys);
		$multisig = RawTransaction::create_multisig(
			$signaturesRequired,
			$publicKeys,
			$this->prefixScriptHash
		);
		
		if ($segwit)
			$multisig['address'] = $this->encodeRedeemscript($multisig['redeemScript'], true);
		
		return $multisig;
	}
	
	public function encodeRedeemscript($redeemScript, $segwit = false) {
		if ($segwit){
			$bs = @pack("H*", $redeemScript);
			return $this->encodeRedeemscript('00' . RawTransaction::pushdata(hash("sha256", $bs)));
		}
		
		return BitcoinLib::public_key_to_address($redeemScript, $this->prefixScriptHash);
	}
}
