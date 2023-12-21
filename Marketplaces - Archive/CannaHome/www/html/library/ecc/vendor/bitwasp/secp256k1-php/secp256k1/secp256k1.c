/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_secp256k1.h"

static zend_class_entry *spl_ce_InvalidArgumentException;


ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_context_create, 0)
    ZEND_ARG_INFO(0, flags)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_context_destroy, 0)
    ZEND_ARG_INFO(0, context)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_context_clone, 0)
    ZEND_ARG_INFO(0, context)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_context_randomize, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(0, seed32)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_signature_parse_der, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, secp256k1_ecdsa_signature)
    ZEND_ARG_INFO(0, signatureStr)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_signature_serialize_der, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, signatureStr)
    ZEND_ARG_INFO(0, secp256k1_ecdsa_signature)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_signature_normalize, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, secp256k1_ecdsa_signature)
    ZEND_ARG_INFO(0, secp256k1_ecdsa_signature)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_verify, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, signature)
    ZEND_ARG_INFO(0, publicKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_sign, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, signature)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, secretKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_sign_recoverable, 0)
    ZEND_ARG_INFO(0, secp256k1_context)
    ZEND_ARG_INFO(1, secp256k1_ecdsa_recoverable_signature)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, secretKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_recoverable_signature_serialize_compact, 0)
    ZEND_ARG_INFO(0, secp256k1_context)
    ZEND_ARG_INFO(0, secp256k1_ecdsa_recoverable_signature)
    ZEND_ARG_INFO(1, output64)
    ZEND_ARG_INFO(1, recid)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_recoverable_signature_convert, 0)
    ZEND_ARG_INFO(0, secp256k1_context)
    ZEND_ARG_INFO(1, secp256k1_ecdsa_signature)
    ZEND_ARG_INFO(0, secp256k1_ecdsa_recoverable_signature)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_recoverable_signature_parse_compact, 0)
    ZEND_ARG_INFO(0, secp256k1_context)
    ZEND_ARG_INFO(1, secp256k1_ecdsa_recoverable_signature)
    ZEND_ARG_INFO(0, input64)
    ZEND_ARG_INFO(0, recid)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdsa_recover, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, secp256k1_pubkey)
    ZEND_ARG_INFO(0, secp256k1_ecdsa_recoverable_signature)
    ZEND_ARG_INFO(0, msg32)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_seckey_verify, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(0, secretKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_parse, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKey)
    ZEND_ARG_INFO(0, input)
ZEND_END_ARG_INFO();


ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_combine, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, combinedKey)
    ZEND_ARG_INFO(0, publicKeys)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_serialize, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKeyStr)
    ZEND_ARG_INFO(0, secp256k1_pubkey)
    ZEND_ARG_INFO(0, compressed)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_create, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKey)
    ZEND_ARG_INFO(0, secretKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_privkey_tweak_add, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, seckey)
    ZEND_ARG_INFO(0, tweak)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_tweak_add, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKey)
    ZEND_ARG_INFO(0, tweak)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_privkey_tweak_mul, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, seckey)
    ZEND_ARG_INFO(0, tweak)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ec_pubkey_tweak_mul, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKey)
    ZEND_ARG_INFO(0, publicKeyLength)
    ZEND_ARG_INFO(0, tweak)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_ecdh, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, result)
    ZEND_ARG_INFO(0, pubkey)
    ZEND_ARG_INFO(0, privkey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_verify, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, signature)
    ZEND_ARG_INFO(0, publicKey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_sign, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, secretKey)
    ZEND_ARG_INFO(1, signature)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_recover, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, publicKey)
    ZEND_ARG_INFO(0, sig64)
    ZEND_ARG_INFO(0, msg32)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_generate_nonce_pair, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, pubNonce)
    ZEND_ARG_INFO(1, privNonce)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, seckey)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_partial_sign, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, sig64)
    ZEND_ARG_INFO(0, msg32)
    ZEND_ARG_INFO(0, seckey)
    ZEND_ARG_INFO(0, pubNonceOthers)
    ZEND_ARG_INFO(0, privNonce)
ZEND_END_ARG_INFO();

ZEND_BEGIN_ARG_INFO(arginfo_secp256k1_schnorr_partial_combine, 0)
    ZEND_ARG_INFO(0, context)
    ZEND_ARG_INFO(1, sig64)
    ZEND_ARG_INFO(0, sigs)
ZEND_END_ARG_INFO();


/* {{{ resource_functions[]
 *
 * Every user visible function must have an entry in resource_functions[].
 */
