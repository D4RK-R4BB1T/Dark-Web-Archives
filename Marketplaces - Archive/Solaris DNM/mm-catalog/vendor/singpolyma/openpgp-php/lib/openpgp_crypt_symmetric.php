<?php

use phpseclib\Crypt\AES as Crypt_AES;
use phpseclib\Crypt\Blowfish as Crypt_Blowfish;
use phpseclib\Crypt\TripleDES as Crypt_TripleDES;
use phpseclib\Crypt\Twofish as Crypt_Twofish;
use phpseclib\Crypt\Random;

require_once dirname(__FILE__).'/openpgp.php';
@include_once dirname(__FILE__).'/openpgp_crypt_rsa.php';
@include_once dirname(__FILE__).'/openpgp_mcrypt_wrapper.php';
@include_once dirname(__FILE__).'/openpgp_openssl_wrapper.php';

class OpenPGP_Crypt_Symmetric {
  public static function encrypt($passphrases_and_keys, $message, $symmetric_algorithm=9) {
    list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($symmetric_algorithm);
    if(!$cipher) throw new Exception("Unsupported cipher");
    $prefix = Random::string($key_block_bytes);
    $prefix .= substr($prefix, -2);

    $key = Random::string($key_bytes);
    $cipher->setKey($key);

    $to_encrypt = $prefix . $message->to_bytes();
    $mdc = new OpenPGP_ModificationDetectionCodePacket(hash('sha1', $to_encrypt . "\xD3\x14", true));
    $to_encrypt .= $mdc->to_bytes();
    $encrypted = array(new OpenPGP_IntegrityProtectedDataPacket($cipher->encrypt($to_encrypt)));

    if(!is_array($passphrases_and_keys) && !($passphrases_and_keys instanceof IteratorAggregate)) {
      $passphrases_and_keys = (array)$passphrases_and_keys;
    }

    foreach($passphrases_and_keys as $pass) {
      if($pass instanceof OpenPGP_PublicKeyPacket) {
        if(!in_array($pass->algorithm, array(1,2,3))) throw new Exception("Only RSA keys are supported.");
        $crypt_rsa = new OpenPGP_Crypt_RSA($pass);
        $rsa = $crypt_rsa->public_key();
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $esk = $rsa->encrypt(chr($symmetric_algorithm) . $key . pack('n', self::checksum($key)));
        $esk = pack('n', OpenPGP::bitlength($esk)) . $esk;
        array_unshift($encrypted, new OpenPGP_AsymmetricSessionKeyPacket($pass->algorithm, $pass->fingerprint(), $esk));
      } else if(is_string($pass)) {
        $s2k = new OpenPGP_S2K(Random::string(8));
        $cipher->setKey($s2k->make_key($pass, $key_bytes));
        $esk = $cipher->encrypt(chr($symmetric_algorithm) . $key);
        array_unshift($encrypted, new OpenPGP_SymmetricSessionKeyPacket($s2k, $esk, $symmetric_algorithm));
      }
    }

    return new OpenPGP_Message($encrypted);
  }

  public static function decryptSymmetric($pass, $m) {
    $epacket = self::getEncryptedData($m);

    foreach($m as $p) {
      if($p instanceof OpenPGP_SymmetricSessionKeyPacket) {
        if(strlen($p->encrypted_data) > 0) {
          list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($p->symmetric_algorithm);
          if(!$cipher) continue;
          $cipher->setKey($p->s2k->make_key($pass, $key_bytes));

          $padAmount = $key_block_bytes - (strlen($p->encrypted_data) % $key_block_bytes);
          $data = substr($cipher->decrypt($p->encrypted_data . str_repeat("\0", $padAmount)), 0, strlen($p->encrypted_data));
          $decrypted = self::decryptPacket($epacket, ord($data[0]), substr($data, 1));
        } else {
          list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($p->symmetric_algorithm);
          $decrypted = self::decryptPacket($epacket, $p->symmetric_algorithm, $p->s2k->make_key($pass, $key_bytes));
        }

        if($decrypted) return $decrypted;
      }
    }

    return NULL; /* If we get here, we failed */
  }

  public static function encryptSecretKey($pass, $packet, $symmetric_algorithm=9) {
    $packet = clone $packet; // Do not mutate original
    $packet->s2k_useage = 254;
    $packet->symmetric_algorithm = $symmetric_algorithm;

    list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($packet->symmetric_algorithm);
    if(!$cipher) throw new Exception("Unsupported cipher");

    $material = '';
    foreach(OpenPGP_SecretKeyPacket::$secret_key_fields[$packet->algorithm] as $field) {
      $f = $packet->key[$field];
      $material .= pack('n', OpenPGP::bitlength($f)) . $f;
      unset($packet->key[$field]);
    }
    $material .= hash('sha1', $material, true);

    $iv = Random::string($key_block_bytes);
    if(!$packet->s2k) $packet->s2k = new OpenPGP_S2K(Random::string(8));
    $cipher->setKey($packet->s2k->make_key($pass, $key_bytes));
    $cipher->setIV($iv);
    $packet->encrypted_data = $iv . $cipher->encrypt($material);

    return $packet;
  }

