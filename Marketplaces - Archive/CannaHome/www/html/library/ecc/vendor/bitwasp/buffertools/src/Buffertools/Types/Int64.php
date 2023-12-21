<?php

namespace BitWasp\Buffertools\Types;

class Int64 extends AbstractSignedInt
{
    public function getBitSize()
    {
        return 64;
    }
}
