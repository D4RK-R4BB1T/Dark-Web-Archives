<?php

namespace BitWasp\Secp256k1Tests;

class Secp256k1SignRecoverableTest extends TestCase
{
    public function test()
    {
        $privKey = hash('sha256', 'private key', true);
        $msg32 = hash('sha256', 'msg', true);
        $pub_t = '';
        /** @var resource $pub_t */
        $context = TestCase::getContext();

        // Create public key of our private key
        $this->assertEquals(1, secp256k1_ec_pubkey_create($context, $pub_t, $privKey));

        // Create recoverable signature
        $r_sig_t = '';
        /** @var resource $r_sig_t */
        $this->assertEquals(1, secp256k1_ecdsa_sign_recoverable($context, $r_sig_t, $msg32, $privKey));
        $this->assertEquals(SECP256K1_TYPE_RECOVERABLE_SIG, get_resource_type($r_sig_t));

        // Recover public key from the signature
        $r_pubkey_t = '';
        /** @var resource $r_pubkey_t */
        $this->assertEquals(1, secp256k1_ecdsa_recover($context, $r_pubkey_t, $r_sig_t, $msg32));

        // Compare the two public keys
        $sPubkey = '';
        $srPubkey = '';
        $this->assertEquals(1, secp256k1_ec_pubkey_serialize($context, $sPubkey, $pub_t, 0));
        $this->assertEquals(1, secp256k1_ec_pubkey_serialize($context, $srPubkey, $r_pubkey_t, 0));
        $this->assertEquals($sPubkey, $srPubkey);

        // Double check that serialize(sig) == serialize(parse(serialize(sig))
        $sSig = '';
        /** @var resource $sSig */
        $recid = '';
        secp256k1_ecdsa_recoverable_signature_serialize_compact($context, $r_sig_t, $sSig, $recid);

        $parsedSig = '';
        /** @var resource $parsedSig */
        $this->assertEquals(1, secp256k1_ecdsa_recoverable_signature_parse_compact($context, $parsedSig, $sSig, $recid));

        $sSigAgain = '';
        $recidAgain = '';
        secp256k1_ecdsa_recoverable_signature_serialize_compact($context, $parsedSig, $sSigAgain, $recidAgain);


        // Prepare expected DER sig
        $rl = 32;
        $r = substr($sSig, 0, 32);
        if (ord($sSig[0]) > 0x80) {
            $rl++;
            $r = "\x00" . $r;
        }
        $sl = 32;
        $s = substr($sSig, 32, 32);
        if (ord($sSig[32]) > 0x80) {
            $sl++;
            $s = "\x00" . $s;
        }
        $t = 4 + $rl + $sl;
        $der = "\x30" . chr($t) . "\x02" . chr($rl) . $r . "\x02" . chr($sl) . $s;

        $plain = '';
        /** @var resource $plain */

        // Test that conversion is successful
        $this->assertEquals(1, secp256k1_ecdsa_recoverable_signature_convert($context, $plain, $r_sig_t));

        // Test the converted sig's DER output matches what we expect
        $derSer = '';
        $this->assertEquals(1, secp256k1_ecdsa_signature_serialize_der($context, $derSer, $plain));
        $this->assertEquals($der, $derSer);
    }
}