<?php

namespace Kunnu\RabbitMQ;

use Closure;

class RabbitMQGenericMessageConsumer extends RabbitMQMessageConsumer
{
    protected $scope;
    protected Closure $handler;

    public function __construct(Closure $handler, $scope, array $config = [])
    {
        parent::__construct($config);
        $this->handler = $handler;
        $this->scope = $scope;
    }

    public function handle(RabbitMQIncomingMessage $message): void
    {
        $this->handler->call($this->scope, $message);
    }
}
