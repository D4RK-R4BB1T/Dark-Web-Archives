<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

/////////////////////////////
// Parameters for creation..
// Set up inputs here
$inputs = array(
    array(
        'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
        'vout' => 0
    )
);
// Set up outputs here.
$outputs = array('1PGa6cMAzzrBpTtfvQTzX5PmUxsDiFzKyW' => BitcoinLib::toSatoshi(0.00015));

////////////////////////////
// Parameters for signing.
// Create JSON inputs parameter
// - These can come from bitcoind, or just knowledge of the txid/vout/scriptPubKey, 
//   and redeemScript if needed.
$json_inputs = json_encode(
    array(
        array('txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
            'vout' => 0,
            // OP_DUP OP_HASH160 push14bytes       PkHash      OP_EQUALVERIFY OP_CHECKSIG
            'scriptPubKey' => '76a914' . '7e3f939e8ded8c0d93695310d6d481ae5da39616' . '88ac')
    )
);
// Private Key
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, array('L2V4QgXVUyWVoMGejTj7PrRUUCEi9D9Y1AhUM8E6f5yJm7gemgN6'), '00');

// Create raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

// Sign the transaction
//  To broadcast you would send the $sign['hex'] on to the network
//  eg; with `bitcoind sendrawtransaction <hex>`
$sign = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);
print_r($sign); echo "\n";

// Get the transaction hash from the raw transaction
$txid = RawTransaction::txid_from_raw($sign['hex']);
print_r($txid); echo "\n";
