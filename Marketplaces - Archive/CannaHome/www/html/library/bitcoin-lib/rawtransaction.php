<?php

/**
 * Raw Transaction Library
 *
 * This library contains functions used to decode hex-encoded raw transactions
 * into an array mirroring bitcoind's decoderawtransaction format.
 *
 * Highest level functions are:
 *  - decode    : decodes a raw transaction hex to a bitcoind-like array
 *  - encode    : encodes a bitcoind-like transaction array to a raw transaction hex.
 *  - validate_signed_transaction : takes a raw transaction hex, it's json inputs,
 *                  and an optional input-specifier, and validates the signature(s).
 *  - create_multisig : creates a multisig address from m, and the public keys.
 *  - create_redeem_script - takes a set of public keys, and the number of signatures
 *                required to redeem funds.
 *  - decode_redeem_script - decodes a redeemScript to obtain the pubkeys, m, and n.
 */
class RawTransaction
{
    /**
     * used in _check_sig to determine if we want to fail on high S values
     *
     * @var bool
     */
    const ALLOW_HIGH_S = false;

    /**
     * Some of the defined OP CODES available in Bitcoins script.
     *
     */
    public static $op_code = array(
        '00' => 'OP_0', // or OP_FALSE
        '51' => 'OP_1', // or OP_TRUE
        '61' => 'OP_NOP',
        '6a' => 'OP_RETURN',
        '76' => 'OP_DUP',
        '87' => 'OP_EQUAL',
        '88' => 'OP_EQUALVERIFY',
        'a6' => 'OP_RIPEMD160',
        'a7' => 'OP_SHA1',
        'a8' => 'OP_SHA256',
        'a9' => 'OP_HASH160',
        'aa' => 'OP_HASH256',
        'ac' => 'OP_CHECKSIG',
        'ae' => 'OP_CHECKMULTISIG'
    );

    /**
     * Flip Byte Order
     *
     * This function is used to swap the byte ordering from little to big
     * endian, and vice-versa. A byte string, not a reference, is supplied,
     * the byte order reversed, and the string returned.
     *
     * @param    string $bytes
     * @return    string
     */
    public static function _flip_byte_order($bytes)
    {
        return implode('', array_reverse(str_split($bytes, 2)));
    }

    /**
     * Return Bytes
     *
     * This function accepts $string as a reference, and takes the first
     * $byte_count bytes of hex (twice the number when dealing with hex
     * characters in a string), and returns it to the user.
     * Setting the third parameter to TRUE will cause the byte order to flip.
     * Note: Because $string is a reference to the original copy, this
     * function actually removes the data from the string.
     *
     * @param    string  $string
     * @param    int     $byte_count
     * @param    boolean $reverse
     * @return    string
     */
    public static function _return_bytes(&$string, $byte_count, $reverse = false)
    {
        if (strlen($string) < $byte_count * 2) {
            throw new \InvalidArgumentException("Could not read enough bytes");
        }

        $requested_bytes = substr($string, 0, $byte_count * 2);

        // Overwrite $string, starting $byte_count bytes from the start.
        $string = substr($string, $byte_count * 2);
        // Flip byte order if requested.
        return ($reverse == false) ? $requested_bytes : self::_flip_byte_order($requested_bytes);
    }

    /**
     * Decimal to Bytes
     *
     * This function encodes a $decimal number as a $bytes byte long hex string.
     * Byte order can be flipped by setting $reverse to TRUE.
     *
     * @param    int     $decimal
     * @param    int     $bytes
     * @param    boolean $reverse
     * @return    string
     */
    public static function _dec_to_bytes($decimal, $bytes, $reverse = false)
    {
        $hex = dechex($decimal);
        if (strlen($hex) % 2 != 0) {
            $hex = "0" . $hex;
        }

        $hex = str_pad($hex, $bytes * 2, "0", STR_PAD_LEFT);

        return ($reverse == true) ? self::_flip_byte_order($hex) : $hex;
    }

    /**
     * Get VarInt
     *
     * https://en.bitcoin.it/wiki/Protocol_specification#Variable_length_integer
     *
     * This function is used when dealing with a varint. Because their size
     * is variable, the first byte must be checked to learn the true length
     * of the encoded number.
     * $tx is passed by reference, and the first byte is popped.
     * It is compared against a list of bytes, which are used to infer the
     * following numbers length.
     *
     * @param    string $string
     * @return    int
     */
    public static function _get_vint(&$string)
    {
        // Load the next byte, convert to decimal.
        $decimal = hexdec(self::_return_bytes($string, 1));

        // Less than 253: Not encoding extra bytes.
        // More than 253, work out the $number of bytes using the 2^(offset)
        $num_bytes = ($decimal < 253) ? 0 : 2 ^ ($decimal - 253);

        // Num_bytes is 0: Just return the decimal
        // Otherwise, return $num_bytes bytes (order flipped) and converted to decimal
        return ($num_bytes == 0) ? $decimal : hexdec(self::_return_bytes($string, $num_bytes, true));

    }

    /**
     * Encode VarInt
     * Accepts a $decimal number and attempts to encode it to a VarInt.
     * https://en.bitcoin.it/wiki/Protocol_specification#Variable_length_integer
     *
     * If the number is less than 0xFD/253, then the varint returned
     *  is the decimal number, encoded as one hex byte.
     * If larger than this number, then the numbers magnitude determines
     * a prefix, out of FD, FE, and FF, depending on the number size.
     * Returns FALSE if the number is bigger than 64bit.
     *
     * @param    int $decimal
     * @return    string|FALSE
     */
    public static function _encode_vint($decimal)
    {
        $hex = dechex($decimal);
        if ($decimal < 253) {
            $hint = self::_dec_to_bytes($decimal, 1);
            $num_bytes = 0;
        } elseif ($decimal < 65535) {
            $hint = 'fd';
            $num_bytes = 2;
        } elseif ($hex < 4294967295) {
            $hint = 'fe';
            $num_bytes = 4;
        } elseif ($hex < 18446744073709551615) {
            $hint = 'ff';
            $num_bytes = 8;
        } else {
            throw new \InvalidArgumentException("Invalid decimal");
        }

        // If the number needs no extra bytes, just return the 1-byte number.
        // If it needs to indicate a larger integer size (16bit, 32bit, 64bit)
        // then it returns the size hint and the 64bit number.
        return ($num_bytes == 0) ? $hint : $hint . self::_dec_to_bytes($decimal, $num_bytes, true);
    }

