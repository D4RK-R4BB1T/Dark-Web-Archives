<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1PubkeyTweakAddTest extends TestCase
{

    /**
     * @return array
     */
    public function getVectors()
    {
        $stop = 0;
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/secp256k1_pubkey_tweak_add.yml');

        $compressed = 0;
        $context = TestCase::getContext();
        $fixtures = array();
        foreach ($data['vectors'] as $c => $vector) {
            if ($stop && $c >= 2) {
                break;
            }
            $fixtures[] = array(
                $context,
                $vector['publicKey'],
                $vector['tweak'],
                $vector['tweaked'],
                $compressed
            );
        }
        return $fixtures;
    }

    /**
     * @dataProvider getVectors
     */
    public function testAddsToPubkey($context, $publicKey, $tweak, $expectedPublicKey, $compressed)
    {
        $this->genericTest(
            $context,
            $publicKey,
            $tweak,
            $expectedPublicKey,
            1,
            $compressed
        );
    }

    /**
     * @param $publicKey
     * @param $tweak
     * @param $expectedPublicKey
     * @param $eAdd
     */
    private function genericTest($context, $publicKey, $tweak, $expectedPublicKey, $eAdd, $compressed)
    {
        $publicKey = $this->toBinary32($publicKey);
        /** @var resource $p */
        $p = '';
        secp256k1_ec_pubkey_parse($context, $p, $publicKey);
        $tweak = $this->toBinary32($tweak);
        $result = secp256k1_ec_pubkey_tweak_add($context, $p, $tweak);
        $this->assertEquals($eAdd, $result);

        $pSer = '';
        secp256k1_ec_pubkey_serialize($context, $pSer, $p, $compressed);
        $this->assertEquals(bin2hex($pSer), $expectedPublicKey);
    }


    public function getErroneousTypeVectors()
    {
        $context = TestCase::getContext();
        $publicKey = $this->pack('041a2756dd506e45a1142c7f7f03ae9d3d9954f8543f4c3ca56f025df66f1afcba6086cec8d4135cbb5f5f1d731f25ba0884fc06945c9bbf69b9b543ca91866e79');

        $array = array();
        $class = new self;
        $resource = openssl_pkey_new();

        return array(
            // Only test second value, first is zVal to tested elsewhere
            array($context, $publicKey, $array),
            array($context, $publicKey, $resource),
            array($context, $publicKey, $class)
        );
    }

    /**
     * @dataProvider getErroneousTypeVectors
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testErroneousTypes($context, $pubkey, $tweak)
    {
        \secp256k1_ec_pubkey_tweak_add($context, $pubkey, $tweak);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testEnforceZvalString()
    {
        $tweak = $this->pack('0af79b2b747548d59a4a765fb73a72bc4208d00b43d0606c13d332d5c284b0ef');
        $pubkey = array();
        \secp256k1_ec_pubkey_tweak_add(TestCase::getContext(), $pubkey, $tweak);
    }
}
