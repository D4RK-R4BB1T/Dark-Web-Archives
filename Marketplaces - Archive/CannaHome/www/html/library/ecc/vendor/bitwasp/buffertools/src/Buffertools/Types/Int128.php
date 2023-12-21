<?php

namespace BitWasp\Buffertools\Types;

class Int128 extends AbstractSignedInt
{
    public function getBitSize()
    {
        return 128;
    }
}
