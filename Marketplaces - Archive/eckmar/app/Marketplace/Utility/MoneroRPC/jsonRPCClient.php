<?php

namespace App\Marketplace\Utility\MoneroRPC;

use App\Marketplace\Utility\MoneroRPC\Exceptions\InvalidArgumentException;
use App\Marketplace\Utility\MoneroRPC\Exceptions\RuntimeException;

class jsonRPCClient
{
    protected $url = null, $is_debug = false, $parameters_structure = 'array';
    private $username;
    private $password;
    protected $curl_options = array(
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 8
    );


    private $httpErrors = array(
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        408 => '408 Request Timeout',
        500 => '500 Internal Server Error',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable'
    );

    public function __construct($pUrl, $pUser, $pPass) {

        $this->validate(false === extension_loaded('curl'), 'The curl extension must be loaded to use this class!');
        $this->validate(false === extension_loaded('json'), 'The json extension must be loaded to use this class!');

        $this->url = $pUrl;
        $this->username = $pUser;
        $this->password = $pPass;
    }

    private function getHttpErrorMessage($pErrorNumber) {
        return isset($this->httpErrors[$pErrorNumber]) ? $this->httpErrors[$pErrorNumber] : null;
    }

    public function setDebug($pIsDebug) {
        $this->is_debug = !empty($pIsDebug);
        return $this;
    }

    /* public function setParametersStructure($pParametersStructure)
    {
        if (in_array($pParametersStructure, array('array', 'object')))
        {
            $this->parameters_structure = $pParametersStructure;
        }
        else
        {
            throw new UnexpectedValueException('Invalid parameters structure type.');
        }
        return $this;
    } */

    public function setCurlOptions($pOptionsArray) {
        if (is_array($pOptionsArray)) {
            $this->curl_options = $pOptionsArray + $this->curl_options;
        } else {
            throw new InvalidArgumentException('Invalid options type.');
        }
        return $this;
    }

    public function _run($pMethod, $pParams) {
        static $requestId = 0;
        // generating unique id per process
        $requestId++;
        // check if given params are correct
        $this->validate(false === is_scalar($pMethod), 'Method name has no scalar value');
        // $this->validate(false === is_array($pParams), 'Params must be given as array');
        // send params as an object or an array
        //$pParams = ($this->parameters_structure == 'object') ? $pParams[0] : array_values($pParams);
        // Request (method invocation)
        $request = json_encode(array('jsonrpc' => '2.0', 'method' => $pMethod, 'params' => $pParams, 'id' => $requestId));
        // if is_debug mode is true then add url and request to is_debug
        $this->debug('Url: ' . $this->url . "\r\n", false);
        $this->debug('Request: ' . $request . "\r\n", false);
        $responseMessage = $this->getResponse($request);
        // if is_debug mode is true then add response to is_debug and display it
        $this->debug('Response: ' . $responseMessage . "\r\n", true);
        // decode and create array ( can be object, just set to false )
        $responseDecoded = json_decode($responseMessage, true);
        // check if decoding json generated any errors
        $jsonErrorMsg = $this->getJsonLastErrorMsg();
        $this->validate(!is_null($jsonErrorMsg), $jsonErrorMsg . ': ' . $responseMessage);
        // check if response is correct
        $this->validate(empty($responseDecoded['id']), 'Invalid response data structure: ' . $responseMessage);
        $this->validate($responseDecoded['id'] != $requestId, 'Request id: ' . $requestId . ' is different from Response id: ' . $responseDecoded['id']);
        if (isset($responseDecoded['error'])) {
            $errorMessage = 'Request have return error: ' . $responseDecoded['error']['message'] . '; ' . "\n" .
                'Request: ' . $request . '; ';
            if (isset($responseDecoded['error']['data'])) {
                $errorMessage .= "\n" . 'Error data: ' . $responseDecoded['error']['data'];
            }
            $this->validate(!is_null($responseDecoded['error']), $errorMessage);
        }
        return $responseDecoded['result'];
    }

    protected function & getResponse(&$pRequest) {
        // do the actual connection
        $ch = curl_init();
        if (!$ch) {
            throw new RuntimeException('Could\'t initialize a cURL session');
        }
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!curl_setopt_array($ch, $this->curl_options)) {
            throw new RuntimeException('Error while setting curl options');
        }
        // send the request
        $response = curl_exec($ch);
        // check http status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (isset($this->httpErrors[$httpCode])) {
            throw new RuntimeException('Response Http Error - ' . $this->httpErrors[$httpCode]);
        }
        // check for curl error
        if (0 < curl_errno($ch)) {
            throw new RuntimeException('Unable to connect to ' . $this->url . ' Error: ' . curl_error($ch));
        }
        // close the connection
        curl_close($ch);
        return $response;
    }

    public function validate($pFailed, $pErrMsg) {
        if ($pFailed) {
            throw new RuntimeException($pErrMsg);
        }
    }

    protected function debug($pAdd, $pShow = false) {
        static $debug, $startTime;
        // is_debug off return
        if (false === $this->is_debug) {
            return;
        }
        // add
        $debug .= $pAdd;
        // get starttime
        $startTime = empty($startTime) ? array_sum(explode(' ', microtime())) : $startTime;
        if (true === $pShow and !empty($debug)) {
            // get endtime
            $endTime = array_sum(explode(' ', microtime()));
            // performance summary
            $debug .= 'Request time: ' . round($endTime - $startTime, 3) . ' s Memory usage: ' . round(memory_get_usage() / 1024) . " kb\r\n";
            echo nl2br($debug);
            // send output immediately
            flush();
            // clean static
            $debug = $startTime = null;
        }
    }

    function getJsonLastErrorMsg() {
        if (!function_exists('json_last_error_msg')) {
            function json_last_error_msg() {
                static $errors = array(
                    JSON_ERROR_NONE => 'No error',
                    JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                    JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
                    JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
                    JSON_ERROR_SYNTAX => 'Syntax error',
                    JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
                );
                $error = json_last_error();
                return array_key_exists($error, $errors) ? $errors[$error] : 'Unknown error (' . $error . ')';
            }
        }

        // Fix PHP 5.2 error caused by missing json_last_error function
        if (function_exists('json_last_error')) {
            return json_last_error() ? json_last_error_msg() : null;
        } else {
            return null;
        }
    }
}
