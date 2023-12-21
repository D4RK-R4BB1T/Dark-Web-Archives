<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Support\Collection;

class RabbitMQExchange
{
    /**
     * Exchange name.
     *
     * @var string $name
     */
    protected string $name;

    /**
     * Exchange config.
     *
     * @var Collection $config
     */
    protected Collection $config;

    /**
     * Create a new RabbitMQ Exchange instance.
     *
     * @param string $name
     * @param array $config
     */
    public function __construct(string $name, array $config = [])
    {
        $this
            ->setName($name)
            ->setConfig($config);
    }

    /**
     * Get Exchange name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Exchange name.
     *
     * @param string $name
     *
     * @return RabbitMQExchange
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get Exchange Config.
     *
     * @return Collection
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * Set Exchange config.
     *
     * @param array $config
     *
     * @return RabbitMQExchange
     */
    public function setConfig(array $config): self
    {
        $this->config = new Collection($config);

        return $this;
    }
}
