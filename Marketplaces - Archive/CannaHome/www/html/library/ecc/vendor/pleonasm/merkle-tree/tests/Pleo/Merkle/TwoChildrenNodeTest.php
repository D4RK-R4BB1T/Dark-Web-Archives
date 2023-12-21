<?php
/**
 * @copyright 2013 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use PHPUnit_Framework_TestCase;
use StdClass;

class TwoChildrenNodeTest extends PHPUnit_Framework_TestCase
{
    private $calls;
    private $return;
    private $callback;
    private $node;

    public function setUp()
    {
        $this->calls = array();
        $this->return = 'woo!';

        $this->callback = function ($ipt) {
            $this->calls[] = $ipt;
            return $this->return;
        };
        $this->node = new TwoChildrenNode($this->callback);
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWithNoDataReturnsNull()
    {
        $this->assertNull($this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     * @dataProvider badInputs
     * @expectedException InvalidArgumentException
     */
    public function testSettingFirstWithBadVarTypesThrowsException($one, $two)
    {
        $this->node->data($one, $two);
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     * @expectedException LogicException
     */
    public function testSettingAnyDataASecondTimeThrowsException()
    {
        $this->node->data('hello', 'world');
        $this->node->data('hello', 'world');
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWithStringDataCallsHasherWithConcatinatedStrings()
    {
        $this->node->data('hello', 'world');
        $this->node->hash();
        $this->assertSame('helloworld', $this->calls[0]);
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWithStringDataCallsHasherOnlyOnce()
    {
        $this->node->data('hello', 'world');
        $this->node->hash();
        $this->node->hash();
        $this->node->hash();
        $this->node->hash();
        $this->assertSame(1, count($this->calls[0]));
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     * @expectedException LogicException
     */
    public function testSettingDataAfterSuccessfulHashStillThrowsException()
    {
        $this->node->data('hello', 'world');
        $this->node->hash();
        $this->node->data('hello!', 'world!');
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWillUsedCallbackReturnAfterSettingData()
    {
        $this->node->data('hello', 'world');
        $actual = $this->node->hash();
        $this->assertEquals('woo!', $actual);
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWithTwoChildrenNodesUsesConcatinationOfTheirHashesForCallback()
    {
        $first = new TwoChildrenNode(function () {
            return 'foo';
        });
        $first->data('', '');
        $second = new TwoChildrenNode(function () {
            return 'bar';
        });
        $second->data('', '');
        $this->node->data($first, $second);
        $this->node->hash();
        $this->assertSame('foobar', $this->calls[0]);
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWillReturnNullIfFirstDecendantTwoChildrenNodesHashReturnsNull()
    {
        $first = new TwoChildrenNode(function () {
            return 'foo';
        });
        $second = new TwoChildrenNode(function () {
            return 'bar';
        });
        $second->data('', '');

        $this->node->data($first, $second);
        $this->assertNull($this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWillReturnNullIfSecondDecendantTwoChildrenNodesHashReturnsNull()
    {
        $first = new TwoChildrenNode(function () {
            return 'foo';
        });
        $first->data('', '');
        $second = new TwoChildrenNode(function () {
            return 'bar';
        });

        $this->node->data($first, $second);
        $this->assertNull($this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     */
    public function testHashWillReturnNullIfBothDecendantTwoChildrenNodesHashReturnsNull()
    {
        $first = new TwoChildrenNode(function () {
            return 'foo';
        });
        $second = new TwoChildrenNode(function () {
            return 'bar';
        });

        $this->node->data($first, $second);
        $this->assertNull($this->node->hash());
    }

    /**
     * @covers Pleo\Merkle\TwoChildrenNode
     * @dataProvider badCallbackReturns
     * @expectedException UnexpectedValueException
     */
    public function testThrowExceptionIfCallbackDoesNotReturnString($return)
    {
        $this->return = $return;
        $this->node->data('hello', 'world');
        $this->node->hash();
    }

    public function badCallbackReturns()
    {
        return array(
            array(null),
            array(true),
            array(false),
            array(0),
            array(1.1),
            array(array()),
            array(new StdClass),
            array(STDIN),
        );
    }

    public function badInputs()
    {
        return array(
            array(null, null),
            array(null, false),
            array(null, true),
            array(null, 0),
            array(null, 1.1),
            array(null, 'hello'),
            array(null, array()),
            array(null, new StdClass),
            array(null, STDIN),
            array(null, new TwoChildrenNode(function () {})),
            array(false, null),
            array(false, false),
            array(false, true),
            array(false, 0),
            array(false, 1.1),
            array(false, 'hello'),
            array(false, array()),
            array(false, new StdClass),
            array(false, STDIN),
            array(false, new TwoChildrenNode(function () {})),
            array(true, null),
            array(true, false),
            array(true, true),
            array(true, 0),
            array(true, 1.1),
            array(true, 'hello'),
            array(true, array()),
            array(true, new StdClass),
            array(true, STDIN),
            array(true, new TwoChildrenNode(function () {})),
            array(0, null),
            array(0, false),
            array(0, true),
            array(0, 0),
            array(0, 1.1),
            array(0, 'hello'),
            array(0, array()),
            array(0, new StdClass),
            array(0, STDIN),
            array(0, new TwoChildrenNode(function () {})),
            array(1.1, null),
            array(1.1, false),
            array(1.1, true),
            array(1.1, 0),
            array(1.1, 1.1),
            array(1.1, 'hello'),
            array(1.1, array()),
            array(1.1, new StdClass),
            array(1.1, STDIN),
            array(1.1, new TwoChildrenNode(function () {})),
            array('hello', null),
            array('hello', false),
            array('hello', true),
            array('hello', 0),
            array('hello', 1.1),
            array('hello', array()),
            array('hello', new StdClass),
            array('hello', STDIN),
            array('hello', new TwoChildrenNode(function () {})),
            array(array(), null),
            array(array(), false),
            array(array(), true),
            array(array(), 0),
            array(array(), 1.1),
            array(array(), 'hello'),
            array(array(), array()),
            array(array(), new StdClass),
            array(array(), STDIN),
            array(array(), new TwoChildrenNode(function () {})),
            array(new StdClass, null),
            array(new StdClass, false),
            array(new StdClass, true),
            array(new StdClass, 0),
            array(new StdClass, 1.1),
            array(new StdClass, 'hello'),
            array(new StdClass, array()),
            array(new StdClass, new StdClass),
            array(new StdClass, STDIN),
            array(new StdClass, new TwoChildrenNode(function () {})),
            array(STDIN, null),
            array(STDIN, false),
            array(STDIN, true),
            array(STDIN, 0),
            array(STDIN, 1.1),
            array(STDIN, 'hello'),
            array(STDIN, array()),
            array(STDIN, new StdClass),
            array(STDIN, STDIN),
            array(STDIN, new TwoChildrenNode(function () {})),
            array(new TwoChildrenNode(function () {}), null),
            array(new TwoChildrenNode(function () {}), false),
            array(new TwoChildrenNode(function () {}), true),
            array(new TwoChildrenNode(function () {}), 0),
            array(new TwoChildrenNode(function () {}), 1.1),
            array(new TwoChildrenNode(function () {}), 'hello'),
            array(new TwoChildrenNode(function () {}), array()),
            array(new TwoChildrenNode(function () {}), new StdClass),
            array(new TwoChildrenNode(function () {}), STDIN),
        );
    }
}
