<?php

$context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

function serializePubkey($context, $pubkey)
{
    $serialized = '';
    secp256k1_ec_pubkey_serialize($context, $serialized, $pubkey, true);
    return bin2hex($serialized);
}

/**
 * @var resource $pub1
 * @var resource $pub2
 * @var resource $pub3
 */
$priv1 = str_pad('', 32, "\x41");
$pub1 = '';

$priv2 = str_pad('', 32, "\x12");
$pub2 = '';

$priv3 = str_pad('', 32, "\x14");
$pub3 = '';

$c = secp256k1_ec_pubkey_create($context, $pub1, $priv1);
$c = secp256k1_ec_pubkey_create($context, $pub2, $priv2);
$c = secp256k1_ec_pubkey_create($context, $pub3, $priv3);

echo "Pubkey1: ".serializePubkey($context, $pub1).PHP_EOL;
echo "Pubkey2: ". serializePubkey($context, $pub2).PHP_EOL;
echo "Pubkey3: ". serializePubkey($context, $pub3).PHP_EOL;
$msg32 = hash('sha256','text', true);
echo "Hash: ".bin2hex($msg32).PHP_EOL;
/**
 * @var resource $pubnonce1
 * @var resource $pubnonce2
 * @var resource $pubnonce3
 */
$pubnonce1 = '';
$privnonce1 = '';

$pubnonce2 = '';
$privnonce2 = '';

$pubnonce3 = '';
$privnonce3 = '';

secp256k1_schnorr_generate_nonce_pair($context, $pubnonce1, $privnonce1, $msg32, $priv1);
secp256k1_schnorr_generate_nonce_pair($context, $pubnonce2, $privnonce2, $msg32, $priv2);
secp256k1_schnorr_generate_nonce_pair($context, $pubnonce3, $privnonce3, $msg32, $priv3);

/** @var resource $combinedKey3 */
/** @var resource $combinedKey2 */
/** @var resource $combinedKey1 */
/** @var resource $combinedAll */
$combinedKey3 = '';
secp256k1_ec_pubkey_combine($context, $combinedKey3, [$pubnonce1, $pubnonce2]);
$combinedKey2 = '';
secp256k1_ec_pubkey_combine($context, $combinedKey2, [$pubnonce1, $pubnonce3]);
$combinedKey1 = '';
secp256k1_ec_pubkey_combine($context, $combinedKey1, [$pubnonce2, $pubnonce3]);

$combinedAll ='';
secp256k1_ec_pubkey_combine($context, $combinedAll, [$pub1, $pub2, $pub3]);

echo "GroupKey: ".serializePubkey($context, $combinedAll).PHP_EOL;

$sig64a = '';
secp256k1_schnorr_partial_sign($context, $sig64a, $msg32, $priv3, $combinedKey3, $privnonce3);

$sig64b = '';
secp256k1_schnorr_partial_sign($context, $sig64b, $msg32, $priv2, $combinedKey2, $privnonce2);

$sig64c = '';
secp256k1_schnorr_partial_sign($context, $sig64c, $msg32, $priv1, $combinedKey1, $privnonce1);

$sig64 = '';
secp256k1_schnorr_partial_combine($context, $sig64, [$sig64a, $sig64b, $sig64c]);

echo bin2hex($sig64) . PHP_EOL;

var_dump(secp256k1_schnorr_verify($context, $sig64, $msg32, $combinedAll));
