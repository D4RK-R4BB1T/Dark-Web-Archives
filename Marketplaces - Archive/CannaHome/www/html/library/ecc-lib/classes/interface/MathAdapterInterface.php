<?php

namespace Mdanter\Ecc\Math;

use Mdanter\Ecc\Primitives\CurveFpInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;

interface MathAdapterInterface
{
    /**
     * Compares two numbers
     *
     * @param  int|string $first
     * @param  int|string $other
     * @return int        less than 0 if first is less than second, 0 if equal, greater than 0 if greater than.
     */
    public function cmp($first, $other);

    /**
     * Returns the remainder of a division
     *
     * @param  int|string $number
     * @param  int|string $modulus
     * @return int|string
     */
    public function mod($number, $modulus);

    /**
     * Adds two numbers
     *
     * @param  int|string $augend
     * @param  int|string $addend
     * @return int|string
     */
    public function add($augend, $addend);

    /**
     * Substract one number from another
     *
     * @param  int|string $minuend
     * @param  int|string $subtrahend
     * @return int|string
     */
    public function sub($minuend, $subtrahend);

    /**
     * Multiplies a number by another.
     *
     * @param  int|string $multiplier
     * @param  int|string $multiplicand
     * @return int|string
     */
    public function mul($multiplier, $multiplicand);

    /**
     * Divides a number by another.
     *
     * @param  int|string $dividend
     * @param  int|string $divisor
     * @return int|string
     */
    public function div($dividend, $divisor);

    /**
     * Raises a number to a power.
     *
     * @param  int|string $base     The number to raise.
     * @param  int|string $exponent The power to raise the number to.
     * @return int|string
     */
    public function pow($base, $exponent);

    /**
     * Performs a logical AND between two values.
     *
     * @param  int|string $first
     * @param  int|string $other
     * @return int|string
     */
    public function bitwiseAnd($first, $other);

    /**
     * Performs a logical XOR between two values.
     *
     * @param  int|string $first
     * @param  int|string $other
     * @return int|string
     */
    public function bitwiseXor($first, $other);

    /**
     * Shifts bits to the right
     * @param int|string $number    Number to shift
     * @param int|string $positions Number of positions to shift
     */
    public function rightShift($number, $positions);

    /**
     * Shifts bits to the left
     * @param int|string $number    Number to shift
     * @param int|string $positions Number of positions to shift
     */
    public function leftShift($number, $positions);

    /**
     * Returns the string representation of a returned value.
     *
     * @param int|string $value
     */
    public function toString($value);

    /**
     * Converts an hexadecimal string to decimal.
     *
     * @param  string $hexString
     * @return int|string
     */
    public function hexDec($hexString);

    /**
     * Converts a decimal string to hexadecimal.
     *
     * @param  int|string $decString
     * @return int|string
     */
    public function decHex($decString);

    /**
     * Calculates the modular exponent of a number.
     *
     * @param int|string $base
     * @param int|string $exponent
     * @param int|string $modulus
     */
    public function powmod($base, $exponent, $modulus);

    /**
     * Checks whether a number is a prime.
     *
     * @param  int|string $n
     * @return boolean
     */
    public function isPrime($n);

    /**
     * Gets the next known prime that is greater than a given prime.
     *
     * @param  int|string $currentPrime
     * @return int|string
     */
    public function nextPrime($currentPrime);

    /**
     *
     * @param int|string $a
     * @param int|string $m
     */
    public function inverseMod($a, $m);

    /**
     *
     * @param int|string $a
     * @param int|string $p
     */
    public function jacobi($a, $p);

    /**
     * @param  int|string $x
     * @return string|null
     */
    public function intToString($x);

    /**
     *
     * @param  int|string $s
     * @return int|string
     */
    public function stringToInt($s);

    /**
     *
     * @param  int|string $m
     * @return int|string
     */
    public function digestInteger($m);

    /**
     *
     * @param  int|string $a
     * @param  int|string $m
     * @return int|string
     */
    public function gcd2($a, $m);

    /**
     * @param $value
     * @param $fromBase
     * @param $toBase
     * @return int|string
     */
    public function baseConvert($value, $fromBase, $toBase);

    /**
     * @return NumberTheory
     */
    public function getNumberTheory();

    /**
     * @return ModularArithmetic
     */
    public function getPrimeFieldArithmetic(CurveFpInterface $curve);

    /**
     * @param $modulus
     * @return ModularArithmetic
     */
    public function getModularArithmetic($modulus);

    /**
     * @return EcMath
     */
    public function getEcMath(GeneratorPoint $generatorPoint, $input);
}
