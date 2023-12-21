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
use JsonSerializable;

/**
 * Class for building up an authentication request
 *
 * @package u2flib_server
 */
class RegisterResponse implements RegisterResponseInterface, JsonSerializable
{
    /**
     * @var string Registration data
     */
    private $registrationData;

    /** @var string client data */
    private $clientData;

    /** @var string errorCode from the browser */
    private $errorCode;

    /**
     * RegisterResponse constructor.
     * @param string $registrationData
     * @param string $clientData
     * @param int $errorCode
     */
    public function __construct($registrationData, $clientData, $errorCode = 0)
    {
        $this->setRegistrationData($registrationData);
        $this->setClientData($clientData);
        $this->setErrorCode($errorCode);
    }

    /**
     * @return string
     */
    public function getRegistrationData()
    {
        return $this->registrationData;
    }

    /**
     * @param string $registrationData
     * @return RegisterResponseInterface
     */
    public function setRegistrationData($registrationData)
    {
        $this->registrationData = $registrationData;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientData()
    {
        return $this->clientData;
    }

    /**
     * @param string $clientData
     * @return RegisterResponseInterface
     */
    public function setClientData($clientData)
    {
        $this->clientData = $clientData;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     * @return RegisterResponseInterface
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return [
            'registrationData'  => $this->getRegistrationData(),
            'clientData'        => $this->getClientData(),
            'errorCode'         => $this->getErrorCode(),
        ];
    }
}