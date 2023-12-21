php-u2flib-server
-----------------


[![Build Status](https://travis-ci.org/paul999/php-u2flib-server.svg?branch=master)](https://travis-ci.org/paul999/php-u2flib-server)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/paul999/php-u2flib-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/paul999/php-u2flib-server/)
[![Coverage Status](https://coveralls.io/repos/paul999/php-u2flib-server/badge.svg?branch=master&service=github)](https://coveralls.io/github/paul999/php-u2flib-server?branch=master)

Introduction
============
Serverside U2F library for PHP. Provides functionality for registering
tokens and authentication with said tokens.

This project started as fork of the code Originally from [Yubico](https://github.com/Yubico/php-u2flib-server), however 
it is not a direct replacement. Code changes are required to use this new libary.

To read more about U2F and how to use a U2F library, visit
[http://developers.yubico.com/U2F](developers.yubico.com/U2F).

Usage
=====
A full set of documentation is coming. Some old examples of the original libary are still in examples/, however these are not
compitable with the fork yet.

It is prefered to use composer for managing your dependencies. You can add this libary by simply running composer require:
```
composer require paul999/u2flib-server
```

License
========
The project is licensed under a BSD license.  See the file COPYING for
exact wording. 

Tests
=====
To run the test suite link:https://phpunit.de[PHPUnit] is required. To run it, type:

 $ phpunit
