<?php

use JsonRPC\Validator\HostValidator;

require_once __DIR__.'/../../vendor/autoload.php';

class HostValidatorTest extends PHPUnit_Framework_TestCase
{
    public function testWithEmptyHosts()
    {
        $this->assertNull(HostValidator::validate(array(), '127.0.0.1', '127.0.0.1'));
    }

    public function testWithValidHosts()
    {
        $this->assertNull(HostValidator::validate(array('127.0.0.1'), '127.0.0.1', '127.0.0.1'));
    }

    public function testWithNotAuthorizedHosts()
    {
        $this->setExpectedException('\JsonRPC\Exception\AccessDeniedException');
        HostValidator::validate(array('192.168.1.1'), '127.0.0.1', '127.0.0.1');
    }
}
