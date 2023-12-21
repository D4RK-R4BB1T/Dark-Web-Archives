<?php

use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

// Transaction this created was spent in: 7f3bf55fdfd7c7858c6a9119ad13d2c2e10cd07186c0f6050bbb7c8b626c8642

// Private keys and redeem scripts
$pks	= array(
			'5KKNNaV63GB68zCJAF6CnJTx3Zp71vNDBXcjHTLr6wjd9c2ETmu',
			'KynFytiTLa2x8keQQwoEVKvewL5z5D1Hti4FDMU1v8kC3pk5BBhr'
		);
$rs		= array(
			'52210385fae44cb9f0cf858e0d404baecf78d026fae4cc9dd4343b8562059473c2af7b2102f34a1b64155db258d3a910625bd80fae6adf67d7e5b5f3de03265a4208b552d841040fa9a86f3237237423dd8331dc481c0a949fe11594c5dfa0b54bdc105daa319f9de6547d97c22296d4211073e7cffa71c8d6cd4da639607ca64fca2705e562a353ae'
		);

// Initialize wallet
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, $pks, '00');
RawTransaction::redeem_scripts_to_wallet($wallet, $rs, '05');

$raw_transaction = '01000000018240b84b90a3ae326e13219dc8a2781661fa28e23129b26fea848dd8e01a0c520000000000ffffffff02d8270000000000001976a914b7119dfb9b5c8aa7157fec48fbe640ea347dc92b88ac584d0000000000001976a914592fb6dc8cf6cd561ec86fd5fbc2a140e5ac7bc988ac00000000'; 
$json = '[{"txid":"520c1ae0d88d84ea6fb22931e228fa611678a2c89d21136e32aea3904bb84082","vout":0,"scriptPubKey":"a914fb56f0d4845487cc9da00c3e91e63503245f151787","redeemScript":"52210385fae44cb9f0cf858e0d404baecf78d026fae4cc9dd4343b8562059473c2af7b2102f34a1b64155db258d3a910625bd80fae6adf67d7e5b5f3de03265a4208b552d841040fa9a86f3237237423dd8331dc481c0a949fe11594c5dfa0b54bdc105daa319f9de6547d97c22296d4211073e7cffa71c8d6cd4da639607ca64fca2705e562a353ae"}]';
$sign = RawTransaction::sign($wallet, $raw_transaction, $json);
print_r($sign);

