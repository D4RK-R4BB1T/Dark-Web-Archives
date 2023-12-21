<?php

namespace Mdanter\Ecc\Curves;

use Mdanter\Ecc\Math\MathAdapterFactory;

class CurveFactory
{
    /**
     * @param $name
     * @return NamedCurveFp|\Mdanter\Ecc\Primitives\CurveFp|\Mdanter\Ecc\Primitives\CurveFpInterface
     */
    public static function getCurveByName($name)
    {
        $nistFactory = self::getNistFactory();
        $secpFactory = self::getSecpFactory();

        switch ($name) {
            case NistCurve::NAME_P192:
                return $nistFactory->curve192();
            case NistCurve::NAME_P224:
                return $nistFactory->curve224();
            case NistCurve::NAME_P256:
                return $nistFactory->curve256();
            case NistCurve::NAME_P384:
                return $nistFactory->curve384();
            case NistCurve::NAME_P521:
                return $nistFactory->curve521();
            case SecgCurve::NAME_SECP_256K1:
                return $secpFactory->curve256k1();
            case SecgCurve::NAME_SECP_256R1:
                return $secpFactory->curve256r1();
            case SecgCurve::NAME_SECP_384R1:
                return $secpFactory->curve384r1();
            default:
                throw new \RuntimeException('Unknown curve.');
        }
    }

    /**
     * @param $name
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public static function getGeneratorByName($name)
    {
        $nistFactory = self::getNistFactory();
        $secpFactory = self::getSecpFactory();

        switch ($name) {
            case NistCurve::NAME_P192:
                return $nistFactory->generator192();
            case NistCurve::NAME_P224:
                return $nistFactory->generator224();
            case NistCurve::NAME_P256:
                return $nistFactory->generator256();
            case NistCurve::NAME_P384:
                return $nistFactory->generator384();
            case NistCurve::NAME_P521:
                return $nistFactory->generator521();
            case SecgCurve::NAME_SECP_256K1:
                return $secpFactory->generator256k1();
            case SecgCurve::NAME_SECP_256R1:
                return $secpFactory->generator256r1();
            case SecgCurve::NAME_SECP_384R1:
                return $secpFactory->generator384r1();
            default:
                throw new \RuntimeException('Unknown generator.');
        }
    }

    /**
     * @return NistCurve
     */
    private static function getNistFactory()
    {
        return new NistCurve(MathAdapterFactory::getAdapter());
    }

    /**
     * @return SecgCurve
     */
    private static function getSecpFactory()
    {
        return new SecgCurve(MathAdapterFactory::getAdapter());
    }
}
