<?php

namespace BitWasp\Secp256k1Tests;


class Secp256k1SchnorrSignTest extends TestCase
{
    public function serializePubkey($context, $pubkey)
    {
        $serialized = '';
        secp256k1_ec_pubkey_serialize($context, $serialized, $pubkey, true);
        return bin2hex($serialized);
    }

    public function testEndtoEnd()
    {
        $context = TestCase::getContext();

        /**
         * @var resource $pub1
         * @var resource $pub2
         * @var resource $pub3
         */
        $priv1 = str_pad('', 32, "\x41");
        $priv2 = str_pad('', 32, "\x12");
        $priv3 = str_pad('', 32, "\x14");
        $pub1 = '';
        $pub2 = '';
        $pub3 = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub1, $priv1));
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub2, $priv2));
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub3, $priv3));

        $msg32 = hash('sha256','text', true);

        /**
         * @var resource $pubnonce1
         * @var resource $pubnonce2
         * @var resource $pubnonce3
         */
        $pubnonce1 = '';
        $pubnonce2 = '';
        $pubnonce3 = '';
        $privnonce1 = '';
        $privnonce2 = '';
        $privnonce3 = '';

        $this->assertEquals(1, secp256k1_schnorr_generate_nonce_pair($context, $pubnonce1, $privnonce1, $msg32, $priv1));
        $this->assertEquals(1, secp256k1_schnorr_generate_nonce_pair($context, $pubnonce2, $privnonce2, $msg32, $priv2));
        $this->assertEquals(1, secp256k1_schnorr_generate_nonce_pair($context, $pubnonce3, $privnonce3, $msg32, $priv3));

        /** @var resource $combinedKey3 */
        /** @var resource $combinedKey2 */
        /** @var resource $combinedKey1 */
        /** @var resource $groupKey */
        $combinedKey3 = '';
        $combinedKey2 = '';
        $combinedKey1 = '';
        $groupKey = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_combine($context, $groupKey, array($pub1, $pub2, $pub3)));
        $this->assertEquals(1, secp256k1_ec_pubkey_combine($context, $combinedKey3, array($pubnonce1, $pubnonce2)));
        $this->assertEquals(1, secp256k1_ec_pubkey_combine($context, $combinedKey2, array($pubnonce1, $pubnonce3)));
        $this->assertEquals(1, secp256k1_ec_pubkey_combine($context, $combinedKey1, array($pubnonce2, $pubnonce3)));

        $sig64a = '';
        $sig64b = '';
        $sig64c = '';
        $groupSig = '';
        $this->assertEquals(1, secp256k1_schnorr_partial_sign($context, $sig64a, $msg32, $priv3, $combinedKey3, $privnonce3));
        $this->assertEquals(1, secp256k1_schnorr_partial_sign($context, $sig64b, $msg32, $priv2, $combinedKey2, $privnonce2));
        $this->assertEquals(1, secp256k1_schnorr_partial_sign($context, $sig64c, $msg32, $priv1, $combinedKey1, $privnonce1));
        $this->assertEquals(1, secp256k1_schnorr_partial_combine($context, $groupSig, array($sig64a, $sig64b, $sig64c)));

        $this->assertEquals(1, secp256k1_schnorr_verify($context, $groupSig, $msg32, $groupKey));

    }
}