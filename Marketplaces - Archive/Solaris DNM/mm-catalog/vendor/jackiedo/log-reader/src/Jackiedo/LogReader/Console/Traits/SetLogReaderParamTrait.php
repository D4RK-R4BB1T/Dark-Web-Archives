<?php namespace Jackiedo\LogReader\Console\Traits;

trait SetLogReaderParamTrait
{
    /**
     * Set parameters for LogReader
     *
     * @return void
     */
    protected function setLogReaderParam()
    {
        if (array_key_exists('log-path', $this->option()) && ! empty($this->option('log-path'))) {
            $this->reader->setLogPath($this->option('log-path'));
        }

        if (array_key_exists('order-by', $this->option()) && ! empty($this->option('order-by'))) {
            if (array_key_exists('order-direction', $this->option()) && ! empty($this->option('order-direction'))) {
                $this->reader->orderBy($this->option('order-by'), $this->option('order-direction'));
            } else {
                $this->reader->orderBy($this->option('order-by'));
            }
        }

        if (array_key_exists('with-read', $this->option()) && $this->option('with-read')) {
            $this->reader->withRead();
        }

        if (array_key_exists('file-name', $this->option())) {
            $this->reader->filename($this->option('file-name'));
        }

        if (array_key_exists('env', $this->option())) {
            $this->reader->environment($this->option('env'));
        }

        if (array_key_exists('level', $this->option())) {
            $this->reader->level($this->option('level'));
        }
    }
}
