<?php

$context = secp256k1_context_create(SECP256K1_CONTEXT_VERIFY | SECP256K1_CONTEXT_SIGN);

$context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
$msg32 = hash('sha256', 'this is a message!', true);

$signatureRaw = pack("H*", "3044022055ef6953afd139d917d947ba7823ab5dfb9239ba8a26295a218cad88fb7299ef022057147cf4233ff3b87fa64d82a0b9a327e9b6d5d0070ab3f671b795934c4f2074");
$publicKeyRaw = pack("H*", '04fae8f5e64c9997749ef65c5db9f0ec3e121dc6901096c30da0f105a13212b6db4315e65a2d63cc667c034fac05cdb3c7bc1abfc2ad90f7f97321613f901758c9');

// Load up the public key from its bytes (into $publicKey):
/** @var resource $publicKey */
$publicKey = '';;
secp256k1_ec_pubkey_parse($context, $publicKey, $publicKeyRaw);

// Load up the signature from its bytes (into $signature):
/** @var resource $signature */
$signature = '';
secp256k1_ecdsa_signature_parse_der($context,$signature, $signatureRaw);

// Verify:
for($i = 0; $i < 10000; $i++) {
  $result = secp256k1_ecdsa_verify($context, $signature, $msg32, $publicKey);
}
