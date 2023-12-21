#!/bin/bash
#
# This file is part of the phpBB Forum Software package.
#
# @copyright (c) phpBB Limited <https://www.phpbb.com>
# @license GNU General Public License, version 2 (GPL-2.0)
#
# For full copyright and license information, please see
# the docs/CREDITS.txt file.
#
set -e
set -x

TRAVIS_PHP_VERSION=$1

if [ "$TRAVIS_PHP_VERSION" == "5.6" ]
then
	phpunit --coverage-clover=coverage.clover
    wget https://scrutinizer-ci.com/ocular.phar
    php ocular.phar code-coverage:upload --format=php-clover coverage.clover
else
    phpunit
fi
