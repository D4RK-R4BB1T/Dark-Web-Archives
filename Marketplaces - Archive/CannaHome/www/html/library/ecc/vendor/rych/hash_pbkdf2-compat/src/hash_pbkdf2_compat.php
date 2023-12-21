<?php
/**
 * This file is part of Rych\hash_pbkdf2-compat
 *
 * (c) Ryan Chouinard <rchouinard@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// @codeCoverageIgnoreStart
require __DIR__ . '/hash_pbkdf2.php';

if (!function_exists('hash_pbkdf2')) {

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
    return \Rych\hash_pbkdf2($algo, $password, $salt, $iterations, $length, $raw_output);
}

}
// @codeCoverageIgnoreEnd
