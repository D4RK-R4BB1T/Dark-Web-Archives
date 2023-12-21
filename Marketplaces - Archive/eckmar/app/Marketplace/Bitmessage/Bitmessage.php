<?php


namespace App\Marketplace\Bitmessage;


use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class Bitmessage
{

    private $client;
    public function __construct($username,$password,$host,$port) {
        $connectionString = 'http://'.$username.':'.$password.'@'.$host.':'.$port.'/';
        $this->client = new Client($connectionString);
    }

    /**
     * Return true if connection is established and valid
     * @return bool
     */
    public function testConnection(): bool {
        $response = $this->client->send(new Request('helloWorld', [
            new Value('market'),
            new Value('place')
        ]));

        return $response->value()->scalarval() == 'market-place';
    }
    
    public function broadcast(string $fromAddress,string $subject,string $message){
        $response = $this->client->send(new Request('sendBroadcast', [
            new Value($fromAddress),
            new Value(base64_encode($subject)),
            new Value(base64_encode($message)),
        ]));
        return $response->value()->scalarval();
    }

    public function sendMessage(string $toAddress, string $fromAddress, string $subject, string $message){
        $response = $this->client->send(new Request('sendMessage', [
            new Value($toAddress),
            new Value($fromAddress),
            new Value(base64_encode($subject)),
            new Value(base64_encode($message))
        ]));
        $responseValue = $response->value()->scalarval();
        if (strpos(strtolower($responseValue),'error') !== false){
            throw new \Exception($responseValue);
        }
        return $responseValue;
    }

}