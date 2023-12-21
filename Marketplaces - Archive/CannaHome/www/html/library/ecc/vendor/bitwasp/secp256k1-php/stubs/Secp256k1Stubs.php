<?php

namespace {

    define('SECP256K1_CONTEXT_SIGN', 1 << 0);
    define('SECP256K1_CONTEXT_VERIFY', 1 << 1);
    define('SECP256K1_TYPE_CONTEXT', "secp256k1_context");
    define('SECP256K1_TYPE_PUBKEY', "secp256k1_pubkey");
    define('SECP256K1_TYPE_SIG', "secp256k1_ecdsa_signature");
    define('SECP256K1_TYPE_RECOVERABLE_SIG', "secp256k1_ecdsa_recoverable_signature");
    define('SECP256K1_EC_COMPRESSED', 258);

    /**
     * Create a Secp256k1 context resource
     *
     * @param $flags - create a VERIFY (or/and) SIGN context
     * @return resource
     */
    function secp256k1_context_create($flags)
    {
    }

    /**
     * Destroy a Secp256k1 context resource
     *
     * @param resource $secp256k1_context - context to destroy
     * @return bool
     */
    function secp256k1_context_destroy($secp256k1_context)
    {
    }

    /**
     * Clone a Secp256k1 context resource
     *
     * @param resource $secp256k1_context - context to clone
     * @return resource
     */
    function secp256k1_context_clone($secp256k1_context)
    {
    }

    /**
     * Updates the context randomization (used only internally for blinding)
     *
     * @param resource $secp256k1_context
     * @return int
     */
    function secp256k1_context_randomize($secp256k1_context)
    {
    }

    /**
     * Serializes a secp256k1_ecdsa_signature_t resource as DER into $signatureOut.
     *
     * @param resource $secp256k1_context
     * @param string $signatureOut
     * @param resource $secp256k1_ecdsa_signature
     * @return int
     */
    function secp256k1_ecdsa_signature_serialize_der($secp256k1_context, $signatureOut, $secp256k1_ecdsa_signature)
    {
    }

    /**
     * Parses a DER signature into a secp256k1_ecdsa_signature_t resource.
     *
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_signature
     * @param string $signatureIn
     * @return int
     */
    function secp256k1_ecdsa_signature_parse_der($secp256k1_context, $secp256k1_ecdsa_signature, $signatureIn)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_signature - signature resource
     * @param string $msg32 - hash of data to be verified
     * @param resource $secp256k1_pubkey - the public key resource
     * @return int
     */
    function secp256k1_ecdsa_verify($secp256k1_context, $secp256k1_ecdsa_signature, $msg32, $secp256k1_pubkey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_signature_out
     * @param resource $secp256k1_ecdsa_signature_in
     * @return int
     */
    function secp256k1_ecdsa_signature_normalize($secp256k1_context, $secp256k1_ecdsa_signature_out, $secp256k1_ecdsa_signature_in)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_signature
     * @param string $msg32
     * @param string $privateKey
     * @return int
     */
    function secp256k1_ecdsa_sign($secp256k1_context, $secp256k1_ecdsa_signature, $msg32, $privateKey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_recoverable_signature
     * @param string $msg32
     * @param string $privateKey
     * @return int
     */
    function secp256k1_ecdsa_sign_recoverable($secp256k1_context, $secp256k1_ecdsa_recoverable_signature, $msg32, $privateKey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_recoverable_signature
     * @param string $signatureOut
     * @param int $recid
     * @return int
     */
    function secp256k1_ecdsa_recoverable_signature_serialize_compact($secp256k1_context, $secp256k1_ecdsa_recoverable_signature, $signatureOut, $recid)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_recoverable_signature
     * @param string $input64
     * @param int $recid
     * @return int
     */
    function secp256k1_ecdsa_recoverable_signature_parse_compact($secp256k1_context, $secp256k1_ecdsa_recoverable_signature, $input64, $recid)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_ecdsa_signature
     * @param resource $secp256k1_ecdsa_recoverable_signature
     * @return int
     */
    function secp256k1_ecdsa_recoverable_signature_convert($secp256k1_context, $secp256k1_ecdsa_signature, $secp256k1_ecdsa_recoverable_signature)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param resource $secp256k1_ecdsa_recoverable_signature
     * @param string $msg32
     * @return int
     */
    function secp256k1_ecdsa_recover($secp256k1_context, $secp256k1_pubkey, $secp256k1_ecdsa_recoverable_signature, $msg32)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $secretKey
     * @return int
     */
    function secp256k1_ec_pubkey_create($secp256k1_context, $secp256k1_pubkey, $secretKey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $publicKey
     * @param resource[] $publicKeys
     * @return int
     */
    function secp256k1_ec_pubkey_combine($secp256k1_context, $publicKey, array $publicKeys)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $pubkeyIn
     * @return int
     */
    function secp256k1_ec_pubkey_parse($secp256k1_context, $secp256k1_pubkey, $pubkeyIn)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $pubkeyOut
     * @param bool $compressed
     * @return int
     */
    function secp256k1_ec_pubkey_serialize($secp256k1_context, $pubkeyOut, $secp256k1_pubkey, $compressed)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_add($secp256k1_context, $privateKey, $tweak)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $privateKey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_privkey_tweak_mul($secp256k1_context, $privateKey, $tweak)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_add($secp256k1_context, $secp256k1_pubkey, $tweak)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $tweak
     * @return int
     */
    function secp256k1_ec_pubkey_tweak_mul($secp256k1_context, $secp256k1_pubkey, $tweak)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $secKey
     * @return int
     */
    function secp256k1_ec_seckey_verify($secp256k1_context, $secKey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $result
     * @param resource $secp256k1_pubkey
     * @param string $privKey
     * @return int
     */
    function secp256k1_ecdh($secp256k1_context, $result, $secp256k1_pubkey, $privKey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $sig64
     * @param string $msg32
     * @param string $seckey
     * @return int
     */
    function secp256k1_schnorr_sign($secp256k1_context, $sig64, $msg32, $seckey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $sig64
     * @param string $msg32
     * @param resource $secp256k1_pubkey
     * @return int
     */
    function secp256k1_schnorr_verify($secp256k1_context, $sig64, $msg32, $secp256k1_pubkey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $pubnonce
     * @param string $privnonce
     * @param string $msg32
     * @param string $seckey
     * @return int
     */
    function secp256k1_schnorr_generate_nonce_pair($secp256k1_context, $pubnonce, $privnonce, $msg32, $seckey)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param resource $secp256k1_pubkey
     * @param string $sig64
     * @param string $msg32
     * @return int
     */
    function secp256k1_schnorr_recover($secp256k1_context, $secp256k1_pubkey, $sig64, $msg32)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $sig64
     * @param string[] $signatures
     * @return int
     */
    function secp256k1_schnorr_partial_combine($secp256k1_context, $sig64, array $signatures)
    {
    }

    /**
     * @param resource $secp256k1_context
     * @param string $sig64
     * @param string $msg32
     * @param string $seckey
     * @param resource $pubnonce_others
     * @param string $secnonce32
     * @return int
     */
    function secp256k1_schnorr_partial_sign($secp256k1_context, $sig64, $msg32, $seckey, $pubnonce_others, $secnonce32)
    {
    }

}

