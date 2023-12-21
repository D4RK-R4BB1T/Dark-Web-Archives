<?php

namespace Mdanter\Ecc\Tests\Math;

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\NumberTheory;
use Mdanter\Ecc\Math\MathAdapterInterface;
use Mdanter\Ecc\Tests\AbstractTestCase;

class NumberTheoryTest extends AbstractTestCase
{
    /**
     * @var
     */
    protected $compression_data;

    /**
     * @var
     */
    protected $sqrt_data;

    /**
     * @var \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    protected $generator;

    protected function setUp()
    {
        // file containing a json array of {compressed=>'', decompressed=>''} values
        // of compressed and uncompressed ECDSA public keys (testing secp256k1 curve)
        $file_comp = TEST_DATA_DIR.'/compression.json';

        if (! file_exists($file_comp)) {
            $this->fail('Key compression input data not found');
        }

        $file_sqrt = TEST_DATA_DIR.'/square_root_mod_p.json';
        if (! file_exists($file_sqrt)) {
            $this->fail('Square root input data not found');
        }
        $this->generator = EccFactory::getSecgCurves()->generator256k1();
        $this->compression_data = json_decode(file_get_contents($file_comp));

        $this->sqrt_data = json_decode(file_get_contents($file_sqrt));
    }

    /**
     * @dataProvider getAdapters
     * @expectedException \LogicException
     */
    public function testSqrtDataWithNoRoots(MathAdapterInterface $adapter)
    {
        $theory = $adapter->getNumberTheory();

        foreach ($this->sqrt_data->no_root as $r) {
            $theory->squareRootModP($r->a, $r->p);
        }
    }
    /**
     * @dataProvider getAdapters
     */
    public function testSqrtDataWithRoots(MathAdapterInterface $adapter)
    {
        $theory = $adapter->getNumberTheory();

        foreach ($this->sqrt_data->has_root as $r) {
            $root1 = $theory->squareRootModP($r->a, $r->p);
            $root2 = $adapter->sub($r->p, $root1);
            $this->assertTrue(in_array($root1, $r->res));
            $this->assertTrue(in_array($root2, $r->res));
        }
    }

    /**
     * @dataProvider getAdapters
     */
    public function testCompressionConsistency(MathAdapterInterface $adapter)
    {
        $theory = $adapter->getNumberTheory();
        $this->_doCompressionConsistence($adapter, $theory);
    }

    public function _doCompressionConsistence(MathAdapterInterface $adapter, NumberTheory $theory)
    {
        foreach ($this->compression_data as $o) {
            // Try and regenerate the y coordinate from the parity byte
            // '04' . $x_coordinate . determined y coordinate should equal $o->decompressed
            // Tests squareRootModP which touches most functions in NumberTheory
            $y_byte = substr($o->compressed, 0, 2);
            $x_coordinate = substr($o->compressed, 2);

            $x = $adapter->hexDec($x_coordinate);

            // x^3
            $x3 = $adapter->powmod($x, 3, $this->generator->getCurve()->getPrime());

            // y^2
            $y2 = $adapter->add(
                        $x3,
                        $this->generator->getCurve()->getB()
                    );

            // y0 = sqrt(y^2)
            $y0 = $theory->squareRootModP(
                        $y2,
                        $this->generator->getCurve()->getPrime()
                    );

            if ($y_byte == '02') {
                $y_coordinate = ($adapter->mod($y0, 2) == '0')
                    ? gmp_strval(gmp_init($y0, 10), 16)
                    : gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16);
            } else {
                $y_coordinate = ($adapter->mod($y0, 2) == '0')
                    ? gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16)
                    : gmp_strval(gmp_init($y0, 10), 16);
            }
            $y_coordinate = str_pad($y_coordinate, 64, '0', STR_PAD_LEFT);

            // Successfully regenerated uncompressed ECDSA key from the x coordinate and the parity byte.
            $this->assertTrue('04'.$x_coordinate.$y_coordinate == $o->decompressed);
        }
    }

    /**
     * @dataProvider getAdapters
     */
    public function testModFunction(MathAdapterInterface $math)
    {
        // $o->compressed, $o->decompressed public key.
        // Check that we can compress a key properly (tests $math->mod())
        foreach ($this->compression_data as $o) {
            $prefix = substr($o->decompressed, 0, 2); // will be 04.

            $this->assertEquals('04', $prefix);

            // hex encoded (X,Y) coordinate of ECDSA public key.
            $x = substr($o->decompressed, 2, 64);
            $y = substr($o->decompressed, 66, 64);

            // y % 2 == 0       - true: y is even(02) / false: y is odd(03)
            $mod = $math->mod($math->hexDec($y), 2);
            $compressed = '0'.(($mod == 0) ? '2' : '3').$x;

            // Check that the mod function reported the parity for the y value.
            $this->assertTrue($compressed === $o->compressed);
        }
    }
}
