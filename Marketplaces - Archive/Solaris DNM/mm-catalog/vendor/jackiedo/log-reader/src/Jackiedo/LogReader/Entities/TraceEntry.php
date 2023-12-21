<?php namespace Jackiedo\LogReader\Entities;

use Jackiedo\LogReader\Contracts\LogParser;

/**
 * The TraceEntry class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class TraceEntry
{
    /**
     * Store information when the trace entry is being recorded
     *
     * @var string
     */
    public $caught_at;

    /**
     * Store information about where the trace entry is being recorded
     *
     * @var string
     */
    public $in;

    /**
     * Store the line in file if location of the trace entry if path of file
     *
     * @var string
     */
    public $line;

    /**
     * Store instance of LogParser for parsing content of the trace entry
     *
     * @var \Jackiedo\LogReader\LogParser
     */
    protected $parser;

    /**
     * Store original trace content
     *
     * @var string
     */
    protected $content;

    /**
     * Create instance of trace entry
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
     * Return content if the trace entry is used as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * Parses content of the trace entry and assigns each information
     * to the corresponding attribute in the trace entry.
     *
     * @return void
     */
    protected function assignAttributes()
    {
        $parsed = $this->parser->parseTraceEntry($this->content);

        foreach ($parsed as $key => $value) {
            $this->{$key} = str_replace('\\\\', '\\', (string) $value);
        }
    }
}
