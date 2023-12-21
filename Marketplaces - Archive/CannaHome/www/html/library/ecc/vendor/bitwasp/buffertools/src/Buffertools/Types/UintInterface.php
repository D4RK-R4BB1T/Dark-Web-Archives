<?php

namespace BitWasp\Buffertools\Types;

interface UintInterface extends TypeInterface
{
    /**
     * @return int
     */
    public function getBitSize();
}
