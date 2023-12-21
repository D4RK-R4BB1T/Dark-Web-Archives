<?php namespace Jackiedo\LogReader\Console\Commands;

use Illuminate\Console\Command;
use Jackiedo\LogReader\Console\Traits\CreateCommandInstanceTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LogReaderFileListCommand extends Command
{
    use CreateCommandInstanceTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log-reader:file-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the log files list';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $logPath = $this->option('log-path');

        if (! empty($logPath)) {
            $this->reader->setLogPath($logPath);
        }

        $fileList  = $this->reader->getLogFilenameList($this->option('file-name'));
        $totalFile = count($fileList);

        $this->line("You have total ".$totalFile." log ".(($totalFile > 1) ? 'files' : 'file').":\r\n");

        $headers = ['File name', 'Path'];
        $output  = [];

        foreach ($fileList as $fileName => $filePath) {
            $output[] = [
                'file_name' => $fileName,
                'file_path' => $filePath
            ];
        }

        $this->table($headers, $output);
        $this->line("");
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
