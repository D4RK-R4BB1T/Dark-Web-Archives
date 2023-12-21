<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\ByteOrder;
use Mdanter\Ecc\Math\MathAdapterInterface;

abstract class AbstractType implements TypeInterface
{
    /**
     * @var MathAdapterInterface
     */
    private $math;

    /**
     * @var
     */
    private $byteOrder;

    /**
     * @param MathAdapterInterface $math
     * @param int                  $byteOrder
     */
    public function __construct(MathAdapterInterface $math, $byteOrder = ByteOrder::BE)
    {
        if (false === in_array($byteOrder, [ByteOrder::BE, ByteOrder::LE])) {
            throw new \InvalidArgumentException('Must pass valid flag for endianness');
        }

        $this->math = $math;
        $this->byteOrder = $byteOrder;
    }

    /**
     * @return int
     */
    public function getByteOrder()
    {
        return $this->byteOrder;
    }

    /**
     * @return bool
     */
    public function isBigEndian()
    {
        return $this->getByteOrder() == ByteOrder::BE;
    }

    /**
     * @return MathAdapterInterface
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @param $bitString
     * @return string
     * @throws \Exception
     */
    public function flipBits($bitString)
    {
        $length = strlen($bitString);

        if ($length % 8 !== 0) {
            throw new \Exception('Bit string length must be a multiple of 8');
        }

        $newString = '';
        for ($i = $length; $i >= 0; $i -= 8) {
            $newString .= substr($bitString, $i, 8);
        }

        return $newString;
    }
}
