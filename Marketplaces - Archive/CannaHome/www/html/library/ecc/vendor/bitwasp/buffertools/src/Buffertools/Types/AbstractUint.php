<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Math\MathAdapterInterface;

abstract class AbstractUint extends AbstractType implements UintInterface
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
        $math = $this->getMath();
        $bitSize = $this->getBitSize();
        $bits = str_pad(
            $math->baseConvert($parser->readBytes($bitSize / 8)->getHex(), 16, 2),
            $bitSize,
            '0',
            STR_PAD_LEFT
        );

        $integer = $math->baseConvert(
            $this->isBigEndian()
            ? $bits
            : $this->flipBits($bits),
            2,
            10
        );

        return $integer;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\Types\TypeInterface::write()
     */
    public function write($integer)
    {
        return pack(
            "H*",
            str_pad(
                $this->getMath()->baseConvert(
                    $this->isBigEndian()
                    ? $this->writeBits($integer)
                    : $this->flipBits($this->writeBits($integer)),
                    2,
                    16
                ),
                $this->getBitSize()/4,
                '0',
                STR_PAD_LEFT
            )
        );
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
