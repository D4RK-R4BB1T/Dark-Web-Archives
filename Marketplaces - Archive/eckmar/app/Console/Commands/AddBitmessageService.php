<?php

namespace App\Console\Commands;

use App\Console\Utility\CommandSignature;
use App\Marketplace\Bitmessage\Bitmessage;
use Illuminate\Console\Command;

class AddBitmessageService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:addbitmessage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically test the configuration for the bitmessage service and add it to .env if test is passed';

    /**
     * Connection parameters
     *
     * @var
     */
    private $host;
    private $port;
    private $user;
    private $password;

    /**
     * Parameters valid check
     *
     * @var bool
     */
    private $parametersValid = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line(CommandSignature::get());
        $this->info('Starting configuration of Bitmessage service');
        $this->line('');
        $this->info('Before starting service configuration, make sure you have enabled API access in Bitmessage keys.dat file and configured access parameters');
        $this->line('');
        $this->info('If you need help go to official documentation:');
        $this->info('https://bitmessage.org/wiki/API_Reference');

        if ($this->confirm('Do you wish to continue ?')) {
            $this->info('Required parameters: host, port, user, password]');

            $this->parametersValid = true;
            $this->getParameters();
            $this->parametersValid = $this->parametersValidCheck();
            while (!$this->parametersValid){
                $this->getParameters();
                $this->parametersValid = $this->parametersValidCheck();
            }


            $this->info('Testing connection...');

            $bitmessage = new Bitmessage($this->user,$this->password,$this->host,$this->port);
            if ($bitmessage->testConnection()){
                $this->info('Connection to the Bitmessage client established successfully');
                $this->enableService();
                $this->info('Service configured successfully');
            } else {
                $this->error('Could not connect to the Bitmessage client, parameters are invalid or client is not active');
            }
        }
    }

    private function getParameters(){
        $this->host = $this->ask('Enter Bitmessage host (localhost if hosted on same server)');
        $this->port = $this->ask('Enter Bitmessage port (Default port is 8442)');
        $this->user = $this->ask('Enter Bitmessage user');
        $this->password = $this->ask('Enter Bitmessage password');
    }

    public function parametersValidCheck(){
        $headers = ['Parameter', 'Value'];
        $data = [
            ['host', $this->host],
            ['port', $this->port],
            ['user', $this->user],
            ['password', $this->password]
        ];
        $this->table($headers, $data);

        return $this->confirm('Does this look right ?');
    }

    private function enableService(){
        self::changeEnv('BITMESSAGE_ENABLED','true');
        self::changeEnv('BITMESSAGE_HOST',$this->host);
        self::changeEnv('BITMESSAGE_PORT',$this->port);
        self::changeEnv('BITMESSAGE_USER',$this->user);
        self::changeEnv('BITMESSAGE_PASSWORD',$this->password);

    }

    private static function changeEnv($key,$value)
    {
        $path = base_path('.env');

        if(is_bool(env($key)))
        {
            $old = env($key)? 'true' : 'false';
        }
        elseif(env($key)===null){
            $old = 'null';
        }
        else{
            $old = env($key);
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=".$old, "$key=".$value, file_get_contents($path)
            ));
        }
    }

}