const zend_function_entry secp256k1_functions[] = {
        PHP_FE(secp256k1_context_create,                     arginfo_secp256k1_context_create)
        PHP_FE(secp256k1_context_destroy,                    arginfo_secp256k1_context_destroy)
        PHP_FE(secp256k1_context_clone,                      arginfo_secp256k1_context_clone)
        PHP_FE(secp256k1_context_randomize,                  arginfo_secp256k1_context_randomize)

        PHP_FE(secp256k1_ecdsa_signature_parse_der,          arginfo_secp256k1_ecdsa_signature_parse_der)
        PHP_FE(secp256k1_ecdsa_signature_serialize_der,      arginfo_secp256k1_ecdsa_signature_serialize_der)
        PHP_FE(secp256k1_ecdsa_signature_normalize,          arginfo_secp256k1_ecdsa_signature_normalize)
        PHP_FE(secp256k1_ecdsa_verify,                       arginfo_secp256k1_ecdsa_verify)
        PHP_FE(secp256k1_ecdsa_sign,                         arginfo_secp256k1_ecdsa_sign)

        PHP_FE(secp256k1_ec_seckey_verify,                   arginfo_secp256k1_ec_seckey_verify)
        PHP_FE(secp256k1_ec_pubkey_create,                   arginfo_secp256k1_ec_pubkey_create)
        PHP_FE(secp256k1_ec_pubkey_parse,                    arginfo_secp256k1_ec_pubkey_parse)
        PHP_FE(secp256k1_ec_pubkey_combine,                  arginfo_secp256k1_ec_pubkey_combine)
        PHP_FE(secp256k1_ec_pubkey_serialize,                arginfo_secp256k1_ec_pubkey_serialize)
        PHP_FE(secp256k1_ec_pubkey_tweak_add,                arginfo_secp256k1_ec_pubkey_tweak_add)
        PHP_FE(secp256k1_ec_pubkey_tweak_mul,                arginfo_secp256k1_ec_pubkey_tweak_mul)
        PHP_FE(secp256k1_ec_privkey_tweak_add,               arginfo_secp256k1_ec_privkey_tweak_add)
        PHP_FE(secp256k1_ec_privkey_tweak_mul,               arginfo_secp256k1_ec_privkey_tweak_mul)

        PHP_FE(secp256k1_ecdsa_recover,                      arginfo_secp256k1_ecdsa_recover)
        PHP_FE(secp256k1_ecdsa_sign_recoverable,             arginfo_secp256k1_ecdsa_sign_recoverable)
        PHP_FE(secp256k1_ecdsa_recoverable_signature_convert, arginfo_secp256k1_ecdsa_recoverable_signature_convert)
        PHP_FE(secp256k1_ecdsa_recoverable_signature_parse_compact, arginfo_secp256k1_ecdsa_recoverable_signature_parse_compact)
        PHP_FE(secp256k1_ecdsa_recoverable_signature_serialize_compact, arginfo_secp256k1_ecdsa_recoverable_signature_serialize_compact)

        PHP_FE(secp256k1_ecdh,                               arginfo_secp256k1_ecdh)
        PHP_FE(secp256k1_schnorr_verify,                     arginfo_secp256k1_schnorr_verify)
        PHP_FE(secp256k1_schnorr_sign,                       arginfo_secp256k1_schnorr_sign)
        PHP_FE(secp256k1_schnorr_recover,                    arginfo_secp256k1_schnorr_recover)
        PHP_FE(secp256k1_schnorr_generate_nonce_pair,        arginfo_secp256k1_schnorr_generate_nonce_pair)
        PHP_FE(secp256k1_schnorr_partial_sign,               arginfo_secp256k1_schnorr_partial_sign)
        PHP_FE(secp256k1_schnorr_partial_combine,            arginfo_secp256k1_schnorr_partial_combine)

        PHP_FE_END	/* Must be the last line in resource_functions[] */
};
/* }}} */

static int le_secp256k1_ctx;
static int le_secp256k1_pubkey;
static int le_secp256k1_sig;
static int le_secp256k1_recoverable_sig;

static void secp256k1_ctx_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC)
{
    secp256k1_context *ctx = (secp256k1_context*) rsrc->ptr;
    if (ctx) {
        secp256k1_context_destroy(ctx);
    }
}

static void secp256k1_pubkey_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC)
{
    secp256k1_pubkey *pubkey = (secp256k1_pubkey*) rsrc->ptr;
    if (pubkey) {
        efree(pubkey);
    }
}

static void secp256k1_sig_dtor(zend_rsrc_list_entry * rsrc TSRMLS_DC)
{
    secp256k1_ecdsa_signature *sig = (secp256k1_ecdsa_signature*) rsrc->ptr;
    if (sig) {
        efree(sig);
    }
}

static void secp256k1_recoverable_sig_dtor(zend_rsrc_list_entry * rsrc TSRMLS_DC)
{
    secp256k1_ecdsa_recoverable_signature *sig = (secp256k1_ecdsa_recoverable_signature*) rsrc->ptr;
    if (sig) {
        efree(sig);
    }
}

