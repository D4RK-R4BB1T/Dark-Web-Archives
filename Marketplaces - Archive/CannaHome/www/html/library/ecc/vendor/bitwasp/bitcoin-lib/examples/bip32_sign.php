<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

// Fixed seed and derivation to test with
$seed = '41414141414141414141414141414141414141';
$def = "0'/0";

// Create master key from seed
$master = BIP32::master_key($seed);
echo "\nMaster key\n m           : {$master[0]} \n";

// Create derived key from master key + derivation
$key = BIP32::build_key($master, $def);

// Display private extended key and the address that's derived from it.
echo "Generated key: note that all depth=1 keys are hardened. \n {$key[1]}        : {$key[0]}\n";
echo "             : ".BIP32::key_to_address($key[0])."\n";

// Convert the extended private key to the public key, and display the address that's derived from it.
$pub = BIP32::extended_private_to_public($key);
echo "Public key\n {$pub[1]}        : {$pub[0]}\n";
echo "             : ".BIP32::key_to_address($pub[0])."\n";

/////////////////////////////
// We're gonna spent the first txout from this tx:
//  https://www.blocktrail.com/BTC/tx/4a2231e13182cdb64fa2f9aae38fca46549891e9dc15e8aaf484d82fc6e0a1d8

// Set up inputs here
$inputs = array(
    array(
        'txid' => '4a2231e13182cdb64fa2f9aae38fca46549891e9dc15e8aaf484d82fc6e0a1d8',
        'vout' => 0
    )
);
// Set up outputs here
$outputs = array('1KuE17Fbcdsn3Ns5T9Wzi1epurRnKC9qVr' => BitcoinLib::toSatoshi(0.0004));

////////////////////////////
// Parameters for signing.
// Create JSON inputs parameter
// - These can come from bitcoind, or just knowledge of the txid/vout/scriptPubKey,
//   and redeemScript if needed.
$json_inputs = json_encode(
    array(
        array(
            'txid' => '4a2231e13182cdb64fa2f9aae38fca46549891e9dc15e8aaf484d82fc6e0a1d8',
            'vout' => 0,
            // OP_DUP OP_HASH160 push14bytes PkHash OP_EQUALVERIFY OP_CHECKSIG
            'scriptPubKey' => '76a914'.'bf012bde5bd12eb7f9a66de5697b241b65a9a3c9'.'88ac')
    )
);

// build wallet from private key(s)
$wallet = array();
BIP32::bip32_keys_to_wallet($wallet, array($key), '00');

// Create raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

// Sign the transaction
$signed = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);
print_r($signed); echo "\n";

// Decode and print the TX
// print_r(RawTransaction::decode($sign['hex']));

// To broadcast the transaction onto the network
//  - grab the $signed['hex']
//  - `bitcoind sendrawtransaction <singed HEX>`
//    or use an API that supports sendraw
// This transaction was broadcasted and confirmed here:
//  https://www.blocktrail.com/BTC/tx/e04c14270b2a9fcff548bc0bdd16b22ec6c2903ea6aaf9f7656b81f1c4c6153b
