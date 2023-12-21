<?php

namespace JsonRPC;

require_once __DIR__.'/../vendor/autoload.php';

function fopen($url, $mode, $use_include_path, $context)
{
    return HttpClientTest::$functions->fopen($url, $mode, $use_include_path, $context);
}

function stream_context_create(array $params)
{
    return HttpClientTest::$functions->stream_context_create($params);
}

class HttpClientTest extends \PHPUnit_Framework_TestCase
{
    public static $functions;

    public function setUp()
    {
        self::$functions = $this
            ->getMockBuilder('stdClass')
            ->setMethods(array('fopen', 'stream_context_create'))
            ->getMock();
    }

    public function testWithServerError()
    {
        $this->setExpectedException('\JsonRPC\Exception\ServerErrorException');

        $httpClient = new HttpClient();
        $httpClient->handleExceptions(array(
            'HTTP/1.0 301 Moved Permanently',
            'Connection: close',
            'HTTP/1.1 500 Internal Server Error',
        ));
    }

    public function testWithConnectionFailure()
    {
        $this->setExpectedException('\JsonRPC\Exception\ConnectionFailureException');

        $httpClient = new HttpClient();
        $httpClient->handleExceptions(array(
            'HTTP/1.1 404 Not Found',
        ));
    }

    public function testWithAccessForbidden()
    {
        $this->setExpectedException('\JsonRPC\Exception\AccessDeniedException');

        $httpClient = new HttpClient();
        $httpClient->handleExceptions(array(
            'HTTP/1.1 403 Forbidden',
        ));
    }

    public function testWithAccessNotAllowed()
    {
        $this->setExpectedException('\JsonRPC\Exception\AccessDeniedException');

        $httpClient = new HttpClient();
        $httpClient->handleExceptions(array(
            'HTTP/1.0 401 Unauthorized',
        ));
    }

    public function testWithCallback()
    {
        self::$functions
            ->expects($this->at(0))
            ->method('stream_context_create')
            ->with(array(
                'http' => array(
                    'method' => 'POST',
                    'protocol_version' => 1.1,
                    'timeout' => 5,
                    'max_redirects' => 2,
                    'header' => implode("\r\n", array(
                        'User-Agent: JSON-RPC PHP Client <https://github.com/fguillot/JsonRPC>',
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Connection: close',
                        'Content-Length: 4',
                    )),
                    'content' => 'test',
                    'ignore_errors' => true,
                ),
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                )
            ))
            ->will($this->returnValue('context'));

        self::$functions
            ->expects($this->at(1))
            ->method('fopen')
            ->with('url', 'r', false, 'context')
            ->will($this->returnValue(false));

        $httpClient = new HttpClient('url');
        $httpClient->withBeforeRequestCallback(function(HttpClient $client, $payload) {
            $client->withHeaders(array('Content-Length: '.strlen($payload)));
        });

        $this->setExpectedException('\JsonRPC\Exception\ConnectionFailureException');
        $httpClient->execute('test');
    }
}
