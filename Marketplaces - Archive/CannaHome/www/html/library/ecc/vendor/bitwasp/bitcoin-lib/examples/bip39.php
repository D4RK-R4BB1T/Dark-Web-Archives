<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BIP39\BIP39;

require_once(__DIR__. '/../vendor/autoload.php');

$password = "my-oh-so-secret-password";
$entropy = BIP39::generateEntropy(256);
$mnemonic = BIP39::entropyToMnemonic($entropy);
$seed = BIP39::mnemonicToSeedHex($mnemonic, $password);

unset($entropy); // ignore, forget about this, don't use it!

var_dump($mnemonic); // this is what you print on a piece of paper, etc
var_dump($password); // this is secret of course
var_dump($seed); // this is what you use to generate a key

$key = BIP32::master_key($seed); // enjoy
