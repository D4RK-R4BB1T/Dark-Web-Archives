<?php
/**
 *
 * @copyright (c) 2015, Paul Sohier
 * @copyright (c) 2014 Yubico AB
 * @license BSD-2-Clause
 *
 *
 * Orignal Copyright:
 * Copyright (c) 2014 Yubico AB
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above
 *     copyright notice, this list of conditions and the following
 *     disclaimer in the documentation and/or other materials provided
 *     with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace paul999\u2f;

/** Constant for the version of the u2f protocol */
use paul999\u2f\Exceptions\U2fError;


class U2F implements U2F_interface
{
    /** @var string */
    private $appId;

    /** @var null|string */
    private $attestDir;

    /** @internal */
    private $FIXCERTS = array(
        '349bca1031f8c82c4ceca38b9cebf1a69df9fb3b94eed99eb3fb9aa3822d26e8',
        'dd574527df608e47ae45fbba75a2afdd5c20fd94a02419381813cd55a2a3398f',
        '1d8764f0f7cd1352df6150045c8f638e517270e8b5dda1c63ade9c2280240cae',
        'd0edc9a91a1677435a953390865d208c55b3183c6759c9b5a7ff494c322558eb',
        '6073c436dcd064a48127ddbf6032ac1a66fd59a0c24434f070d4e564c124c897',
        'ca993121846c464d666096d35f13bf44c1b05af205f9b4a1e00cf6cc10c5e511'
    );

    /**
     * @param string $appId Application id for the running application
     * @param string|null $attestDir Directory where trusted attestation roots may be found
     * @throws U2fError If OpenSSL older than 1.0.0 is used
     */
    public function __construct($appId, $attestDir = null)
    {
        if (OPENSSL_VERSION_NUMBER < 0x10000000) {
            throw new U2fError('OpenSSL has to be at least version 1.0.0, this is ' . OPENSSL_VERSION_TEXT, U2fError::ERR_OLD_OPENSSL);
        }
        $this->appId = $appId;
        $this->attestDir = $attestDir;
    }

    public function getRegisterData(array $registrations = array())
    {
        $challenge = $this->createChallenge();
        $request = new RegisterRequest($challenge, $this->appId);
        $signs = $this->getAuthenticateData($registrations);
        return array($request, $signs);
    }

    public function doRegister(RegisterRequestInterface $request, RegisterResponseInterface $response, $includeCert = true)
    {
        if ($response->getErrorCode() !== null && $response->getErrorCode() !== 0) {
            throw new U2fError('User-agent returned error. Error code: ' . $response->getErrorCode(), U2fError::ERR_BAD_UA_RETURNING);
        }

        if (!is_bool($includeCert)) {
            throw new \InvalidArgumentException('$include_cert of doRegister() method only accepts boolean.');
        }

        $rawReg = $this->base64u_decode($response->getRegistrationData());
        $regData = array_values(unpack('C*', $rawReg));
        $clientData = $this->base64u_decode($response->getClientData());
        $cli = json_decode($clientData);

        if ($cli->challenge !== $request->getChallenge()) {
            throw new U2fError('Registration challenge does not match', U2fError::ERR_UNMATCHED_CHALLENGE);
        }

        $registration = new Registration();
        $offs = 1;
        $pubKey = substr($rawReg, $offs, self::PUBKEY_LEN);
        $offs += self::PUBKEY_LEN;
        // decode the pubKey to make sure it's good
        $tmpKey = $this->pubkey_to_pem($pubKey);
        if ($tmpKey === null) {
            throw new U2fError('Decoding of public key failed', U2fError::ERR_PUBKEY_DECODE);
        }
        $registration->setPublicKey(base64_encode($pubKey));
        $khLen = $regData[$offs++];
        $kh = substr($rawReg, $offs, $khLen);
        $offs += $khLen;
        $registration->setKeyHandle($this->base64u_encode($kh));

        // length of certificate is stored in byte 3 and 4 (excluding the first 4 bytes)
        $certLen = 4;
        $certLen += ($regData[$offs + 2] << 8);
        $certLen += $regData[$offs + 3];

        $rawCert = $this->fixSignatureUnusedBits(substr($rawReg, $offs, $certLen));
        $offs += $certLen;
        $pemCert = "-----BEGIN CERTIFICATE-----\r\n";
        $pemCert .= chunk_split(base64_encode($rawCert), 64);
        $pemCert .= "-----END CERTIFICATE-----";
        if ($includeCert) {
            $registration->setCertificate(base64_encode($rawCert));
        }
        if ($this->attestDir !== null) {
            if (openssl_x509_checkpurpose($pemCert, -1, $this->get_certs()) !== true) {
                throw new U2fError('Attestation certificate can not be validated', U2fError::ERR_ATTESTATION_VERIFICATION);
            }
        }

        if (!openssl_pkey_get_public($pemCert)) {
            throw new U2fError('Decoding of public key failed', U2fError::ERR_PUBKEY_DECODE);
        }
        $signature = substr($rawReg, $offs);

        $dataToVerify = chr(0);
        $dataToVerify .= hash('sha256', $request->getAppId(), true);
        $dataToVerify .= hash('sha256', $clientData, true);
        $dataToVerify .= $kh;
        $dataToVerify .= $pubKey;

        if (openssl_verify($dataToVerify, $signature, $pemCert, 'sha256') === 1) {
            return $registration;
        } else {
            throw new U2fError('Attestation signature does not match', U2fError::ERR_ATTESTATION_SIGNATURE);
        }
    }

