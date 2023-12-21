<?php

require_once LIBRARY_PATH.'openpgp/lib/openpgp.php';
require_once LIBRARY_PATH.'openpgp/lib/openpgp_crypt_rsa.php';
require_once LIBRARY_PATH.'openpgp/lib/openpgp_crypt_symmetric.php';

class PGP
{
	function __construct($keyASCII){
		$this->public_key = OpenPGP_Message::parse(OpenPGP::unarmor($keyASCII));
	}
	
	public function qEncrypt(
		$message,
		$wrap = true,
		$headers = []
	){
		$data = new OpenPGP_LiteralDataPacket($message);
		$encrypted = OpenPGP_Crypt_Symmetric::encrypt($this->public_key, new OpenPGP_Message(array($data)));
		
		//header("Content-type: text/plain");
		//header("Content-Disposition: attachment; filename=ducks.gpg");
		//echo $encrypted->to_bytes();
		$encrypted_message = OpenPGP::enarmor(
			$encrypted->to_bytes(),
			'PGP Message',
			$headers
		);
		return $wrap ? wordwrap($encrypted_message, 75, PHP_EOL, true) : $encrypted_message;
	}
}
