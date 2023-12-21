<?php

namespace BitWasp\Buffertools\Types;

use BitWasp\Buffertools\Parser;

interface TypeInterface
{
    /**
     * Return the math adapter
     *
     * @return \Mdanter\Ecc\Math\MathAdapterInterface
     */
    public function getMath();

    /**
     * Flip whatever bitstring is given to us
     *
     * @param  string $bitString
     * @return string
     */
    public function flipBits($bitString);

    /**
     * @param int $integer
     * @return mixed
     */
    public function write($integer);

    /**
     * @param Parser $parser
     * @return string|int
     */
    public function read(Parser $parser);

    /**
     * @return int|string
     */
    public function getByteOrder();
}
