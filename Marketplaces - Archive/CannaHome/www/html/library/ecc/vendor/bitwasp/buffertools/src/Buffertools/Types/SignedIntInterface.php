<?php

namespace BitWasp\Buffertools\Types;

interface SignedIntInterface extends TypeInterface
{
    /**
     * @return int
     */
    public function getBitSize();
}
