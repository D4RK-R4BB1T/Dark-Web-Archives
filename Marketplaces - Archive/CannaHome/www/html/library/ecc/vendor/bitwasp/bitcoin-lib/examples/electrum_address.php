<?php

use BitWasp\BitcoinLib\Electrum;

require_once __DIR__.'/vendor/autoload.php';

$mpk = 'eee6754303a65aa693a459269c8deb55e02e2d03ed427ae4ac498d7ab18f30844e53c10cd84faf2d1cac68da135279a6076c5770934e20651624db6bd72f1670';
echo "Uncompressed Keys: \n";
for($i = 0; $i < 40; $i++){
  echo "- ".Electrum::address_from_mpk($mpk, $i, '00', 0, FALSE)."\n";
}
echo "\n";

echo "Compressed Keys: \n";
for($i = 0; $i < 40; $i++){
  echo "- ".Electrum::address_from_mpk($mpk, $i, '00', 0, TRUE)."\n";
}
