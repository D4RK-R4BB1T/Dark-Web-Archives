# MurmurHash3

[![Latest Stable Version](https://poser.pugx.org/lastguest/murmurhash/v/stable)](https://packagist.org/packages/lastguest/murmurhash) [![Total Downloads](https://poser.pugx.org/lastguest/murmurhash/downloads)](https://packagist.org/packages/lastguest/murmurhash) [![Latest Unstable Version](https://poser.pugx.org/lastguest/murmurhash/v/unstable)](https://packagist.org/packages/lastguest/murmurhash) [![License](https://poser.pugx.org/lastguest/murmurhash/license)](https://packagist.org/packages/lastguest/murmurhash)

PHP Implementation of MurmurHash3

More information about these algorithms can be found at:

*	[MurmurHash Homepage](http://sites.google.com/site/murmurhash/)
*	[Wikipedia Entry on MurmurHash](http://en.wikipedia.org/wiki/MurmurHash) 

Porting of the MurmurHash3 JavaScript version created by Gary Court (https://github.com/garycourt/murmurhash-js)

## Installation
Use [composer](https://getcomposer.org/download/) :

```bash
composer require lastguest/murmurhash
```

## Usage

```php
<?php
echo murmurhash3("Hello World");
// cnd0ue
```


## License (MIT)

Copyright (c) 2011 Stefano Azzolini

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
