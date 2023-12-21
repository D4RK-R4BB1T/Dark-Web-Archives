<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\UintInterface;
use BitWasp\Buffertools\Types\Uint8;
use BitWasp\Buffertools\Types\Uint16;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\Uint64;
use BitWasp\Buffertools\Types\Uint128;
use BitWasp\Buffertools\Types\Uint256;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\EccFactory;

class UintSetTest extends BinaryTest
{

    /**
     * @param $bitSize
     * @return array
     */
    private function generateSizeBasedTests($bitSize, $byteOrder)
    {
        $math = EccFactory::getAdapter();
        $halfPos = $math->baseConvert(str_pad('7', $bitSize / 4, 'f', STR_PAD_RIGHT), 16, 10);
        $maxPos = $math->baseConvert(str_pad('', $bitSize / 4, 'f', STR_PAD_RIGHT), 16, 10);

        $test = function ($integer) use ($bitSize, $math, $byteOrder) {
            $hex = str_pad($math->baseConvert($integer, 10, 16), $bitSize / 4, '0', STR_PAD_LEFT);

            if ($byteOrder == ByteOrder::LE) {
                $hex = Buffertools::flipBytes(Buffer::hex($hex))->getHex();
            }
            return [
                $integer,
                $hex,
                null
            ];
        };

        return [
            $test(0),
            $test(1),
            $test($halfPos),
            $test($maxPos)
        ];
    }

    /**
     * @param $math
     * @return UintInterface[]
     */
    public function getUintClasses($math)
    {
        return [
            new Uint8($math),
            new Uint16($math),
            new Uint32($math),
            new Uint64($math),
            new Uint128($math),
            new Uint256($math),
            new Uint8($math, ByteOrder::LE),
            new Uint16($math, ByteOrder::LE),
            new Uint32($math, ByteOrder::LE),
            new Uint64($math, ByteOrder::LE),
            new Uint128($math, ByteOrder::LE),
            new Uint256($math, ByteOrder::LE),
        ];
    }

    /**
     * @return array
     */
    public function AllTests()
    {
        $math = EccFactory::getAdapter();
        $vectors = [];
        foreach ($this->getUintClasses($math) as $val) {
            $order = $val->getByteOrder();
            foreach ($this->generateSizeBasedTests($val->getBitSize(), $order) as $t) {
                $vectors[] = array_merge([$val], $t);
            }
        }
        return $vectors;
    }

    /**
     * @dataProvider AllTests
     * @param $int
     * @param $eHex
     */
    public function testUint(UintInterface $comp, $int, $eHex)
    {
        $binary = $comp->write($int);
        $this->assertEquals($eHex, str_pad(bin2hex($binary), $comp->getBitSize() / 4, '0', STR_PAD_LEFT));

        $parser = new Parser(new Buffer($binary));
        $recovered = $comp->read($parser);
        $this->assertEquals($int, $recovered);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Must pass valid flag for endianness
     */
    public function testUintInvalidOrder()
    {
        $math = EccFactory::getAdapter();
        new Uint8($math, 2);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Bit string length must be a multiple of 8
     */
    public function testInvalidFlipLength()
    {
        $math = EccFactory::getAdapter();
        $u = new Uint8($math, 1);
        $u->flipBits('0');
    }
}
