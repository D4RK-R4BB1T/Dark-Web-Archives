<?php

namespace BitWasp\Secp256k1Tests;

use Symfony\Component\Yaml\Yaml;

class Secp256k1PrivkeyImportExportTest
{
    /**
     * @return array
     */
    public function getPkVectors()
    {
        $parser = new Yaml();
        $data = $parser->parse(__DIR__ . '/data/pubkey_create.yml');

        $fixtures = array();
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        foreach ($data['vectors'] as $c => $vector) {
            $fixtures[] = array(
                $context,
                $vector['seckey'],
                ($c%2 == 0)
            );
        }
        return $fixtures;
    }

    /**
     * @dataProvider getPkVectors
     * @param $seckey
     * @param $compressed
     */
    public function testImportExport($context, $seckey, $compressed)
    {
        $seckey = $this->toBinary32($seckey);

        $der = '';
        $this->assertEquals(1, secp256k1_ec_privkey_export($context, $seckey, $compressed, $der));

        //$recovered = '';
        //$this->assertEquals(1, secp256k1_ec_privkey_import($context, $der, $recovered));

        //$this->assertEquals($seckey, $recovered);

    }
}
