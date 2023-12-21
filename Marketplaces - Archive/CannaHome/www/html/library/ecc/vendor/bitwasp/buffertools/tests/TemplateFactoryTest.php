<?php

namespace BitWasp\Buffertools\Tests;

use BitWasp\Buffertools\TemplateFactory;
use Mdanter\Ecc\EccFactory;

class TemplateFactoryTest extends BinaryTest
{
    /**
     * @return array
     */
    public function getTestVectors()
    {
        $vectors = [];

        for ($i = 8; $i <= 256; $i = $i * 2) {
            foreach (array('', 'le') as $byteOrder) {
                $vectors[] = [
                    'uint' . $i . $byteOrder,
                    '\BitWasp\Buffertools\Types\Uint' . $i,
                ];
                $vectors[] = [
                    'int' . $i . $byteOrder,
                    '\BitWasp\Buffertools\Types\Int' . $i,
                ];
            }
        }

        $vectors[] = [
            'varint',
            '\BitWasp\Buffertools\Types\VarInt'
        ];

        $vectors[] = [
            'varstring',
            '\BitWasp\Buffertools\Types\VarString'
        ];

        return $vectors;
    }

    /**
     * @dataProvider getTestVectors
     */
    public function testTemplateUint($function, $eClass)
    {
        $math = EccFactory::getAdapter();
        $factory = new TemplateFactory(null, $math);
        $factory->$function();
        $template = $factory->getTemplate();
        $this->assertEquals(1, count($template));
        $template = $factory->getTemplate()->getItems();
        $this->assertInstanceOf($eClass, $template[0]);
    }

    public function testVector()
    {
        $math = EccFactory::getAdapter();
        $factory = new TemplateFactory(null, $math);
        $factory->vector(
            function () {
                return;
            }
        );
        $template = $factory->getTemplate();
        $this->assertEquals(1, count($template));
        $template = $factory->getTemplate()->getItems();
        $this->assertInstanceOf('BitWasp\Buffertools\Types\Vector', $template[0]);
    }
}
