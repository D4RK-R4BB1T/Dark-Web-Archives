<?php namespace Jackiedo\LogReader\Entities;

use Jackiedo\LogReader\Contracts\LogParser;

/**
 * The LogContext class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogContext
{
    /**
     * Store message of the log context
     *
     * @var string
     */
    public $message;

    /**
     * Store exception in the log context
     *
     * @var string
     */
    public $exception;

    /**
     * Store location of the log context
     *
     * @var string
     */
    public $in;

    /**
     * Store the line in file
     *
     * @var int
     */
    public $line;

    /**
     * Store instance of LogParser for parsing content of the log context
     *
     * @var \Jackiedo\LogReader\LogParser
     */
    protected $parser;

    /**
     * Store original log context
     *
     * @var string
     */
    protected $content;

    /**
     * Create instance of log context
     *
     * @param  object  $parser
     * @param  string  $content
     *
     * @return void
     */
    public function __construct(LogParser $parser, $content)
    {
        $this->parser  = $parser;
        $this->content = $content;

        $this->assignAttributes();
    }

    /**
     * Return content if the log context is used as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * Parses content of the log context and assigns each information
     * to the corresponding attribute in log context
     *
     * @return void
     */
    protected function assignAttributes()
    {
        $parsed = $this->parser->parseLogContext($this->content);

        foreach ($parsed as $key => $value) {
            $this->{$key} = str_replace('\\\\', '\\', $value);
        }
    }
}
