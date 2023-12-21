# PHP PBKDF2 Compatibility Functions

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

This component provides compatibility for the [`hash_pbkdf2()`](http://php.net/manual/en/function.hash-pbkdf2.php)
function on PHP versions >=5.3,<5.5.

## Install

Via Composer

``` bash
$ composer require rych/hash_pbkdf2-compat
```

## Usage

When loaded via Composer on PHP versions <5.5, a global function `hash_pbkdf2()` is automatically loaded which matches
the >=5.5 built-in function as closely as possible. On PHP versions >=5.5, the shim function is available as
`\Rych\hash_pbkdf2()`.

If not using Composer, make sure to include `src/hash_pbkdf2-compat.php` in order to load the function.

See the PHP documentation for [`hash_pbkdf2()`](http://php.net/manual/en/function.hash-pbkdf2.php) for information.

## Testing

``` bash
$ composer test
```

## Credits

- [Ryan Chouinard][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/rych/hash_pbkdf2-compat.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/rchouinard/hash_pbkdf2-compat/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/rych/hash_pbkdf2-compat.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/rych/hash_pbkdf2-compat
[link-travis]: https://travis-ci.org/rchouinard/hash_pbkdf2-compat
[link-downloads]: https://packagist.org/packages/rych/hash_pbkdf2-compat
[link-author]: https://github.com/rchouinard/
[link-contributors]: ../../contributors
