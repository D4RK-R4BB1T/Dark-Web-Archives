<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Math\MathAdapterInterface;

abstract class AbstractSignedInt extends AbstractType implements SignedIntInterface
{
    /**
     * @param MathAdapterInterface $math
     * @param int                  $byteOrder
     */
    public function __construct(MathAdapterInterface $math, $byteOrder = ByteOrder::BE)
    {
        parent::__construct($math, $byteOrder);
    }

    /**
     * @param $integer
     * @return string
     */
    public function writeBits($integer)
    {
        $math = $this->getMath();

        return str_pad(
            $math->baseConvert($integer, 10, 2),
            $this->getBitSize(),
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * @param Parser $parser
     * @return int|string
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function readBits(Parser $parser)
    {
        $bitSize = $this->getBitSize();
        $byteSize = $bitSize / 8;

        $bytes = $parser->readBytes($byteSize);
        $bytes = $this->isBigEndian() ? $bytes : $bytes->flip();
        $chars = $bytes->getBinary();

        $offsetIndex = 0;
        $isNegative = (ord($chars[$offsetIndex]) & 0x80) != 0x00;
        $number = gmp_init(ord($chars[$offsetIndex++]) & 0x7F, 10);

        for ($i = 0; $i < $byteSize-1; $i++) {
            $number = gmp_or(gmp_mul($number, 0x100), ord($chars[$offsetIndex++]));
        }

        if ($isNegative) {
            $number = gmp_sub($number, gmp_pow(2, $bitSize - 1));
        }

        return gmp_strval($number, 10);
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\Types\TypeInterface::write()
     */
    public function write($integer)
    {
        $bitSize = $this->getBitSize();
        if (gmp_sign($integer) < 0) {
            $integer = gmp_add($integer, (gmp_sub(gmp_pow(2, $bitSize), 1)));
            $integer = gmp_add($integer, 1);
        }

        $binary = \BitWasp\Buffertools\Buffer::hex(str_pad(gmp_strval($integer, 16), $bitSize/4, '0', STR_PAD_LEFT), $bitSize/8);

        if (!$this->isBigEndian()) {
            $binary = $binary->flip();
        }

        return $binary->getBinary();
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\Types\TypeInterface::read()
     */
    public function read(Parser $binary)
    {
        return $this->readBits($binary);
    }
}
