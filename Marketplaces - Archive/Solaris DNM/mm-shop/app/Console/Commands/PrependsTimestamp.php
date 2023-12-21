<?php

namespace App\Console\Commands;

trait PrependsTimestamp
{
    protected function getPrependString($string)
    {
        return date(property_exists($this, 'outputTimestampFormat') ?
                $this->outputTimestampFormat : '[Y-m-d H:i:s]').' ';
    }
}
