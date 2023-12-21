<?php

namespace Mdanter\Ecc\Serializer\Util;

use FG\ASN1\Universal\ObjectIdentifier;
use Mdanter\Ecc\Curves\NamedCurveFp;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\NistCurve;
use Mdanter\Ecc\Curves\SecgCurve;
use Mdanter\Ecc\Primitives\CurveFpInterface;

class CurveOidMapper
{

    const NIST_P192_OID = '1.2.840.10045.3.1.1';

    const NIST_P224_OID = '1.3.132.0.33';

    const NIST_P256_OID = '1.2.840.10045.3.1.7';

    const NIST_P384_OID = '1.3.132.0.34';

    const NIST_P521_OID = '1.3.132.0.35';

    const SECP_256K1_OID = '1.3.132.0.10';

    const SECP_256R1_OID = '1.2.840.10045.3.1.7';

    const SECP_384R1_OID = '1.3.132.0.34';

    /**
     * @var array
     */
    private static $oidMap = array(
        NistCurve::NAME_P192 => self::NIST_P192_OID,
        NistCurve::NAME_P224 => self::NIST_P224_OID,
        NistCurve::NAME_P256 => self::NIST_P256_OID,
        NistCurve::NAME_P384 => self::NIST_P384_OID,
        NistCurve::NAME_P521 => self::NIST_P521_OID,
        SecgCurve::NAME_SECP_256K1 => self::SECP_256K1_OID,
        SecgCurve::NAME_SECP_384R1 => self::SECP_384R1_OID,
    );

    /**
     * @var array
     */
    private static $sizeMap = array(
        NistCurve::NAME_P192 => 12,
        NistCurve::NAME_P224 => 28,
        NistCurve::NAME_P256 => 32,
        NistCurve::NAME_P384 => 48,
        NistCurve::NAME_P521 => 66,
        SecgCurve::NAME_SECP_256K1 => 28,
        SecgCurve::NAME_SECP_384R1 => 48,
    );

    /**
     * @return array
     */
    public static function getNames()
    {
        return array_keys(self::$oidMap);
    }

    /**
     * @param CurveFpInterface $curve
     * @return mixed
     */
    public static function getByteSize(CurveFpInterface $curve)
    {
        if ($curve instanceof NamedCurveFp && array_key_exists($curve->getName(), self::$sizeMap)) {
            return self::$sizeMap[$curve->getName()];
        }

        throw new \RuntimeException('Unsupported curve type.');
    }

    /**
     * @param NamedCurveFp $curve
     * @return ObjectIdentifier
     */
    public static function getCurveOid(NamedCurveFp $curve)
    {
        if (array_key_exists($curve->getName(), self::$oidMap)) {
            $oidString = self::$oidMap[$curve->getName()];

            return new ObjectIdentifier($oidString);
        }

        throw new \RuntimeException('Unsupported curve type.');
    }

    /**
     * @param ObjectIdentifier $oid
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public static function getCurveFromOid(ObjectIdentifier $oid)
    {
        $oidString = $oid->getContent();
        $invertedMap = array_flip(self::$oidMap);

        if (array_key_exists($oidString, $invertedMap)) {
            return CurveFactory::getGeneratorByName($invertedMap[$oidString]);
        }

        throw new \RuntimeException('Invalid data: unsupported curve.');
    }

    /**
     * @param ObjectIdentifier $oid
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public static function getGeneratorFromOid(ObjectIdentifier $oid)
    {
        $oidString = $oid->getContent();
        $invertedMap = array_flip(self::$oidMap);

        if (array_key_exists($oidString, $invertedMap)) {
            return CurveFactory::getGeneratorByName($invertedMap[$oidString]);
        }

        throw new \RuntimeException('Invalid data: unsupported generator.');
    }
}
