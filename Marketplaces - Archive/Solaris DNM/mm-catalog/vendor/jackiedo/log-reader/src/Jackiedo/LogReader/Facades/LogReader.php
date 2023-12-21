<?php namespace Jackiedo\LogReader\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The LogReader class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogReader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log-reader';
    }
}
