<?php

use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

// Supply a raw transaction to verify
$raw_tx = '01000000010a74a5750934ce563a9f18812b73dea945e3796d08be5e2c7e817197b4b0665b000000006a47304402203e2b56c1728f6cdcd531d006f7a17e6608513432113290229762de1d1bc0e76902205a9a41c196845d40dc98b67641fa2a1ae52f714094c9ad1e6b99514fd567d187012103161f0ec2a99876733c7b7f63bdb3cede0980e39f18abd50adad2774bd8fe0917ffffffff02426f0f00000000001976a91402a82b3afaff3c4113d86005f7029301c770c61188acbd0e3f0e010000001976a9146284bcf16e0507a35d28c1608ee1708ed26c839488ac00000000';

// Look up the TxIn transactions to learn about the scriptPubKey.. 
$json_string = '[{"txid":"5b66b0b49771817e2c5ebe086d79e345a9de732b81189f3a56ce340975a5740a","vout":0,"scriptPubKey":"76a91416489ece44cc457e14f4e882fd9a0ae082fdf6c688ac"}]';

// Perform signature validation!
$verify = RawTransaction::validate_signed_transaction($raw_tx, $json_string);
var_dump($verify);
