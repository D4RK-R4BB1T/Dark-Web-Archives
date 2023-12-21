<?php namespace Jackiedo\LogReader\Contracts;

/**
 * The Levelable interface.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
interface Levelable
{
    /**
     * Filter logs by level
     *
     * @param  string $level   Level need to check
     * @param  array  $allowed Strict levels to filter
     *
     * @return bool
     */
    public function filter($level, $allowed);
}
