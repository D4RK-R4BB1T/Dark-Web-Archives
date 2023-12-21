<?php

namespace BitWasp\BitcoinLib\BIP39;

class BIP39
{

    protected static $defaultWordList;

    /**
     * generate random entropy using \MCRYPT_DEV_URANDOM
     *
     * @param int  $size                    desired strength, must be multiple of 32, recommended 128-256
     * @throws \Exception
     * @return string                       hex Entropy
     */
    public static function generateEntropy($size = 256)
    {
        if ($size % 32 !== 0) {
            throw new \InvalidArgumentException("Entropy must be in a multiple of 32");
        }

        return bin2hex(mcrypt_create_iv($size / 8, \MCRYPT_DEV_URANDOM));
    }

    /**
     * create Mnemonic from Entropy
     *
     * @param string        $entropyHex     hex Entropy
     * @param BIP39WordList $wordList       defaults to BIP39 english word list
     * @return string                       hex Mnemonic
     * @throws \Exception
     */
    public static function entropyToMnemonic($entropyHex, BIP39WordList $wordList = null)
    {
        // calculate entropy, /2 because PHP can't do bytes
        $ENT = (strlen($entropyHex) / 2) * 8;
        // calculate how long the checksum should be
        $CS = $ENT / 32;

        // get the checksum
        $checksum = self::entropyChecksum($entropyHex);

        // create the string of bits to use
        $bits = str_pad(gmp_strval(gmp_init($entropyHex, 16), 2) . $checksum, $ENT + $CS, "0", STR_PAD_LEFT); // PHP trims off 0s

        // use provided wordList or default
        $wordList = $wordList ?: self::defaultWordList();

        // build word list
        $result = array();
        foreach (str_split($bits, 11) as $bit) {
            $idx = gmp_strval(gmp_init($bit, 2), 10);

            $result[] = $wordList->getWord($idx);
        }

        // implode and enjoy
        $result = implode(" ", $result);

        return $result;
    }

    /**
     * create Checksum from Entropy
     *
     * @param string        $entropyHex     hex Entropy
     * @return string                       bits checksum
     */
    protected static function entropyChecksum($entropyHex)
    {
        // calculate entropy, /2 because PHP can't do bytes
        $ENT = (strlen($entropyHex) / 2) * 8;
        // calculate how long the checksum should be
        $CS = $ENT / 32;

        $hashHex = hash("sha256", hex2bin($entropyHex));

        // create full checksum
        $hashBits = gmp_strval(gmp_init($hashHex, 16), 2);
        $hashBits = str_pad($hashBits, 256, "0", STR_PAD_LEFT); // PHP trims off 0s

        // take only the bits we need
        $checksum = substr($hashBits, 0, $CS);

        return $checksum;
    }

    /**
     * create Entropy from Mnemonic
     *
     * @param string        $mnemonic       hex Mnemonic
     * @param BIP39WordList $wordList       defaults to BIP39 english word list
     * @return string                       hex Entropy
     * @throws \Exception
     */
    public static function mnemonicToEntropy($mnemonic, BIP39WordList $wordList = null)
    {
        $words = explode(" ", $mnemonic);

        if (count($words) % 3 !== 0) {
            throw new \InvalidArgumentException("Invalid mnemonic");
        }

        // wordList or default
        $wordList = $wordList ?: self::defaultWordList();

        // convert the words back into bit strings
        $bits = array();
        foreach ($words as $word) {
            $idx = $wordList->getIndex($word);

            $bits[] = str_pad(gmp_strval(gmp_init($idx, 10), 2), 11, "0", STR_PAD_LEFT); // PHP trims off 0s
        }

        // implode the bitstring to it's original form
        $bits = implode("", $bits);

        // calculate how long the checksum should be
        $CS = strlen($bits) / 33;
        // calculate how long the original entropy should be
        $ENT = strlen($bits) - $CS;

        // get the checksum and the original entropy
        $checksum = substr($bits, -1 * $CS);
        $entropyBits = substr($bits, 0, -1 * $CS);

        // recreate the hex for the entropy
        $entropyHex = str_pad(gmp_strval(gmp_init($entropyBits, 2), 16), ($ENT * 2) / 8, "0", STR_PAD_LEFT); // PHP trims off 0s

        // validate
        if ($checksum !== self::entropyChecksum($entropyHex)) {
            throw new \InvalidArgumentException("Checksum does not match!");
        }

        return $entropyHex;
    }

    /**
     * create Seed from Mnemonic and Passphrase
     *
     * @param string        $mnemonic       hex Mnemonic
     * @param string        $passphrase
     * @return mixed
     * @throws \Exception
     */
    public static function mnemonicToSeedHex($mnemonic, $passphrase)
    {
        $passphrase = self::normalizePassphrase($passphrase);
        $salt = "mnemonic" . $passphrase;
        return hash_pbkdf2("sha512", $mnemonic, $salt, 2048, 64 * 2, false);
    }

    /**
     * normalize Passphrase if it's UTF-8
     *
     * requires the Normalizer class from the PECL intl extension
     *  so if the Passphrase is UTF-8 and the class isn't there we throw an error!
     *
     * @param string        $passphrase
     * @return string
     * @throws \Exception
     */
    public static function normalizePassphrase($passphrase)
    {
        if (!class_exists('Normalizer')) {
            if (mb_detect_encoding($passphrase) == "UTF-8") {
                throw new \Exception("UTF-8 passphrase is not supported without the PECL intl extension installed.");
            } else {
                return $passphrase;
            }
        }

        return \Normalizer::normalize($passphrase, \Normalizer::FORM_KD);
    }

    /**
     * get the default (english BIP39) word list
     *
     * @return BIP39EnglishWordList
     */
    public static function defaultWordList()
    {
        if (self::$defaultWordList === null) {
            self::$defaultWordList = new BIP39EnglishWordList();
        }

        return self::$defaultWordList;
    }
}