PHP_MINIT_FUNCTION(secp256k1) {
    le_secp256k1_ctx = zend_register_list_destructors_ex(secp256k1_ctx_dtor, NULL, SECP256K1_CTX_RES_NAME, module_number);
    le_secp256k1_pubkey = zend_register_list_destructors_ex(secp256k1_pubkey_dtor, NULL, SECP256K1_PUBKEY_RES_NAME, module_number);
    le_secp256k1_sig = zend_register_list_destructors_ex(secp256k1_sig_dtor, NULL, SECP256K1_SIG_RES_NAME, module_number);
    le_secp256k1_recoverable_sig = zend_register_list_destructors_ex(secp256k1_recoverable_sig_dtor, NULL, SECP256K1_RECOVERABLE_SIG_RES_NAME, module_number);

    REGISTER_LONG_CONSTANT("SECP256K1_CONTEXT_VERIFY", SECP256K1_CONTEXT_VERIFY, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("SECP256K1_CONTEXT_SIGN", SECP256K1_CONTEXT_SIGN, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("SECP256K1_EC_COMPRESSED", SECP256K1_EC_COMPRESSED, CONST_CS | CONST_PERSISTENT);

    REGISTER_STRING_CONSTANT("SECP256K1_TYPE_CONTEXT", SECP256K1_CTX_RES_NAME, CONST_CS | CONST_PERSISTENT);
    REGISTER_STRING_CONSTANT("SECP256K1_TYPE_PUBKEY", SECP256K1_PUBKEY_RES_NAME, CONST_CS | CONST_PERSISTENT);
    REGISTER_STRING_CONSTANT("SECP256K1_TYPE_SIG", SECP256K1_SIG_RES_NAME, CONST_CS | CONST_PERSISTENT);
    REGISTER_STRING_CONSTANT("SECP256K1_TYPE_RECOVERABLE_SIG", SECP256K1_RECOVERABLE_SIG_RES_NAME, CONST_CS | CONST_PERSISTENT);
    /*
    ZEND_INIT_MODULE_GLOBALS(secp256k1, php_secp256k1_init_globals, NULL);
     */
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(secp256k1) {
    return SUCCESS;
}

/* Remove if there's nothing to do at request start */
PHP_RINIT_FUNCTION(secp256k1) {
    return SUCCESS;
}

/* Remove if there's nothing to do at request end */
PHP_RSHUTDOWN_FUNCTION(secp256k1) {
    return SUCCESS;
}

PHP_MINFO_FUNCTION(secp256k1) {
    php_info_print_table_start();
    php_info_print_table_header(2, "secp256k1 support", "enabled");
    php_info_print_table_end();
}

zend_module_entry secp256k1_module_entry = {
        STANDARD_MODULE_HEADER,
        "secp256k1",
        secp256k1_functions,
        PHP_MINIT(secp256k1),
        PHP_MSHUTDOWN(secp256k1),
        PHP_RINIT(secp256k1), /* Replace with NULL if there's nothing to do at request start */
        PHP_RSHUTDOWN(secp256k1), /* Replace with NULL if there's nothing to do at request end */
        PHP_MINFO(secp256k1),
        PHP_SECP256K1_VERSION,
        STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_SECP256K1
ZEND_GET_MODULE(secp256k1)
#endif

size_t pubkeyLengthFromCompressed(int compressed)
{
    return compressed ? PUBKEY_COMPRESSED_LENGTH : PUBKEY_UNCOMPRESSED_LENGTH;
}

/* Create a secp256k1 context resource */
/* {{{ proto resource secp256k1_context_create(int flags)
   Return a secp256k1 context initialized with the desired pregenerated tables.
   NB: This is the most expensive operation in the library */
PHP_FUNCTION(secp256k1_context_create)
{
    long flags;
    secp256k1_context * ctx;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &flags) == FAILURE) {
        RETURN_FALSE;
    }

    ctx = secp256k1_context_create(flags);
    ZEND_REGISTER_RESOURCE(return_value, ctx, le_secp256k1_ctx);
}
/* }}} */

/* Destroy a secp256k1 context resource */
/* {{{ proto bool secp256k1_context_destroy(resource secp256k1_context)
   Destroy the given secp256k1 context. The context may not be used afterwards. */
PHP_FUNCTION(secp256k1_context_destroy)
{
    zval *zCtx;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &zCtx) == FAILURE) {
        RETURN_FALSE;
    }
    zend_list_delete(Z_LVAL_P(zCtx));
    RETURN_TRUE;
}
/* }}} */

/* Copies a secp256k1 context resource */
/** {{{ proto resource secp256k1_context_clone(resource secp256k1_context)
 *  In:   ctx:   an existing context to copy
 *  Out:  ctx:   a newly created context object. */
PHP_FUNCTION(secp256k1_context_clone)
{
    zval *zCtx;
    secp256k1_context *ctx;
    secp256k1_context *newCtx;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &zCtx) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    newCtx = secp256k1_context_clone(ctx);
    ZEND_REGISTER_RESOURCE(return_value, newCtx, le_secp256k1_ctx);
}
/* }}} */

/* Updates the context randomization */
/** {{{ proto int secp256k1_context_random(resource secp256k1_context, seed32)
 *  In:   ctx:    a context resource 
 *        seed32: a random 32-byte seed (NULL resets to initial state)
 *  Out:  0:      an error occured
 *        1:      randomization successfully updated
 */
