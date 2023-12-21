<?php

namespace BitWasp\Buffertools\Types;

class Int256 extends AbstractSignedInt
{
    public function getBitSize()
    {
        return 256;
    }
}
