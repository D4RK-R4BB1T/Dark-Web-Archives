<?php

use BitWasp\BitcoinLib\BIP32;

require_once(__DIR__. '/../vendor/autoload.php');

$master = BIP32::master_key(bin2hex(mcrypt_create_iv(64, \MCRYPT_DEV_URANDOM)));

// Load a 128 bit key, and convert this to extended key format.
//$master = BIP32::master_key('41414141414141414141414141414141414141');
$def = "0'/0";

echo "\nMaster key\n m           : {$master[0]} \n";

$key = BIP32::build_key($master, $def);

// Define what derivation you wish to calculate.

// Display private extended key and the address that's derived from it.
echo "Generated key: note that all depth=1 keys are hardened. \n {$key[1]}        : {$key[0]}\n";
echo "             : ".BIP32::key_to_address($key[0])."\n";

// Convert the extended private key to the public key, and display the 
// address that's derived from it.
$pub = BIP32::extended_private_to_public($key);
echo "Public key\n {$pub[1]}        : {$pub[0]}\n";
echo "             : ".BIP32::key_to_address($pub[0])."\n";

//$nextpub = BIP32::build_key($pub, '0');
//echo  "Child key\n";
//echo " {$nextpub[1]}      : {$nextpub[0]}\n";