    public static function pushdata($script)
    {
        $length = strlen($script) / 2;

        /** Note that larger integers are serialized without flipping bits - Big endian */
        if ($length < 75) {
            $l = self::_dec_to_bytes($length, 1);
            $string = $l . $script;
        } elseif ($length <= 0xff) {
            $l = self::_dec_to_bytes($length, 1);
            $string = '4c' . $l . $script;
        } elseif ($length <= 0xffff) {
            $l = self::_dec_to_bytes($length, 2, true);
            $string = '4d' . $l . $script;
        } elseif ($length <= 0xffffffff) {
            $l = self::_dec_to_bytes($length, 4, true);
            $string = '4e' . $l . $script;
        } else {
            throw new \RuntimeException('Size of pushdata too large');
        }

        return $string;
    }

    /**
     * Decode Script
     *
     * This function accepts a $script (such as scriptSig) and converts it
     * into an assembled version. Written based on the pybitcointools
     * transaction.deserialize_script() function.
     *
     * @param    string $script
     * @return    string
     */
    public static function _decode_script($script)
    {
        $pos = 0;
        $data = array();
        while ($pos < strlen($script)) {
            $code = hexdec(substr($script, $pos, 2)); // hex opcode.
            $pos += 2;

            if ($code < 1) {
                // OP_FALSE
                $push = '0';
            } elseif ($code <= 75) {
                // $code bytes will be pushed to the stack.
                $push = substr($script, $pos, ($code * 2));
                $pos += $code * 2;
            } elseif ($code <= 78) {
                // In this range, 2^($code-76) is the number of bytes to take for the *next* number onto the stack.
                $szsz = pow(2, $code - 75); // decimal number of bytes.
                $sz = hexdec(substr($script, $pos, ($szsz * 2))); // decimal number of bytes to load and push.
                $pos += $szsz;
                $push = substr($script, $pos, ($pos + $sz * 2)); // Load the data starting from the new position.
                $pos += $sz * 2;
            } elseif ($code <= 96) {
                // OP_x, where x = $code-80
                $push = ($code - 80);
            } else {
                $push = $code;
            }
            $data[] = $push;
        }
        return implode(" ", $data);
    }

    /**
     * Decode Inputs
     *
     * This function accepts a $raw_transaction by reference, and $input_count,
     * a decimal number of inputs to extract (learned by calling the get_vint()
     * function). Returns an array of the construction [vin] if successful,
     * returns FALSE if an error was encountered.
     *
     * @param    string $raw_transaction
     * @param    int    $input_count
     * @return    array
     */
    public static function _decode_inputs(&$raw_transaction, $input_count)
    {
        $inputs = array();

        // Loop until $input count is reached, sequentially removing the
        // leading data from $raw_transaction reference.
        for ($i = 0; $i < $input_count; $i++) {
            // Load the TxID (32bytes) and vout (4bytes)
            $txid = self::_return_bytes($raw_transaction, 32, true);
            $vout = self::_return_bytes($raw_transaction, 4, true);

            // Script is prefixed with a varint that must be decoded.
            $script_length = self::_get_vint($raw_transaction); // decimal number of bytes.
            $script = self::_return_bytes($raw_transaction, $script_length);

            // Build input body depending on whether the TxIn is coinbase.
            if ($txid == '0000000000000000000000000000000000000000000000000000000000000000') {
                $input_body = array('coinbase' => $script);
            } else {
                $input_body = array('txid' => $txid,
                    'vout' => hexdec($vout),
                    'scriptSig' => array('asm' => self::_decode_script($script),
                        'hex' => $script));
            }

            // Append a sequence number, and finally add the input to the array.
            $input_body['sequence'] = hexdec(self::_return_bytes($raw_transaction, 4));

            $inputs[$i] = $input_body;
        }

        return $inputs;
    }

    /**
     * Encode Inputs
     *
     * Accepts a decoded $transaction['vin'] array as input: $vin. Also
     * requires $input count.
     * This function encodes the txid, vout, and script into hex format.
     *
     * @param    array $vin
     * @param    int   $input_count
     * @return    string
     */
    public static function _encode_inputs($vin, $input_count)
    {
        $inputs = '';
        for ($i = 0; $i < $input_count; $i++) {
            if (isset($vin[$i]['coinbase'])) {
                // Coinbase
                $txHash = '0000000000000000000000000000000000000000000000000000000000000000';
                $vout = 'ffffffff';
                $script_size = strlen($vin[$i]['coinbase']) / 2; // Decimal number of bytes
                $script_varint = self::_encode_vint($script_size); // Varint
                $scriptSig = $script_varint . $vin[$i]['coinbase'];
            } else {
                // Regular transaction
                $txHash = self::_flip_byte_order($vin[$i]['txid']);
                $vout = self::_dec_to_bytes($vin[$i]['vout'], 4, true);

                $script_size = strlen($vin[$i]['scriptSig']['hex']) / 2; // decimal number of bytes
                $script_varint = self::_encode_vint($script_size); // Create the varint encoding scripts length
                $scriptSig = $script_varint . $vin[$i]['scriptSig']['hex'];
            }
            // Add the sequence number.
            $sequence = self::_dec_to_bytes($vin[$i]['sequence'], 4, true);

            // Append this encoded input to the byte string.
            $inputs .= $txHash . $vout . $scriptSig . $sequence;
        }
        return $inputs;
    }

    /**
     * Decode scriptPubKey
     *
     * This function takes $script (hex) as an argument, and decodes an
     * script hex into an assembled human readable string.
     *
     * @param     string $script
     * @param     bool $matchBitcoinCore
     * @return    string
     */
    public static function _decode_scriptPubKey($script, $matchBitcoinCore = false)
    {
        $data = array();
        while (strlen($script) !== 0) {
            $byteHex = self::_return_bytes($script, 1);
            $byteInt = hexdec($byteHex);

            if (isset(self::$op_code[$byteHex])) {
                // This checks if the OPCODE is defined from the list of constants.

                if ($matchBitcoinCore && self::$op_code[$byteHex] == "OP_0") {
                    $data[] = '0';
                } else if ($matchBitcoinCore && self::$op_code[$byteHex] == "OP_1") {
                    $data[] = '1';
                } else {
                    $data[] = self::$op_code[$byteHex];
                }

            } elseif ($byteInt >= 0x01 && $byteInt <= 0x4b) {
                // This checks if the OPCODE falls in the PUSHDATA range
                $data[] = self::_return_bytes($script, $byteInt);

            } elseif ($byteInt >= 0x51 && $byteInt <= 0x60) {
                // This checks if the CODE falls in the OP_X range
                $data[] = $matchBitcoinCore ? ($byteInt - 0x50) : 'OP_' . ($byteInt - 0x50);
            } else {
                throw new \RuntimeException("Failed to decode scriptPubKey");
            }
        }
        return implode(" ", $data);
    }

