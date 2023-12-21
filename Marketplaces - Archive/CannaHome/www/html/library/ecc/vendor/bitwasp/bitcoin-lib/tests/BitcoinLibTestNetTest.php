<?php

use BitWasp\BitcoinLib\BitcoinLib;

require_once(__DIR__ . '/BitcoinLibTest.php');

class BitcoinLibTestNetTest extends BitcoinLibTest
{
    public function __construct()
    {
        parent::__construct();

        $this->addressVersion = '6f';
        $this->p2shAddressVersion = 'c4';
        $this->WIFVersion = 'ef';
        $this->keyConversionData = array(
            "5HxWvvfubhXpYYpS3tJkw6fq9jE9j18THftkZjHHfmFiWtmAbrj" => "n4mo8QZBt6zjpVmr28rx985jdiw9zwfcvS",
            "5KC4ejrDjv152FGwP386VD1i2NYc5KkfSMyv1nGy1VGDxGHqVY3" => "mubvNHAEAdWomjnVtifiQPrNuFpf6cT8ie",
            "Kwr371tjA9u2rFSMZjTNun2PXXP3WPZu2afRHTcta6KxEUdm1vEw" => "n3KG9rxrmQcaSJmX26RS7VqdiX1w3iSB5t"
        );
    }

    public function setup()
    {
        parent::setup();

        // ensure we're set to bitcoin-testnet and not bitcoin
        BitcoinLib::setMagicByteDefaults('bitcoin-testnet');
    }
}

