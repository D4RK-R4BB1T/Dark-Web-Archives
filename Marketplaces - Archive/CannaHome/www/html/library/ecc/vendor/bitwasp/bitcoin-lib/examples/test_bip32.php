<?php

use BitWasp\BitcoinLib\BIP32;

require_once(__DIR__. '/../vendor/autoload.php');

echo "bip32 tests - https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki#Test_Vectors \n\n";

echo "test one\n";
$master = BIP32::master_key('000102030405060708090a0b0c0d0e0f');
echo "Chain m\n";
echo "    ext priv:\n    ".$master[0]."\n";
$public = BIP32::extended_private_to_public($master);
echo "    ext pub:\n    ".$public[0]."\n";

echo "Chain m/0h\n";
$key = BIP32::build_key($master, "0'");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0h/1\n";
$key = BIP32::build_key($key, '1');
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0h/1/2h\n";
$key = BIP32::build_key($key, "2'");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0h/1/2h/2\n";
$key = BIP32::build_key($key, "2");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0h/1/2h/2/1000000000\n";
$key = BIP32::build_key($key, "1000000000");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n\n\n\n";


echo "test two\n";
$master = BIP32::master_key('fffcf9f6f3f0edeae7e4e1dedbd8d5d2cfccc9c6c3c0bdbab7b4b1aeaba8a5a29f9c999693908d8a8784817e7b7875726f6c696663605d5a5754514e4b484542');
echo "Chain m\n";
echo "    ext priv:\n    ".$master[0]."\n";
$public = BIP32::extended_private_to_public($master);
echo "    ext pub:\n    ".$public[0]."\n";

echo "Chain m/0\n";
$key = BIP32::build_key($master, '0');
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0/2147483647'\n";
$key = BIP32::build_key($key, "2147483647'");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0/2147483647'/1\n";
$key = BIP32::build_key($key, "1");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0/2147483647'/1/2147483646'\n";
$key = BIP32::build_key($key, "2147483646'");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";

echo "Chain m/0/2147483647'/1/2147483646'/2\n";
$key = BIP32::build_key($key, "2");
echo "    ext priv:\n    ".$key[0]."\n";
$public = BIP32::extended_private_to_public($key);
echo "    ext pub: \n    ".$public[0]."\n";
