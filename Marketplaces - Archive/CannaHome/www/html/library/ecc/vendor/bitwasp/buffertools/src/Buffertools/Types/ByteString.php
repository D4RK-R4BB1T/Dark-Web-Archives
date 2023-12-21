<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Math\MathAdapterInterface;

class ByteString extends AbstractType
{
    /**
     * @var int|string
     */
    private $length;

    /**
     * @param MathAdapterInterface $math
     * @param int|string           $length
     * @param int|string           $byteOrder
     */
    public function __construct(MathAdapterInterface $math, $length, $byteOrder = ByteOrder::BE)
    {
        $this->length = $length;
        parent::__construct($math, $byteOrder);
    }

    /**
     * @param BufferInterface $string
     * @return string
     */
    public function writeBits(BufferInterface $string)
    {
        $bits = str_pad(
            $this->getMath()->baseConvert($string->getHex(), 16, 2),
            $this->length * 8,
            '0',
            STR_PAD_LEFT
        );

        return $bits;
    }

    /**
     * @param $string
     * @return string
     * @throws \Exception
     */
    public function write($string)
    {
        if (false === $string instanceof Buffer) {
            throw new \InvalidArgumentException('FixedLengthString::write() must be passed a Buffer');
        }

        $bits = $this->isBigEndian()
            ? $this->writeBits($string)
            : $this->flipBits($this->writeBits($string));

        $hex = str_pad(
            $this->getMath()->baseConvert($bits, 2, 16),
            $this->length * 2,
            '0',
            STR_PAD_LEFT
        );

        return pack("H*", $hex);
    }

    /**
     * @param BufferInterface $buffer
     * @return string
     */
    public function readBits(BufferInterface $buffer)
    {
        return str_pad(
            $this->getMath()->baseConvert($buffer->getHex(), 16, 2),
            $this->length * 8,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * @param Parser $parser
     * @return Buffer
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function read(Parser $parser)
    {
        $bits = $this->readBits($parser->readBytes($this->length));
        if (!$this->isBigEndian()) {
            $bits = $this->flipBits($bits);
        }

        return Buffer::hex(
            str_pad(
                $this->getMath()->baseConvert($bits, 2, 16),
                $this->length * 2,
                '0',
                STR_PAD_LEFT
            ),
            $this->length,
            $this->getMath()
        );
    }
}
