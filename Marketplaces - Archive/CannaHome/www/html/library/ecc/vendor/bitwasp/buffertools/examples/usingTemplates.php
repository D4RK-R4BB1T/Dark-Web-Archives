<?php

require "../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

// Parsers read Buffers
$setBuffer = new Buffer('aaabccdeee');
$setParser = new Parser($setBuffer);

// Read data into $set
$set = (new TemplateFactory())
    ->bytestring(3)
    ->bytestring(1)
    ->bytestring(2)
    ->bytestring(1)
    ->bytestring(3)
    ->getTemplate()
    ->parse($setParser);

print_r($set);

// We can serialize a set:
// data starts with the size of the set
// write each member as a buffer
// a structure-specific parser reads the internal structure (not needed for writing)

$vector = (new TemplateFactory())
    ->vector(function () {}) // can be null, since we're writing
    ->getTemplate()
    ->write([$set]);

echo $vector->getHex() . PHP_EOL;

echo $vector->getBinary() . PHP_EOL;

