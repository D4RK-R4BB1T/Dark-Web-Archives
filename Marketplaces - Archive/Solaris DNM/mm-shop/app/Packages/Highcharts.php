<?php
/**
 * File: Highcharts.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


use GuzzleHttp\Client;

class Highcharts
{
    /** @var string */
    private $_host;

    /** @var int */
    private $_port;

    /** @var Client */
    private $_client;

    public function __construct($host, $port)
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_client = new Client();
    }

    public function drawGraph($json)
    {
        $highchartsUrl = 'http://' . $this->_host . ':' . $this->_port . '/';
        $response = $this->_client->post($highchartsUrl, [
            'json' => $json,
        ]);

        return $response->getBody()->getContents();
    }
}