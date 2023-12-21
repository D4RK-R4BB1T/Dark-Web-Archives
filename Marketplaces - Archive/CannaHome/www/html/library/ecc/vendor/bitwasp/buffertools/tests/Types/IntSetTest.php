<?php

namespace BitWasp\Buffertools\Tests\Types;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Tests\BinaryTest;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\SignedIntInterface;
use Mdanter\Ecc\EccFactory;

class IntSetTest extends BinaryTest
{

    public function getIntSetVectors()
    {
        $int32_le = new Int32(EccFactory::getAdapter(), ByteOrder::LE);
        $int32_be = new Int32(EccFactory::getAdapter(), ByteOrder::BE);
        return [
            [$int32_be, '1', '00000001'],
            [$int32_le, '1', '01000000'],
            [$int32_be, '-1', 'ffffffff'],
            [$int32_le, '-1', 'ffffffff'],
            [$int32_be, '0', '00000000'],
            [$int32_le, '0', '00000000'],
        ];
    }

    /**
     * @dataProvider getIntSetVectors
     */
    public function testInt(SignedIntInterface $signed, $int, $expectedHex)
    {
        $out = $signed->write($int);
        $this->assertEquals($expectedHex, str_pad(bin2hex($out), $signed->getBitSize() / 4, '0', STR_PAD_LEFT));

        $parser = new Parser(new Buffer($out));
        $recovered = $signed->read($parser);
        $this->assertEquals($int, $recovered);
    }
}
