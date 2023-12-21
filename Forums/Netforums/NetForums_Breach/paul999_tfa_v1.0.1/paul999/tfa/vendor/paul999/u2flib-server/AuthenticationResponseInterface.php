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


/**
 * Class for building up an authentication request
 *
 * @package u2flib_server
 */
interface AuthenticationResponseInterface
{
    /**
     * @return string
     */
    public function getSignatureData();

    /**
     * @param string $signatureData
     * @return AuthenticationResponseInterface
     */
    public function setSignatureData($signatureData);

    /**
     * @return string
     */
    public function getClientData();

    /**
     * @param string $clientData
     * @return AuthenticationResponseInterface
     */
    public function setClientData($clientData);

    /**
     * @return string
     */
    public function getKeyHandle();

    /**
     * @param string $keyHandle
     * @return AuthenticationResponseInterface
     */
    public function setKeyHandle($keyHandle);

    /**
     * @return string
     */
    public function getErrorCode();

    /**
     * @param string $errorCode
     * @return AuthenticationResponseInterface
     */
    public function setErrorCode($errorCode);
}