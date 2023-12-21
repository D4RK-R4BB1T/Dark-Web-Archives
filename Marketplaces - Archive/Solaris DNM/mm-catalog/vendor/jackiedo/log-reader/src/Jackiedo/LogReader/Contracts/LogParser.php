<?php namespace Jackiedo\LogReader\Contracts;

/**
 * The LogParser interface.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
interface LogParser
{
    /**
     * Parses content of the log file into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['headerSet' => [], 'dateSet' => [], 'envSet' => [], 'levelSet' => [], 'bodySet' => []]
     */
    public function parseLogContent($content);

    /**
     * Parses the body part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['context' => '', 'stack_traces' => '']
     */
    public function parseLogBody($content);

    /**
     * Parses the context part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['message' => '', 'exception' => '', 'in' => '', 'line' => '']
     */
    public function parseLogContext($content);

    /**
     * Parses the stack trace part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array
     */
    public function parseStackTrace($content);

    /**
     * Parses the content of the trace entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['caught_at' => '', 'in' => '', 'line' => '']
     */
    public function parseTraceEntry($content);
}