    /**
     * Get Transaction Type
     *
     * This function takes a $data string, from a decoded scriptPubKey,
     * explodes it into an array of the operations/data. Returns FALSE if
     * the decoded scriptPubKey does not match against the definition of
     * any type of transaction.
     * Currently identifies pay-to-pubkey-hash and pay-to-script-hash.
     *
     * Transaction types are defined using the $define array, and
     * corresponding rules are build using the $rule array. The function
     * will attempt to create the address based on the transaction type
     * and $address_version byte.
     *
     *
     * @param    string $data
     * @param    string $magic_byte
     * @param    string $magic_p2sh_byte
     * @return   array|FALSE
     */
    public static function _get_transaction_type($data, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        $data = explode(" ", $data);

        // Define information about eventual transactions cases, and
        // the position of the hash160 address in the stack.
        $define = array();
        $rule = array();

        // Standard: pay to pubkey hash
        $define['p2ph'] = array('type' => 'pubkeyhash',
            'reqSigs' => 1,
            'data_index_for_hash' => 2);
        $rule['p2ph'] = array(
            '0' => '/^OP_DUP/',
            '1' => '/^OP_HASH160/',
            '2' => '/^[0-9a-f]{40}$/i', // 2
            '3' => '/^OP_EQUALVERIFY/',
            '4' => '/^OP_CHECKSIG/');

        // Pay to script hash
        $define['p2sh'] = array('type' => 'scripthash',
            'reqSigs' => 1,
            'data_index_for_hash' => 1);
        $rule['p2sh'] = array(
            '0' => '/^OP_HASH160/',
            '1' => '/^[0-9a-f]{40}$/i', // pos 1
            '2' => '/^OP_EQUAL/');

        // Work out how many rules are applied in each case
        $valid = array();
        foreach ($rule as $tx_type => $def) {
            $valid[$tx_type] = count($def);
        }

        // Attempt to validate against each of these rules.
        $matches = array();
        foreach ($data as $index => $test) {
            foreach ($rule as $tx_type => $def) {
                $matches[$tx_type] = array();
                if (isset($def[$index])) {
                    preg_match($def[$index], $test, $matches[$tx_type]);
                    if (count($matches[$tx_type]) == 1) {
                        $valid[$tx_type]--;
                        break;
                    }
                }
            }
        }

        // Loop through rules, check if any transaction is a match.
        foreach ($rule as $tx_type => $def) {
            if ($valid[$tx_type] == 0) {
                // Load predefined info for this transaction type if detected.
                $return = $define[$tx_type];
                $return['hash160'] = $data[$define[$tx_type]['data_index_for_hash']];

                $return['addresses'][0] = BitcoinLib::hash160_to_address($return['hash160'], ($return['type'] == 'scripthash') ? $magic_p2sh_byte : $magic_byte);

                unset($return['data_index_for_hash']);
            }
        }

        return (!isset($return)) ? false : $return;
    }

    /**
     * Decode Outputs
     *
     * This function accepts $tx - a reference to the raw transaction being
     * decoded, and $output_count. Also accepts $address_version for when
     * dealing with networks besides bitcoin.
     * Returns FALSE if
     *
     * @param    string $tx
     * @param    int    $output_count
     * @param    string $magic_byte
     * @param    string $magic_p2sh_byte
     * @return   array|FALSE
     */
    public static function _decode_outputs(&$tx, $output_count, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $math = EccFactory::getAdapter();

        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        $outputs = array();
        for ($i = 0; $i < $output_count; $i++) {
            // Pop 8 bytes (flipped) from the $tx string, convert to decimal,
            // and then convert to Satoshis.
            $satoshis = $math->hexDec(self::_return_bytes($tx, 8, true));

            // Decode the varint for the length of the scriptPubKey
            $script_length = self::_get_vint($tx); // decimal number of bytes
            $script = self::_return_bytes($tx, $script_length);

            try {
                $asm = self::_decode_scriptPubKey($script);
            } catch (\Exception $e) {
                $asm = null;
            }

            // Begin building scriptPubKey
            $scriptPubKey = array(
                'asm' => $asm,
                'hex' => $script
            );

            // Try to decode the scriptPubKey['asm'] to learn the transaction type.
            $txn_info = self::_get_transaction_type($scriptPubKey['asm'], $magic_byte, $magic_p2sh_byte);
            if ($txn_info !== false) {
                $scriptPubKey = array_merge($scriptPubKey, $txn_info);
            } else {
                $scriptPubKey['message'] = 'unable to decode tx type!';
            }

            $outputs[$i] = array(
                'value' => $satoshis,
                'vout' => $i,
                'scriptPubKey' => $scriptPubKey);

        }
        return $outputs;
    }

    /**
     * Encode Outputs
     *
     * This function encodes $tx['vin'] array into hex format. Requires
     * the $vout_arr, and also $output_count - the number of outputs
     * this transaction has.
     *
     * @param    array
     * @param    int
     * @return   string|FALSE
     */
    public static function _encode_outputs($vout_arr, $output_count)
    {
        // If $vout_arr is empty, check if it's MEANT to be before failing.
        if (count($vout_arr) == 0) {
            return ($output_count == 0) ? '' : false;
        }

        $outputs = '';
        for ($i = 0; $i < $output_count; $i++) {
            $satoshis = $vout_arr[$i]['value'];
            $amount = self::_dec_to_bytes($satoshis, 8);
            $amount = self::_flip_byte_order($amount);

            $script_size = strlen($vout_arr[$i]['scriptPubKey']['hex']) / 2; // number of bytes
            $script_varint = self::_encode_vint($script_size);
            $scriptPubKey = $vout_arr[$i]['scriptPubKey']['hex'];

            $outputs .= $amount . $script_varint . $scriptPubKey;
        }
        return $outputs;
    }

