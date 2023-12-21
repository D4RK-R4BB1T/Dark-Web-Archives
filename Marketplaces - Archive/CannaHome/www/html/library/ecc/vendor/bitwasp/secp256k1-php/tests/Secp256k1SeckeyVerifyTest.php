<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1SeckeyVerifyTest extends TestCase
{
    public function getVectors()
    {
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/pubkey_create.yml');

        $context = TestCase::getContext();
        $fixtures = array();
        foreach ($data['vectors'] as $vector) {
            $fixtures[] = array($context, $vector['seckey']);
        }
        return $fixtures;
    }

    /**
     * @dataProvider getVectors
     */
    public function testVerfiesSeckey($context, $hexPrivKey)
    {
        $this->genericTest($context, $hexPrivKey, 1);
    }

    /**
     * @param string $privkey
     * @param bool $eVerify
     */
    public function genericTest($context, $privkey, $eVerify)
    {
        $sec = $this->toBinary32($privkey);
        $this->assertEquals($eVerify, \secp256k1_ec_seckey_verify($context, $sec));
    }

    public function getErroneousTypeVectors()
    {
        $context = TestCase::getContext();
        $array = array();
        $class = new self;
        $resource = openssl_pkey_new();

        return array(
            array($context, $array),
            array($context, $resource),
            array($context, $class)
        );
    }

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $seckey)
    {
        \secp256k1_ec_seckey_verify($context, $seckey);
    }
}
