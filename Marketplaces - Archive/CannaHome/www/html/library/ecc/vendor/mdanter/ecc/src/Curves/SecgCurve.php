<?php

namespace Mdanter\Ecc\Curves;

use Mdanter\Ecc\Math\MathAdapterInterface;
use Mdanter\Ecc\Random\RandomNumberGeneratorInterface;

/**
 * *********************************************************************
 * Copyright (C) 2012 Matyas Danter
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
 * OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 * ***********************************************************************
 */

/**
 *
 */
class SecgCurve
{
    private $adapter;

    const NAME_SECP_112R1 = 'secp112r1';

    const NAME_SECP_256K1 = 'secp256k1';

    const NAME_SECP_256R1 = 'secp256r1';

    const NAME_SECP_384R1 = 'secp384r1';

    /**
     * @param MathAdapterInterface $adapter
     */
    public function __construct(MathAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return NamedCurveFp
     */
    public function curve112r1()
    {
        $p = $this->adapter->hexDec('0xDB7C2ABF62E35E668076BEAD208B');
        $a = $this->adapter->hexDec('0xDB7C2ABF62E35E668076BEAD2088');
        $b = $this->adapter->hexDec('0x659EF8BA043916EEDE8911702B22');

        return new NamedCurveFp(self::NAME_SECP_112R1, $p, $a, $b, $this->adapter);
    }

    /**
     * @param RandomNumberGeneratorInterface $randomGenerator
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function generator112r1(RandomNumberGeneratorInterface $randomGenerator = null)
    {
        $curve = $this->curve112r1();

        $order = $this->adapter->hexDec('0xDB7C2ABF62E35E7628DFAC6561C5');
        $x = $this->adapter->hexDec('0x09487239995A5EE76B55F9C2F098');
        $y = $this->adapter->hexDec('0xA89CE5AF8724C0A23E0E0FF77500');

        return $curve->getGenerator($x, $y, $order, $randomGenerator);
    }

    /**
     * @return NamedCurveFp
     */
    public function curve256k1()
    {
        $p = $this->adapter->hexDec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F');
        $a = 0;
        $b = 7;

        return new NamedCurveFp(self::NAME_SECP_256K1, $p, $a, $b, $this->adapter);
    }

    /**
     * @param RandomNumberGeneratorInterface $randomGenerator
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function generator256k1(RandomNumberGeneratorInterface $randomGenerator = null)
    {
        $curve = $this->curve256k1();

        $order = $this->adapter->hexDec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        $x = $this->adapter->hexDec('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798');
        $y = $this->adapter->hexDec('0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8');

        return $curve->getGenerator($x, $y, $order, $randomGenerator);
    }

    /**
     * @return NamedCurveFp
     */
    public function curve256r1()
    {
        $p = $this->adapter->hexDec('0xFFFFFFFF00000001000000000000000000000000FFFFFFFFFFFFFFFFFFFFFFFF');
        $a = $this->adapter->hexDec('0xFFFFFFFF00000001000000000000000000000000FFFFFFFFFFFFFFFFFFFFFFFC');
        $b = $this->adapter->hexDec('0x5AC635D8AA3A93E7B3EBBD55769886BC651D06B0CC53B0F63BCE3C3E27D2604B');

        return new NamedCurveFp(self::NAME_SECP_256R1, $p, $a, $b, $this->adapter);
    }

    /**
     * @param RandomNumberGeneratorInterface $randomGenerator
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function generator256r1(RandomNumberGeneratorInterface $randomGenerator = null)
    {
        $curve = $this->curve256r1();

        $order = $this->adapter->hexDec('0xFFFFFFFF00000000FFFFFFFFFFFFFFFFBCE6FAADA7179E84F3B9CAC2FC632551');
        $x = $this->adapter->hexDec('0x6B17D1F2E12C4247F8BCE6E563A440F277037D812DEB33A0F4A13945D898C296');
        $y = $this->adapter->hexDec('0x4FE342E2FE1A7F9B8EE7EB4A7C0F9E162BCE33576B315ECECBB6406837BF51F5');

        return $curve->getGenerator($x, $y, $order, $randomGenerator);
    }

    /**
     * @return NamedCurveFp
     */
    public function curve384r1()
    {
        $p = $this->adapter->hexDec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFF0000000000000000FFFFFFFF');
        $a = $this->adapter->hexDec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFF0000000000000000FFFFFFFC');
        $b = $this->adapter->hexDec('0xB3312FA7E23EE7E4988E056BE3F82D19181D9C6EFE8141120314088F5013875AC656398D8A2ED19D2A85C8EDD3EC2AEF');

        return new NamedCurveFp(self::NAME_SECP_384R1, $p, $a, $b, $this->adapter);
    }

    /**
     * @param RandomNumberGeneratorInterface $randomGenerator
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function generator384r1(RandomNumberGeneratorInterface $randomGenerator = null)
    {
        $curve = $this->curve384r1();

        $order = $this->adapter->hexDec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFC7634D81F4372DDF581A0DB248B0A77AECEC196ACCC52973');
        $x = $this->adapter->hexDec('0xAA87CA22BE8B05378EB1C71EF320AD746E1D3B628BA79B9859F741E082542A385502F25DBF55296C3A545E3872760AB7');
        $y = $this->adapter->hexDec('0x3617DE4A96262C6F5D9E98BF9292DC29F8F41DBD289A147CE9DA3113B5F0B8C00A60B1CE1D7E819D7A431D7C90EA0E5F');

        return $curve->getGenerator($x, $y, $order, $randomGenerator);
    }
}
