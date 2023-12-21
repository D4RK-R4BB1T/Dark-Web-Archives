<?php namespace Jackiedo\LogReader;

use Illuminate\Support\ServiceProvider;
use Jackiedo\LogReader\Console\Commands\LogReaderDeleteCommand;
use Jackiedo\LogReader\Console\Commands\LogReaderDetailCommand;
use Jackiedo\LogReader\Console\Commands\LogReaderFileListCommand;
use Jackiedo\LogReader\Console\Commands\LogReaderGetCommand;
use Jackiedo\LogReader\Console\Commands\LogReaderRemoveFileCommand;

/**
 * LogReaderServiceProvider
 *
 * @package Jackiedo\LogReader
 * @author Jackie Do <anhvudo@gmail.com>
 * @copyright 2017
 * @access public
 */
class LogReaderServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Publishing package's config
         */
        $packageConfigPath = __DIR__ . '/../../config/config.php';
        $appconfigPath     = config_path('log-reader.php');

        $this->mergeConfigFrom($packageConfigPath, 'log-reader');

        $this->publishes([
            $packageConfigPath => $appconfigPath,
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('log-reader', 'Jackiedo\LogReader\LogReader');

        $this->registerCommands();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['log-reader'];
    }

    /**
     * Register commands
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bind('command.log-reader.delete', 'Jackiedo\LogReader\Console\Commands\LogReaderDeleteCommand');
        $this->app->bind('command.log-reader.detail', 'Jackiedo\LogReader\Console\Commands\LogReaderDetailCommand');
        $this->app->bind('command.log-reader.file-list', 'Jackiedo\LogReader\Console\Commands\LogReaderFileListCommand');
        $this->app->bind('command.log-reader.get', 'Jackiedo\LogReader\Console\Commands\LogReaderGetCommand');
        $this->app->bind('command.log-reader.remove-file', 'Jackiedo\LogReader\Console\Commands\LogReaderRemoveFileCommand');

        $this->commands('command.log-reader.delete');
        $this->commands('command.log-reader.detail');
        $this->commands('command.log-reader.file-list');
        $this->commands('command.log-reader.get');
        $this->commands('command.log-reader.remove-file');
    }
}
