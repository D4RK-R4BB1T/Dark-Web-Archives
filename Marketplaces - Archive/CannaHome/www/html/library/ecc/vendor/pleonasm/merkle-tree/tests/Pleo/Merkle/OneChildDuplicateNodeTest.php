<?php
/**
 * @copyright 2013 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use PHPUnit_Framework_TestCase;

class OneChildDuplicateNodeTest extends PHPUnit_Framework_TestCase
{
    private $tcn;
    private $node;

    public function setUp()
    {
        $this->tcn = $this
            ->getMockBuilder('\Pleo\Merkle\TwoChildrenNode')
            ->disableOriginalConstructor()
            ->getMock();

        $this->node = new OneChildDuplicateNode($this->tcn);
    }

    /**
     * @covers Pleo\Merkle\OneChildDuplicateNode
     */
    public function testHashPassesThroughNullReturn()
    {
        $this->tcn
            ->expects($this->once())
            ->method('hash')
            ->will($this->returnValue(null));

        $this->assertNull($this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\OneChildDuplicateNode
     */
    public function testHashPassesThroughStringReturn()
    {
        $this->tcn
            ->expects($this->once())
            ->method('hash')
            ->will($this->returnValue('hashstring'));

        $this->assertSame('hashstring', $this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\OneChildDuplicateNode
     */
    public function testDataDuplicatesStringInput()
    {
        $data = 'somedata';
        $this->tcn
            ->expects($this->once())
            ->method('data')
            ->with($this->equalTo($data), $this->equalTo($data));

        $this->node->data($data);
    }

    /**
     * @covers Pleo\Merkle\OneChildDuplicateNode
     */
    public function testDataPassesInSameReferenceTwiceOnITreeNodeInput()
    {
        $inputNode = $this
            ->getMockBuilder('\Pleo\Merkle\TwoChildrenNode')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->tcn
            ->expects($this->once())
            ->method('data')
            ->with($this->identicalTo($inputNode), $this->identicalTo($inputNode));

        $this->node->data($inputNode);
    }
}