    /**
     * Decode
     *
     * A high-level function which takes $raw_transaction hex, and decodes
     * it into an array similar to that returned by bitcoind.
     * Accepts an optional $address_version for creating the addresses
     * - defaults to bitcoins version byte.
     *
     * @param    string $raw_transaction
     * @param    string $magic_byte
     * @param    string $magic_p2sh_byte
     * @return  array|FALSE
     */
    public static function decode($raw_transaction, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $math = EccFactory::getAdapter();

        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        $raw_transaction = trim($raw_transaction);
        if (((bool)preg_match('/^[0-9a-fA-F]{2,}$/i', $raw_transaction) !== true)
            || (strlen($raw_transaction)) % 2 !== 0
        ) {
            throw new \InvalidArgumentException("Raw transaction is invalid hex");
        }

        $txHash = hash('sha256', hash('sha256', pack("H*", trim($raw_transaction)), true));
        $txid = self::_flip_byte_order($txHash);

        $info = array();
        $info['txid'] = $txid;
        $info['version'] = $math->hexDec(self::_return_bytes($raw_transaction, 4, true));
        if (!in_array($info['version'], array('0', '1'))) {
            throw new \InvalidArgumentException("Invalid transaction version");
        }

        $input_count = self::_get_vint($raw_transaction);
        if (!($input_count >= 0 && $input_count <= 4294967296)) {
            throw new \InvalidArgumentException("Invalid input count");
        }

        $info['vin'] = self::_decode_inputs($raw_transaction, $input_count);
        if ($info['vin'] == false) {
            throw new \InvalidArgumentException("No inputs in transaction");
        }

        $output_count = self::_get_vint($raw_transaction);
        if (!($output_count >= 0 && $output_count <= 4294967296)) {
            throw new \InvalidArgumentException("Invalid output count");
        }

        $info['vout'] = self::_decode_outputs($raw_transaction, $output_count, $magic_byte, $magic_p2sh_byte);

        $info['locktime'] = $math->hexDec(self::_return_bytes($raw_transaction, 4));
        return $info;
    }

    /**
     * Encode
     *
     * This function takes an array in a format similar to bitcoind's
     * (and compatible with the output of debug above) and re-encodes it
     * into a raw transaction hex string.
     *
     * @param    array $raw_transaction_array
     * @return    string
     */
    public static function encode($raw_transaction_array)
    {
        $encoded_version = self::_dec_to_bytes($raw_transaction_array['version'], 4, true); // TRUE - get little endian

        // $encoded_inputs - set the encoded varint, then work out if any input hex is to be displayed.
        $decimal_inputs_count = count($raw_transaction_array['vin']);
        $encoded_inputs = self::_encode_vint($decimal_inputs_count) . (($decimal_inputs_count > 0) ? self::_encode_inputs($raw_transaction_array['vin'], $decimal_inputs_count) : '');

        // $encoded_outputs - set varint, then work out if output hex is required.
        $decimal_outputs_count = count($raw_transaction_array['vout']);
        $encoded_outputs = self::_encode_vint($decimal_outputs_count) . (($decimal_inputs_count > 0) ? self::_encode_outputs($raw_transaction_array['vout'], $decimal_outputs_count) : '');

        // Transaction locktime
        $encoded_locktime = self::_dec_to_bytes($raw_transaction_array['locktime'], 4, true);

        return $encoded_version . $encoded_inputs . $encoded_outputs . $encoded_locktime;

    }

    /**
     * Get the txid from the raw transaction hex
     *
     * @param $raw_transaction
     * @return string
     */
    public static function txid_from_raw($raw_transaction)
    {
        return self::decode($raw_transaction)['txid'];
    }

    /**
     * Create Signature Hash
     *
     * This function accepts a $raw_transaction hex, and generates a hash
     * for each input, against which a signature and public key can be
     * verified.
     * See https://en.bitcoin.it/w/images/en/7/70/Bitcoin_OpCheckSig_InDetail.png
     *
     * If $specific_input is not set, then a hash will be generated for
     * each input, and these values returned as an array for comparison
     * during another script.
     *
     * @param   string  $raw_transaction
     * @param   string  $json_inputs
     * @param   int     $specific_input
     * @param   array   $e
     * @return  string
     */
    public static function _create_txin_signature_hash($raw_transaction, $json_inputs, $specific_input = -1, $e = null)
    {

        $decode = ($e == null) ? self::decode($raw_transaction) : $e;

        $inputs = (array)json_decode($json_inputs);
        if ($specific_input !== -1 && !is_numeric($specific_input)) {
            throw new \InvalidArgumentException("Specified input should be numeric");
        }

        // Check that $raw_transaction and $json_inputs correspond to the right inputs
        $inputCount = count($decode['vin']);
        for ($i = 0; $i < $inputCount; $i++) {
            if (!isset($inputs[$i])) {
                throw new \InvalidArgumentException("Raw transaction does not match expected inputs");
            }
            if ($decode['vin'][$i]['txid'] !== $inputs[$i]->txid ||
                $decode['vin'][$i]['vout'] !== $inputs[$i]->vout
            ) {
                throw new \InvalidArgumentException("Raw transaction does not match expected inputs");
            }
        }

        $sighashcode = '01000000';

        if ($specific_input == -1) {
            // Return a hash for each input.
            $hash = array();
            foreach ($decode['vin'] as $vin => $input) {
                $copy = $decode;

                foreach ($copy['vin'] as &$copy_input) {
                    $copy_input['scriptSig']['hex'] = '';
                }

                $copy['vin'][$vin]['scriptSig']['hex'] = (isset($inputs[$vin]->redeemScript)) ? $inputs[$vin]->redeemScript : $inputs[$vin]->scriptPubKey;

                // Encode the transaction, convert to a raw byte sting,
                // and calculate a double sha256 hash for this input.
                $hash[] = hash('sha256', hash('sha256', pack("H*", self::encode($copy) . $sighashcode), true));
            }

        } else {
            // Return a message hash for the specified output.
            $copy = $decode;
            $copy['vin'][$specific_input]['scriptSig']['hex'] = (isset($inputs[$specific_input]->redeemScript)) ? $inputs[$specific_input]->redeemScript : $inputs[$specific_input]->scriptPubKey;

            $hash = hash('sha256', hash('sha256', pack("H*", self::encode($copy) . $sighashcode), true));
        }
        return $hash;
    }

    /**
     * Check Sig
     *
     * This function will check a provided DER encoded $sig, a digest of
     * the message to be signed - $hash (the output of _create_txin_signature_hash()),
     * and the $key for the signature to be tested against.
     * Returns TRUE if the signature is valid for this $hash and $key,
     * otherwise returns FALSE.
     *
     * @param    string $sig
     * @param    string $hash
     * @param    string $key
     * @param    bool   $allowHighS
     * @return bool
     */
    public static function _check_sig($sig, $hash, $key, $allowHighS = self::ALLOW_HIGH_S)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves()->generator256k1();
        $curve = $generator->getCurve();

        $hash = $math->hexDec($hash);
        $decodedSignature = self::decode_signature($sig);
        $signature = new Signature($math->hexDec($decodedSignature['r']), $math->hexDec($decodedSignature['s']));

        if (!$allowHighS) {
            // if S is > half then someone should have fixed it
            if (self::check_signature_is_high_s($signature)) {
                return false;
            }
        }

        if (strlen($key) == '66') {
            $decompress = BitcoinLib::decompress_public_key($key);
            $public_key_point = $decompress['point'];
        } else {
            $x = $math->hexDec(substr($key, 2, 64));
            $y = $math->hexDec(substr($key, 66, 64));

            $public_key_point = $curve->getPoint($x, $y);
        }

