<?php

namespace BitWasp\Secp256k1Tests;

class Secp256k1EcdsaRecoverCompactTest extends TestCase
{
    public function testVerifyCompact()
    {

        $context = TestCase::getContext();
        $recid = 1;
        $compressed = 0;

        $sig = pack("H*", 'fe5fe404f3d8c21e1204a08c38ff3912d43c5a22541d2f1cdc4977cbcad240015a3b6e9040f62cacf016df4fef9412091592e4908e5e3a7bd2a42a4d1be01951');
        /** @var resource $s */
        $s = '';

        $this->assertEquals(1, secp256k1_ecdsa_recoverable_signature_parse_compact($context, $s, $sig, $recid));

        $privateKey = pack("H*", 'fbb80e8a0f8af4fb52667e51963ac9860c192981f329debcc5d123a492a726af');

        $publicKey = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $publicKey, $privateKey));

        $ePubKey = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_serialize($context, $ePubKey, $publicKey, $compressed));

        $msg = pack("H*", '03acc83ba10066e791d51e8a8eb90ec325feea7251cb8f979996848fff551d13');

        $recPubKey = '';
        $this->assertEquals(1, secp256k1_ecdsa_recover($context, $recPubKey, $s, $msg));

        $serPubKey = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_serialize($context, $serPubKey, $recPubKey, $compressed));
        $this->assertEquals($ePubKey, $serPubKey);
    }

    public function getErroneousTypeVectors()
    {
        $context = TestCase::getContext();
        $msg32 = pack("H*", '03acc83ba10066e791d51e8a8eb90ec325feea7251cb8f979996848fff551d13');
        $sig = pack("H*", 'fe5fe404f3d8c21e1204a08c38ff3912d43c5a22541d2f1cdc4977cbcad240015a3b6e9040f62cacf016df4fef9412091592e4908e5e3a7bd2a42a4d1be01951');
        $s = '';
        $recid = 0;
        $this->assertEquals(1, secp256k1_ecdsa_recoverable_signature_parse_compact($context, $s, $sig, $recid));

        $array = array();
        $class = new Secp256k1EcdsaRecoverCompactTest;
        $resource = openssl_pkey_new();

        return array(
            array($context, $array, $s),
            array($context, $resource, $s),
            array($context, $class, $s),
            array($context, $msg32, $array),
            array($context, $msg32, $resource),
            array($context, $msg32, $class),
        );
    }/**/

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $msg32, $sig)
    {
        $publicKey = '';
        \secp256k1_ecdsa_recover($context, $publicKey, $sig, $msg32);
    }/**/
}
