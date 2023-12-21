<?php namespace Jackiedo\LogReader\Entities;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use Jackiedo\LogReader\Contracts\LogParser;

/**
 * The LogEntry class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogEntry
{
    /**
     * The Unique ID of the log entry.
     *
     * @var string
     */
    public $id;

    /**
     * The date of the log entry.
     *
     * @var \Carbon\Carbon
     */
    public $date;

    /**
     * The environment of the log entry.
     *
     * @var string
     */
    public $environment;

    /**
     * The level of the log entry.
     *
     * @var string
     */
    public $level;

    /**
     * The path to the log file containing the log entry.
     *
     * @var string
     */
    public $file_path;

    /**
     * The context of the log entry.
     *
     * @var \Jackiedo\LogReader\Entities\LogContext
     */
    public $context;

    /**
     * The stack trace entries of the log entry.
     * Each trace entry is an instance of
     * \Jackiedo\LogReader\Entities\TraceEntry
     *
     * @var \Illuminate\Support\Collection
     */
    public $stack_traces;

    /**
     * Store instance of LogParser for parsing content of the log entry
     *
     * @var \Jackiedo\LogReader\LogParser
     */
    protected $parser;

    /**
     * Store instance of Cache Repository for caching.
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * The original attributes of the log entry.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Constructs a new entry object with the specified attributes.
     *
     * @param  object  $parser
     * @param  object  $cache
     * @param  array   $attributes
     *
     * @return void
     */
    public function __construct(LogParser $parser, Cache $cache, $attributes = [])
    {
        $this->parser = $parser;
        $this->cache  = $cache;

        $this->setAttributes($attributes);
        $this->assignAttributes();
    }

    /**
     * Magic accessor
     *
     * @param  string  $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        return $this->getAttribute($property);
    }

    /**
     * Retrieves an attribute of the log entry
     *
     * @param  $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        $key = $this->reFormatForCompatibility($key);

        return (property_exists($this, $key)) ? $this->{$key} : null;
    }

    /**
     * Get original value of an property of the log entry
     *
     * @param  string  $key
     *
     * @return void
     */
    public function getOriginal($key)
    {
        $key = $this->reFormatForCompatibility($key);

        return (array_key_exists($key, $this->attributes)) ? $this->attributes[$key] : null;
    }

    /**
     * Stores the log entry in the cache so it is no longer shown
     * in the log results.
     *
     * @return mixed
     */
    public function markAsRead()
    {
        return $this->cache->rememberForever($this->makeCacheKey(), function () {
            return $this->getRawContent();
        });
    }

    /**
     * Alias of the markAsRead() method.
     *
     * @return mixed
     */
    public function markRead()
    {
        return $this->markAsRead();
    }

    /**
     * Returns true/false depending if the log entry
     * has been marked read (exists inside the cache).
     *
     * @return bool
     */
    public function isRead()
    {
        if ($this->cache->has($this->makeCacheKey())) {
            return true;
        }

        return false;
    }

    /**
     * Removes the current entry from the log file.
     *
     * @return bool
     */
    public function delete()
    {
        $rawContent = $this->getRawContent();
        $filePath   = $this->attributes['file_path'];
        $logContent = file_get_contents($filePath);
        $logContent = str_replace($rawContent, '', $logContent);

        file_put_contents($filePath, $logContent);

        return true;
    }

    /**
     * Return raw content of the log entry.
     *
     * @return string
     */
    public function getRawContent()
    {
        return $this->attributes['header'].' '.$this->attributes['body'];
    }

    /**
     * Returns a compressed entry context suitable to
     * be used as the log entry's ID.
     *
     * @return string
     */
    protected function generateId()
    {
        return md5($this->getRawContent());
    }

    /**
     * Returns a key string for storing the log entry
     * inside the cache.
     *
     * @return string
     */
    protected function makeCacheKey()
    {
        return 'log'.$this->id;
    }

    /**
     * Sets the log entry's ID property.
     *
     * @param  $id
     *
     * @return void
     */
    protected function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets the log entry's date property.
     *
     * @param  string  $date
     *
     * @return void
     */
    protected function setDate($date = null)
    {
        if ($date) {
            $this->date = Carbon::createFromFormat('Y-m-d H:i:s', $date);
        }
    }

    /**
     * Sets the log entry's environment property.
     *
     * @param  string  $environment
     *
     * @return void
     */
    protected function setEnvironment($environment = null)
    {
        if ($environment) {
            $this->environment = strtolower($environment);
        }
    }

    /**
     * Sets the log entry's level property.
     *
     * @param  string  $level
     *
     * @return void
     */
    protected function setLevel($level = null)
    {
        if ($level) {
            $this->level = strtolower($level);
        }
    }

    /**
     * Sets the log entry's file_path property.
     *
     * @param  string  $path
     *
     * @return void
     */
    protected function setFilePath($path = null)
    {
        if ($path) {
            $this->file_path = realpath($path);
        }
    }

    /**
     * Sets the log entry's context property.
     *
     * @param  string  $context
     *
     * @return void
     */
    protected function setContext($context = null)
    {
        $this->context = new LogContext($this->parser, $context);
    }

    /**
     * Sets the log entry's level property.
     *
     * @param  $stackTraces
     *
     * @return void
     */
    protected function setStackTraces($stackTraces = null)
    {
        $traces = $this->parser->parseStackTrace($stackTraces);

        $output = [];

        foreach ($traces as $trace) {
            $output[] = new TraceEntry($this->parser, $trace);
        }

        $this->stack_traces = new Collection($output);
    }

    /**
     * Sets the attributes property.
     *
     * @param  array  $attributes
     *
     * @return void
     */
    protected function setAttributes($attributes = [])
    {
        if (is_array($attributes)) {
            $this->attributes = $attributes;
        }
    }

    /**
     * Assigns the valid keys in the attributes array
     * to the properties in the log entry.
     *
     * @return void
     */
    protected function assignAttributes()
    {
        $bodyParsed                       = $this->parser->parseLogBody($this->attributes['body']);
        $this->attributes['context']      = $bodyParsed['context'];
        $this->attributes['stack_traces'] = $bodyParsed['stack_traces'];

        $this->setId($this->generateId());
        $this->setDate($this->attributes['date']);
        $this->setEnvironment($this->attributes['environment']);
        $this->setLevel($this->attributes['level']);
        $this->setFilePath($this->attributes['file_path']);
        $this->setContext($this->attributes['context']);
        $this->setStackTraces($this->attributes['stack_traces']);
    }

    /**
     * Convert the property strings to be compatible with older version
     *
     * @param  string $property
     *
     * @return string
     */
    protected function reFormatForCompatibility($property)
    {
        switch (true) {
            case ($property == 'header'):
                $property = 'context';
                break;

            case ($property == 'stack'):
                $property = 'stack_traces';
                break;

            case ($property == 'filePath'):
                $property = 'file_path';
                break;

            default:
                # code...
                break;
        }

        return $property;
    }
}