PHP_FUNCTION(secp256k1_context_randomize)
{
    zval *zCtx;
    secp256k1_context *ctx;
    unsigned char *seed32;
    int result, seedlen = 0;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r|s", &zCtx, &seed32, &seedlen) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    result = secp256k1_context_randomize(ctx, seed32);
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ecdsa_signature_serialize_der)
{
    zval *zCtx, *zSig, *zSigOut;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *sig;
    int result;
    size_t sigoutlen = MAX_SIGNATURE_LENGTH;
    unsigned char *sigout;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzr", &zCtx, &zSigOut, &zSig) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(sig, secp256k1_ecdsa_signature*, &zSig, -1, SECP256K1_SIG_RES_NAME, le_secp256k1_sig);
    sigout = emalloc(sigoutlen);
    result = secp256k1_ecdsa_signature_serialize_der(ctx, sigout, &sigoutlen, sig);
    if (result) {
        ZVAL_STRINGL(zSigOut, sigout, sigoutlen, 1);
    }
    RETURN_LONG(result);
}
/* }}} */

/* Parse a DER signature into a signature resource */
/**
 * {{{ proto int secp256k1_ecdsa_signature_parse_der(
 *         resource secp256k1_context,
 *         string serializedSignature,
 *         resource & secp256k1_ecdsa_signature
 *     );
 */
PHP_FUNCTION(secp256k1_ecdsa_signature_parse_der)
{
    zval *zCtx, *zSig;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *sig;
    unsigned char *sigin;
    int result, siglen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zSig, &sigin, &siglen) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    sig = emalloc(sizeof(secp256k1_ecdsa_signature));
    result = secp256k1_ecdsa_signature_parse_der(ctx, sig, sigin, siglen);
    if (result) {
        ZEND_REGISTER_RESOURCE(zSig, sig, le_secp256k1_sig);
    }
    RETURN_LONG(result);
}
/* }}} */

/* Parse a DER signature into a signature resource */
/**
 * {{{ proto int secp256k1_ecdsa_signature_normalize(
 *         resource secp256k1_context,
 *         resource & secp256k1_ecdsa_signature_out
 *         resource secp256k1_ecdsa_signature_in
 *     );
 */
PHP_FUNCTION(secp256k1_ecdsa_signature_normalize)
{
    zval *zCtx, *zSigIn, *zSigOut;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *sigin, *sigout;
    int result;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzz", &zCtx, &zSigOut, &zSigIn) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(sigin, secp256k1_ecdsa_signature*, &zSigIn, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    sigout = emalloc(sizeof(secp256k1_ecdsa_signature));
    result = secp256k1_ecdsa_signature_normalize(ctx, sigout, sigin);
    if (result) {
        ZEND_REGISTER_RESOURCE(zSigOut, sigout, le_secp256k1_sig);
    }

    RETURN_LONG(result);
}
/* }}} */

/**
 * Verify an ECDSA signature.
 *
 * In:
 *  msg32: the 32-byte message hash being verified (cannot be NULL)
 *  sig: the signature resource being verified (cannot be NULL)
 *  pubkey: the public key resource to verify with (cannot be NULL)
 * Returns:
 *  1: correct signature
 *  0: incorrect signature
 */
PHP_FUNCTION(secp256k1_ecdsa_verify) {
    zval *zCtx, *zSig, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *sig;
    secp256k1_pubkey *pubkey;
    unsigned char *msg32;
    int result, msg32len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rrsr", &zCtx, &zSig, &msg32, &msg32len, &zPubKey) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(sig, secp256k1_ecdsa_signature*, &zSig, -1, SECP256K1_SIG_RES_NAME, le_secp256k1_sig);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    secp256k1_ecdsa_signature *sigcpy = emalloc(sizeof(secp256k1_ecdsa_signature));
    secp256k1_ecdsa_signature_normalize(ctx, sigcpy, sig);
    result = secp256k1_ecdsa_verify(ctx, sigcpy, msg32, pubkey);

    RETURN_LONG(result);
}
/* }}} */

/**
 * Create an ECDSA signature.
 *
 * In:
 *  msg32:  the 32-byte message hash being signed (cannot be NULL)
 *  seckey: pointer to a 32-byte secret key (cannot be NULL)
 *
 * Out:
 *  sig:    pointer to an array where the signature will be placed (cannot be NULL)
 *
 * Returns:
 *  1: signature created
 *  0: the nonce generation function failed, the private key was invalid, or there is not
 *     enough space in the signature.
 *
 * The sig always has an s value in the lower half of the range (From 0x1
 * to 0x7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF5D576E7357A4501DDFE92F46681B20A0,
 * inclusive), unlike many other implementations.
 * With ECDSA a third-party can can forge a second distinct signature
 * of the same message given a single initial signature without knowing
 * the key by setting s to its additive inverse mod-order, 'flipping' the
 * sign of the random point R which is not included in the signature.
 * Since the forgery is of the same message this isn't universally
 * problematic, but in systems where message malleability or uniqueness
 * of signatures is important this can cause issues.  This forgery can be
 * blocked by all verifiers forcing signers to use a canonical form. The
 * lower-S form reduces the size of signatures slightly on average when
 * variable length encodings (such as DER) are used and is cheap to
 * verify, making it a good choice. Security of always using lower-S is
 * assured because anyone can trivially modify a signature after the
 * fact to enforce this property.  Adjusting it inside the signing
 * function avoids the need to re-serialize or have curve specific
 * constants outside of the library.  By always using a canonical form
 * even in applications where it isn't needed it becomes possible to
 * impose a requirement later if a need is discovered.
 * No other forms of ECDSA malleability are known and none seem likely,
 * but there is no formal proof that ECDSA, even with this additional
 * restriction, is free of other malleability.  Commonly used serialization
 * schemes will also accept various non-unique encodings, so care should
 * be taken when this property is required for an application.
 */
