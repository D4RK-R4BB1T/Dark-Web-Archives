/* $Id$ */
#include <secp256k1.h>
#include <secp256k1_ecdh.h>
#include <secp256k1_recovery.h>
#include <secp256k1_schnorr.h>

#ifndef PHP_SECP256K1_H
#define PHP_SECP256K1_H

extern zend_module_entry secp256k1_module_entry;
#define phpext_secp256k1_ptr &secp256k1_module_entry

#define PHP_SECP256K1_VERSION "0.1.0"
#define SECP256K1_CTX_RES_NAME "secp256k1_context"
#define SECP256K1_PUBKEY_RES_NAME "secp256k1_pubkey"
#define SECP256K1_SIG_RES_NAME "secp256k1_ecdsa_signature"
#define SECP256K1_RECOVERABLE_SIG_RES_NAME "secp256k1_ecdsa_recoverable_signature"

#ifdef ZTS
# define SECP256K1_G(v) TSRMG(secp256k1_globals_id, zend_secp256k1_globals *, v)
#else
# define SECP256K1_G(v) (secp256k1_globals.v)
#endif

#define MAX_SIGNATURE_LENGTH 72
#define COMPACT_SIGNATURE_LENGTH 64
#define PUBKEY_COMPRESSED_LENGTH 33
#define PUBKEY_UNCOMPRESSED_LENGTH 65
#define HASH_LENGTH 32
#define SECRETKEY_LENGTH 32
#define DERKEY_LENGTH 300

PHP_FUNCTION(secp256k1_context_create);
PHP_FUNCTION(secp256k1_context_destroy);
PHP_FUNCTION(secp256k1_context_clone);
PHP_FUNCTION(secp256k1_context_randomize);

PHP_FUNCTION(secp256k1_ecdsa_verify);
PHP_FUNCTION(secp256k1_ecdsa_sign);
PHP_FUNCTION(secp256k1_ecdsa_signature_parse_der);
PHP_FUNCTION(secp256k1_ecdsa_signature_serialize_der);
PHP_FUNCTION(secp256k1_ecdsa_signature_normalize);

PHP_FUNCTION(secp256k1_ec_seckey_verify);
PHP_FUNCTION(secp256k1_ec_pubkey_create);
PHP_FUNCTION(secp256k1_ec_pubkey_combine);
PHP_FUNCTION(secp256k1_ec_pubkey_parse);
PHP_FUNCTION(secp256k1_ec_pubkey_serialize);

PHP_FUNCTION(secp256k1_ec_privkey_tweak_add);
PHP_FUNCTION(secp256k1_ec_privkey_tweak_mul);
PHP_FUNCTION(secp256k1_ec_pubkey_tweak_add);
PHP_FUNCTION(secp256k1_ec_pubkey_tweak_mul);

/* Recovery module */
PHP_FUNCTION(secp256k1_ecdsa_sign_recoverable);
PHP_FUNCTION(secp256k1_ecdsa_recover);
PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_convert);
PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_serialize_compact);
PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_parse_compact);

/* ECDH module */
PHP_FUNCTION(secp256k1_ecdh);

/* Schnorr module */
PHP_FUNCTION(secp256k1_schnorr_verify);
PHP_FUNCTION(secp256k1_schnorr_sign);
PHP_FUNCTION(secp256k1_schnorr_recover);
PHP_FUNCTION(secp256k1_schnorr_generate_nonce_pair);
PHP_FUNCTION(secp256k1_schnorr_partial_sign);
PHP_FUNCTION(secp256k1_schnorr_partial_combine);


#endif	/* PHP_SECP256K1_H */
