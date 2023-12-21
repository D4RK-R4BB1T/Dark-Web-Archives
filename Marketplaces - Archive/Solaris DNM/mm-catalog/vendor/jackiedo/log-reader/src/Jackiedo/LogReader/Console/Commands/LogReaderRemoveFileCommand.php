<?php namespace Jackiedo\LogReader\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\LogReader\Console\Traits\CreateCommandInstanceTrait;
use Jackiedo\LogReader\Console\Traits\SetLogReaderParamTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LogReaderRemoveFileCommand extends Command
{
    use CreateCommandInstanceTrait, SetLogReaderParamTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log-reader:remove-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the log files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->setLogReaderParam();

        $removed = $this->reader->removeLogFile();

        $this->info("You deleted ".$removed." log ".(($removed > 1) ? 'files' : 'file')." successfully.");
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
        );
    }

}