PHP_FUNCTION(secp256k1_ecdsa_sign)
{
    zval *zCtx, *zSig;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *newsig;
    int seckeylen, msg32len, result;
    unsigned char *seckey, *msg32;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzss", &zCtx, &zSig, &msg32, &msg32len, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    if (msg32len != HASH_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ecdsa_sign(): Parameter 3 should be 32 bytes");
        return;
    }

    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ecdsa_sign(): Parameter 4 should be 32 bytes");
        return;
    }

    newsig = emalloc(sizeof(secp256k1_ecdsa_signature));
    result = secp256k1_ecdsa_sign(ctx, newsig, msg32, seckey, NULL, NULL);
    if (result) {
        ZEND_REGISTER_RESOURCE(zSig, newsig, le_secp256k1_sig);
    }
    RETURN_LONG(result);
}
/* }}} */

/** Verify an ECDSA secret key.
 *  Returns: 1: secret key is valid
 *           0: secret key is invalid
 *  In:      ctx: pointer to a context object (cannot be NULL)
 *           seckey: pointer to a 32-byte secret key (cannot be NULL)
 */
PHP_FUNCTION(secp256k1_ec_seckey_verify)
{
    zval *zCtx;
    secp256k1_context *ctx;
    unsigned char *seckey;
    int result, seckeylen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs", &zCtx, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_seckey_verify(): Parameter 1 should be 32 bytes");
        return;
    }

    result = secp256k1_ec_seckey_verify(ctx, seckey);
    RETURN_LONG(result);
}
/* }}} */

/**
 * Compute the public key for a secret key.
 *
 * In:
 *  compressed: whether the computed public key should be compressed
 *  seckey:     pointer to a 32-byte private key (cannot be NULL)
 *
 * Out:
 *  pubkey:     pointer to a 33-byte (if compressed) or 65-byte (if uncompressed)
 *              area to store the public key (cannot be NULL)
 *
 * Returns:
 *  1: secret was valid, public key stored
 *  0: secret was invalid, try again.
 */
PHP_FUNCTION(secp256k1_ec_pubkey_create)
{
    zval *zCtx, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    int result, seckeylen;
    unsigned char *seckey;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zPubKey, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_pubkey_create(): Parameter 2 should be 32 bytes");
        return;
    }

    pubkey = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_ec_pubkey_create(ctx, pubkey, seckey);
    if (result) {
        ZEND_REGISTER_RESOURCE(zPubKey, pubkey, le_secp256k1_pubkey);
    }
    RETURN_LONG(result);
}
/* }}} */

/* Parse a variable length public key into a public key resource */
/** {{{ proto int secp256k1_ec_pubkey_parse(resource secp256k1_context, string publicKey)
 *  In:    ctx:          a secp256k1_context resource
 *         publicKey:    a serialized public key
 *         pubkey:     an empty variable, set the public key resource here.
 *  Returns: 1:          a public key was set to pubkey.
             anything else: error.
 */
