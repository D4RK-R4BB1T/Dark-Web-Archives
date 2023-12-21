<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\Electrum;

class ElectrumTest extends PHPUnit_Framework_TestCase
{

    public function __construct()
    {
        $this->magic_byte = '00';
    }

    public function setup()
    {
        // ensure we're set to bitcoin and not bitcoin-testnet
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function tearDown()
    {
    }

    public function testMnemonicDecode()
    {
        $mnemonic = trim('teach start paradise collect blade chill gay childhood creek picture creator branch');
        $known_seed = 'dcb85458ec2fcaaac54b71fba90bd4a5';
        $known_secexp = '74b1f6c0caae485b4aeb2f26bab3cabdec4f0b432751bd454fe11b2d2907cbda';
        $known_mpk = '819519e966729f31e1855eb75133d9e7f0c31abaadd8f184870d62771c62c2e759406ace1dee933095d15e4c719617e252f32dc0465393055f867aee9357cd52';
        $known_addresses = ["", "", "", "", ""];

        $seed = Electrum::decode_mnemonic($mnemonic);
        $this->assertEquals($seed, $known_seed);

        $mpk = Electrum::generate_mpk($seed);
        $this->assertEquals($mpk, $known_mpk);

        $secexp = Electrum::stretch_seed($seed);
        $secexp = $secexp['seed'];
        $this->assertEquals($secexp, $known_secexp);

        $count_known_addresses = count($known_addresses);
        for ($i = 0; $i < $count_known_addresses; $i++) {
            $privkey = Electrum::generate_private_key($secexp, $i, 0);
            $address_private_deriv = BitcoinLib::private_key_to_address($privkey, $this->magic_byte);

            $public_deriv = Electrum::public_key_from_mpk($mpk, $i);
            $address_private_deriv = BitcoinLib::public_key_to_address($public_deriv, $this->magic_byte);
        }
    }
}

;
