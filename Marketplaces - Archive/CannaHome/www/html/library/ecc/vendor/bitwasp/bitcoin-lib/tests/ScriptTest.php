<?php

use BitWasp\BitcoinLib\RawTransaction;

class ScriptTest extends PHPUnit_Framework_TestCase
{
    public function testPushData()
    {
        $data = json_decode(file_get_contents(__DIR__ . "/data/pushdata.json"), true);

        foreach ($data as $row) {

            $script = '';
            array_map(
                function ($value) use (&$script) {
                    $script .= RawTransaction::pushdata($value);
                },
                $row['pushes']
            );

            $this->assertSame($row['script'], $script);
        }
    }

    public function testPushDataOps()
    {
        $data = json_decode(file_get_contents(__DIR__ . "/data/pushdataops.json"), true);

        foreach ($data as $row) {
            $script = RawTransaction::pushdata($row['string']);
            $op = substr($script, 0, 2);
            $this->assertSame($row['op'], $op);
        }
    }
}
