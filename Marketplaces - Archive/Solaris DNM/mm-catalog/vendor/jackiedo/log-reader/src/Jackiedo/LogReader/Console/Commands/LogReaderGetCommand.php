<?php namespace Jackiedo\LogReader\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\LogReader\Console\Traits\CreateCommandInstanceTrait;
use Jackiedo\LogReader\Console\Traits\SetLogReaderParamTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LogReaderGetCommand extends Command
{
    use CreateCommandInstanceTrait, SetLogReaderParamTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log-reader:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all log entries from the log files (don\'t include stack traces).';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->setLogReaderParam();

        $logs = $this->getLogEntries();

        foreach ($logs as $logEntry) {
            $this->line("----------------------------------------------");
            $this->line(">>> Entry ID: ".$logEntry->id);
            $this->line("[-] In file: ".$logEntry->file_path);
            $this->line("[-] Date: ".$logEntry->date);
            $this->line("[-] Environment: ".$logEntry->environment);
            $this->line("[-] Level: ".$logEntry->level."\r\n");

            $this->line(">>> Message:");
            $this->line($logEntry->context->message."\r\n");

            $this->line(">>> More informations:");
            $this->line("[-] Exception: ".$logEntry->context->exception);
            $this->line("[-] Caught in: ".$logEntry->context->in.((!empty($logEntry->context->line)) ? ' (line '.$logEntry->context->line.')' : null)."\r\n");
        }
    }

    /**
     * Reading log files and get log entries
     *
     * @return mixed
     */
    protected function getLogEntries()
    {
        if ($this->option('paginate')) {
            $logs  = $this->reader->paginate($this->option('per-page'), $this->option('page'));
            $total = $logs->total();

            $this->line("You have total ".$total." log ".(($total > 1) ? 'entries' : 'entry').".");
            $this->line("You are viewing page ".$logs->currentPage()."/".$logs->lastPage()." as follow:\r\n");
        } else {
            $logs  = $this->reader->get();
            $total = $logs->count();

            $this->line("You have total ".$total." log ".(($total > 1) ? 'entries' : 'entry')." as follow:\r\n");
        }

        return $logs;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('log-path', null, InputOption::VALUE_OPTIONAL, 'The path to directory storing the log files.', $this->reader->getLogPath()),
            array('file-name', null, InputOption::VALUE_OPTIONAL, 'The pattern of the log filenames.', $this->reader->getLogFilename()),
            array('level', null, InputOption::VALUE_OPTIONAL, 'Filter by level.', null),
            array('with-read', 'r', InputOption::VALUE_NONE, 'Include log entries that marked as read in request.'),
            array('order-by', null, InputOption::VALUE_OPTIONAL, 'Order by information.', 'date'),
            array('order-direction', null, InputOption::VALUE_OPTIONAL, 'Order by direction.', 'asc'),
            array('paginate', 'p', InputOption::VALUE_NONE, 'Paging the result.'),
            array('per-page', null, InputOption::VALUE_OPTIONAL, 'The number of log entries that you want to get per page.', 10),
            array('page', null, InputOption::VALUE_OPTIONAL, 'The page that you want to display from paging.', 1),
        );
    }

}
