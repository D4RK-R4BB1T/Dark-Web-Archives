<?php

use BitWasp\BitcoinLib\BitcoinLib;

class BitcoinLibTest extends PHPUnit_Framework_TestCase
{
    protected $testHexEncode_i;
    protected $addressVersion;
    protected $WIFVersion;
    protected $keyConversionData = array();

    public function __construct()
    {
        $this->addressVersion = '00';
        $this->p2shAddressVersion = '05';
        $this->WIFVersion = '80';
        $this->keyConversionData = array(
            "5HxWvvfubhXpYYpS3tJkw6fq9jE9j18THftkZjHHfmFiWtmAbrj" => "1QFqqMUD55ZV3PJEJZtaKCsQmjLT6JkjvJ",
            "5KC4ejrDjv152FGwP386VD1i2NYc5KkfSMyv1nGy1VGDxGHqVY3" => "1F5y5E5FMc5YzdJtB9hLaUe43GDxEKXENJ",
            "Kwr371tjA9u2rFSMZjTNun2PXXP3WPZu2afRHTcta6KxEUdm1vEw" => "1NoJrossxPBKfCHuJXT4HadJrXRE9Fxiqs"
        );
    }

    public function setup()
    {
        // ensure we're set to bitcoin and not bitcoin-testnet
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function tearDown()
    {

    }

    public function testPrivateKeyVersion()
    {
        $this->assertEquals($this->WIFVersion, BitcoinLib::get_private_key_address_version($this->addressVersion));
    }

    //////////////////////////////////////////////////////
    // hex_encode() test functions
    //
    public function _testHexEncode_result($hex)
    {
        $this->assertEquals($this->testHexEncode_i, hexdec($hex));
    }

    public function _testHexEncode_length($hex)
    {
        $this->assertTrue(strlen($hex) % 2 == 0);
    }

    public function testHexEncode()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 50;

        for ($this->testHexEncode_i = 0; $this->testHexEncode_i < $cnt; ($this->testHexEncode_i++)) {
            $hex = BitcoinLib::hex_encode((string)$this->testHexEncode_i);
            $this->_testHexEncode_result($hex);
            $this->_testHexEncode_length($hex);
        }
    }
    ///////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////
    // bin2hex() test function
    public function testBin2Hex()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 50;

