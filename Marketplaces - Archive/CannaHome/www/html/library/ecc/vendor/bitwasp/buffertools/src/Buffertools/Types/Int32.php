<?php

namespace BitWasp\Buffertools\Types;

class Int32 extends AbstractSignedInt
{
    public function getBitSize()
    {
        return 32;
    }
}
