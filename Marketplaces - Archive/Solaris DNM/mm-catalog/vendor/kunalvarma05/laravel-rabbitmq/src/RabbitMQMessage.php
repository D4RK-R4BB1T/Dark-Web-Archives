<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Support\Collection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQMessage
{
    /**
     * Message stream.
     *
     * @var string $stream
     */
    protected string $stream;

    /**
     * Message exchange.
     *
     * @var RabbitMQExchange|null $exchange
     */
    protected ?RabbitMQExchange $exchange = null;

    /**
     * Message config.
     *
     * @var Collection $config
     */
    protected Collection $config;

    /**
     * Create a new RabbitMQ Message instance.
     *
     * @param string $stream
     * @param array $config
     */
    public function __construct(string $stream, array $config = [])
    {
        $this
            ->setStream($stream)
            ->setConfig($config);
    }

    /**
     * Set message config.
     *
     * @param array $config
     *
     * @return RabbitMQMessage
     */
    public function setConfig(array $config): self
    {
        $this->config = new Collection($config);

        return $this;
    }

    /**
     * Get AMQP Message.
     *
     * @return AMQPMessage
     */
    public function getAmqpMessage(): AMQPMessage
    {
        return new AMQPMessage($this->stream, $this->config ? $this->config->toArray() : []);
    }

    /**
     * Set message stream.
     *
     * @param string $stream
     * @return self
     */
    public function setStream(string $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    /**
     * @return string
     */
    public function getStream(): string
    {
        return $this->stream;
    }

    /**
     * @return Collection
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * @return null|RabbitMQExchange
     */
    public function getExchange(): ?RabbitMQExchange
    {
        return $this->exchange;
    }

    /**
     * Set message exchange.
     *
     * @param RabbitMQExchange $exchange
     *
     * @return self
     */
    public function setExchange(RabbitMQExchange $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }
}
