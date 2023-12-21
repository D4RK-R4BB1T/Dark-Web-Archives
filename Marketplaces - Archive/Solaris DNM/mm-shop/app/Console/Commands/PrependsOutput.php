<?php

namespace App\Console\Commands;

trait PrependsOutput
{
    public function comment($string, $style = NULL, $verbosity = NULL)
    {
        parent::comment($this->prepend($string), $style, $verbosity);
    }

    public function error($string, $style = NULL, $verbosity = NULL)
    {
        parent::error($this->prepend($string), $style, $verbosity);
    }

    public function info($string, $style = NULL, $verbosity = NULL)
    {
        parent::info($this->prepend($string), $style, $verbosity);
    }

    public function warn($string, $style = NULL, $verbosity = NULL)
    {
        parent::warn($this->prepend($string), $style, $verbosity);
    }

    protected function prepend($string)
    {
        if (method_exists($this, 'getPrependString')) {
            return $this->getPrependString($string).$string;
        }

        return $string;
    }
}
