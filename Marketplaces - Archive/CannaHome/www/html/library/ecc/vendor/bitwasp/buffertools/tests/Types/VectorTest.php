<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\Vector;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\EccFactory;

class VectorTest extends BinaryTest
{
    public function testVector()
    {
        $math = EccFactory::getAdapter();
        $varint = new VarInt($math);
        $vector = new Vector(
            $varint,
            function () {
                return;
            }
        );

        $buffer = Buffer::hex('010203040506070809000a0b0c0d0e0f');
        $array = [$buffer, $buffer, $buffer];
        $this->assertEquals('03' . $buffer->getHex() . $buffer->getHex() . $buffer->getHex(), bin2hex($vector->write($array)));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Vector::write() must be supplied with an array
     */
    public function testWriteInvalid()
    {
        $math = EccFactory::getAdapter();
        $varint = new VarInt($math);
        $vector = new Vector(
            $varint,
            function () {
                return;
            }
        );
        $buffer = Buffer::hex('010203040506070809000a0b0c0d0e0f');
        $vector->write($buffer);
    }

    public function testReadVector()
    {
        $math = EccFactory::getAdapter();
        $varint = new VarInt($math);
        $vector = new Vector(
            $varint,
            function (Parser $parser) {
                return $parser->readBytes(16);
            }
        );

        $eBuffer = Buffer::hex('010203040506070809000a0b0c0d0e0f');
        $hex = '03010203040506070809000a0b0c0d0e0f010203040506070809000a0b0c0d0e0f010203040506070809000a0b0c0d0e0f';
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);

        $array = $vector->read($parser);
        foreach ($array as $item) {
            $this->assertEquals($eBuffer->getBinary(), $item->getBinary());
        }
    }
}