    public function getAuthenticateData(array $registrations)
    {
        $sigs = array();
        foreach ($registrations as $reg) {
            if (!($reg instanceof RegistrationInterface)) {
                throw new \InvalidArgumentException('$registrations of getAuthenticateData() method only accepts array of object.');
            }
            $sigs[] = new SignRequest($this->createChallenge(), $reg->getKeyHandle(), $this->appId);
        }
        return $sigs;
    }

    public function doAuthenticate(array $requests, array $registrations, AuthenticationResponseInterface $response)
    {
        if ($response->getErrorCode() != null) {
            throw new U2fError('User-agent returned error. Error code: ' . $response->getErrorCode(), U2fError::ERR_BAD_UA_RETURNING);
        }

        $clientData = $this->base64u_decode($response->getClientData());
        $decodedClient = json_decode($clientData);
        /**
         * @var SignRequestInterface $req
         */
        foreach ($requests as $row) {
            if (!($row instanceof SignRequestInterface)) {
                throw new \InvalidArgumentException('$requests of doAuthenticate() method only accepts array of SignRequest.');
            }

            if ($row->getKeyHandle() === $response->getKeyHandle() && $row->getChallenge() === $decodedClient->challenge) {
                $req = $row;
                break;
            }
        }
        if (!isset($req)) {
            throw new U2fError('No matching request found', U2fError::ERR_NO_MATCHING_REQUEST);
        }
        /**
         * @var RegistrationInterface reg
         */
        foreach ($registrations as $row) {
            if (!($row instanceof RegistrationInterface)) {
                throw new \InvalidArgumentException('$registrations of doAuthenticate() method only accepts array of Registration.');
            }

            if ($row->getKeyHandle() === $response->getKeyHandle()) {
                $reg = $row;
                break;
            }
        }
        if (!isset($reg)) {
            throw new U2fError('No matching registration found', U2fError::ERR_NO_MATCHING_REGISTRATION);
        }
        $pemKey = $this->pubkey_to_pem($this->base64u_decode($reg->getPublicKey()));
        if ($pemKey === null) {
            throw new U2fError('Decoding of public key failed', U2fError::ERR_PUBKEY_DECODE);
        }

        $signData = $this->base64u_decode($response->getSignatureData());
        $dataToVerify = hash('sha256', $req->getAppId(), true);
        $dataToVerify .= substr($signData, 0, 5);
        $dataToVerify .= hash('sha256', $clientData, true);
        $signature = substr($signData, 5);

        if (openssl_verify($dataToVerify, $signature, $pemKey, 'sha256') === 1) {
            $ctr = unpack("Nctr", substr($signData, 1, 4));
            $counter = $ctr['ctr'];
            /* TODO: wrap-around should be handled somehow.. */
            if ($counter > $reg->getCounter()) {
                $reg->setCounter($counter);
                return $reg;
            } else {
                throw new U2fError('Counter too low.', U2fError::ERR_COUNTER_TOO_LOW);
            }
        } else {
            throw new U2fError('Authentication failed', U2fError::ERR_AUTHENTICATION_FAILURE);
        }
    }

    /**
     * @return array
     */
    private function get_certs()
    {
        $files = array();
        if ($this->attestDir !== null && $handle = opendir($this->attestDir)) {
            while (false !== ($entry = @readdir($handle))) {
                if (is_file("{$this->attestDir}/$entry")) {
                    $files[] = "{$this->attestDir}/$entry";
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64u_encode($data)
    {
        return trim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64u_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param string $key
     * @return null|string
     */
    private function pubkey_to_pem($key)
    {
        if (strlen($key) !== self::PUBKEY_LEN || $key[0] !== "\x04") {
            return null;
        }

        /*
         * Convert the public key to binary DER format first
         * Using the ECC SubjectPublicKeyInfo OIDs from RFC 5480
         *
         *  SEQUENCE(2 elem)                        30 59
         *   SEQUENCE(2 elem)                       30 13
         *    OID1.2.840.10045.2.1 (id-ecPublicKey) 06 07 2a 86 48 ce 3d 02 01
         *    OID1.2.840.10045.3.1.7 (secp256r1)    06 08 2a 86 48 ce 3d 03 01 07
         *   BIT STRING(520 bit)                    03 42 ..key..
         */
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
        $der .= "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42";
        $der .= "\0" . $key;

        $pem = "-----BEGIN PUBLIC KEY-----\r\n";
        $pem .= chunk_split(base64_encode($der), 64);
        $pem .= "-----END PUBLIC KEY-----";

        return $pem;
    }

    /**
     * @return string
     * @throws U2fError
     */
    private function createChallenge()
    {
        $challenge = openssl_random_pseudo_bytes(32, $crypto_strong);
        if ($crypto_strong !== true) {
            throw new U2fError('Unable to obtain a good source of randomness', U2fError::ERR_BAD_RANDOM);
        }

        $challenge = $this->base64u_encode($challenge);

        return $challenge;
    }

    /**
     * Fixes a certificate where the signature contains unused bits.
     *
     * @param string $cert
     * @return string
     */
    private function fixSignatureUnusedBits($cert)
    {
        if (in_array(hash('sha256', $cert), $this->FIXCERTS)) {
            $cert[strlen($cert) - 257] = "\0";
        }
        return $cert;
    }
}
