<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1EcdsaVerifyTest extends TestCase
{

    /**
     * @return array
     */
    public function getVectors()
    {
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/signatures.yml');

        $fixtures = array();
        $context = TestCase::getContext();
        foreach ($data['signatures'] as $vector) {
            $fixtures[] = array($context, $vector['privkey'], $vector['msg'], substr($vector['sig'], 0, -2));
            //$fixtures[] = array($context, $vector['privkey'], $vector['msg'], $vector['sig']);
        }
        return $fixtures;
    }

    /**
     * Testing return value 1
     * @dataProvider getVectors
     */
    public function testEcdsaVerify($context, $hexPrivKey, $msg, $sig)
    {
        $this->genericTest(
            $context,
            $hexPrivKey,
            $msg,
            $sig,
            1,
            1
        );
    }

    public function getErroneousTypeVectors()
    {
        $private = $this->pack('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
        $public = '';
        $context = TestCase::getContext();
        $this->assertEquals(1, \secp256k1_ec_pubkey_create($context, $public, $private), 'public');
        $msg32 = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $sig = $this->pack('304502206af189487988df26eb4c2b2c7d74b78e19822bbb2fc27dada0800019abd20b46022100f0e6c4dabd4970afe125f707fbd6d62e79e950bdb2b4b9700214779ae475b05d01');

        $array = array();
        $class = new Secp256k1EcdsaVerifyTest;
        $resource = openssl_pkey_new();

        return array(
            array($context, $array, $sig, $public),
            array($context, $msg32, $array, $public),
            array($context, $msg32, $sig, $array),
            array($context, $resource, $sig, $public),
            array($context, $msg32, $resource, $public),
            array($context, $msg32, $sig, $resource),
            array($context, $class, $sig, $public),
            array($context, $msg32, $class, $public),
            array($context, $msg32, $sig, $class)
        );
    }

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $msg32, $sig, $public)
    {
        $s = '';
        $p = '';
        secp256k1_ecdsa_signature_parse_der($context, $s, $sig);
        secp256k1_ec_pubkey_parse($context, $public, $p);

        \secp256k1_ecdsa_verify($context, $s, $msg32, $p);
    }

    public function testVerifyWithInvalidInput()
    {
        $context = TestCase::getContext();
        $private = $this->pack('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
        $msg32 = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $sig = $this->pack('304502206af189487988df26eb4c2b2c7d74b78e19822bbb2fc27dada0800019abd20b46022100f0e6c4dabd4970afe125f707fbd6d62e79e950bdb2b4b9700214779ae475b05d');

        /** @var resource $s */
        $s = '';
        secp256k1_ecdsa_signature_parse_der($context, $s, $sig);
        /** @var resource $public */
        $public = '';
        $this->assertEquals(1, \secp256k1_ec_pubkey_create($context, $public, $private), 'public');
        $this->assertEquals(1, \secp256k1_ecdsa_verify($context, $s, $msg32, $public), 'initial check');

        $this->assertEquals(0, \secp256k1_ecdsa_verify($context, $s, '', $public), 'msg32 as empty string');

        $this->assertEquals(0, \secp256k1_ecdsa_verify($context, $s, 1, $public), 'msg32 as 1');
        

    }

    /**
     * @param $privkey
     * @param $msg
     * @param $sig
     * @param $ePubCreate
     * @param $eSigCreate
     */
    private function genericTest($context, $privkey, $msg, $sig, $ePubCreate, $eSigCreate)
    {
        $seckey = $this->toBinary32($privkey);
        $msg = $this->toBinary32($msg);
        $sig = pack("H*", $sig);

        /** @var resource $pubkey */
        $pubkey = '';
        $this->assertEquals($ePubCreate, \secp256k1_ec_pubkey_create($context, $pubkey, $seckey));

        /** @var resource $s */
        $s = '';
        secp256k1_ecdsa_signature_parse_der($context, $s, $sig);
        $this->assertEquals($eSigCreate, \secp256k1_ecdsa_verify($context, $s, $msg, $pubkey));
    }
}
