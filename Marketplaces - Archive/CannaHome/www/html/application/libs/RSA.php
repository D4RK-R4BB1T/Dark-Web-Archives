<?php

require_once('Crypt_RSA.php');

class RSA
{
	private $private_key = false;
	
	function __construct($private_key = false){
		$this->private_key = $private_key ? $private_key : Session::get('private_key');
	}
	
	function qEncrypt($content, $public_key = false){
		$rsa = new Crypt_RSA();
		$rsa->setHash('sha256');
		$rsa->setMGFHash('sha256');
		if(!$public_key && $this->private_key){
			$rsa->loadKey($this->private_key);
			$public_key = $rsa->getPublicKey();
		} elseif(!$this->private_key) {
			return false; // No public key
		}
		$rsa->loadKey($public_key);
		return $rsa->encrypt($content);
	}
	
	function qDecrypt($ciphertext){
		if($this->private_key){
			$rsa = new Crypt_RSA();
			$rsa->setHash('sha256');
			$rsa->setMGFHash('sha256');
			$rsa->loadKey($this->private_key);
			return $rsa->decrypt($ciphertext);
		} else {
			return false;
		}
	}
	
	function qSign($content){
		if($this->private_key){
			$rsa = new Crypt_RSA();
			$rsa->setHash('sha256');
			$rsa->setMGFHash('sha256');
			$rsa->loadKey($this->private_key);
			return $rsa->sign($content);
		} else {
			return false;
		}
	}
	
	function qVerify($content, $signature, $public_key = false){
		$rsa = new Crypt_RSA();
		$rsa->setHash('sha256');
		$rsa->setMGFHash('sha256');
		if(!$public_key && $this->private_key){
			$rsa->loadKey($this->private_key);
			$public_key = $rsa->getPublicKey();
		} elseif(!$this->private_key) {
			return false; // No public key
		}
		$rsa->loadKey($public_key);
		return $rsa->verify($content, $signature);
	}
}