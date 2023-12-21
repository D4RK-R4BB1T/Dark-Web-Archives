<?php

use BitWasp\BitcoinLib\BitcoinLib;

require_once(__DIR__. '/../vendor/autoload.php');;

$magic_byte = '00';

$keypair = BitcoinLib::get_new_key_set($magic_byte);
echo "Key pair: \n";print_r($keypair); echo "\n";

$compress = BitcoinLib::compress_public_key($keypair['pubKey']);
echo "Compressed public key: $compress \n";
$decompress = BitcoinLib::decompress_public_key($compress);
echo "Decompressed key info: \n";
print_r($decompress);

echo "\n";
$address = BitcoinLib::public_key_to_address($compress, $magic_byte);

echo "decoding $address\n";
echo BitcoinLib::base58_decode($address);
echo "\n\n";

$sc = '5357';
$ad = BitcoinLib::public_key_to_address($sc, '05');
echo $ad."\n";
