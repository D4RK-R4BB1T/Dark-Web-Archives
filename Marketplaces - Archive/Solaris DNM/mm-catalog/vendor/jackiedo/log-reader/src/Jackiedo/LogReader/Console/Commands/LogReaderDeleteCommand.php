<?php namespace Jackiedo\LogReader\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\LogReader\Console\Traits\CreateCommandInstanceTrait;
use Jackiedo\LogReader\Console\Traits\SetLogReaderParamTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LogReaderDeleteCommand extends Command
{
    use CreateCommandInstanceTrait, SetLogReaderParamTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log-reader:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete one or all log entries (don\'t remove log file)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->setLogReaderParam();

        if (! empty($this->argument('id'))) {
            $this->reader->find($this->argument('id'))->delete();

            $this->info("You deleted one entry successfully");
        } else {
            $deleted = $this->reader->delete();

            $this->info("You deleted ".$deleted." ".(($deleted > 1) ? 'entries' : 'entry')." successfully");
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
            array('id', InputArgument::OPTIONAL, 'The unique ID of the log entry.'),
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
            array('with-read', 'r', InputOption::VALUE_NONE, 'Include log entries that marked as read in request.'),
        );
    }

}
