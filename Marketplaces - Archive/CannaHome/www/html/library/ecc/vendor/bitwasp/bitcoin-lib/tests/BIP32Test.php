<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BitcoinLib;

/**
 * test vectors generated/verified using http://bip32.org/
 *
 * Class BIP32Test
 */
class BIP32Test extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {

    }

    public function setup()
    {
        // ensure we're set to bitcoin and not bitcoin-testnet
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function tearDown()
    {

    }

    public function testDefinitionTuple()
    {
        $masterKey = BIP32::master_key("000102030405060708090a0b0c0d0e0f", "bitcoin", false);

        $this->assertEquals("00000003", BIP32::calc_address_bytes("3", false));
        $this->assertEquals("80000003", BIP32::calc_address_bytes("3", true));
        $this->assertEquals("00000003", BIP32::calc_address_bytes("3'", false));
        $this->assertEquals("80000003", BIP32::calc_address_bytes("3'", true));

        $this->assertEquals(
            [
                "00000003",
                "00000003",
                "00000003",
                "00000000",
            ],
            BIP32::get_definition_tuple($masterKey[0], "m/3/3/3")
        );

        $this->assertEquals(
            [
                "00000003",
                "80000003",
                "00000003",
                "00000000",
            ],
            BIP32::get_definition_tuple($masterKey[0], "m/3/3'/3")
        );
    }

    public function testCKD()
    {
        // create master key
        $masterKey = BIP32::master_key("000102030405060708090a0b0c0d0e0f", "bitcoin", false);
        $this->assertEquals("m", $masterKey[1]);
        $this->assertEquals("xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi", $masterKey[0]);

        // get the "m" derivation, should be equal to the master key, by absolute path
        $sameMasterKey = BIP32::build_key($masterKey, "m");
        $this->assertEquals("m", $sameMasterKey[1]);
        $this->assertEquals("xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi", $sameMasterKey[0]);

        // get the "m/0" derivation, should be the first child, by absolute path
        $firstChildKey = BIP32::build_key($masterKey, "m/0");
        $this->assertEquals("m/0", $firstChildKey[1]);
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey[0]);

        // get the "m/0" derivation, should be the first child, by relative path
        $firstChildKey = BIP32::build_key($masterKey, "0");
        $this->assertEquals("m/0", $firstChildKey[1]);
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey[0]);

        // get the "m/0" derivation, should be the first child, by relative path, by only providing the key and not the original path
        $firstChildKey = BIP32::build_key($masterKey[0], "0");
        $this->assertEquals("m/0", $firstChildKey[1]);
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey[0]);

        // get the "m/0" derivation, should be the first child, by absolute path, by only providing the key and not the original path
        $firstChildKey = BIP32::build_key($masterKey[0], "m/0");
        $this->assertEquals("m/0", $firstChildKey[1]);
        $this->assertEquals("xprv9uHRZZhbkedL37eZEnyrNsQPFZYRAvjy5rt6M1nbEkLSo378x1CQQLo2xxBvREwiK6kqf7GRNvsNEchwibzXaV6i5GcsgyjBeRguXhKsi4R", $firstChildKey[0]);

        // get the "m/44'/0'/0'/0/0" derivation, by absolute path
        $bip44ChildKey = BIP32::build_key($masterKey, "m/44'/0'/0'/0/0");
        $this->assertEquals("m/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey[0]);

        // get the "m/44'/0'/0'/0/0" derivation, by relative path, in 2 steps
        $bip44ChildKey = BIP32::build_key($masterKey, "44'/0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'/0/0");
        $this->assertEquals("m/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey[0]);

        // get the "m/44'/0'/0'/0/0" derivation, by relative path, in 2 steps
        $bip44ChildKey = BIP32::build_key($masterKey, "44'/0'/0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0/0");
        $this->assertEquals("m/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey[0]);

        // get the "m/44'/0'/0'/0/0" derivation, by relative path, in 2 steps
        $bip44ChildKey = BIP32::build_key($masterKey, "44'/0'/0'/0");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $this->assertEquals("m/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey[0]);

        // get the "m/44'/0'/0'/0/0" derivation, by relative path, in single steps
        $bip44ChildKey = BIP32::build_key($masterKey, "44'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $this->assertEquals("m/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xprvA4A9CuBXhdBtCaLxwrw64Jaran4n1rgzeS5mjH47Ds8V67uZS8tTkG8jV3BZi83QqYXPcN4v8EjK2Aof4YcEeqLt688mV57gF4j6QZWdP9U", $bip44ChildKey[0]);

        // we're expecting an exception
        $e = null;
        try {
            $bip44ChildKey = BIP32::build_key($masterKey, "m/44'/0'/0'/0/0");
            BIP32::build_key($bip44ChildKey, "m/44'/1'/0'/0/0");
        } catch (\Exception $e) {
        }
        $this->assertTrue(!!$e, "build_key should throw exception with bad path");
    }

    public function testCKDPrivateToPublic()
    {
        // create master key
        $masterKey = BIP32::master_key("000102030405060708090a0b0c0d0e0f", "bitcoin", false);
        $this->assertEquals("m", $masterKey[1]);
        $this->assertEquals("xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi", $masterKey[0]);

        // get the "m" derivation, should be equal to the master key, by absolute path
        $pubMasterKey = BIP32::build_key($masterKey, "M");
        $this->assertEquals("M", $pubMasterKey[1]);
        $this->assertEquals("xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8", $pubMasterKey[0]);

        // get the "M/0" derivation, should be the first child, by absolute path
        $firstChildKey = BIP32::build_key($masterKey, "M/0");
        $this->assertEquals("M/0", $firstChildKey[1]);
        $this->assertEquals("xpub68Gmy5EVb2BdFbj2LpWrk1M7obNuaPTpT5oh9QCCo5sRfqSHVYWex97WpDZzszdzHzxXDAzPLVSwybe4uPYkSk4G3gnrPqqkV9RyNzAcNJ1", $firstChildKey[0]);

        // get the "m/0" derivation, should be the first child, by absolute path, by only providing the key and not the original path
        $firstChildKey = BIP32::build_key($masterKey[0], "M/0");
        $this->assertEquals("M/0", $firstChildKey[1]);
        $this->assertEquals("xpub68Gmy5EVb2BdFbj2LpWrk1M7obNuaPTpT5oh9QCCo5sRfqSHVYWex97WpDZzszdzHzxXDAzPLVSwybe4uPYkSk4G3gnrPqqkV9RyNzAcNJ1", $firstChildKey[0]);

        // get the "M/44'/0'/0'/0/0" derivation, by absolute path
        $bip44ChildKey = BIP32::build_key($masterKey, "M/44'/0'/0'/0/0");
        $this->assertEquals("M/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xpub6H9VcQiRXzkBR4RS3tU6RSXb8ouGRKQr1f1NXfTinCfTxvEhygCiJ4TDLHz1dyQ6d2Vz8Ne7eezkrViwaPo2ZMsNjVtFwvzsQXCDV6HJ3cV", $bip44ChildKey[0]);

        // get the "M/44'/0'/0'/0/0" derivation, by relative path, in 2 steps
        $bip44ChildKey = BIP32::build_key($masterKey, "M/44'/0'/0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0/0");
        $this->assertEquals("M/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xpub6H9VcQiRXzkBR4RS3tU6RSXb8ouGRKQr1f1NXfTinCfTxvEhygCiJ4TDLHz1dyQ6d2Vz8Ne7eezkrViwaPo2ZMsNjVtFwvzsQXCDV6HJ3cV", $bip44ChildKey[0]);

        // get the "M/44'/0'/0'/0/0" derivation, by relative path, in 2 steps
        $bip44ChildKey = BIP32::build_key($masterKey, "M/44'/0'/0'/0");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $this->assertEquals("M/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xpub6H9VcQiRXzkBR4RS3tU6RSXb8ouGRKQr1f1NXfTinCfTxvEhygCiJ4TDLHz1dyQ6d2Vz8Ne7eezkrViwaPo2ZMsNjVtFwvzsQXCDV6HJ3cV", $bip44ChildKey[0]);

        // get the "M/44'/0'/0'/0/0" derivation, by relative path, in single steps
        $bip44ChildKey = BIP32::build_key($masterKey, "m/44'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0");
        $bip44ChildKey = BIP32::extended_private_to_public($bip44ChildKey);

        $this->assertEquals("M/44'/0'/0'/0/0", $bip44ChildKey[1]);
        $this->assertEquals("xpub6H9VcQiRXzkBR4RS3tU6RSXb8ouGRKQr1f1NXfTinCfTxvEhygCiJ4TDLHz1dyQ6d2Vz8Ne7eezkrViwaPo2ZMsNjVtFwvzsQXCDV6HJ3cV", $bip44ChildKey[0]);

        // we're expecting an exception
        $e = null;
        try {
            $bip44ChildKey = BIP32::build_key($masterKey, "M/44'/0'");
            $bip44ChildKey = BIP32::build_key($bip44ChildKey, "0'");
        } catch (\Exception $e) {
        }
        $this->assertTrue(!!$e, "build_key should throw exception with bad path");
    }

    public function testMasterKeyFromSeed()
    {
        $intended_pub = 'xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8';
        $intended_priv = 'xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi';

        $master = BIP32::master_key('000102030405060708090a0b0c0d0e0f');
        $this->assertEquals($master[0], $intended_priv);

        $public = BIP32::extended_private_to_public($master);
        $this->assertEquals($public[0], $intended_pub);

    }

    public function __helpTestChildKeyDerivation()
    {

    }

    public function testChildKeyDerivationOne()
    {

        $test_vectors = [
            0 => [
                'master' => '000102030405060708090a0b0c0d0e0f',
                'ckd' => [
                    0 => [
                        'child' => "0'",
                        'priv' => 'xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7',
                        'pub' => 'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw'
                    ],
                    1 => [
                        'child' => '1',
                        'priv' => 'xprv9wTYmMFdV23N2TdNG573QoEsfRrWKQgWeibmLntzniatZvR9BmLnvSxqu53Kw1UmYPxLgboyZQaXwTCg8MSY3H2EU4pWcQDnRnrVA1xe8fs',
                        'pub' => 'xpub6ASuArnXKPbfEwhqN6e3mwBcDTgzisQN1wXN9BJcM47sSikHjJf3UFHKkNAWbWMiGj7Wf5uMash7SyYq527Hqck2AxYysAA7xmALppuCkwQ'
                    ],
                    2 => [
                        'child' => "2'",
                        'priv' => 'xprv9z4pot5VBttmtdRTWfWQmoH1taj2axGVzFqSb8C9xaxKymcFzXBDptWmT7FwuEzG3ryjH4ktypQSAewRiNMjANTtpgP4mLTj34bhnZX7UiM',
                        'pub' => 'xpub6D4BDPcP2GT577Vvch3R8wDkScZWzQzMMUm3PWbmWvVJrZwQY4VUNgqFJPMM3No2dFDFGTsxxpG5uJh7n7epu4trkrX7x7DogT5Uv6fcLW5'
                    ],
                    3 => [
                        'child' => '2',
                        'priv' => 'xprvA2JDeKCSNNZky6uBCviVfJSKyQ1mDYahRjijr5idH2WwLsEd4Hsb2Tyh8RfQMuPh7f7RtyzTtdrbdqqsunu5Mm3wDvUAKRHSC34sJ7in334',
                        'pub' => 'xpub6FHa3pjLCk84BayeJxFW2SP4XRrFd1JYnxeLeU8EqN3vDfZmbqBqaGJAyiLjTAwm6ZLRQUMv1ZACTj37sR62cfN7fe5JnJ7dh8zL4fiyLHV'
                    ],
                    4 => [
                        'child' => '1000000000',
                        'priv' => 'xprvA41z7zogVVwxVSgdKUHDy1SKmdb533PjDz7J6N6mV6uS3ze1ai8FHa8kmHScGpWmj4WggLyQjgPie1rFSruoUihUZREPSL39UNdE3BBDu76',
                        'pub' => 'xpub6H1LXWLaKsWFhvm6RVpEL9P4KfRZSW7abD2ttkWP3SSQvnyA8FSVqNTEcYFgJS2UaFcxupHiYkro49S8yGasTvXEYBVPamhGW6cFJodrTHy'
                    ]
                ]
            ],
            1 => [
                'master' => 'fffcf9f6f3f0edeae7e4e1dedbd8d5d2cfccc9c6c3c0bdbab7b4b1aeaba8a5a29f9c999693908d8a8784817e7b7875726f6c696663605d5a5754514e4b484542',
                'ckd' => [
                    0 => [
                        'child' => "0",
                        'priv' => 'xprv9vHkqa6EV4sPZHYqZznhT2NPtPCjKuDKGY38FBWLvgaDx45zo9WQRUT3dKYnjwih2yJD9mkrocEZXo1ex8G81dwSM1fwqWpWkeS3v86pgKt',
                        'pub' => 'xpub69H7F5d8KSRgmmdJg2KhpAK8SR3DjMwAdkxj3ZuxV27CprR9LgpeyGmXUbC6wb7ERfvrnKZjXoUmmDznezpbZb7ap6r1D3tgFxHmwMkQTPH'
                    ],
                    1 => [
                        'child' => "2147483647'",
                        'priv' => 'xprv9wSp6B7kry3Vj9m1zSnLvN3xH8RdsPP1Mh7fAaR7aRLcQMKTR2vidYEeEg2mUCTAwCd6vnxVrcjfy2kRgVsFawNzmjuHc2YmYRmagcEPdU9',
                        'pub' => 'xpub6ASAVgeehLbnwdqV6UKMHVzgqAG8Gr6riv3Fxxpj8ksbH9ebxaEyBLZ85ySDhKiLDBrQSARLq1uNRts8RuJiHjaDMBU4Zn9h8LZNnBC5y4a'
                    ],
                    2 => [
                        'child' => "1",
                        'priv' => 'xprv9zFnWC6h2cLgpmSA46vutJzBcfJ8yaJGg8cX1e5StJh45BBciYTRXSd25UEPVuesF9yog62tGAQtHjXajPPdbRCHuWS6T8XA2ECKADdw4Ef',
                        'pub' => 'xpub6DF8uhdarytz3FWdA8TvFSvvAh8dP3283MY7p2V4SeE2wyWmG5mg5EwVvmdMVCQcoNJxGoWaU9DCWh89LojfZ537wTfunKau47EL2dhHKon'
                    ],
                    3 => [
                        'child' => "2147483646'",
                        'priv' => 'xprvA1RpRA33e1JQ7ifknakTFpgNXPmW2YvmhqLQYMmrj4xJXXWYpDPS3xz7iAxn8L39njGVyuoseXzU6rcxFLJ8HFsTjSyQbLYnMpCqE2VbFWc',
                        'pub' => 'xpub6ERApfZwUNrhLCkDtcHTcxd75RbzS1ed54G1LkBUHQVHQKqhMkhgbmJbZRkrgZw4koxb5JaHWkY4ALHY2grBGRjaDMzQLcgJvLJuZZvRcEL'
                    ],
                    4 => [
                        'child' => '2',
                        'priv' => 'xprvA2nrNbFZABcdryreWet9Ea4LvTJcGsqrMzxHx98MMrotbir7yrKCEXw7nadnHM8Dq38EGfSh6dqA9QWTyefMLEcBYJUuekgW4BYPJcr9E7j',
                        'pub' => 'xpub6FnCn6nSzZAw5Tw7cgR9bi15UV96gLZhjDstkXXxvCLsUXBGXPdSnLFbdpq8p9HmGsApME5hQTZ3emM2rnY5agb9rXpVGyy3bdW6EEgAtqt'
                    ]
                ]
            ]
        ];

        foreach ($test_vectors as $test => $vector) {
            $master = BIP32::master_key($vector['master']);
            $key = $master;
            foreach ($vector['ckd'] as $test_array) {
                $key = BIP32::build_key($key, $test_array['child']);
                $this->assertEquals($key[0], $test_array['priv']);
                $pub = BIP32::extended_private_to_public($key);
                $this->assertEquals($pub[0], $test_array['pub']);
            }
        }
    }

}

;
