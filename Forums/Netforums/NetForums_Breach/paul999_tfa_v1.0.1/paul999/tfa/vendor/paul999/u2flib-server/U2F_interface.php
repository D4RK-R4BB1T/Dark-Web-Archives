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

use paul999\u2f\Exceptions\U2fError;

interface U2F_interface
{
    /**
     * Called to get a registration request to send to a user.
     * Returns an array of one registration request and a array of sign requests.
     *
     * @param array $registrations List of current registrations for this
     * user, to prevent the user from registering the same authenticator several
     * times.
     * @return array An array of two elements, the first containing a
     * RegisterRequest the second being an array of SignRequest
     * @throws U2fError
     */
    public function getRegisterData(array $registrations = array());

    /**
     * Called to verify and unpack a registration message.
     *
     * @param RegisterRequestInterface $request this is a reply to
     * @param RegisterResponseInterface $response response from a user
     * @param bool $includeCert set to true if the attestation certificate should be
     * included in the returned Registration object
     * @return RegistrationInterface
     * @throws U2fError
     */
    public function doRegister(RegisterRequestInterface $request, RegisterResponseInterface $response, $includeCert = true);

    /**
     * Called to get an authentication request.
     *
     * @param array $registrations An array of the registrations to create authentication requests for.
     * @return array An array of SignRequest
     * @throws U2fError
     */
    public function getAuthenticateData(array $registrations);

    /**
     * Called to verify an authentication response
     *
     * @param array $requests An array of outstanding authentication requests
     * @param array $registrations An array of current registrations
     * @param AuthenticationResponseInterface $response A response from the authenticator
     * @return RegistrationInterface
     * @throws U2fError
     *
     * The Registration object returned on success contains an updated counter
     * that should be saved for future authentications.
     * If the Error returned is ERR_COUNTER_TOO_LOW this is an indication of
     * token cloning or similar and appropriate action should be taken.
     */
    public function doAuthenticate(array $requests, array $registrations, AuthenticationResponseInterface $response);

    const U2F_VERSION = "U2F_V2";

    /**
     * Public key Length
     */
    const PUBKEY_LEN = 65;
}