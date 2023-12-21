// $Id$
// vim:ft=javascript

// If your extension references something external, use ARG_WITH
// ARG_WITH("secp256k1", "for secp256k1 support", "no");

// Otherwise, use ARG_ENABLE
// ARG_ENABLE("secp256k1", "enable secp256k1 support", "no");

if (PHP_SECP256K1 != "no") {
	EXTENSION("secp256k1", "secp256k1.c", PHP_EXTNAME_SHARED, "/DZEND_ENABLE_STATIC_TSRMLS_CACHE=1");
}

