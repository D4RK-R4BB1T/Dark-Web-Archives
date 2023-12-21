<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1PrivkeyTweakAddTest extends TestCase
{

    public function getVectors()
    {
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/secp256k1_privkey_tweak_add.yml');
        $fixtures = array();
        $context = TestCase::getContext();
        foreach ($data['vectors'] as $vector) {
            $fixtures[] = array(
                $context,
                $vector['privkey'],
                $vector['tweak'],
                $vector['tweaked']
            );
        }
        return $fixtures;
    }

    /**
     * @dataProvider getVectors
     */
    public function testTweaksPrivateKeyAdd($context, $privkey, $tweak, $expectedTweaked)
    {
        $privkey = $this->toBinary32($privkey);
        $tweak = $this->toBinary32($tweak);
        $expectedTweaked = $this->toBinary32($expectedTweaked);

        $result = secp256k1_ec_privkey_tweak_add($context, $privkey, $tweak);
        $this->assertEquals(1, $result);
        $this->assertEquals($privkey, $expectedTweaked);

    }

    public function getErroneousTypeVectors()
    {
        $context = TestCase::getContext();
        $tweak = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $privateKey = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');

        $array = array();
        $class = new self;
        $resource = openssl_pkey_new();

        return array(
            array($context, $privateKey, $array),
            array($context, $privateKey, $resource),
            array($context, $privateKey, $class)
        );
    }

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $seckey, $tweak)
    {
        $r = \secp256k1_ec_privkey_tweak_add($context, $seckey, $tweak);
    }/**/

    /**
     * @expectedException \Exception
     */
    public function testEnforceZvalString()
    {
        $tweak = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $privateKey = array();
        \secp256k1_ec_privkey_tweak_add(TestCase::getContext(), $privateKey, $tweak);
    }
}
