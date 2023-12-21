<?php

require_once(LIBRARY_PATH . 'ecc-lib/classes/util/gmp_Utils.php');

/**
 * Class BitcoinMessage
 */
 
class BitcoinMessage {
	
	public static function validate_message($address, $signature, $message) {
		$secp256k1 = new CurveFp('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', '0', '7');
		$secp256k1_G = new Point(
			$secp256k1,
			'0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798',
			'0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8',
			'0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141'
		);
	
		// extract parameters
		$address = self::base58check_decode($address);
		if (strlen($address) != 21 || $address[0] != "\x0") {
			return false; // throw new InvalidArgumentException('invalid Bitcoin address');
		}
	
		$signature = base64_decode($signature, true);
		if ($signature === false) {
			return false; // throw new InvalidArgumentException('invalid base64 signature');
		}
	
		if (strlen($signature) != 65) {
			return false; // throw new InvalidArgumentException('invalid signature length');
		}
	
		$recoveryFlags = ord($signature[0]) - 27;
		if ($recoveryFlags < 0 || $recoveryFlags > 7) {
			return false; // throw new InvalidArgumentException('invalid signature type');
		}
		$isCompressed = ($recoveryFlags & 4) != 0;
	
		// hash message, recover key
		$messageHash = hash('sha256', hash('sha256', "\x18Bitcoin Signed Message:\n" . self::numToVarIntString(strlen($message)).$message, true), true);
		$pubkey = self::recoverPubKey(self::bin2gmp(substr($signature, 1, 32)), self::bin2gmp(substr($signature, 33, 32)), self::bin2gmp($messageHash), $recoveryFlags, $secp256k1_G);
		if ($pubkey === false) {
			return false; // throw new InvalidArgumentException('unable to recover key');
		}
		$point = $pubkey->getPoint();
	
		// see that the key we recovered is for the address given
		if (!$isCompressed) {
			$pubBinStr = "\x04" . str_pad(self::gmp2bin($point->getX()), 32, "\x00", STR_PAD_LEFT) .
								  str_pad(self::gmp2bin($point->getY()), 32, "\x00", STR_PAD_LEFT);
		} else {
			$pubBinStr =	(self::isBignumEven($point->getY()) ? "\x02" : "\x03") .
								  str_pad(self::gmp2bin($point->getX()), 32, "\x00", STR_PAD_LEFT);
		}
		$derivedAddress = "\x00". hash('ripemd160', hash('sha256', $pubBinStr, true), true);
	
		return $address === $derivedAddress;
	}
	
	private static function isBignumEven($bnStr) {
		return (((int)$bnStr[strlen($bnStr)-1]) & 1) == 0;
	}
	
	// based on bitcoinjs-lib's implementation
	// and SEC 1: Elliptic Curve Cryptography, section 4.1.6, "Public Key Recovery Operation".
	// http://www.secg.org/download/aid-780/sec1-v2.pdf
	private static function recoverPubKey($r, $s, $e, $recoveryFlags, $G) {
		$isYEven = ($recoveryFlags & 1) != 0;
		$isSecondKey = ($recoveryFlags & 2) != 0;
		$curve = $G->getCurve();
		$signature = new Signature($r, $s);
	
		// Precalculate (p + 1) / 4 where p is the field order
		static $p_over_four; // XXX just assuming only one curve/prime will be used
		if (!$p_over_four) {
			$p_over_four = gmp_div(gmp_add($curve->getPrime(), 1), 4);
		}
	
		// 1.1 Compute x
		if (!$isSecondKey) {
			$x = $r;
		} else {
			$x = gmp_add($r, $G->getOrder());
		}
	
		// 1.3 Convert x to point
		$alpha = gmp_mod(gmp_add(gmp_add(gmp_pow($x, 3), gmp_mul($curve->getA(), $x)), $curve->getB()), $curve->getPrime());
		$beta = NumberTheory::modular_exp($alpha, $p_over_four, $curve->getPrime());
	
		// If beta is even, but y isn't or vice versa, then convert it,
		// otherwise we're done and y == beta.
		if (self::isBignumEven($beta) == $isYEven) {
			$y = gmp_sub($curve->getPrime(), $beta);
		} else {
			$y = $beta;
		}
	
		// 1.4 Check that nR is at infinity (implicitly done in construtor)
		$R = new Point($curve, $x, $y, $G->getOrder());
	
		$point_negate = function($p) { return new Point($p->curve, $p->x, gmp_neg($p->y), $p->order); };
	
		// 1.6.1 Compute a candidate public key Q = r^-1 (sR - eG)
		$rInv = NumberTheory::inverse_mod($r, $G->getOrder());
		$eGNeg = $point_negate(Point::mul($e, $G));
		$Q = Point::mul($rInv, Point::add(Point::mul($s, $R), $eGNeg));
	
		// 1.6.2 Test Q as a public key
		$Qk = new PublicKey($G, $Q);
		if ($Qk->verifies($e, $signature)) {
			return $Qk;
		}
	
		return false;
	}
	
	private static function base58check_decode($str) {
		// strtr thanks to https://github.com/prusnak/addrgen/blob/master/php/addrgen.php
		// ltrim because leading zeroes can mess up the parsing even if you specify the base..
		$v = gmp_init(ltrim(strtr($str,
			'123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz',
			'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv'), '0'), 58);
		$v = self::gmp2bin($v);
		// for each leading 1, pre-pad the byte array with a 0
		for ($i = 0; $i < strlen($str); $i++) {
			if ($str[$i] != '1') {
				break;
			}
			$v = "\x00" . $v;
		}
	
		$checksum = substr($v, -4);
		$v = substr($v, 0, -4);
	
		$expCheckSum = substr(hash('sha256', hash('sha256', $v, true), true), 0, 4);
	
		if ($expCheckSum != $checksum) {
			return false; // throw new InvalidArgumentException('invalid checksum');
		}
	
		return $v;
	}
	
	private static function numToVarIntString($i) {
		if ($i < 0xfd) {
			return chr($i);
		} else if ($i <= 0xffff) {
			return pack('Cv', 0xfd, $i);
		} else if ($i <= 0xffffffff) {
			return pack('CV', 0xfe, $i);
		} else {
			return false; // throw new InvalidArgumentException('int too large');
		}
	}
	
	private static function bin2gmp($binStr) {
		$v = gmp_init('0');
	
		for ($i = 0; $i < strlen($binStr); $i++) {
			$v = gmp_add(gmp_mul($v, 256), ord($binStr[$i]));
		}
	
		return $v;
	}
	
	private static function gmp2bin($v) {
		$binStr = '';
	
		while (gmp_cmp($v, 0) > 0) {
			list($v, $r) = gmp_div_qr($v, 256);
			$binStr = chr(gmp_intval($r)) . $binStr;
		}
	
		return $binStr;
	}
	
}