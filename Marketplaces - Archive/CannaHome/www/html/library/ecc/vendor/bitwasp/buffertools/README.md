# Buffertools
=============
This library provides a `Buffer` and `Parser` class to make dealing with binary data in PHP easier.
`Templates` extend this by offering a read/write interface for larger serialized structures. 

[![Build Status](https://travis-ci.org/Bit-Wasp/buffertools-php.svg)](https://travis-ci.org/Bit-Wasp/buffertools-php)
[![Code Coverage](https://scrutinizer-ci.com/g/bit-wasp/buffertools-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bit-wasp/buffertools-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/buffertools-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/buffertools-php/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bitwasp/buffertools/v/stable.png)](https://packagist.org/packages/bitwasp/buffertools)
[![Join the chat at https://gitter.im/Bit-Wasp/bitcoin-php](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bit-Wasp/bitcoin-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## Requirements:

 * PHP 5.4+
 * Composer
 * ext-gmp

## Installation

 You can install this library via Composer: `composer require bitwasp/buffertools` 
  
## Examples 
 
 Buffer's are immutable classes to store binary data. 
 BufferHex will convert the provided data to binary, as will BufferInt. 
 Buffer's main methods are:
  - getBinary()
  - getHex()
  - getInt()
  
 Parser will read Buffers. 
 Parser's main methods are: 
  - readBytes()
  - getArray()
  - getVarInt()
  - getString()
  - writeBytes()
  
 In most cases, the interface offered by Parser should not be used directly. 
 Instead, Templates expose read/write access to larger serialized structures.
 
 ### Using Parser to read binary data
```php
    use BitWasp\Buffertools\Buffer;
    use BitWasp\Buffertools\Parser;
    
    // Parsers read Buffers
    $buffer = new Buffer('abc');
    $parser = new Parser($buffer);
    
    // Call readBytes to unpack the data
    /** @var Buffer[] $set */
    $set = [
        $parser->readBytes(1),
        $parser->readBytes(1),
        $parser->readBytes(1)
    ];
    
    foreach ($set as $item) {
        echo $item->getBinary() . PHP_EOL;
    }
```

### Using Templates
```php
    
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

$structure = (object) [
    'hash' => hash('sha256', 'abc'),
    'message_id' => 9123,
    'message' => "Hi there! What's up?"
];

// Templates are read/write
$template = (new TemplateFactory)
    ->bytestring(32)
    ->uint32()
    ->varstring()
    ->getTemplate();

// Write the structure
$binary = $template->write([
    Buffer::hex($structure->hash),
    $structure->message_id,
    new Buffer($structure->message)
]);

echo $binary->getHex() . "\n";

// Use the template to read resulting Buffer
$parsed = $template->parse(new Parser($binary));

$p = (object) [
    'hash' => $parsed[0]->getHex(),
    'message_id' => $parsed[1],
    'message' => $parsed[2]->getBinary()
];

print_r($p);
```