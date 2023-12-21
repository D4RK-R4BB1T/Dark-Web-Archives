<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Math\MathAdapterInterface;

class VarInt extends AbstractType
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
     * @return array
     */
    private function getSizeInfo()
    {
        $math = $this->getMath();

        return [
            ['\BitWasp\Buffertools\Types\Uint16', $math->pow(2, 16), 0xfd],
            ['\BitWasp\Buffertools\Types\Uint32', $math->pow(2, 32), 0xfe],
            ['\BitWasp\Buffertools\Types\Uint64', $math->pow(2, 64), 0xff],
        ];
    }

    /**
     * @param int|string $integer
     * @return array
     */
    public function solveWriteSize($integer)
    {
        $math = $this->getMath();

        foreach ($this->getSizeInfo() as $config) {
            list($uint, $limit, $prefix) = $config;
            if ($math->cmp($integer, $limit) < 0) {
                return [
                    new $uint($math, ByteOrder::LE),
                    $prefix
                ];
            }
        }

        throw new \InvalidArgumentException('Integer too large, exceeds 64 bit');
    }

    /**
     * @param int|string $givenPrefix
     * @return UintInterface[]
     * @throws \InvalidArgumentException
     */
    public function solveReadSize($givenPrefix)
    {
        $math = $this->getMath();

        foreach ($this->getSizeInfo() as $config) {
            $uint = $config[0];
            $prefix = $config[2];
            if ($math->cmp($givenPrefix, $prefix) == 0) {
                return [
                    new $uint($math, ByteOrder::LE)
                ];
            }
        }

        throw new \InvalidArgumentException('Integer too large, exceeds 64 bit');
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\Types\TypeInterface::write()
     */
    public function write($integer)
    {
        $math = $this->getMath();

        $uint8 = new Uint8($math);
        if ($math->cmp($integer, 0xfd) < 0) {
            $int = $uint8;
        } else {
            list ($int, $prefix) = $this->solveWriteSize($integer);
        }

        $prefix = isset($prefix) ? $uint8->write($prefix) : '';
        $bits = $prefix . $int->write($integer);

        return $bits;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\Types\TypeInterface::read()
     */
    public function read(Parser $parser)
    {
        $math = $this->getMath();
        $uint8 = new Uint8($math);
        $int = $uint8->readBits($parser);

        if ($math->cmp($int, 0xfd) < 0) {
            return $int;
        } else {
            $uint = $this->solveReadSize($int)[0];
            return $uint->read($parser);
        }
    }
}