PHP_FUNCTION(secp256k1_ec_pubkey_parse)
{
    zval *zCtx, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    unsigned char *pubkeyin;
    int result;
    size_t pubkeylen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zPubKey, &pubkeyin, &pubkeylen) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    pubkey = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_ec_pubkey_parse(ctx, pubkey, pubkeyin, pubkeylen);
    if (result) {
        ZEND_REGISTER_RESOURCE(zPubKey, pubkey, le_secp256k1_pubkey);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ec_pubkey_serialize)
{
    zval *zCtx, *zPubKey, *zPubOut;
    secp256k1_context *ctx;
    secp256k1_pubkey * pubkey;
    int result;
    long compressed;
    unsigned int flags = 0;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzrl", &zCtx, &zPubOut, &zPubKey, &compressed) == FAILURE) {
        RETURN_FALSE;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    flags |= compressed ? SECP256K1_EC_COMPRESSED : SECP256K1_EC_UNCOMPRESSED;

    size_t pubkeylen = pubkeyLengthFromCompressed(compressed);
    unsigned char *pubkeyout = emalloc(pubkeylen);
    result = secp256k1_ec_pubkey_serialize(ctx, pubkeyout, &pubkeylen, pubkey, flags);
    if (result) {
        ZVAL_STRINGL(zPubOut, pubkeyout, pubkeylen, 1);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ec_privkey_tweak_add)
{
    zval *zCtx, *zSecKey;
    secp256k1_context *ctx;
    unsigned char *newseckey, *tweak;
    int result, tweaklen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zSecKey, &tweak, &tweaklen) == FAILURE) {
        return;
    }

    if (Z_TYPE_P(zSecKey) != IS_STRING) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_add(): Parameter 2 should be string");
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    if (Z_STRLEN_P(zSecKey) != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_add(): Parameter 2 should be 32 bytes");
        return;
    }

    if (tweaklen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_add(): Parameter 3 should be 32 bytes");
        return;
    }

    newseckey = Z_STRVAL_P(zSecKey);
    result = secp256k1_ec_privkey_tweak_add(ctx, newseckey, tweak);
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ec_pubkey_tweak_add)
{
    zval *zCtx, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    unsigned char *tweak;
    int result, tweaklen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rrs", &zCtx, &zPubKey, &tweak, &tweaklen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    if (tweaklen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_pubkey_tweak_add(): Parameter 3 should be 32 bytes");
        return;
    }

    result = secp256k1_ec_pubkey_tweak_add(ctx, pubkey, tweak);
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ec_privkey_tweak_mul)
{
    zval *zCtx, *zSecKey;
    unsigned char *newseckey, *tweak;
    secp256k1_context *ctx;
    int result, tweaklen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zSecKey, &tweak, &tweaklen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    if (Z_TYPE_P(zSecKey) != IS_STRING) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_mul(): Parameter 2 should be string");
        return;
    }

    if (Z_STRLEN_P(zSecKey) != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_mul(): Parameter 2 should be 32 bytes");
        return;
    }

    if (tweaklen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ec_privkey_tweak_mul(): Parameter 3 should be 32 bytes");
        return;
    }

    newseckey = Z_STRVAL_P(zSecKey);
    result = secp256k1_ec_privkey_tweak_mul(ctx, newseckey, tweak);
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ec_pubkey_tweak_mul)
{
    zval *zCtx, *zPubKey;
    unsigned char *tweak, *newpubkey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    int result, tweaklen, newpubkeylen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzs", &zCtx, &zPubKey, &tweak, &tweaklen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    result = secp256k1_ec_pubkey_tweak_mul(ctx, pubkey, tweak);
    RETURN_LONG(result);
}
/* }}} */

/* Begin recovery module functions */

PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_parse_compact)
{
    zval *zCtx, *zSig;
    unsigned char *input64;
    int input64len;
    secp256k1_context *ctx;
    secp256k1_ecdsa_recoverable_signature *sig;
    int result;
    long recid;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzsl", &zCtx, &zSig, &input64, &input64len, &recid) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    sig = emalloc(sizeof(secp256k1_ecdsa_recoverable_signature));
    result = secp256k1_ecdsa_recoverable_signature_parse_compact(ctx, sig, input64, recid);
    if (result) {
        ZEND_REGISTER_RESOURCE(zSig, sig, le_secp256k1_recoverable_sig);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_convert)
{
    zval *zCtx, *zRecoverableSig, *zNormalSig;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature * nSig;
    secp256k1_ecdsa_recoverable_signature * rSig;
    int result;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzr", &zCtx, &zNormalSig, &zRecoverableSig) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(rSig, secp256k1_ecdsa_recoverable_signature*, &zRecoverableSig, -1, SECP256K1_RECOVERABLE_SIG_RES_NAME, le_secp256k1_recoverable_sig);

    nSig = emalloc(sizeof(secp256k1_ecdsa_recoverable_signature));
    result = secp256k1_ecdsa_recoverable_signature_convert(ctx, nSig, rSig);
    if (result) {
        ZEND_REGISTER_RESOURCE(zNormalSig, nSig, le_secp256k1_sig);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ecdsa_recoverable_signature_serialize_compact)
{
    zval *zCtx, *zRecSig, *zSigOut, *zRecId;
    secp256k1_context *ctx;
    secp256k1_ecdsa_recoverable_signature *recsig;
    unsigned char *sig;
    int result, recid;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rrzz", &zCtx, &zRecSig, &zSigOut, &zRecId) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(recsig, secp256k1_ecdsa_recoverable_signature*, &zRecSig, -1, SECP256K1_RECOVERABLE_SIG_RES_NAME, le_secp256k1_recoverable_sig);
    sig = emalloc(COMPACT_SIGNATURE_LENGTH);
    result = secp256k1_ecdsa_recoverable_signature_serialize_compact(ctx, sig, &recid, recsig);
    if (result) {
        ZVAL_STRINGL(zSigOut, sig, COMPACT_SIGNATURE_LENGTH, 1);
        ZVAL_LONG(zRecId, recid);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ecdsa_sign_recoverable)
{
    zval *zCtx, *zSig;
    secp256k1_context *ctx;
    secp256k1_ecdsa_recoverable_signature *newsig;
    int seckeylen, msg32len, result;
    unsigned char *seckey, *msg32;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzss", &zCtx, &zSig, &msg32, &msg32len, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    if (msg32len != HASH_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ecdsa_sign(): Parameter 2 should be 32 bytes");
        return;
    }

    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_ecdsa_sign(): Parameter 3 should be 32 bytes");
        return;
    }

    newsig = emalloc(sizeof(secp256k1_ecdsa_recoverable_signature));
    result = secp256k1_ecdsa_sign_recoverable(ctx, newsig, msg32, seckey, 0, 0);
    if (result) {
        ZEND_REGISTER_RESOURCE(zSig, newsig, le_secp256k1_recoverable_sig);
    }
    RETURN_LONG(result);
}
/* }}} */

