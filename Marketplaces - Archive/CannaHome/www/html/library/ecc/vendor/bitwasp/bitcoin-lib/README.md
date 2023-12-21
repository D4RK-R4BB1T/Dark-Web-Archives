bitcoin-lib-php
===============

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bit-Wasp/bitcoin-lib-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

PHP libraries implementing bitcoin key functions, as well as BIP32 and electrum.

The library intends to expose a lot of general functionality which isn't 
available using the RPC (like deterministic addresses). 

It also allows you to reduce the number of queries that are made to bitcoind,
such as createrawtransaction/signrawtransaction/decoderawtransaction. As such,
such, use of bitcoin RPC functionality can be kept to a minimum.

Libraries
=========
- Raw Transactions: create, sign, validate, with support for P2SH. 
- Create multi-signature addresses, create redeeming transactions. 
- BIP32: Functions for generating BIP32 deterministic keys.
- Electrum: Create seed from mnemonic, create MPK from seed, derive public keys from MPK, or private keys from seed.
- BitcoinLib: The core class, with key functionality, encoding/decoding & validation functions, etc. 
- BIP39: Functions for generating Mnemonic code for generating deterministic keys (possibly password protected)

If this library powers your project and you're feeling tipsy, buy me lunch some day! 1sCVtkEhQmvp3D4K22Pw9xhFPTDWFh8SZ

Installation
============

Installing via Composer (recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install Composer in your project:

    curl -s http://getcomposer.org/installer | php

2. Create a `composer.json` file in your project root:

    {
        "require": {
            "bitwasp/bitcoin-lib": "1.0.*"
        }
    }

3. Install via Composer

    php composer.phar install

Dependencies
============
Mcrypt Extension (Random data)
------------------------------
The Mcrypt Extension is required for generating random data, it does this internally 
by using `/dev/urandom` on unix or `CryptGenRandom` on windows.

GMP Extension (Math)
--------------------
The GMP Extension is required for the crypto math.

PECL intl extension (BIP39)
---------------------------
The PECL intl extension is required for BIP39 Mnemonic Seeds when a UTF-8 passphrase is used.

Mdanter's PHP Pure PHP Elliptic Curve Cryptography Library
----------------------------------------------------------
`mdanter/ecc` is required for most of the crypto.


Contributing
============
Please make sure that all phpunit tests pass (and preferably added new unit tests) and that the coding style passing PSR2 checks:
 - `./vendor/bin/phpunit`
 - `./vendor/bin/phpcs --standard=./phpcs.xml -n -s ./src/`
