<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\ByteString;
use Mdanter\Ecc\Math\MathAdapterFactory;

class ByteStringTest extends BinaryTest
{
    public function getVectors()
    {
        $math = MathAdapterFactory::getAdapter();
        return [
            [$math, 1, '04'],
            [$math, 1, '41'],
            [$math, 4, '0488b21e'],
        ];
    }

    /**
     * @dataProvider getVectors
     */
    public function testByteString($math, $size, $string)
    {
        $buffer = Buffer::hex($string, $size);

        $t = new ByteString($math, $size);
        $out = $t->write($buffer);

        $this->assertEquals(pack("H*", $string), $out);

        $parser = new Parser(new Buffer($out));
        $this->assertEquals($string, $t->read($parser)->getHex());
    }

    /**
     * @dataProvider getVectors
     */
    public function testByteStringLe($math, $size, $string)
    {
        $buffer = Buffer::hex($string, $size);

        $t = new ByteString($math, $size, ByteOrder::LE);
        $out = $t->write($buffer);

        $eFlipped = Buffertools::flipBytes(pack("H*", $string));
        $this->assertEquals($eFlipped, $out);

        $parser = new Parser(new Buffer($out));
        $this->assertEquals($string, $t->read($parser)->getHex());
    }
}
