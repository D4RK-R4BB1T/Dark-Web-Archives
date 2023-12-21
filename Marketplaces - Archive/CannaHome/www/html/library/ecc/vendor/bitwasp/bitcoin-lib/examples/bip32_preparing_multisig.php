<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

echo "Lets start off by generating a wallet for each of the 'users'.\n";
echo "This will be stored on their machine.\n";
$wallet[0] = BIP32::master_key('b861e093a58718e145b9791af35fb111');
$wallet[1] = BIP32::master_key('b861e093a58718e145b9791af35fb222');
$wallet[2] = BIP32::master_key('b861e093a58718e145b9791af35fb333');
print_r($wallet);

echo "Now we will generate a m/0' extended key. These will yield a private key\n";
$user[0] = BIP32::build_key($wallet[0][0], "3'");
$user[1] = BIP32::build_key($wallet[1][0], "23'");
$user[2] = BIP32::build_key($wallet[2][0], "9'");
print_r($user);

// As the previous is a private key, we should convert to the corresponding
// public key: M/0'
echo "As the previous is a private key, we should convert it to the corresponding\n";
echo "public key: M/0' \n";
$pub[0] = BIP32::extended_private_to_public($user[0]);
$pub[1] = BIP32::extended_private_to_public($user[1]);
$pub[2] = BIP32::extended_private_to_public($user[2]);
print_r($pub);	

echo "This is the key you will ask your users for. For repeated transactions\n";
echo "BIP32 allows you to deterministically generate public keys, meaning less\n";
echo "effort for everyone involved\n\n";
echo "Now we can generate many multisignature addresses from what we have here: \n";
for($i = 0; $i < 3; $i++) {
	$bip32key[0] = BIP32::build_key($pub[0], "0/{$i}");
	$bip32key[1] = BIP32::build_key($pub[1], "0/{$i}");
	$bip32key[2] = BIP32::build_key($pub[2], "0/{$i}");
	print_r($bip32key);
	$pubkey[0] = BIP32::extract_public_key($bip32key[0]);
	$pubkey[1] = BIP32::extract_public_key($bip32key[1]);
	$pubkey[2] = BIP32::extract_public_key($bip32key[2]);
	print_r($pubkey);
	print_r(RawTransaction::create_multisig(2, $pubkey));
}
