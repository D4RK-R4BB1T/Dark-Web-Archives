<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\VarString;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\EccFactory;

class VarStringTest extends BinaryTest
{

    public function testGetVarString()
    {
        $strings = array(
            '',
            '00',
            '00010203040506070809',
            '00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102',
        );

        $math = EccFactory::getAdapter();
        $varstring = new VarString(new VarInt($math));

        foreach ($strings as $string) {
            $binary = $varstring->write(Buffer::hex($string));
            $parser = new Parser(new Buffer($binary));
            $original = $varstring->read($parser);
            $this->assertSame($string, $original->getHex());
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Must provide a buffer
     */
    public function testFailsWithoutBuffer()
    {
        $math = EccFactory::getAdapter();
        $varstring = new VarString(new VarInt($math));
        $varstring->write('');
    }
}
