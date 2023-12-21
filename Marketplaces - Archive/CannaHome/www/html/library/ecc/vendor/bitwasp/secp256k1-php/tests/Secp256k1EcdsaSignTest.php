<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1EcdsaSignTest extends TestCase
{

    /**
     * @return array
     */
    public function getVectors()
    {
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/deterministicSignatures.yml');

        $fixtures = array();
        $context = TestCase::getContext();
        foreach ($data['vectors'] as $vector) {
            $fixtures[] = array($context, $vector['privkey'], $vector['msg'], substr($vector['sig'], 0, strlen($vector['sig'])-2));
        }
        return $fixtures;
    }

    /**
     * Testing return value 1
     * @dataProvider getVectors
     */
    public function testEcdsaSign($context, $hexPrivKey, $msg, $sig)
    {
        $this->genericTest(
            $context,
            $hexPrivKey,
            $msg,
            $sig,
            1
        );
    }

    /**
     * @param $privkeyhex
     * @param $msg
     * @param $expectedSig
     * @param $eSigCreate
     */
    private function genericTest($context, $privkeyhex, $msg, $expectedSig, $eSigCreate)
    {
        $privkey = $this->toBinary32($privkeyhex);
        $msg = $this->toBinary32($msg);

        /** @var resource $signature */
        $signature = '';
        $sign = secp256k1_ecdsa_sign($context, $signature, $msg, $privkey);
        $this->assertEquals($eSigCreate, $sign);
        $this->assertEquals(SECP256K1_TYPE_SIG, get_resource_type($signature));
        
        return;

    }

    public function getErroneousTypeVectors()
    {
        $private = $this->pack('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
        $msg32 = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $context = TestCase::getContext();

        $array = array();
        $class = new Secp256k1EcdsaSignTest;
        $resource = openssl_pkey_new();

        return array(
            array($context, $array, $private),
            array($context, $msg32, $array),
            array($context, $resource, $private),
            array($context, $msg32, $resource),
            array($context, $class, $private),
            array($context, $msg32, $class)
        );
    }

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $msg32, $private)
    {
        $sig = '';
        \secp256k1_ecdsa_sign($context, $sig, $msg32, $private);
    }

}
