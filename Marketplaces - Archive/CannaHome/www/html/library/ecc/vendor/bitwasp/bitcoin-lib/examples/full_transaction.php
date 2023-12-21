<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

// spending from transaction 6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c 1st output (vout=0)
//  value of output is 0.0002
$inputs = array(
    array(
        'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
        'vout' => 0,
        'value' => 0.0002,
        'scriptPubKey' => '76a9147e3f939e8ded8c0d93695310d6d481ae5da3961688ac',
    )
);

// sum up the total amount of coins we're spending
$inputsTotal = 0;
foreach ($inputs as $input) {
    $inputsTotal += $input['value'];
}

// fixed fee
$fee = 0.0001;

// information of who we're sending coins to and how much
$to = '1PGa6cMAzzrBpTtfvQTzX5PmUxsDiFzKyW';
$send = 0.00005;

// calculate change
$change = $inputsTotal - $send - $fee;

// this is our own address
$changeAddress = "1CWYJZ4vSoemSCrfBvXreqEtojEeCUeKw3";

// create ouputs, one to recipient and one to change
$outputs = array(
    $to => BitcoinLib::toSatoshi($send),
    $changeAddress => BitcoinLib::toSatoshi($change),
);

// import private key
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, array('L2V4QgXVUyWVoMGejTj7PrRUUCEi9D9Y1AhUM8E6f5yJm7gemgN6'), '00');

// crate unsigned raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

// sign the transaction
// to broadcast transaction take this value and `bitcoin-cli sendrawtransaction <hex>`
$sign = RawTransaction::sign($wallet, $raw_transaction, json_encode($inputs));
print_r($sign); echo "\n";

// set the transaction hash from the raw transaction
$txid = RawTransaction::txid_from_raw($sign['hex']);
print_r($txid); echo "\n";
