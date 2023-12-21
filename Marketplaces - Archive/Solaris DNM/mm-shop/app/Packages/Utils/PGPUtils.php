<?php
/**
 * File: PGPUtils.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\Utils;


use OpenPGP;
use OpenPGP_Crypt_Symmetric;
use OpenPGP_LiteralDataPacket;
use OpenPGP_Message;

class PGPUtils
{
    public static function encrypt($pgpKey, $plainString) {
        $key = OpenPGP_Message::parse(OpenPGP::unarmor($pgpKey, "PGP PUBLIC KEY BLOCK"));
        $data = new OpenPGP_LiteralDataPacket($plainString, ['format' => 'u']);
        $encrypted = OpenPGP_Crypt_Symmetric::encrypt($key, new OpenPGP_Message([$data]));
        return OpenPGP::enarmor($encrypted->to_bytes(), "PGP MESSAGE");
    }
}