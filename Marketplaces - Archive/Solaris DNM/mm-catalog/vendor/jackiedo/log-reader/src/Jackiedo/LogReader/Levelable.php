<?php namespace Jackiedo\LogReader;

use Jackiedo\LogReader\Contracts\Levelable as LevelableInterface;

/**
 * The Levelable class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class Levelable implements LevelableInterface
{
    /**
     * The log accepted levels.
     *
     * @var array
     */
    protected $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    /**
     * Get log accepted levels
     *
     * @return array
     */
    public function getAcceptedLevels()
    {
        return $this->levels;
    }

    /**
     * Filter logs by level
     *
     * @param  string $level   Level need to check
     * @param  array  $allowed Strict levels to filter
     *
     * @return bool
     */
    public function filter($level, $allowed)
    {
        if (empty($allowed)) {
            return true;
        }

        if (is_array($allowed)) {
            $merges = array_values(array_uintersect($this->levels, $allowed, "strcasecmp"));
            if (in_array(strtolower($level), $merges)) {
                return true;
            }
        }

        return false;
    }
}
