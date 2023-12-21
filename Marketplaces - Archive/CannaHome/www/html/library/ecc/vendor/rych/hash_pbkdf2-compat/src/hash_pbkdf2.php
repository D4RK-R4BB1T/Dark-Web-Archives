<?php
/**
 * This file is part of Rych\hash_pbkdf2-compat
 *
 * (c) Ryan Chouinard <rchouinard@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rych;

/**
 * @param   string  $algo       Name of selected hashing algorithm (i.e. md5,
 *                              sha256, haval160,4, etc..) See hash_algos() for
 *                              a list of supported algorithms.
 * @param   string  $password   The password to use for the derivation.
 * @param   string  $salt       The salt to use for the derivation. This value
 *                              should be generated randomly.
 * @param   integer $iterations The number of internal iterations to perform for
 *                              the derivation.
 * @param   integer $length     The length of the output string. If raw_output
 *                              is TRUE this corresponds to the byte-length of
 *                              the derived key, if raw_output is FALSE this
 *                              corresponds to twice the byte-length of the
 *                              derived key (as every byte of the key is
 *                              returned as two hexits).
 *                              
 *                              If 0 is passed, the entire output of the
 *                              supplied algorithm is used.
 * @param   boolean $raw_output When set to TRUE, outputs raw binary data.
 *                              FALSE outputs lowercase hexits.
 * @return  string              Returns a string containing the derived key as
 *                              lowercase hexits unless raw_output is set to
 *                              TRUE in which case the raw binary representation
 *                              of the derived key is returned.
 */
function hash_pbkdf2($algo = null, $password = null, $salt = null, $iterations = null, $length = 0, $raw_output = false)
{    
    // Recreate \hash_pbkdf2() error conditions
    $num_args = func_num_args();
    if ($num_args < 4) {
        trigger_error(sprintf('%s() expects at least 4 parameters, %d given', __FUNCTION__, $num_args), E_USER_WARNING);
        return null;
    }

    if (!in_array($algo, hash_algos())) {
        trigger_error(sprintf('%s(): Unknown hashing algorithm: %s', __FUNCTION__, $algo), E_USER_WARNING);
        return false;
    }

    if (!is_integer($iterations)) {
        trigger_error(sprintf('%s() expects parameter 4 to be long, %s given', __FUNCTION__, gettype($iterations)), E_USER_WARNING);
        return null;
    }

    if ($iterations <= 0) {
        trigger_error(sprintf('%s(): Iterations must be a positive integer: %d', __FUNCTION__, $iterations), E_USER_WARNING);
        return false;
    }

    if (!is_integer($length)) {
        trigger_error(sprintf('%s() expects parameter 5 to be long, %s given', __FUNCTION__, gettype($length)), E_USER_WARNING);
        return null;
    }

    if ($length < 0) {
        trigger_error(sprintf('%s(): Length must be greater than or equal to 0: %d', __FUNCTION__,  $length), E_USER_WARNING);
        return false;
    }

    $salt_len = strlen($salt);
    if ($salt_len > PHP_INT_MAX - 4) {
        trigger_error(sprintf('%s(): Supplied salt is too long, max of PHP_INT_MAX - 4 bytes: %d supplied', __FUNCTION__, $salt_len), E_USER_WARNING);
        return false;
    }

    // Algorithm implementation
    $hash_len = strlen(hash($algo, null, $raw_output));
    if ($length == 0) {
        $length = $hash_len;
    }

    $output = '';
    $block_count = ceil($length / $hash_len);
    for ($block = 1; $block <= $block_count; ++$block) {
        $key1 = $key2 = hash_hmac($algo, $salt . pack('N', $block), $password, true);
        for ($iteration = 1; $iteration < $iterations; ++$iteration) {
            $key2 ^= $key1 = hash_hmac($algo, $key1, $password, true);
        }
        $output .= $key2;
    }

    // Output the derived key
    // NOTE: The built-in \hash_pbkdf2() function trims the output to $length,
    // not the raw bytes before encoding as might be expected. I'm not a fan
    // of that decision, but it's emulated here for full compatibility.
    return substr(($raw_output) ? $output : bin2hex($output), 0, $length);
}
