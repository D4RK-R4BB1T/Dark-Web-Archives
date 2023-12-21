<?php

namespace App\Marketplace\Encryption;


class Keypair
{
    private $keys;

    /**
     * Generate new KeyPair
     * Keypair constructor.
     */
    public function __construct() {
        $this->keys = sodium_crypto_box_keypair();
    }

    /**
     * Extract public key from keypair
     * @return string
     */
    public function getPublicKey() {
        return sodium_crypto_box_publickey($this->keys);
    }

    /**
     * Extract private key from keypair
     * @return string
     */
    public function getPrivateKey() {
        return sodium_crypto_box_secretkey($this->keys);
    }


}