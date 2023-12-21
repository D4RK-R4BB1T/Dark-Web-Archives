<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

/*
 * !! TESTNET !!
 */
BitcoinLib::setMagicByteDefaults("bitcoin-testnet");

/*
 * address: n3P94USXs7LzfF4BKJVyGv2uCfBQRbvMZJ
 * priv:    cV2BRcdtWoZMSovYCpoY9gyvjiVK5xufpAwdAFk1jdonhGZq1cCm
 * pub:     03c0b1fd07752ebdd43c75c0a60d67958eeac8d4f5245884477eae094c4361418d
 *
 * address: mhsywR248h21gCB8oSwse5tmFSPvo9d5ML
 * priv:    cMps8Dg4Z1ThcwvPiPpshR6cbosYoTrgUwgLcFasBSxsdLHwzoUK
 * pub:     02ab1fae8dacd465460ad8e0c08cb9c25871782aa539a58b65f9bf1264c355d098
 *
 * address: mh7gsCxi4pcuNyHU9aWD9pGogHNJJZcCta
 * priv:    cNn72iUvQhuzZCWg3TC31fvyNDYttL8emHgMcFJzhF4xnFo8LYCk
 * pub:     02dc43b58ee5313d1969b939718d2c8104a3365d45f12f91753bfc950d16d3e82e
 *
 * 2of3 address: 2N1zEScjXeBDX2Gy4c6ojLTfqjRjSvf7iEC
 * 2of3 redeem:  522103c0b1fd07752ebdd43c75c0a60d67958eeac8d4f5245884477eae094c4361418d2102ab1fae8dacd465460ad8e0c08cb9c25871782aa539a58b65f9bf1264c355d0982102dc43b58ee5313d1969b939718d2c8104a3365d45f12f91753bfc950d16d3e82e53ae
 *
 * funded in TX: 83c5c88e94d9c518f314e30ca0529ab3f8e5e4f14a8936db4a32070005e3b61f
 */

$redeem_script = "522103c0b1fd07752ebdd43c75c0a60d67958eeac8d4f5245884477eae094c4361418d2102ab1fae8dacd465460ad8e0c08cb9c25871782aa539a58b65f9bf1264c355d0982102dc43b58ee5313d1969b939718d2c8104a3365d45f12f91753bfc950d16d3e82e53ae";

$inputs = array(
    array(
        "txid" => "83c5c88e94d9c518f314e30ca0529ab3f8e5e4f14a8936db4a32070005e3b61f",
        "vout" => 0,
        "scriptPubKey" => "a9145fe34588f475c5251ff994eafb691a5ce197d18b87",

        // only needed for RawTransaction::sign
        "redeemScript" => $redeem_script
    )
);
$outputs = array(
    "n3P94USXs7LzfF4BKJVyGv2uCfBQRbvMZJ" => BitcoinLib::toSatoshi(0.00010000)
);
$raw_transaction = RawTransaction::create($inputs, $outputs);


/*
 * sign with first key
 */
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, array("cV2BRcdtWoZMSovYCpoY9gyvjiVK5xufpAwdAFk1jdonhGZq1cCm"));
RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
$sign = RawTransaction::sign($wallet, $raw_transaction, json_encode($inputs));
print_r($sign);
var_dump(2 == $sign['req_sigs'], 1 == $sign['sign_count'], 'false' === $sign['complete']);

/*
 * sign with second key
 */
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, array("cMps8Dg4Z1ThcwvPiPpshR6cbosYoTrgUwgLcFasBSxsdLHwzoUK"));
RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
$sign = RawTransaction::sign($wallet, $sign['hex'], json_encode($inputs));
print_r($sign);
var_dump(2 == $sign['req_sigs'], 2 == $sign['sign_count'], 'true' === $sign['complete']);

/*
 * sign with third key
 */
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, array("cNn72iUvQhuzZCWg3TC31fvyNDYttL8emHgMcFJzhF4xnFo8LYCk"));
RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
$sign = RawTransaction::sign($wallet, $sign['hex'], json_encode($inputs));
print_r($sign);
var_dump(2 == $sign['req_sigs'], 3 == $sign['sign_count'], 'true' === $sign['complete']);

