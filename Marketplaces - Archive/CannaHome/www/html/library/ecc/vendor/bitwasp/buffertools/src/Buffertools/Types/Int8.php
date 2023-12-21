<?php

namespace BitWasp\Buffertools\Types;

class Int8 extends AbstractSignedInt
{
    public function getBitSize()
    {
        return 8;
    }
}
