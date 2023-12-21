<?php

use BitWasp\BitcoinLib\BitcoinLib;

require_once(__DIR__. '/../vendor/autoload.php');

$usage = "Usage: php {$argv[0]} <magic byte>\n\n";

$usage.= "Some sample bytes are on this list, but you can chose any 2 character byte.\n";
$usage.= "Bitcoin: 00\t\tTestnet: 6f \n";
$usage.= "Litecoin: 48 \n";
$usage.= "Namecoin: 52 \n";
$usage.= "Auroracoin: 17 \n";




if(count($argv) !== 2)
	die($usage);

$magic_byte = $argv[1];

echo "Generated keypair: (this will not be saved, do not lose this data!)\n";

$keypair = BitcoinLib::get_new_key_set($magic_byte);
echo "Key pair: \n";print_r($keypair); echo "\n";

echo "\n";

