<?php namespace Jackiedo\LogReader;

use Jackiedo\LogReader\Contracts\LogParser as LogParserInterface;

/**
 * The LogParser class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogParser implements LogParserInterface
{
    const LOG_DATE_PATTERN            = "\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]";
    const LOG_ENVIRONMENT_PATTERN     = "(\w+)";
    const LOG_LEVEL_PATTERN           = "([A-Z]+)";
    const CONTEXT_MESSAGE_PATTERN     = "([^\{]*)?";
    const CONTEXT_EXCEPTION_PATTERN   = "(\{\"exception\"\:\"\[object\]\s\(([^\s\(]+))?.*";
    const CONTEXT_IN_PATTERN          = "\s(in|at)\s(.*)\:(\d+)\)?";
    const STACK_TRACE_DIVIDER_PATTERN = "(\[stacktrace\]|Stack trace\:)";
    const STACK_TRACE_INDEX_PATTERN   = "\#\d+\s";
    const TRACE_IN_DIVIDER_PATTERN    = "\:\s";
    const TRACE_FILE_PATTERN          = "(.*)\((\d+)\)";

    /**
     * Parses content of the log file into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['headerSet' => [], 'dateSet' => [], 'envSet' => [], 'levelSet' => [], 'bodySet' => []]
     */
    public function parseLogContent($content)
    {
        $headerSet = $dateSet = $envSet = $levelSet = $bodySet = [];

        $pattern = "/^" .self::LOG_DATE_PATTERN. "\s" .self::LOG_ENVIRONMENT_PATTERN. "\." .self::LOG_LEVEL_PATTERN. "\:|Next/m";

        preg_match_all($pattern, $content, $matchs);

        if (is_array($matchs)) {
            $bodySet = array_map('ltrim', preg_split($pattern, $content));

            if (empty($bodySet[0]) && count($bodySet) > count($matchs[0])) {
                array_shift($bodySet);
            }

            $headerSet = $matchs[0];
            $dateSet   = $matchs[1];
            $envSet    = $matchs[2];
            $levelSet  = $matchs[3];
            $bodySet   = $bodySet;
        }

        return compact('headerSet', 'dateSet', 'envSet', 'levelSet', 'bodySet');
    }

    /**
     * Parses the body part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['context' => '', 'stack_traces' => '']
     */
    public function parseLogBody($content)
    {
        $pattern      = "/^".self::STACK_TRACE_DIVIDER_PATTERN."/m";
        $parts        = array_map('ltrim', preg_split($pattern, $content));
        $context      = $parts[0];
        $stack_traces = (isset($parts[1])) ? $parts[1] : null;

        // Delete the last unnecessary line of stack_traces
        $stack_traces = preg_match("/^(.*)\"\}\s*$/ms", $stack_traces, $match) ? $match[1] : $stack_traces;

        return compact('context', 'stack_traces');
    }

    /**
     * Parses the context part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['message' => '', 'exception' => '', 'in' => '', 'line' => '']
     */
    public function parseLogContext($content)
    {
        $content = trim($content);
        $pattern = "/^".self::CONTEXT_MESSAGE_PATTERN.self::CONTEXT_EXCEPTION_PATTERN.self::CONTEXT_IN_PATTERN."$/ms";

        preg_match($pattern, $content, $matchs);

        $message   = isset($matchs[1]) ? trim($matchs[1]) : trim($content);
        $exception = isset($matchs[2]) ? trim($matchs[3]) : null;
        $in        = isset($matchs[5]) ? trim($matchs[5]) : null;
        $line      = isset($matchs[6]) ? trim($matchs[6]) : null;

        // if exception is not exist, it may be placed before message
        if (! $exception) {
            $pattern = "/^((exception\s\')?([^\s\']+)(\'|\:))?(\swith\smessage\s)?(.*)$/ms";

            unset($matchs);
            preg_match($pattern, $message, $matchs);

            $exception = isset($matchs[1]) ? trim($matchs[3]) : null;
            $message   = isset($matchs[6]) ? trim($matchs[6]) : trim($content);
            $message   = preg_match("/^\'(.*)\'$/ms", $message, $trimmedQuote) ? $trimmedQuote[1] : $message;
        }

        return compact('message', 'exception', 'in', 'line');
    }

    /**
     * Parses the stack trace part of the log entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array
     */
    public function parseStackTrace($content)
    {
        $content = trim($content);
        $pattern = "/^".self::STACK_TRACE_INDEX_PATTERN."/m";

        if (empty($content)) {
            return [];
        }

        $traces = preg_split($pattern, $content);

        if (empty($traces[0])) {
            array_shift($traces);
        }

        return $traces;
    }

    /**
     * Parses the content of the trace entry into an array containing the necessary information
     *
     * @param  string  $content
     *
     * @return array   Structure is ['caught_at' => '', 'in' => '', 'line' => '']
     */
    public function parseTraceEntry($content)
    {
        $content = trim($content);

        $caught_at = $content;
        $in = $line = null;

        if (!empty($content) && preg_match("/.*".self::TRACE_IN_DIVIDER_PATTERN.".*/", $content)) {
            $split = array_map('trim', preg_split("/".self::TRACE_IN_DIVIDER_PATTERN."/", $content));

            $in        = trim($split[0]);
            $caught_at = (isset($split[1])) ? $split[1] : null;

            if (preg_match("/^".self::TRACE_FILE_PATTERN."$/", $in, $matchs)) {
                $in   = trim($matchs[1]);
                $line = $matchs[2];
            }
        }

        return compact('caught_at', 'in', 'line');
    }
}
