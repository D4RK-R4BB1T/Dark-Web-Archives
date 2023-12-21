<?php

class BIP32CoreTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {

    }

    public function setup()
    {

    }

    public function tearDown()
    {

    }


    public function testGMP()
    {
        $math = \Mdanter\Ecc\EccFactory::getAdapter();

        $I_l = "e97a4d6be13f8f5804c0a76080428fc6d51260f74801678c4127045d2640af14";
        $private_key = "142018c66b43a95de58c1cf603446fc0da322bc15fb4df068b844b57c706dd05";
        $n = "115792089237316195423570985008687907852837564279074904382605163141518161494337";

        $gmp_I_l = gmp_init($I_l, 16);
        $gmp_private_key = gmp_init($private_key, 16);
        $gmp_add = gmp_add($gmp_I_l, $gmp_private_key);
        $gmp_add_res = gmp_strval($gmp_add, 10);


        $this->assertEquals("105604983404708440304568772161069255144976878830542744455590282065741265022740", gmp_strval($gmp_I_l));
        $this->assertEquals("9102967069016248707169900673545386030247334423973996501079368232055584775429", gmp_strval($gmp_private_key));
        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", gmp_strval($gmp_add));
        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", gmp_strval(gmp_div_r($gmp_add, gmp_init($n))));

        $this->assertEquals("-4", gmp_strval(gmp_cmp(0, gmp_div_r($gmp_add, $n))));
        $this->assertEquals("230500039711040884435309657843302549028061777533591645339274813439315011292506", gmp_strval(gmp_add(gmp_init($n), gmp_div_r($gmp_add, gmp_init($n)))));


        $gmp_mod2 = $math->mod($gmp_add_res, $n);

        $this->assertTrue(is_string($gmp_mod2));
        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", $gmp_mod2);

        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", $gmp_mod2);

        // when no base is provided both a resource and string work
        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", gmp_strval(gmp_init($gmp_mod2)));
        $this->assertEquals("114707950473724689011738672834614641175224213254516740956669650297796849798169", gmp_strval($gmp_mod2));

        // when base is provided it fails on HHVM when inputting a string
        $this->assertEquals("fd9a66324c8338b5ea4cc4568386ff87af448cb8a7b64692ccab4fb4ed478c19", gmp_strval(gmp_init($gmp_mod2), 16));
        // $this->assertEquals("fd9a66324c8338b5ea4cc4568386ff87af448cb8a7b64692ccab4fb4ed478c19", gmp_strval($gmp_mod2, 16));

        $this->assertEquals("fd9a66324c8338b5ea4cc4568386ff87af448cb8a7b64692ccab4fb4ed478c19", str_pad(gmp_strval(gmp_init($gmp_mod2), 16), 64, '0', STR_PAD_LEFT));
    }
}
