<?php namespace Jackiedo\LogReader;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Jackiedo\LogReader\Contracts\LogParser as LogParserInterface;
use Jackiedo\LogReader\Entities\LogEntry;
use Jackiedo\LogReader\Exceptions\UnableToRetrieveLogFilesException;
use Jackiedo\LogReader\Levelable;

/**
 * The LogReader class.
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogReader
{
    /**
     * Store instance of Cache Repository for caching
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * Store instance of Config Repository for working with config
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Store instance of Request for getting request input
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Store instance of LogParser for parsing content of the log file
     *
     * @var \Jackiedo\LogReader\LogParser
     */
    protected $parser;

    /**
     * Store instance of Levelable to filter logs entry by level
     *
     * @var \Jackiedo\LogReader\Levelable
     */
    protected $levelable;

    /**
     * Stores the current environment to sort the log entries.
     *
     * @var string
     */
    protected $environment = null;

    /**
     * Stores the current level to sort the log entries.
     *
     * @var null|array
     */
    protected $level = null;

    /**
     * The path to directory storing the log files.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Stores the filename to search log files for.
     *
     * @var string
     */
    protected $filename = '';

    /**
     * The current log file path.
     *
     * @var string
     */
    protected $currentLogPath = '';

    /**
     * Stores the field to order the log entries in.
     *
     * @var string
     */
    protected $orderByField = '';

    /**
     * Stores the direction to order the log entries in.
     *
     * @var string
     */
    protected $orderByDirection = '';

    /**
     * Stores the bool whether or not to return read entries.
     *
     * @var bool
     */
    protected $includeRead = false;

    /**
     * Construct a new instance and set attributes.
     *
     * @param  object  $cache
     * @param  object  $config
     * @param  object  $request
     *
     * @return void
     */
    public function __construct(Cache $cache, Config $config, Request $request)
    {
        $this->cache     = $cache;
        $this->config    = $config;
        $this->request   = $request;
        $this->levelable = new Levelable;
        $this->parser    = new LogParser;

        $defaultParserClass = (string) $this->config->get('log-reader.default_log_parser', null);

        if (class_exists($defaultParserClass)) {
            $logParser = new $defaultParserClass;

            if ($logParser instanceof LogParserInterface) {
                $this->parser = new $logParser;
            }
        }

        $this->setLogPath($this->config->get('log-reader.path', storage_path('logs')));
        $this->setLogFilename($this->config->get('log-reader.filename', 'laravel.log'));
        $this->setEnvironment($this->config->get('log-reader.environment'));
        $this->setLevel($this->config->get('log-reader.level'));
        $this->setOrderByField($this->config->get('log-reader.order_by_field', ''));
        $this->setOrderByDirection($this->config->get('log-reader.order_by_direction', ''));
    }

    /**
     * Sets the path to directory storing the log files.
     *
     * @param  string  $path
     *
     * @return void
     */
    public function setLogPath($path)
    {
        $this->path = $path;
    }

    /**
     * Setting the parser for structural analysis
     *
     * @param  object $parser
     *
     * @return void
     */
    public function setLogParser(LogParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Get instance of Levelable
     *
     * @return \Jackiedo\LogReader\Levelable
     */
    public function getLevelable()
    {
        return $this->levelable;
    }

    /**
     * Retrieves the orderByField property.
     *
     * @return string
     */
    public function getOrderByField()
    {
        return $this->orderByField;
    }

    /**
     * Retrieves the orderByDirection property.
     *
     * @return string
     */
    public function getOrderByDirection()
    {
        return $this->orderByDirection;
    }

    /**
     * Retrieves the environment property.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Retrieves the level property.
     *
     * @return array
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Retrieves the currentLogPath property.
     *
     * @return string
     */
    public function getCurrentLogPath()
    {
        return $this->currentLogPath;
    }

    /**
     * Retrieves the path to directory storing the log files.
     *
     * @return string
     */
    public function getLogPath()
    {
        return $this->path;
    }

    /**
     * Retrieves the log filename property.
     *
     * @return string
     */
    public function getLogFilename()
    {
        return $this->filename;
    }

    /**
     * Sets the environment to sort the log entries by.
     *
     * @param  string  $environment
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function environment($environment)
    {
        $this->setEnvironment($environment);

        return $this;
    }

    /**
     * Sets the level to sort the log entries by.
     *
     * @param  mixed  $level
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function level($level)
    {
        if (empty($level)) {
            $level = [];
        } elseif (is_string($level)) {
            $level = explode(',', str_replace(' ', '', $level));
        } else {
            $level = is_array($level) ? $level : func_get_args();
        }

        $this->setLevel($level);

        return $this;
    }

    /**
     * Sets the filename to get log entries.
     *
     * @param  string  $filename
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function filename($filename)
    {
        $this->setLogFilename($filename);

        return $this;
    }

    /**
     * Includes read entries in the log results.
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function withRead()
    {
        $this->setIncludeRead(true);

        return $this;
    }

    /**
     * Alias of the withRead() method.
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function includeRead()
    {
        return $this->withRead();
    }

    /**
     * Sets the direction to return the log entries in.
     *
     * @param  string  $field
     * @param  string  $direction
     *
     * @return \Jackiedo\LogReader\LogReader
     */
    public function orderBy($field, $direction = 'asc')
    {
        $this->setOrderByField($field);
        $this->setOrderByDirection($direction);

        return $this;
    }

    /**
     * Returns a Laravel collection of log entries.
     *
     * @throws \Jackiedo\LogReader\Exceptions\UnableToRetrieveLogFilesException
     *
     * @return Collection
     */
    public function get()
    {
        $entries = [];

        $files = $this->getLogFiles();

        if (! is_array($files)) {
            throw new UnableToRetrieveLogFilesException('Unable to retrieve files from path: '.$this->getLogPath());
        }

        foreach ($files as $log) {
            /*
             * Set the current log path for easy manipulation
             * of the file if needed
             */
            $this->setCurrentLogPath($log['path']);

            /*
             * Parse the log into an array of entries, passing in the level
             * so it can be filtered
             */
            $parsedLog = $this->parseLog($log['contents'], $this->getEnvironment(), $this->getLevel());

            /*
             * Create a new LogEntry object for each parsed log entry
             */
            foreach ($parsedLog as $entry) {
                $newEntry = new LogEntry($this->parser, $this->cache, $entry);

                /*
                 * Check if the entry has already been read,
                 * and if read entries should be included.
                 *
                 * If includeRead is false, and the entry is read,
                 * then continue processing.
                 */
                if (!$this->includeRead && $newEntry->isRead()) {
                    continue;
                }

                $entries[$newEntry->id] = $newEntry;
            }
        }

        return $this->postCollectionModifiers(new Collection($entries));
    }

    /**
     * Returns total of log entries.
     *
     * @return int
     */
    public function count()
    {
        return $this->get()->count();
    }

    /**
     * Finds a logged error by it's ID.
     *
     * @param  string  $id
     *
     * @return mixed|null
     */
    public function find($id = '')
    {
        return $this->get()->get($id);
    }

    /**
     * Marks all retrieved log entries as read and
     * returns the number of entries that have been marked.
     *
     * @return int
     */
    public function markAsRead()
    {
        $entries = $this->get();

        $count = 0;

        foreach ($entries as $entry) {
            if ($entry->markAsRead()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Alias of the markAsRead() method.
     *
     * @return int
     */
    public function markRead()
    {
        return $this->markAsRead();
    }

    /**
     * Deletes all retrieved log entries and returns
     * the number of entries that have been deleted.
     *
     * @return int
     */
    public function delete()
    {
        $entries = $this->get();

        $count = 0;

        foreach ($entries as $entry) {
            if ($entry->delete()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Deletes all retrieved log entries and returns
     * the number of entries that have been deleted.
     *
     * @return int
     */
    public function removeLogFile()
    {
        $files = $this->getLogFileList();

        $count = 0;

        foreach ($files as $file) {
            if (@unlink($file)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Paginates the returned log entries.
     *
     * @param  int    $perPage
     * @param  int    $currentPage
     * @param  array  $options  [path => '', query => [], fragment => '', pageName => '']
     *
     * @return mixed
     */
    public function paginate($perPage = 25, $currentPage = null, array $options = [])
    {
        $currentPage = $this->getPageFromInput($currentPage, $options);
        $offset      = ($currentPage - 1) * $perPage;
        $total       = $this->count();
        $entries     = $this->get()->slice($offset, $perPage)->all();

        return new LengthAwarePaginator($entries, $total, $perPage, $currentPage, $options);
    }

    /**
     * Returns an array of log filenames.
     *
     * @param  null|string  $filename
     *
     * @return array
     */
    public function getLogFilenameList($filename = null)
    {
        $data = [];

        if (empty($filename)) {
            $filename = '*.*';
        }

        $files = $this->getLogFileList($filename);

        if (is_array($files)) {
            foreach ($files as $file) {
                $basename = pathinfo($file, PATHINFO_BASENAME);
                $data[$basename] = $file;
            }
        }

        return $data;
    }

    /**
     * Sets the currentLogPath property to
     * the specified path.
     *
     * @param  string  $path
     *
     * @return void
     */
    protected function setCurrentLogPath($path)
    {
        $this->currentLogPath = $path;
    }

    /**
     * Sets the log filename to retrieve the logs data from.
     *
     * @param  string  $filename
     *
     * @return void
     */
    protected function setLogFilename($filename)
    {
        if (empty($filename)) {
            $this->filename = '*.*';
        } else {
            $this->filename = $filename;
        }
    }

    /**
     * Sets the orderByField property to the specified field.
     *
     * @param  string  $field
     *
     * @return void
     */
    protected function setOrderByField($field)
    {
        $field = strtolower($field);

        $acceptedFields = [
            'id',
            'date',
            'level',
            'environment',
            'file_path'
        ];

        if (in_array($field, $acceptedFields)) {
            $this->orderByField = $field;
        }
    }

    /**
     * Sets the orderByDirection property to the specified direction.
     *
     * @param  string  $direction
     *
     * @return void
     */
    protected function setOrderByDirection($direction)
    {
        $direction = strtolower($direction);

        if ($direction == 'desc' || $direction == 'asc') {
            $this->orderByDirection = $direction;
        }
    }

    /**
     * Sets the environment property to the specified environment.
     *
     * @param  string  $environment
     *
     * @return void
     */
    protected function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Sets the level property to the specified level.
     *
     * @param  array  $level
     *
     * @return void
     */
    protected function setLevel($level)
    {
        if (is_array($level)) {
            $this->level = $level;
        }
    }

    /**
     * Sets the includeRead property.
     *
     * @param  bool  $bool
     *
     * @return void
     */
    protected function setIncludeRead($bool = false)
    {
        $this->includeRead = $bool;
    }

    /**
     * Modifies and returns the collection result if modifiers are set
     * such as an orderBy.
     *
     * @param  Collection  $collection
     *
     * @return Collection
     */
    protected function postCollectionModifiers(Collection $collection)
    {
        if ($this->getOrderByField() && $this->getOrderByDirection()) {
            $field = $this->getOrderByField();
            $desc  = false;

            if ($this->getOrderByDirection() === 'desc') {
                $desc = true;
            }

            $sorted = $collection->sortBy(function ($entry) use ($field) {
                if (property_exists($entry, $field)) {
                    return $entry->{$field};
                }
            }, SORT_NATURAL, $desc);

            return $sorted;
        }

        return $collection;
    }

    /**
     * Returns the current page from the current input. Used for pagination.
     *
     * @param  int    $currentPage
     * @param  array  $options  [path => '', query => [], fragment => '', pageName => '']
     *
     * @return int
     */
    protected function getPageFromInput($currentPage = null, array $options = [])
    {
        if (is_numeric($currentPage)) {
            return intval($currentPage);
        }

        $pageName = (array_key_exists('pageName', $options)) ? $options['pageName'] : 'page';

        $page = $this->request->input($pageName);

        if (is_numeric($page)) {
            return intval($page);
        }

        return 1;
    }

    /**
     * Parses the content of the file separating the errors into a single array.
     *
     * @param  string  $content
     * @param  string  $allowedEnvironment
     * @param  array   $allowedLevel
     *
     * @return array
     */
    protected function parseLog($content, $allowedEnvironment = null, $allowedLevel = [])
    {
        $log = [];

        $parsed = $this->parser->parseLogContent($content);

        extract($parsed, EXTR_PREFIX_ALL, 'parsed');

        if (empty($parsed_headerSet)) {
            return $log;
        }

        $needReFormat = in_array('Next', $parsed_headerSet);
        $newContent   = null;

        foreach ($parsed_headerSet as $key => $header) {
            if (empty($parsed_dateSet[$key])) {
                $parsed_dateSet[$key]  = $parsed_dateSet[$key-1];
                $parsed_envSet[$key]   = $parsed_envSet[$key-1];
                $parsed_levelSet[$key] = $parsed_levelSet[$key-1];
                $header                = str_replace("Next", $parsed_headerSet[$key-1], $header);
            }

            $newContent .= $header.' '.$parsed_bodySet[$key];

            if ((empty($allowedEnvironment) || $allowedEnvironment == $parsed_envSet[$key]) && $this->levelable->filter($parsed_levelSet[$key], $allowedLevel)) {
                $log[] = [
                    'environment' => $parsed_envSet[$key],
                    'level'       => $parsed_levelSet[$key],
                    'date'        => $parsed_dateSet[$key],
                    'file_path'   => $this->getCurrentLogPath(),
                    'header'      => $header,
                    'body'        => $parsed_bodySet[$key]
                ];
            }
        }

        if ($needReFormat) {
            file_put_contents($this->getCurrentLogPath(), $newContent);
        }

        return $log;
    }

    /**
     * Retrieves all the data inside each log file from the log file list.
     *
     * @return array|bool
     */
    protected function getLogFiles()
    {
        $data = [];

        $files = $this->getLogFileList();

        if (is_array($files)) {
            $count = 0;

            foreach ($files as $file) {
                $data[$count]['contents'] = file_get_contents($file);
                $data[$count]['path'] = $file;
                $count++;
            }

            return $data;
        }

        return false;
    }

    /**
     * Returns an array of log file paths.
     *
     * @param  null|string  $forceName
     *
     * @return bool|array
     */
    protected function getLogFileList($forceName = null)
    {
        $path = $this->getLogPath();

        if (is_dir($path)) {

            /*
             * Matches files in the log directory with the special name'
             */
            $logPath = sprintf('%s%s%s', $path, DIRECTORY_SEPARATOR, $this->getLogFilename());

            /*
             * Force matches all files in the log directory'
             */
            if (!is_null($forceName)) {
                $logPath = sprintf('%s%s%s', $path, DIRECTORY_SEPARATOR, $forceName);
            }

            return glob($logPath, GLOB_BRACE);
        }

        return false;
    }
}
