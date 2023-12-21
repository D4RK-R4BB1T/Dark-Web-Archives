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

namespace paul999\u2f\Exceptions;

/**
 * Error class, returned on errors
 *
 * @package u2flib_server
 */
class U2fError extends \Exception
{
    /** Error for the authentication message not matching any outstanding
     * authentication request */
    const ERR_NO_MATCHING_REQUEST = 1;

    /** Error for the authentication message not matching any registration */
    const ERR_NO_MATCHING_REGISTRATION = 2;

    /** Error for the signature on the authentication message not verifying with
     * the correct key */
    const ERR_AUTHENTICATION_FAILURE = 3;

    /** Error for the challenge in the registration message not matching the
     * registration challenge */
    const ERR_UNMATCHED_CHALLENGE = 4;

    /** Error for the attestation signature on the registration message not
     * verifying */
    const ERR_ATTESTATION_SIGNATURE = 5;

    /** Error for the attestation verification not verifying */
    const ERR_ATTESTATION_VERIFICATION = 6;

    /** Error for not getting good random from the system */
    const ERR_BAD_RANDOM = 7;

    /** Error when the counter is lower than expected */
    const ERR_COUNTER_TOO_LOW = 8;

    /** Error decoding public key */
    const ERR_PUBKEY_DECODE = 9;

    /** Error user-agent returned error */
    const ERR_BAD_UA_RETURNING = 10;

    /** Error old OpenSSL version */
    const ERR_OLD_OPENSSL = 11;

    /**
     * Override constructor and make message and code mandatory
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $code, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}