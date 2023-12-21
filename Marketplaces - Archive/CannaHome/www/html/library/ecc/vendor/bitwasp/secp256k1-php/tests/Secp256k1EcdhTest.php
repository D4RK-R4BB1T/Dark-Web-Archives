<?php

namespace BitWasp\Secp256k1Tests;

class Secp256k1EcDHTest extends TestCase
{

    /**
     * Testing return value 1
     */
    public function testEcdsa()
    {
        $context = TestCase::getContext();

        $priv1 = str_pad('', 32, "\x41");
        $priv2 = str_pad('', 32, "\x40");
        $expectedSecret = '238c14f420887f8e9bfa78bc9bdded1975f0bb6384e33b4ebbf7a8c776844aec';

        $pub1 = '';
        $pub2 = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub1, $priv1));
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub2, $priv2));

        /**
         * @var resource $pub2
         * @var resource $pub1
         */
        $result = '';
        $this->assertEquals(1, secp256k1_ecdh($context, $result, $pub2, $priv1));

        $this->assertEquals(32, strlen($result));
        $this->assertEquals(pack("H*", $expectedSecret), $result);
    }

}