  public static function decryptSecretKey($pass, $packet) {
    $packet = clone $packet; // Do not mutate orinigal

    list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($packet->symmetric_algorithm);
    if(!$cipher) throw new Exception("Unsupported cipher");
    $cipher->setKey($packet->s2k->make_key($pass, $key_bytes));
    $cipher->setIV(substr($packet->encrypted_data, 0, $key_block_bytes));
    $material = $cipher->decrypt(substr($packet->encrypted_data, $key_block_bytes));

    if($packet->s2k_useage == 254) {
      $chk = substr($material, -20);
      $material = substr($material, 0, -20);
      if($chk != hash('sha1', $material, true)) return NULL;
    } else {
      $chk = unpack('n', substr($material, -2));
      $chk = reset($chk);
      $material = substr($material, 0, -2);

      $mkChk = self::checksum($material);
      if($chk != $mkChk) return NULL;
    }

    $packet->s2k = NULL;
    $packet->s2k_useage = 0;
    $packet->symmetric_algorithm = 0;
    $packet->encrypted_data = NULL;
    $packet->input = $material;
    $packet->key_from_input();
    unset($packet->input);
    return $packet;
  }

  public static function decryptPacket($epacket, $symmetric_algorithm, $key) {
    list($cipher, $key_bytes, $key_block_bytes) = self::getCipher($symmetric_algorithm);
    if(!$cipher) return NULL;
    $cipher->setKey($key);

    if($epacket instanceof OpenPGP_IntegrityProtectedDataPacket) {
      $padAmount = $key_block_bytes - (strlen($epacket->data) % $key_block_bytes);
      $data = substr($cipher->decrypt($epacket->data . str_repeat("\0", $padAmount)), 0, strlen($epacket->data));
      $prefix = substr($data, 0, $key_block_bytes + 2);
      $mdc = substr(substr($data, -22, 22), 2);
      $data = substr($data, $key_block_bytes + 2, -22);

      $mkMDC = hash("sha1", $prefix . $data . "\xD3\x14", true);
      if($mkMDC !== $mdc) return false;

      try {
        $msg = OpenPGP_Message::parse($data);
      } catch (Exception $ex) { $msg = NULL; }
      if($msg) return $msg; /* Otherwise keep trying */
    } else {
      // No MDC mean decrypt with resync
      $iv = substr($epacket->data, 2, $key_block_bytes);
      $edata = substr($epacket->data, $key_block_bytes + 2);
      $padAmount = $key_block_bytes - (strlen($edata) % $key_block_bytes);

      $cipher->setIV($iv);
      $data = substr($cipher->decrypt($edata . str_repeat("\0", $padAmount)), 0, strlen($edata));

      try {
        $msg = OpenPGP_Message::parse($data);
      } catch (Exception $ex) { $msg = NULL; }
      if($msg) return $msg; /* Otherwise keep trying */
    }

    return NULL; /* Failed */
  }

  public static function getCipher($algo) {
    $cipher = NULL;
    switch($algo) {
    case NULL:
      case 0:
        throw new Exception("Data is already unencrypted");
      case 2:
        $cipher = new Crypt_TripleDES(Crypt_TripleDES::MODE_CFB);
        $key_bytes = 24;
        $key_block_bytes = 8;
        break;
      case 3:
        if(class_exists('OpenSSLWrapper')) {
          $cipher = new OpenSSLWrapper("CAST5-CFB");
        } else if(defined('MCRYPT_CAST_128')) {
          $cipher = new MCryptWrapper(MCRYPT_CAST_128);
        }
        break;
      case 4:
        $cipher = new Crypt_Blowfish(Crypt_Blowfish::MODE_CFB);
        $key_bytes = 16;
        $key_block_bytes = 8;
        break;
      case 7:
        $cipher = new Crypt_AES(Crypt_AES::MODE_CFB);
        $cipher->setKeyLength(128);
        break;
      case 8:
        $cipher = new Crypt_AES(Crypt_AES::MODE_CFB);
        $cipher->setKeyLength(192);
        break;
      case 9:
        $cipher = new Crypt_AES(Crypt_AES::MODE_CFB);
        $cipher->setKeyLength(256);
        break;
      case 10:
        $cipher = new Crypt_Twofish(Crypt_Twofish::MODE_CFB);
        if(method_exists($cipher, 'setKeyLength')) {
          $cipher->setKeyLength(256);
        } else {
          $cipher = NULL;
        }
        break;
    }
    if(!$cipher) return array(NULL, NULL, NULL); // Unsupported cipher
    if(!isset($key_bytes)) $key_bytes = isset($cipher->key_size)?$cipher->key_size:$cipher->key_length;
    if(!isset($key_block_bytes)) $key_block_bytes = $cipher->block_size;
    return array($cipher, $key_bytes, $key_block_bytes);
  }

  public static function getEncryptedData($m) {
    foreach($m as $p) {
      if($p instanceof OpenPGP_EncryptedDataPacket) return $p;
    }
    throw new Exception("Can only decrypt EncryptedDataPacket");
  }

  public static function checksum($s) {
    $mkChk = 0;
    for($i = 0; $i < strlen($s); $i++) {
      $mkChk = ($mkChk + ord($s[$i])) % 65536;
    }
    return $mkChk;
  }
}
