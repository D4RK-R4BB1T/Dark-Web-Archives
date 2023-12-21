<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\Electrum;

require_once(__DIR__. '/../vendor/autoload.php');

$magic_byte = '00';
$string = trim('teach start paradise collect blade chill gay childhood creek picture creator branch');

$seed = Electrum::decode_mnemonic($string);
echo "Words: $string\n";
echo "Seed:  $seed\n";

$secexp = Electrum::stretch_seed($seed);
$secexp = $secexp['seed'];
echo "Secret Exponent: $secexp\n";

$mpk = Electrum::generate_mpk($seed);
echo "MPK: $mpk\n";
for($i = 0; $i < 5; $i++) {
	$privkey = Electrum::generate_private_key($secexp, $i, 0);
	echo "Private key: $privkey\n";
	echo "Private WIF: ".BitcoinLib::private_key_to_WIF($privkey, FALSE, $magic_byte)."\n";

	$public_key = Electrum::public_key_from_mpk($mpk, $i);
	echo "Public Key: $public_key\n";
	$address = BitcoinLib::public_key_to_address($public_key, $magic_byte);
	echo "Public derivation: $address.\n";
	$address = BitcoinLib::private_key_to_address($privkey, $magic_byte);
	echo "Private derivation: $address.\n";
	echo "-----------\n";
}


