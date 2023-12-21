<?php

namespace BitWasp\Secp256k1Tests;

class Secp256k1ConstantsTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('secp256k1_ecdsa_signature', SECP256K1_TYPE_SIG);
        $this->assertEquals('secp256k1_ecdsa_recoverable_signature', SECP256K1_TYPE_RECOVERABLE_SIG);
        $this->assertEquals('secp256k1_pubkey', SECP256K1_TYPE_PUBKEY);
        $this->assertEquals('secp256k1_context', SECP256K1_TYPE_CONTEXT);

        $this->assertEquals('257', SECP256K1_CONTEXT_VERIFY);
        $this->assertEquals('513', SECP256K1_CONTEXT_SIGN);

        $this->assertEquals(258, SECP256K1_EC_COMPRESSED);
    }
}