        $signer = new Signer($math);
        $public_key = new PublicKey($math, $generator, $public_key_point);

        return $signer->verify($public_key, $signature, $hash) == true;
    }

    public static function check_signature_is_high_s(Signature $signature)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves()->generator256k1();

        $n = $generator->getOrder();
        $against = $n;
        $against = $math->rightShift($against, 1);

        // if S is > half then someone should have substracted N
        return $math->cmp($signature->getS(), $against) > 0;
    }

    /**
     * Decode Redeem Script
     *
     * This recursive function extracts the m and n values for the
     * multisignature address, as well as the public keys.
     * Don't set $data.
     *
     * @param    string $redeem_script
     * @param    array  $data
     * @return    array
     */
    public static function decode_redeem_script($redeem_script, $data = array())
    {
        $math = EccFactory::getAdapter();

        // If there is no more work to be done (script is fully parsed, return the array)
        if (strlen($redeem_script) == 0) {
            return $data;
        }

        // Fail if the redeem_script has an uneven number of characters.
        if (strlen($redeem_script) % 2 !== 0) {
            throw new \InvalidArgumentException("Redeem script is invalid hex");
        }

        // First step is to get m, the required number of signatures
        if (!isset($data['m']) || count($data) == 0) {
            $data['m'] = $math->sub($math->hexDec(substr($redeem_script, 0, 2)), $math->hexDec('50'));
            $data['keys'] = array();
            $redeem_script = substr($redeem_script, 2);

        } elseif (count($data['keys']) == 0 && !isset($data['next_key_charlen'])) {
            // Next is to find out the length of the following public key.
            $hex = substr($redeem_script, 0, 2);
            // Set up the length of the following key.
            $data['next_key_charlen'] = $math->mul(2, $math->hexDec($hex));

            $redeem_script = substr($redeem_script, 2);
        } elseif (isset($data['next_key_charlen'])) {
            // Extract the key, and work out the next step for the code.
            $data['keys'][] = substr($redeem_script, 0, $data['next_key_charlen']);
            $next_op = substr($redeem_script, $data['next_key_charlen'], 2);
            $redeem_script = substr($redeem_script, ($data['next_key_charlen'] + 2));

            unset($data['next_key_charlen']);

            // If 1 <= $next_op >= 4b : A key is coming up next. This if block runs again.
            if (in_array($math->cmp($math->hexDec($next_op), 1), array(0, 1))
                && in_array($math->cmp($math->hexDec($next_op), $math->hexDec('4b')), array(-1, 0))
            ) {
                // Set the next key character length
                $data['next_key_charlen'] = $math->mul(2, $math->hexDec($next_op));

                // If 52 <= $next_op >= 60 : End of keys, now have n.
            } elseif (in_array($math->cmp($math->hexDec($next_op), $math->hexDec('51')), array(0, 1))
                && in_array($math->cmp($math->hexDec($next_op), $math->hexDec('60')), array(-1, 0))
            ) {
                // Finish the script - obtain n
                $data['n'] = $math->sub($math->hexDec($next_op), $math->hexDec('50'));
                if ($redeem_script !== 'ae') {
                    throw new \InvalidArgumentException("Redeem script should be 'ae'");
                }

                $redeem_script = '';
            } else {
                // Something weird, malformed redeemScript.
                throw new \InvalidArgumentException("Malformed redeem script");
            }
        }
        return self::decode_redeem_script($redeem_script, $data);
    }

    /**
     * Create Redeem Script
     *
     * This function creates a hex encoded redeemScript, by setting $m,
     * the number of signatures required for redemption, and the public
     * keys involved in the address. Returns FALSE if no public keys are
     * supplied, or $m is zero. Otherwise returns a string containing
     * the redeemScript.
     *
     * @param    int   $m
     * @param    array $public_keys
     * @return   string|FALSE
     */
    public static function create_redeem_script($m, $public_keys = array())
    {
        $math = EccFactory::getAdapter();

        if (count($public_keys) == 0) {
            throw new \InvalidArgumentException("No public keys provided");
        }

        if ($m == 0) {
            throw new \InvalidArgumentException("M should be larger than 0");
        }

        $redeemScript = $math->decHex(0x50 + $m);
        foreach ($public_keys as $public_key) {
            $redeemScript .= $math->decHex(strlen($public_key) / 2) . $public_key;
        }
        $redeemScript .= $math->decHex(0x50 + (count($public_keys))) . 'ae';
        return $redeemScript;
    }

    /**
     * Create Multisig
     *
     * This function mirrors that of Bitcoind's. It creates a redeemScript
     * out of keys given in the given order, creates a redeemScript, and
     * creates the address from this. $m must be greater than zero, and
     * public keys are required.
     *
     * @param    int   $m
     * @param    array $public_keys
     * @param   string $magic_p2sh_byte
     * @return  array|FALSE
     */
    public static function create_multisig($m, $public_keys = array(), $magic_p2sh_byte = null)
    {
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        if ($m == 0) {
            throw new \InvalidArgumentException("M should be larger than 0");
        }

        if (count($public_keys) == 0) {
            throw new \InvalidArgumentException("No public keys provided");
        }

        $redeem_script = self::create_redeem_script($m, $public_keys);

        return array(
            'redeemScript' => $redeem_script,
            'address' => BitcoinLib::public_key_to_address($redeem_script, $magic_p2sh_byte)
        );
    }

    /**
     * Sort Multisig Keys
     *
     * Accepts an array of public keys for multisig, and returns them sorted by lexicographic order.
     *
     * @param    array $public_keys
     * @return    array
     */
    public static function sort_multisig_keys($public_keys)
    {
        $sorted_keys = $public_keys;

        sort($sorted_keys);

        return $sorted_keys;
    }

    /**
     * Validate Signed Transaction
     *
     * Pass a decoded $transaction, the $raw_tx as a reference and to
     * process into a hash when needed, the $json_inputs for the created
     * transaction (stored), and the $address_version ('00' for bitcoin
     * default)
     *
     * @param    string $raw_tx
     * @param    string $json_string
     * @param    string $magic_byte
     * @param    string $magic_p2sh_byte
     * @param    bool   $allowHighS
     * @return bool
     */
    public static function validate_signed_transaction($raw_tx, $json_string, $magic_byte = null, $magic_p2sh_byte = null, $allowHighS = self::ALLOW_HIGH_S)
    {
        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        $decode = self::decode($raw_tx, $magic_byte, $magic_p2sh_byte);

        $json_arr = (array)json_decode($json_string);

        $message_hash = self::_create_txin_signature_hash($raw_tx, $json_string);
        $outcome = true;
        foreach ($decode['vin'] as $i => $vin) {
            // Decode previous scriptPubKey to learn trasaction type.
            $type_info = self::_get_transaction_type(self::_decode_scriptPubKey($json_arr[$i]->scriptPubKey), $magic_byte, $magic_p2sh_byte);

            if ($type_info['type'] == 'pubkeyhash') {
                // Pay-to-pubkey-hash. Check one <sig> <pubkey>
                $scripts = explode(" ", $vin['scriptSig']['asm']);
                $signature = $scripts[0];

                $public_key = $scripts[1];
                $o = self::_check_sig($signature, $message_hash[$i], $public_key, $allowHighS);
                $outcome = $outcome && $o;

            } elseif ($type_info['type'] == 'scripthash') {
                // Pay-to-script-hash. Check OP_FALSE <sig> ... <redeemScript>
                $redeem_script_found = false;
                $pubkey_found = false;

                $scripts = explode(" ", $vin['scriptSig']['asm']);

                // Store the redeemScript, then remove OP_FALSE + the redeemScript from the array.
                $redeemScript = self::decode_redeem_script($scripts[(count($scripts) - 1)]);

                // Die if we fail to decode a redeemScript from a P2SH
                if ($redeemScript !== false) {
                    $redeem_script_found = true;
                }

                unset($scripts[(count($scripts) - 1)]); // Unset redeemScript
                unset($scripts[0]); // Unset '0';

                // Extract signatures, remove the "0" byte, and redeemScript.
                // Loop through the remaining values - the signatures
                foreach ($scripts as $signature) {
                    // Test each signature with the public keys in the redeemScript.
                    foreach ($redeemScript['keys'] as $public_key) {
                        if (self::_check_sig($signature, $message_hash[$i], $public_key, $allowHighS) == true) {
                            $pubkey_found = true;
                            break 2;
                        }
                    }
                }
                $outcome = $outcome && ($redeem_script_found && $pubkey_found);
            }
        }
        return $outcome;
    }

    /**
     * Create
     *
     * This function creates a raw transaction from an array of inputs,
     * and an array of outputs. It takes essentially the same data is
     * bitcoind's createrawtransaction function.
     *
     * Inputs: Each input is a child array of [txid, vout, and optionally a sequence number.]
     * Outputs: Each output is a key in the array: address => $value.
     *
     * @param   array  $inputs
     * @param   array  $outputs [address => value, ] or [[address, value], ]
     *                          or [['address' => address, 'value' => value], ]
     *                          or [['scriptPubKey' => scriptPubKey, 'value' => value], ]
     * @param   string $magic_byte
     * @param   string $magic_p2sh_byte
     * @return  string|FALSE
     */
    public static function create($inputs, $outputs, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        $tx_array = array('version' => '1');

        // Inputs is the set of [txid/vout/scriptPubKey]
        $tx_array['vin'] = array();
        foreach ($inputs as $i => $input) {
            if (!isset($input['txid']) || strlen($input['txid']) !== 64
                || !isset($input['vout']) || !is_numeric($input['vout'])
            ) {
                throw new \InvalidArgumentException("Invalid input [{$i}]");
            }

            $tx_array['vin'][] = [
                'txid' => $input['txid'],
                'vout' => $input['vout'],
                'sequence' => (isset($input['sequence'])) ? $input['sequence'] : 4294967295,
                'scriptSig' => ['hex' => '']
            ];
        }

        // Outputs is the set of [address/amount]
        $tx_array['vout'] = array();
        foreach ($outputs as $k => $v) {
            $address = null;
            $scriptPubKey = null;

            if (is_numeric($k)) {
                if (!is_array($v)) {
                    throw new \InvalidArgumentException(
                        "outputs should be [address => value, ] or [[address, value], ]" .
                        "or [['address' => address, 'value' => value], ] or [['scriptPubKey' => scriptPubKey, 'value' => value], ]"
                    );
                }

                if (isset($v['scriptPubKey']) && isset($v['value'])) {
                    $scriptPubKey = $v['scriptPubKey'];
                    $value = $v['value'];
                } else if (isset($v['address']) && isset($v['value'])) {
                    $address = $v['address'];
                    $value = $v['value'];
                } else if (count($v) == 2 && isset($v[0]) && isset($v[1])) {
                    $address = $v[0];
                    $value = $v[1];
                } else {
                    throw new \InvalidArgumentException(
                        "outputs should be [address => value, ] or [[address, value], ]" .
                        "or [['address' => address, 'value' => value], ] or [['scriptPubKey' => scriptPubKey, 'value' => value], ]"
                    );
                }
            } else {
                $address = $k;
                $value = $v;
            }

            if (!is_int($value)) {
                throw new \InvalidArgumentException("Values should be in Satoshis [{$value}]");
            }
            if (!$scriptPubKey) {
                if (!BitcoinLib::validate_address($address, $magic_byte, $magic_p2sh_byte)) {
                    throw new \InvalidArgumentException("Invalid address [{$address}]");
                }

                $decode_address = BitcoinLib::base58_decode($address);
                $version = substr($decode_address, 0, 2);
                $hash = substr($decode_address, 2, 40);

                if ($version == $magic_p2sh_byte) {
                    // OP_HASH160 <scriptHash> OP_EQUAL
                    $scriptPubKey = "a914{$hash}87";
                } else {
                    // OP_DUP OP_HASH160 <pubKeyHash> OP_EQUALVERIFY OP_CHECKSIG
                    $scriptPubKey = "76a914{$hash}88ac";
                }
            }

            $tx_array['vout'][] = [
                'value' => $value,
                'scriptPubKey' => ['hex' => $scriptPubKey]
            ];
        }

        $tx_array['locktime'] = 0;

        return self::encode($tx_array);

    }

    /**
     * Sign
     *
     * This function accepts the same parameters as signrawtransaction.
     * $raw_transaction is a hex encoded string for an unsigned/partially
     * signed transaction. $inputs is an array, containing the txid/vout/
     * scriptPubKey/redeemscript. $priv_keys contains WIF keys.
     *
     * The function looks at each TxIn and tries to sign, if the hash160
     * belongs to a key specified in the wallet.
     *
     * @param   array  $wallet
     * @param   string $raw_transaction
     * @param   string $inputs
     * @param   string $magic_byte
     * @param   string $magic_p2sh_byte
     * @return  array
     */
    public static function sign($wallet, $raw_transaction, $inputs, $magic_byte = null, $magic_p2sh_byte = null)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();

        $magic_byte = BitcoinLib::magicByte($magic_byte);
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        // Generate digests of inputs to sign.
        $message_hash = self::_create_txin_signature_hash($raw_transaction, $inputs);

        $inputs_arr = (array)json_decode($inputs);

        // Generate an association of expected hash160's and related information.
        $decode = self::decode($raw_transaction);

        $req_sigs = 0;
        $sign_count = 0;
        foreach ($decode['vin'] as $vin => $input) {
            $scriptPubKey = self::_decode_scriptPubKey($inputs_arr[$vin]->scriptPubKey);
            $tx_info = self::_get_transaction_type($scriptPubKey, $magic_byte, $magic_p2sh_byte);

            if (isset($wallet[$tx_info['hash160']])) {
                $key_info = $wallet[$tx_info['hash160']];
                $message_hash_dec = $math->hexDec($message_hash[$vin]);

                if ($key_info['type'] == 'scripthash') {
                    $signatures = self::extract_input_signatures_p2sh($input, $message_hash[$vin], $key_info);
                    $sign_count += count($signatures);

                    // Create Signature
                    foreach ($key_info['keys'] as $key) {
                        $key_dec = $math->hexDec($key['private_key']);
                        $k = $math->hexDec((string)bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)));

                        $signer = new Signer($math);
                        $_private_key = $generator->getPrivateKeyFrom($key_dec);
                        $sign = $signer->sign($_private_key, $message_hash_dec, $k);

                        if ($sign !== false) {
                            $sign_count++;
                            $signatures[$key['public_key']] = self::encode_signature($sign);
                        }
                    }
                    $decode['vin'][$vin]['scriptSig']['hex'] = self::_apply_sig_scripthash_multisig($signatures, $key_info);
                    // Increase required # signature counter.
                    $req_sigs += $key_info['required_signature_count'];
                }

                if ($key_info['type'] == 'pubkeyhash') {
                    $key_dec = $math->hexDec($key_info['private_key']);

                    $signer = new Signer($math);
                    $_private_key = $generator->getPrivateKeyFrom($key_dec);
                    $sign = $signer->sign($_private_key, $message_hash_dec, $math->hexDec((string)bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM))));
                    if ($sign !== false) {
                        $sign_count++;
                        $decode['vin'][$vin]['scriptSig']['hex'] = self::_apply_sig_pubkeyhash(self::encode_signature($sign), $key_info['public_key']);
                    }
                    $req_sigs++;
                }
            } else {
                $req_sigs++;
            }
        }
        $new_raw = self::encode($decode);

        // If the transaction isn't fully signed, return false.
        // If it's fully signed, perform signature verification, return true if valid, or invalid if signatures are incorrect.
        $complete = ((($req_sigs - $sign_count) <= 0)
            ? ((self::validate_signed_transaction($new_raw, $inputs, $magic_byte, $magic_p2sh_byte) == true) ? 'true' : 'false')
            : 'false');

        return array(
            'hex' => $new_raw,
            'complete' => $complete,
            'sign_count' => $sign_count,
            'req_sigs' => $req_sigs
        );
    }

    /**
     * Extract Input Signatures: P2SH
     *
     * This function accepts an array $input (a decoded input array), a $message_hash,
     * with which the signature is checked against, and $script_info - the
     *
     * @param $input
     * @param $message_hash
     * @param $script_info
     * @return array
     */
    public static function extract_input_signatures_p2sh($input, $message_hash, $script_info)
    {
        // May already be signatures there.
        $decodeSigs = explode(" ", self::_decode_script($input['scriptSig']['hex']));
        $signatures = array();
        if (count($decodeSigs) > 0) {
            foreach ($decodeSigs as $sig) {
                if (self::is_canonical_signature($sig)) {
                    foreach ($script_info['public_keys'] as $key) {
                        if (self::_check_sig($sig, $message_hash, $key) == true) {
                            $signatures[$key] = $sig;
                            break;
                        }
                    }
                }
            }
        }
        return $signatures;
    }

    /**
     * Encode Signature
     *
     * This function accepts a signature object, and information about
     * the txout being spent, and the relevant key for signing, and
     * encodes the signature in DER format.
     *
     * @param    Signature $signature
     * @return    string
     */
    public static function encode_signature(Signature $signature)
    {
        $math = EccFactory::getAdapter();
        $generator = EccFactory::getSecgCurves($math)->generator256k1();

        // if S is > half then we substract from N
        if (self::check_signature_is_high_s($signature)) {
            $n = $generator->getOrder();
            $signature = new Signature($signature->getR(), $math->sub($n, $signature->getS()));
        }

        $rBin = pack("H*", BitcoinLib::hex_encode($signature->getR()));
        $sBin = pack("H*", BitcoinLib::hex_encode($signature->getS()));

        // Pad R and S if their highest bit is flipped, ie,
        // they are negative.
        $rt = $rBin[0] & pack('H*', '80');
        if (ord($rt) == 128) {
            $rBin = pack('H*', '00') . $rBin;
        }

        $st = $sBin[0] & pack('H*', '80');
        if (ord($st) == 128) {
            $sBin = pack('H*', '00') . $sBin;
        }

        $r = bin2hex($rBin);
        $s = bin2hex($sBin);

        // Create the signature.
        $der_sig = '30'
            . self::_dec_to_bytes((4 + ((strlen($r) + strlen($s)) / 2)), 1)
            . '02'
            . self::pushdata($r)
            . '02'
            . self::pushdata($s)
            . '01';

        return $der_sig;
    }

    /**
     * Apply Sig: PubKeyHash
     *
     * This function simply takes a signature, $sig, and a $public_key,
     * and serializes them into a pay-to-pubkey-hash scriptSig.
     *
     * @param   string $sig
     * @param   string $public_key
     * @return  string
     */
    public static function _apply_sig_pubkeyhash($sig, $public_key)
    {
        return self::pushdata($sig) . self::pushdata($public_key);
    }

    /**
     * Apply Sig: ScriptHash Multisig
     *
     * This function applies, or generates, a sigScript for a multisig script hash
     * transaction. It uses the $script_info array, containing information about the
     * redeem Script, and $sig_array.
     * It loops through the keys in the script, checking if there exists a signature
     * by that key, applying them in the correct order. Finally serializes redeem
     * script into the scriptSig.
     *
     * @param   array $sig_array
     * @param   array $script_info
     * @return  string
     */
    public static function _apply_sig_scripthash_multisig($sig_array, $script_info)
    {
        $generated = '00';

        // Sig array is in order of the redeem script
        foreach ($script_info['public_keys'] as $key) {
            if (isset($sig_array[$key])) {
                $generated .= self::pushdata($sig_array[$key]);
            }
        }
        $generated .= self::pushdata($script_info['script']);

        return $generated;
    }

    /**
     * Decode Signature
     *
     * This function extracts the r and s parameters from a DER encoded
     * signature. No checking on the validity of the numbers.
     *
     * @param    string $signature
     * @return    array
     */
    public static function decode_signature($signature)
    {
        $math = EccFactory::getAdapter();

        $r_start = 8;
        $r_length = $math->hexDec(substr($signature, 6, 2)) * 2;
        $r_end = $r_start + $r_length;
        $r = substr($signature, $r_start, $r_length);

        $s_start = $r_end + 4;
        $s_length = $math->hexDec(substr($signature, ($r_end + 2), 2)) * 2;
        $s = substr($signature, $s_start, $s_length);
        return array('r' => $r,
            's' => $s,
            'hash_type' => substr($signature, -2),
            'last_byte_s' => substr($s, -2));
    }

    /**
     * Is Canonical Signature
     *
     * Performs some checking on the given $signature to see if it
     * it conforms to the standard.
     *
     * @param   string $signature
     * @return  bool
     */
    public static function is_canonical_signature($signature)
    {
        try {
            return self::check_canonical_signature($signature);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Canonical Signature
     *
     * Performs some checking on the given $signature to see if it
     * it conforms to the standard.
     *
     * Throw exception when it's non canonical.
     *
     * @param   string $signature
     * @return  bool
     */
    public static function check_canonical_signature($signature)
    {
        $signature = pack("H*", $signature);
        $length = strlen($signature);

        if ($length < 9) {
            throw new \InvalidArgumentException("Non-canonical signature: too short");
        }

        if ($length > 73) {
            throw new \InvalidArgumentException("Non-canonical signature: too long");
        }

        if (ord($signature[0]) !== 0x30) {
            throw new \InvalidArgumentException("Non-canonical signature: wrong type");
        }

        if (ord($signature[1]) !== $length - 3) {
            throw new \InvalidArgumentException("Non-canonical signature: wrong length marker");
        }

        $lenR   = ord($signature[3]);
        $r      = substr($signature, 4, $lenR);
        if (5 + $lenR >= $length) {
            throw new \InvalidArgumentException("Non-canonical signature: S length misplaced");
        }

        $lenS   = ord($signature[5 + $lenR]);
        $startS = 4 + $lenR + 2;
        $s      = substr($signature, $startS, $lenS);
        if (($lenR + $lenS + 7) !== $length) {
            throw new \InvalidArgumentException("Non-canonical signature: R+S length mismatched");
        }

        if (ord(substr($signature, 2, 1)) !== 0x02) {
            throw new \InvalidArgumentException("Non-canonical signature: R value type mismatch");
        }

        if ($lenR == 0) {
            throw new \InvalidArgumentException("Non-canonical signature: R length is zero");
        }

        $rAnd   = $r[0] & pack('H*', '80');
        if (ord($rAnd) == 128) {
            throw new \InvalidArgumentException("Non-canonical signature: R value negative");
        }

        if ($lenR > 1 && ord($r[0]) == 0x00 && !ord(($r[1] & pack('H*', '80')))) {
            throw new \InvalidArgumentException("Non-canonical signature: R value excessively padded");
        }

        if (ord(substr($signature, $startS - 2, 1)) !== 0x02) {
            throw new \InvalidArgumentException("Non-canonical signature: S value type mismatch");
        }

        if ($lenS == 0) {
            throw new \InvalidArgumentException("Non-canonical signature: S length is zero");
        }

        $sAnd   = $s[0] & pack('H*', '80');
        if (ord($sAnd) == 128) {
            throw new \InvalidArgumentException("Non-canonical signature: S value negative");
        }

        if ($lenS > 1 && ord($s[0]) == 0x00 && !ord(($s[1] & pack("H*", '80'))) == 0x80) {
            throw new \InvalidArgumentException("Non-canonical signature: S value excessively padded");
        }

        return true;

    }

    /**
     * Private Keys To Wallet
     *
     * This function accepts $wallet - a reference to an array containing
     * wallet info, indexed by hash160 of expected address.
     * It will attempt to add each key to this wallet, as well as all the
     * details that could be needed later on: public key, uncompressed key,
     * address, an indicator for address compression. Type is always set
     * to pubkeyhash for private key entries in the wallet.
     *
     * @param array  $wallet
     * @param array  $wifs
     * @param string $magic_byte
     */
    public static function private_keys_to_wallet(&$wallet, array $wifs, $magic_byte = null)
    {
        $magic_byte = BitcoinLib::magicByte($magic_byte);

        if (count($wifs) > 0) {
            foreach ($wifs as $wif) {
                if (is_array($wif) && isset($wif['key'], $wif['is_compressed'])) {
                    $key = $wif;
                } else {
                    $key = BitcoinLib::WIF_to_private_key($wif);
                }

                $pubkey = BitcoinLib::private_key_to_public_key($key['key'], $key['is_compressed']);
                if ($pubkey !== false) {
                    $pk_hash = BitcoinLib::hash160($pubkey);

                    if ($key['is_compressed'] == true) {
                        $uncompressed_key = BitcoinLib::decompress_public_key($pubkey);
                        $uncompressed_key = $uncompressed_key['public_key'];
                    } else {
                        $uncompressed_key = $pubkey;
                    }
                    $wallet[$pk_hash] = array('type' => 'pubkeyhash',
                        'private_key' => $key['key'],
                        'public_key' => $pubkey,
                        'uncompressed_key' => $uncompressed_key,
                        'is_compressed' => $key['is_compressed'],
                        'address' => BitcoinLib::hash160_to_address($pk_hash, $magic_byte)
                    );
                }
            }
        }
    }

    /**
     * Redeem Scripts To Wallet
     *
     * This function extends on whatever data is in the $wallet array, by
     * adding script hash addresses to the wallet, and linking keys in the
     * multisignature address with keys in the wallet.
     * Adds each redeemScript to the referenced $wallet.
     *
     * @param array $wallet
     * @param array $redeem_scripts
     * @param null  $magic_p2sh_byte
     */
    public static function redeem_scripts_to_wallet(&$wallet, array $redeem_scripts = array(), $magic_p2sh_byte = null)
    {
        $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

        if (count($redeem_scripts) > 0) {
            foreach ($redeem_scripts as $script) {
                $decode = self::decode_redeem_script($script);
                if ($decode == false) {
                    continue;
                }

                $scripthash = BitcoinLib::hash160($script);
                $keys = array();
                foreach ($decode['keys'] as $key) {
                    $keyhash = BitcoinLib::hash160($key);
                    if (isset($wallet[$keyhash])) {
                        $keys[] = $wallet[$keyhash];
                    }
                }

                $wallet[$scripthash] = array('type' => 'scripthash',
                    'script' => $script,
                    'required_signature_count' => $decode['m'],
                    'address' => BitcoinLib::hash160_to_address($scripthash, $magic_p2sh_byte),
                    'public_keys' => $decode['keys'],
                    'keys' => $keys);

            }
        }
    }
}
