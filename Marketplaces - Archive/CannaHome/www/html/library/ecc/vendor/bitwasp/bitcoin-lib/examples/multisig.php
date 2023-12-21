<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__ . '/../vendor/autoload.php');

$m = 2;
$publicKeys = [
    '0379ddc228d8c44a85ae30c877a6b037ec3d627e0507f223a0412790a83a46cd5f',
    '024d1cf2ca917f4d679fc02df2a39c0a8110a1b6935b27ae6762a0ceeec7752801',
    '0258f70f6400aa6f60ff0d21c3aaf1ca236d177877d2b9ad9d2c55280e375ab2d2'
];

// It's recommended that you sort the public keys before creating multisig
// Someday this might be standardized in BIP67; https://github.com/bitcoin/bips/pull/146
// Many other libraries already do this too!
RawTransaction::sort_multisig_keys($publicKeys);

// Create redeem script
$redeemScript = RawTransaction::create_multisig($m, $public_keys);

// Display data
echo "Public Keys: \n";
foreach ($publicKeys as $i => $publicKey) {
    echo "{$i} : {$publicKey} \n";
}
echo "\n";

echo "Redeem Script: \n";
echo "{$redeemScript['redeem_script']} \n\n";

echo "Address: \n";
echo "{$redeemScript['address']} \n\n";
