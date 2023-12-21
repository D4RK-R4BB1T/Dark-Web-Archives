<?php namespace Jackiedo\LogReader\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\LogReader\Console\Traits\CreateCommandInstanceTrait;
use Jackiedo\LogReader\Console\Traits\SetLogReaderParamTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LogReaderDetailCommand extends Command
{
    use CreateCommandInstanceTrait, SetLogReaderParamTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log-reader:detail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get detail of one log entry (include stack traces).';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->setLogReaderParam();

        $logEntry = $this->reader->withRead()->find($this->argument('id'));

        if ($this->option('raw-content')) {
            $this->line("You are viewing raw content of the log entry as follow:\r\n");

            $rawContent = $logEntry->getRawContent();
            $this->line($rawContent);
        } else {
            $this->line("You are viewing detail of the log entry as follow:\r\n");
            $this->line(">>> Entry ID: ".$logEntry->id);
            $this->line("[-] In file: ".$logEntry->file_path);
            $this->line("[-] Date: ".$logEntry->date);
            $this->line("[-] Environment: ".$logEntry->environment);
            $this->line("[-] Level: ".$logEntry->level."\r\n");

            $this->line(">>> Message:");
            $this->line($logEntry->context->message."\r\n");

            $this->line(">>> Context informations:");
            $this->line("[-] Exception: ".$logEntry->context->exception);
            $this->line("[-] Caught in: ".$logEntry->context->in.((!empty($logEntry->context->line)) ? ' (line '.$logEntry->context->line.')' : null)."\r\n");

            $this->line(">>> Stack trace informations:");
            foreach ($logEntry->stack_traces as $key => $trace) {
                $this->line(($key+1).". ---");
                $this->line("Caught at: ".$trace->caught_at);
                $this->line("Caught in: ".$trace->in.((! empty($trace->line)) ? ' (line '.$trace->line.')' : null)."\r\n");
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('id', InputArgument::REQUIRED, 'The unique ID of the log entry.'),
        );
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
            array('raw-content', 'r', InputOption::VALUE_NONE, 'Display raw content of the log entry.'),
        );
    }

}