/** Recover an ECDSA public key from a signature.
 * {{{ proto int secp256k1_ecdsa_recover(secp256k1_context, msg32, secp256k1_recoverable_signature, secp256k1_pubkey)
 *  Returns: 1: public key successfully recovered (which guarantees a correct signature).
 *           0: otherwise.
 *  In:      secp256k1_context:                pointer to a context object, initialized for verification (cannot be NULL)
 *           msg32:                              the 32-byte message hash assumed to be signed (cannot be NULL)
 *           secp256k1_recoverable_signature:  pointer to initialized signature that supports pubkey recovery (cannot be NULL)
 *  Out:     secp256k1_ec_pubkey:              pointer to the recovered secp256k1_pubkey resource (cannot be NULL)
 */
PHP_FUNCTION(secp256k1_ecdsa_recover)
{
    zval *zCtx, *zSig, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    secp256k1_ecdsa_recoverable_signature *sig;
    unsigned char *msg32;
    int result, msg32len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzrs", &zCtx, &zPubKey, &zSig, &msg32, &msg32len) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(sig, secp256k1_ecdsa_recoverable_signature*, &zSig, -1, SECP256K1_RECOVERABLE_SIG_RES_NAME, le_secp256k1_recoverable_sig);

    pubkey = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_ecdsa_recover(ctx, pubkey, sig, msg32);
    if (result) {
        ZEND_REGISTER_RESOURCE(zPubKey, pubkey, le_secp256k1_pubkey);
    }
    RETURN_LONG(result);
}
/* }}} */

PHP_FUNCTION(secp256k1_ecdh)
{
    zval *zCtx, *zResult, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    unsigned char *privKey;
    int result, privKeyLen;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzrs", &zCtx, &zResult, &zPubKey, &privKey, &privKeyLen)== FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    unsigned char resultChars[32];
    memset(resultChars, 0, 32);
    result = secp256k1_ecdh(ctx, resultChars, pubkey, privKey);
    if (result == 1) {
        ZVAL_STRINGL(zResult, resultChars, 32, 1);
    }

    RETURN_LONG(result);
}


PHP_FUNCTION(secp256k1_schnorr_verify)
{
    zval *zCtx, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_ecdsa_signature *sig;
    secp256k1_pubkey *pubkey;
    unsigned char *msg32, *sig64;
    int result, msg32len, sig64len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rssr", &zCtx, &sig64, &sig64len, &msg32, &msg32len, &zPubKey) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubkey, secp256k1_pubkey*, &zPubKey, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    result = secp256k1_schnorr_verify(ctx, sig64, msg32, pubkey);

    RETURN_LONG(result);
}

PHP_FUNCTION(secp256k1_schnorr_sign)
{
    zval *zCtx, *zSig;
    secp256k1_context *ctx;
    unsigned char *seckey, *msg32;
    int seckeylen, msg32len, result;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzss", &zCtx, &zSig, &msg32, &msg32len, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    if (msg32len != HASH_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_sign(): Parameter 2 should be 32 bytes");
        return;
    }

    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_sign(): Parameter 3 should be 32 bytes");
        return;
    }

    unsigned char newsig[64];
    result = secp256k1_schnorr_sign(ctx, newsig, msg32, seckey, NULL, NULL);
    if (result) {
        ZVAL_STRINGL(zSig, newsig, 64, 1);
    }

    RETURN_LONG(result);
}

/** Recover an ECDSA public key from a signature.
 * {{{ proto int secp256k1_schnorr_recover(secp256k1_context, secp256k1_pubkey, sig64, msg32)
 *  Returns: 1: public key successfully recovered (which guarantees a correct signature).
 *           0: otherwise.
 *  In:      secp256k1_context:                pointer to a context object, initialized for verification (cannot be NULL)
 *           msg32:                            the 32-byte message hash assumed to be signed (cannot be NULL)
 *           sig64:                            pointer to signature
 *  Out:     secp256k1_ec_pubkey:              pointer to the recovered secp256k1_pubkey resource (cannot be NULL)
 */
PHP_FUNCTION(secp256k1_schnorr_recover)
{
    zval *zCtx, *zPubKey;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubkey;
    unsigned char *msg32, *sig64;
    int result, msg32len, sig64len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzsr", &zCtx, &zPubKey, &sig64, &sig64len, &msg32, &msg32len) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    pubkey = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_schnorr_recover(ctx, pubkey, sig64, msg32);
    if (result) {
        ZEND_REGISTER_RESOURCE(zPubKey, pubkey, le_secp256k1_pubkey);
    }
    RETURN_LONG(result);
}
/* }}} */