        for ($i = 0; $i < $cnt; $i++) {
            $length = mt_rand(1, 32);
            $hex = (string)bin2hex(mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM));
            $this->assertTrue(ctype_xdigit($hex));
        }
    }
    ///////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////
    // hex_decode() test functions
    public function _testHexDecode_result($dec, $real_val)
    {
        $this->assertEquals($dec, $real_val);
    }

    public function testHexDecodeBaseConvert()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 100;

        // Base_Convert handles UP TO 8 bytes.
        for ($i = 0; $i < $cnt; $i++) {
            $length = mt_rand(1, 7);
            // Generate a random length hex string.
            $hex = (string)bin2hex(mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM));
            // Get real decimal result.
            $dec = base_convert($hex, 16, 10); // handles big ints better than hexdec.
            $this->assertEquals(BitcoinLib::hex_decode($hex), $dec);
        }
    }

    public function testHexDecodeGMP()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 100;

        // GMP has no real upper limit on the number of bytes!
        for ($i = 0; $i < $cnt; $i++) {
            $length = mt_rand(1, 5000);
            // Generate a random length hex string.
            $hex = (string)bin2hex(mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM));
            // Get real decimal result.
            $dec = gmp_strval(gmp_init($hex, 16), 10);
            $this->assertEquals(BitcoinLib::hex_decode($hex), $dec);
        }
    }
    ///////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////
    // base58_encode() & base58_decode() tests for consistency
    public function testBase58FunctionsForConsistency()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 50;

        for ($i = 0; $i < $cnt; $i++) {
            // Generate a random length hex string.
            $hex = (string)bin2hex(mcrypt_create_iv(20, \MCRYPT_DEV_URANDOM));
            $encode = BitcoinLib::base58_encode($hex);
            $decode = BitcoinLib::base58_decode($encode);

            $this->assertTrue($hex == $decode);
        }
    }

    /* public function _testBase58EncodeValues($data, $equals) {
        $this->assertEquals($data, $equals);
    }*/

    public function testBase58EncodeValues()
    {
        // Taken from Bitcoin Core's ./src/tests/data/base58_encode_decode.json file
        $tests = ["" => "",
            "61" => "2g",
            "626262" => "a3gV",
            "636363" => "aPEr",
            "73696d706c792061206c6f6e6720737472696e67" => "2cFupjhnEsSn59qHXstmK2ffpLv2",
            "00eb15231dfceb60925886b67d065299925915aeb172c06647" => "1NS17iag9jJgTHD1VXjvLCEnZuQ3rJDE9L",
            "516b6fcd0f" => "ABnLTmg",
            "bf4f89001e670274dd" => "3SEo3LWLoPntC",
            "572e4794" => "3EFU7m",
            "ecac89cad93923c02321" => "EJDM8drfXA6uyA",
            "10c8511e" => "Rt5zm",
            "00000000000000000000" => "1111111111"];
        foreach ($tests as $data => $equals) {
            $res = BitcoinLib::base58_encode(trim($data));
            $this->assertEquals($res, $equals);
        }
    }
    ///////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////
    // base58_check testing
    public function testBase58CheckEncode()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 50;

        for ($i = 0; $i < $cnt; $i++) {
            // random, 20-byte string.
            $hex = (string)bin2hex(mcrypt_create_iv(20, \MCRYPT_DEV_URANDOM));

            // 'manually' create address
            $encode = BitcoinLib::base58_encode_checksum($this->addressVersion . $hex);
            $decode = BitcoinLib::base58_decode_checksum($encode);

            // validate 'manually' created address
            $this->assertTrue(BitcoinLib::validate_address($encode, $this->addressVersion, $this->p2shAddressVersion));
            // validate 'manually' created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($encode));
            // validate 'manually' created address
            //  disable address version and P2S address version specifically
            $this->assertFalse(BitcoinLib::validate_address($encode, false, null));
            $this->assertTrue(BitcoinLib::validate_address($encode, null, false));

            // validate 'manually'
            $this->assertTrue($hex == $decode);

            // create address
            $check2 = BitcoinLib::hash160_to_address($hex, $this->addressVersion);

            // validate created address
            $this->assertTrue(BitcoinLib::validate_address($check2, $this->addressVersion, $this->p2shAddressVersion));
            // validate created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($check2));
            // validate created address
            //  disable address version and P2S address version specifically
            $this->assertFalse(BitcoinLib::validate_address($check2, false, null));
            $this->assertTrue(BitcoinLib::validate_address($check2, null, false));

            // validate 'manually'
            $this->assertTrue($check2 == $encode);

            // create address,  without specifying the address version
            //  relying on the defaults
            $check3 = BitcoinLib::hash160_to_address($hex);

            // validate created address
            $this->assertTrue(BitcoinLib::validate_address($check3, $this->addressVersion, $this->p2shAddressVersion));
            // validate created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($check3));
            // validate created address
            //  disable address version and P2S address version specifically
            $this->assertFalse(BitcoinLib::validate_address($check3, false, null));
            $this->assertTrue(BitcoinLib::validate_address($check3, null, false));

            // validate 'manually'
            $this->assertTrue($check3 == $encode);
        }
    }

    public function testBase58CheckEncodeP2SH()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 50;

        for ($i = 0; $i < $cnt; $i++) {
            // random, 20-byte string.
            $hex = (string)bin2hex(mcrypt_create_iv(20, \MCRYPT_DEV_URANDOM));

            // 'manually' create address
            $encode = BitcoinLib::base58_encode_checksum($this->p2shAddressVersion . $hex);
            $decode = BitcoinLib::base58_decode_checksum($encode);

            // validate 'manually' created address
            $this->assertTrue(BitcoinLib::validate_address($encode, $this->addressVersion, $this->p2shAddressVersion));
            // validate 'manually' created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($encode));
            // validate 'manually' created address
            //  disable address version and P2S address version specifically
            $this->assertTrue(BitcoinLib::validate_address($encode, false, null));
            $this->assertFalse(BitcoinLib::validate_address($encode, null, false));

            // validate 'manually'
            $this->assertTrue($hex == $decode);

            // create address
            $check2 = BitcoinLib::hash160_to_address($hex, $this->p2shAddressVersion);

            // validate created address
            $this->assertTrue(BitcoinLib::validate_address($check2, $this->addressVersion, $this->p2shAddressVersion));
            // validate created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($check2));
            // validate created address
            //  disable address version and P2S address version specifically
            $this->assertTrue(BitcoinLib::validate_address($check2, false, null));
            $this->assertFalse(BitcoinLib::validate_address($check2, null, false));

            // validate 'manually'
            $this->assertTrue($check2 == $encode);

            // create address,  without specifying the address version
            //  relying on the defaults
            $check3 = BitcoinLib::hash160_to_address($hex, 'p2sh');

            // validate created address
            $this->assertTrue(BitcoinLib::validate_address($check3, $this->addressVersion, $this->p2shAddressVersion));
            // validate created address without specifying the address version
            //  relying on the defaults
            $this->assertTrue(BitcoinLib::validate_address($check3));
            // validate created address
            //  disable address version and P2S address version specifically
            $this->assertTrue(BitcoinLib::validate_address($check3, false, null));
            $this->assertFalse(BitcoinLib::validate_address($check3, null, false));

            // validate 'manually'
            $this->assertTrue($check3 == $encode);
        }
    }

    public function testKeyConversion()
    {
        $tests = $this->keyConversionData;

        foreach ($tests as $priv_key => $true_address) {
            $priv_key = trim($priv_key);
            $priv_key_info = BitcoinLib::WIF_to_private_key($priv_key);

            $pubkey = BitcoinLib::private_key_to_public_key($priv_key_info['key'], $priv_key_info['is_compressed']);

            // validate public key to address
            $this->assertEquals(BitcoinLib::public_key_to_address($pubkey, $this->addressVersion), $true_address);
            // validate public key to address, without specifying address version
            $this->assertEquals(BitcoinLib::public_key_to_address($pubkey), $true_address);
        }
    }


    ///////////////////////////////////////////////////////

    public function testGetPrivKeyWif()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 5;

        for ($i = 0; $i < $cnt; $i++) {
            $hex = (string)str_pad(bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)), 64, '0', STR_PAD_LEFT);

            // create private key and WIF
            $wif = BitcoinLib::private_key_to_WIF($hex, FALSE, $this->addressVersion);
            $key = BitcoinLib::WIF_to_private_key($wif);
            $this->assertTrue($key['key'] == $hex);

            // create private key and WIF, without specifying address version
            $wif = BitcoinLib::private_key_to_WIF($hex, FALSE);
            $key = BitcoinLib::WIF_to_private_key($wif);
            $this->assertTrue($key['key'] == $hex);
        }
    }

    public function testImportPublicKey()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 1;

        for ($i = 0; $i < $cnt; $i++) {
            $hex = (string)str_pad(bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)), 64, '0', STR_PAD_LEFT);
            $public = BitcoinLib::private_key_to_public_key($hex, FALSE);
            $import = BitcoinLib::import_public_key($public);

            $this->assertTrue($import !== FALSE);
        }
    }

    public function testPublicKeyCompressionFaultKeys()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 2;

        for ($i = 0; $i < $cnt; $i++) {
            $hex = (string)str_pad(bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)), 64, '0', STR_PAD_LEFT);
            $public = BitcoinLib::private_key_to_public_key($hex, FALSE);
            $compress = BitcoinLib::compress_public_key($public);
            $decompress = BitcoinLib::decompress_public_key($compress);

            $this->assertTrue($decompress['public_key'] == $public);
        }
    }

    public function testPublicKeyCompression()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 2;

        for ($i = 0; $i < $cnt; $i++) {
            $key = BitcoinLib::get_new_private_key();
            $public = BitcoinLib::private_key_to_public_key($key, FALSE);
            $compress = BitcoinLib::compress_public_key($public);
            $decompress = BitcoinLib::decompress_public_key($compress);

            $this->assertTrue($decompress['public_key'] == $public);
        }
    }

    public function testValidatePublicKey()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 2;

        for ($i = 0; $i < $cnt; $i++) {
            $set = BitcoinLib::get_new_key_set($this->addressVersion);
            $this->assertTrue(BitcoinLib::validate_public_key($set['pubKey']));
        }
    }

    public function testPrivateKeyValidation()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 5;

        $val = FALSE;
        for ($i = 0; $i < $cnt; $i++) {
            $key = BitcoinLib::get_new_key_set($this->addressVersion, $val);
            $val = ($val == FALSE) ? TRUE : FALSE;
            $this->assertTrue(BitcoinLib::validate_WIF($key['privWIF'], $this->WIFVersion));
        }
    }

    public function testImportUncompOrCompPublicKey()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 5;

        $val = FALSE;
        for ($i = 0; $i < $cnt; $i++) {
            $key = BitcoinLib::get_new_private_key();
            $unc = BitcoinLib::private_key_to_public_key($key, FALSE);
            $pubkey = BitcoinLib::private_key_to_public_key($key, $val);
            $val = ($val == FALSE) ? TRUE : FALSE;
            $this->assertTrue($unc == BitcoinLib::import_public_key($pubkey));
        }
    }

    public function testSatoshiConversion()
    {
        $toSatoshi = [
            ["0.00000001", "1", 1],
            [0.00000001, "1", 1],
            ["0.29560000", "29560000", 29560000],
            [0.29560000, "29560000", 29560000],
            ["1.0000009", "100000090", 100000090],
            [1.0000009, "100000090", 100000090],
            ["1.00000009", "100000009", 100000009],
            [1.00000009, "100000009", 100000009],
            ["21000000.00000001", "2100000000000001", 2100000000000001],
            [21000000.00000001, "2100000000000001", 2100000000000001],
            ["21000000.0000009", "2100000000000090", 2100000000000090],
            [21000000.0000009, "2100000000000090", 2100000000000090],
            ["21000000.00000009", "2100000000000009", 2100000000000009],
            [21000000.00000009, "2100000000000009", 2100000000000009], // this is the max possible amount of BTC (atm)
            ["210000000.00000009", "21000000000000009", 21000000000000009],
            [210000000.00000009, "21000000000000009", 21000000000000009],
            // thee fail because when the BTC value is converted to a float it looses precision
            // ["2100000000.00000009", "210000000000000009", 210000000000000009],
            // [2100000000.00000009,   "210000000000000009", 210000000000000009],
        ];

        $toBTC = [
            ["1", "0.00000001"],
            [1, "0.00000001"],
            ["29560000", "0.29560000"],
            [29560000, "0.29560000"],
            ["100000090", "1.00000090"],
            [100000090, "1.00000090"],
            ["100000009", "1.00000009"],
            [100000009, "1.00000009"],
            ["2100000000000001", "21000000.00000001"],
            [2100000000000001, "21000000.00000001"],
            ["2100000000000090", "21000000.00000090"],
            [2100000000000090, "21000000.00000090"],
            ["2100000000000009", "21000000.00000009"], // this is the max possible amount of BTC (atm)
            [2100000000000009, "21000000.00000009"],
            ["21000000000000009", "210000000.00000009"],
            [21000000000000009, "210000000.00000009"],
            ["210000000000000009", "2100000000.00000009"],
            [210000000000000009, "2100000000.00000009"],
            ["2100000000000000009", "21000000000.00000009"],
            [2100000000000000009, "21000000000.00000009"],
            // these fail because they're > PHP_INT_MAX
            // ["21000000000000000009", "210000000000.00000009"],
            // [21000000000000000009,   "210000000000.00000009"],
        ];

        foreach ($toSatoshi as $i => $test) {
            $btc = $test[0];
            $satoshiString = $test[1];
            $satoshiInt = $test[2];

            $string = BitcoinLib::toSatoshiString($btc);
            $this->assertEquals($satoshiString, $string, "[{$i}] {$btc} => {$satoshiString} =? {$string}");
            $this->assertTrue($satoshiString === $string, "[{$i}] {$btc} => {$satoshiString} ==? {$string}");

            $int = BitcoinLib::toSatoshi($btc);
            $this->assertEquals($satoshiInt, $int, "[{$i}] {$btc} => {$satoshiInt} =? {$int}");
            $this->assertTrue($satoshiInt === $int, "[{$i}] {$btc} => {$satoshiInt} ==? {$int}");
        }
        foreach ($toBTC as $i => $test) {
            $satoshi = $test[0];
            $btc = $test[1];

            $this->assertEquals($btc, BitcoinLib::toBTC($satoshi), "[{$i}] {$satoshi} => {$btc}");
            $this->assertTrue($btc === BitcoinLib::toBTC($satoshi), "[{$i}] {$satoshi} => {$btc}");
        }
    }
}

