<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\VarInt;
use Mdanter\Ecc\EccFactory;

class VarIntTest extends BinaryTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Integer too large, exceeds 64 bit
     */
    public function testSolveWriteTooLong()
    {
        $math = EccFactory::getAdapter();
        $varint = new VarInt($math);
        $disallowed = $math->add($math->pow(2, 64), 1);
        $varint->solveWriteSize($disallowed);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Integer too large, exceeds 64 bit
     */
    public function testSolveReadTooLong()
    {
        $math = EccFactory::getAdapter();
        $varint = new VarInt($math);
        $disallowed = $math->add($math->pow(2, 64), 1);
        $varint->solveReadSize($disallowed);
    }
}