PHP_FUNCTION(secp256k1_schnorr_generate_nonce_pair)
{
    zval *zCtx, *zPubNonce, *zPrivNonce;
    secp256k1_context *ctx;
    unsigned char privnonce32[32], *seckey, *msg32;
    int result, seckeylen, msg32len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzzss", &zCtx, &zPubNonce, &zPrivNonce, &msg32, &msg32len, &seckey, &seckeylen) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    if (msg32len != HASH_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_generate_nonce_pair(): Parameter 3 should be 32 bytes");
        return;
    }

    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_generate_nonce_pair(): Parameter 4 should be 32 bytes");
        return;
    }

    secp256k1_pubkey *pubnonce = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_schnorr_generate_nonce_pair(ctx, pubnonce, privnonce32, msg32, seckey, NULL, NULL);
    if (result) {
        ZEND_REGISTER_RESOURCE(zPubNonce, pubnonce, le_secp256k1_pubkey);
        ZVAL_STRINGL(zPrivNonce, privnonce32, SECRETKEY_LENGTH, 1);
    }

    RETURN_LONG(result);
}

PHP_FUNCTION(secp256k1_schnorr_partial_sign)
{
    zval *zCtx, *zSig, *zPubNonceOthers;
    secp256k1_context *ctx;
    secp256k1_pubkey *pubnonce_others;
    unsigned char *seckey, *msg32, *secnonce32;
    int result, seckeylen, msg32len, secnonce32len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rzsszs", &zCtx, &zSig, &msg32, &msg32len, &seckey, &seckeylen, &zPubNonceOthers, &secnonce32, &secnonce32len) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);
    ZEND_FETCH_RESOURCE(pubnonce_others, secp256k1_pubkey*, &zPubNonceOthers, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);

    if (msg32len != HASH_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_partial_sign(): Parameter 3 should be 32 bytes");
        return;
    }

    if (seckeylen != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_partial_sign(): Parameter 4 should be 32 bytes");
        return;
    }

    if (secnonce32len != SECRETKEY_LENGTH) {
        zend_throw_exception_ex(spl_ce_InvalidArgumentException, 0 TSRMLS_CC, "secp256k1_schnorr_partial_sign(): Parameter 6 should be 32 bytes");
        return;
    }

    unsigned char *newsig = emalloc(64);
    result = secp256k1_schnorr_partial_sign(ctx, newsig, msg32, seckey, pubnonce_others, secnonce32);
    if (result == 1) {
        ZVAL_STRINGL(zSig, newsig, 64, 1);
    }

    RETURN_LONG(result);
}

PHP_FUNCTION(secp256k1_schnorr_partial_combine)
{
    zval *arr, **data, *zCtx, *zSigCombined;
    secp256k1_context* ctx;
    HashTable *arr_hash;
    HashPosition pointer;
    int result = 0;
    size_t array_count;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rza", &zCtx, &zSigCombined, &arr) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    arr_hash = Z_ARRVAL_P(arr);
    array_count = (size_t) zend_hash_num_elements(arr_hash);
    const unsigned char *sigs[array_count];
    unsigned char *sig;
    int i = 0;
    for (zend_hash_internal_pointer_reset_ex(arr_hash, &pointer); zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS; zend_hash_move_forward_ex(arr_hash, &pointer)) {
        sig = (unsigned char *) Z_STRVAL_PP(data);
        sigs[i++] = sig;
    }

    unsigned char * sig64 = emalloc(64);
    result = secp256k1_schnorr_partial_combine(ctx, sig64, sigs, array_count);
    if (result == 1) {
        ZVAL_STRINGL(zSigCombined, sig64, 64, 1);
    }

    RETURN_LONG(result);
}

PHP_FUNCTION(secp256k1_ec_pubkey_combine)
{
    zval *arr, **data, *zCtx, *zPubkeyCombined;
    secp256k1_context* ctx;
    HashTable *arr_hash;
    HashPosition pointer;
    int result;
    size_t array_count;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rza", &zCtx, &zPubkeyCombined, &arr) == FAILURE) {
        return;
    }

    ZEND_FETCH_RESOURCE(ctx, secp256k1_context*, &zCtx, -1, SECP256K1_CTX_RES_NAME, le_secp256k1_ctx);

    arr_hash = Z_ARRVAL_P(arr);
    array_count = (size_t) zend_hash_num_elements(arr_hash);
    const secp256k1_pubkey *  pubkeys[array_count];
    secp256k1_pubkey * ptr;
    int i = 0;
    for (zend_hash_internal_pointer_reset_ex(arr_hash, &pointer); zend_hash_get_current_data_ex(arr_hash, (void**) &data, &pointer) == SUCCESS; zend_hash_move_forward_ex(arr_hash, &pointer)) {
        ZEND_FETCH_RESOURCE(ptr, secp256k1_pubkey*, data, -1, SECP256K1_PUBKEY_RES_NAME, le_secp256k1_pubkey);
        pubkeys[i++] = ptr;
    }

    secp256k1_pubkey * combined = emalloc(sizeof(secp256k1_pubkey));
    result = secp256k1_ec_pubkey_combine(ctx, combined, pubkeys, array_count);
    if (result == 1) {
        ZEND_REGISTER_RESOURCE(zPubkeyCombined, combined, le_secp256k1_pubkey);
    }

    RETURN_LONG(result);
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
