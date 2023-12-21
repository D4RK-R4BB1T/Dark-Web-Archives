<?php

namespace BitWasp\Buffertools;

class ByteOrder
{
    /**
     * Little endian means bytes must be flipped
     */
    const LE = 0;

    /**
     * Assuming machine byte order?
     */
    const BE = 1;
}
