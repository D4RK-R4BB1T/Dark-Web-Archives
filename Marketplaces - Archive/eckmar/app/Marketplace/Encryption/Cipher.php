<?php


namespace App\Marketplace\Encryption;


class Cipher
{

    /**
     * Encrypts plaintext message with Encryption Key
     * @param $message
     * @param EncryptionKey $encryptionKey
     * @return EncryptedMessage
     */
    public static function encryptMessage($message,EncryptionKey $encryptionKey){
        $message_nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
        $ciphertext = sodium_crypto_box(
            $message,
            $message_nonce,
            $encryptionKey->getEncryptionKey()
        );
        return new EncryptedMessage($ciphertext,$message_nonce);
    }

    public static function convertToHex($data) {
        return encrypt($data);
        //return sodium_bin2hex($data);
    }
    public static function convertToBin($data) {
        return decrypt($data);
       //return sodium_hex2bin($data);
    }

}