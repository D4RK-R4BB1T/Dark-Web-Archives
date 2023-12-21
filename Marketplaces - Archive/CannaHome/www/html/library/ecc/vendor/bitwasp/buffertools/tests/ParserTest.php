<?php

namespace BitWasp\Buffertools\Tests;

use \BitWasp\Buffertools\Buffer;
use \BitWasp\Buffertools\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BitWasp\Buffertools\Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $parserType = 'BitWasp\Buffertools\Parser';

    /**
     * @var string
     */
    protected $bufferType = 'BitWasp\Buffertools\Buffer';

    public function setUp()
    {
        $this->parser = new Parser();
    }

    public function testParserEmpty()
    {
        $parser = new Parser();
        $this->assertInstanceOf($this->parserType, $parser);

        $this->assertSame(0, $parser->getPosition());
        $this->assertInstanceOf($this->bufferType, $parser->getBuffer());
        $this->assertEmpty($parser->getBuffer()->getHex());
    }

    public function testGetBuffer()
    {
        $buffer = Buffer::hex('41414141');

        $parser = new Parser($buffer);
        $this->assertSame($parser->getBuffer()->getBinary(), $buffer->getBinary());
    }

    public function testGetBufferEmptyNull()
    {
        $buffer = new Buffer();
        $this->parser = new Parser($buffer);
        $parserData = $this->parser->getBuffer()->getBinary();
        $bufferData = $buffer->getBinary();
        $this->assertSame($parserData, $bufferData);
    }

    public function testWriteBytes()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes));
        $returned = $parser->getBuffer()->getHex();
        $this->assertSame($returned, '41424344');
    }

    public function testWriteBytesFlip()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes), true);
        $returned = $parser->getBuffer()->getHex();
        $this->assertSame($returned, '44434241');
    }

    public function testWriteBytesPadded()
    {
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex('34'));
        $this->assertEquals("00000034", $parser->getBuffer()->getHex());
    }

    public function testWriteBytesFlipPadded()
    {
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex('34'), true);
        $this->assertEquals("34000000", $parser->getBuffer()->getHex());
    }

    public function testReadBytes()
    {
        $bytes = '41424344';

        $parser = new Parser($bytes);
        $read = $parser->readBytes(4);
        $this->assertInstanceOf($this->bufferType, $read);

        $hex = $read->getHex();
        $this->assertSame($bytes, $hex);
    }

    public function testReadBytesFlip()
    {
        $bytes = '41424344';

        $parser = new Parser($bytes);
        $read = $parser->readBytes(4, true);
        $this->assertInstanceOf($this->bufferType, $read);

        $hex = $read->getHex();
        $this->assertSame('44434241', $hex);
    }

    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Could not parse string of required length (empty)
     */
    public function testReadBytesEmpty()
    {
        // Should return false because position is zero,
        // and length is zero.

        $parser = new Parser();
        $data = $parser->readBytes(0);
        $this->assertFalse(!!$data);
    }
    /**
     * @expectedException \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @expectedExceptionMessage Could not parse string of required length (empty)
     */
    public function testReadBytesEndOfString()
    {
        $parser = new Parser('4041414142414141');
        $bytes1 = $parser->readBytes(4);
        $bytes2 = $parser->readBytes(4);
        $this->assertSame($bytes1->getHex(), '40414141');
        $this->assertSame($bytes2->getHex(), '42414141');
        $parser->readBytes(1);
    }

    /**
     * @expectedException \Exception
     */
    public function testReadBytesBeyondLength()
    {
        $bytes = '41424344';
        $parser = new Parser($bytes);
        $parser->readBytes(5);
    }
}
