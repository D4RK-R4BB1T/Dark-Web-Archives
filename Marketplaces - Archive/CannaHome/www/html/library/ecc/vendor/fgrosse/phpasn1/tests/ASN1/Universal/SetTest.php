<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Test\ASN1\Universal;

use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\Boolean;

class SetTest extends ASN1TestCase
{

    public function testGetType()
    {
        $object = new Set();
        $this->assertEquals(Identifier::SET, $object->getType());
    }

    public function testGetIdentifier()
    {
        $object = new Set();
        $this->assertEquals(chr(Identifier::SET), $object->getIdentifier());
    }

    public function testContent()
    {
        $child1 = new Integer(123);
        $child2 = new PrintableString("Hello Wold");
        $child3 = new Boolean(true);
        $object = new Set($child1, $child2, $child3);

        $this->assertEquals(array($child1, $child2, $child3), $object->getContent());
    }

    public function testGetObjectLength()
    {
        $child1 = new Boolean(true);
        $object = new Set($child1);
        $this->assertEquals(5, $object->getObjectLength());

        $child1 = new Integer(123);
        $child2 = new Boolean(true);
        $object = new Set($child1, $child2);
        $this->assertEquals(8, $object->getObjectLength());

        $child1 = new Integer(123);
        $child2 = new PrintableString("Hello Wold");
        $child3 = new Boolean(true);
        $object = new Set($child1, $child2, $child3);
        $this->assertEquals(20, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $child1 = new Boolean(true);
        $object = new Set($child1);

        $expectedType = chr(Identifier::SET);
        $expectedLength = chr(0x03);
        $expectedContent = $child1->getBinary();
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $child1 = new Integer(123);
        $child2 = new Boolean(true);
        $object = new Set($child1, $child2);
        $expectedLength = chr(0x06);
        $expectedContent  = $child1->getBinary();
        $expectedContent .= $child2->getBinary();
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalobject = new Set(
            new Boolean(true),
            new Integer(1234567)
        );
        $binaryData = $originalobject->getBinary();
        $parsedObject = Set::fromBinary($binaryData);
        $this->assertEquals($originalobject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
/*    public function testFromBinaryWithOffset() {
        $originalobject1 = new Set(
            new Boolean(true),
            new Integer(123)
        );
        $originalobject2 = new Set(
            new Integer(64),
            new Boolean(false)
        );

        $binaryData  = $originalobject1->getBinary();
        $binaryData .= $originalobject2->getBinary();

        $offset = 0;
        $parsedObject = Set::fromBinary($binaryData, $offset);
        $this->assertEquals($originalobject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = Set::fromBinary($binaryData, $offset);
        $this->assertEquals($originalobject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }*/
}
