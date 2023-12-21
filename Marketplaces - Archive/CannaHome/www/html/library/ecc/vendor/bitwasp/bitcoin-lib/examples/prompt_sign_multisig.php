<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

while(!isset($redeem_script)) {
	echo "Enter redeem script: ";
	$line = trim(fgets(STDIN));
	$decode_redeem_script = RawTransaction::decode_redeem_script($line);
	if($decode_redeem_script == FALSE) {
		echo "[ERROR]- Not a valid script!\n";
		unset($decode_redeem_script);
	} else {
		$redeem_script = $line;
		echo "Learned about {$decode_redeem_script['m']} of {$decode_redeem_script['n']} address: ".BitcoinLib::public_key_to_address($redeem_script, '05')."\n";
	}
}

echo "Enter WIF encoded private keys: \n (1): ";
$private_keys = array();
while ("\n" != ($line = fgets(STDIN))) { 
		$line = trim($line);
		$t = BitcoinLib::validate_WIF($line,'80');
		var_dump($t);
        if(BitcoinLib::validate_WIF($line,'80') == TRUE){
			$private_keys[] = $line;
		} else {
			echo "Not a valid private key.\n";
		}
		echo " (".(count($private_keys)+1)."): ";
}

// Initialize wallet
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, $private_keys, '00');
RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script), '05');

$raw_transaction = '01000000018240b84b90a3ae326e13219dc8a2781661fa28e23129b26fea848dd8e01a0c520000000000ffffffff02d8270000000000001976a914b7119dfb9b5c8aa7157fec48fbe640ea347dc92b88ac584d0000000000001976a914592fb6dc8cf6cd561ec86fd5fbc2a140e5ac7bc988ac00000000'; 
$json = '[{"txid":"520c1ae0d88d84ea6fb22931e228fa611678a2c89d21136e32aea3904bb84082","vout":0,"scriptPubKey":"a914fb56f0d4845487cc9da00c3e91e63503245f151787","redeemScript":"52210385fae44cb9f0cf858e0d404baecf78d026fae4cc9dd4343b8562059473c2af7b2102f34a1b64155db258d3a910625bd80fae6adf67d7e5b5f3de03265a4208b552d841040fa9a86f3237237423dd8331dc481c0a949fe11594c5dfa0b54bdc105daa319f9de6547d97c22296d4211073e7cffa71c8d6cd4da639607ca64fca2705e562a353ae"}]';
$sign = RawTransaction::sign($wallet, $raw_transaction, $json);
print_r($sign);

