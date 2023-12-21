<?php

require_once LIBRARY_PATH.'phpqrcode/qrlib.php';

class QR extends QRcode
{
	public static function paymentRequest(
		$bitcoinAddress,
		$amount,
		$coin = 'bitcoin'
	){
		header("Cache-Control: max-age=2592000"); // 30days
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 30))); // 1 hour
		return QRcode::png(
			strtolower($coin) . ':' . $bitcoinAddress . '?amount=' . $amount,
			FALSE,
			QR_ECLEVEL_L,
			5,
			0,
			FALSE,
			0xFBFBF8,
			0x52987E
		);
	}
}
