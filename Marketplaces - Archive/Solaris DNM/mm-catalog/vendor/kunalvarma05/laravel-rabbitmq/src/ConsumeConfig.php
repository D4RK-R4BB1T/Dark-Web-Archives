<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Support\Collection;

class ConsumeConfig extends Collection
{
    /**
     * Connection Configuration.
     *
     * @var ConnectionConfig|null $connectionConfig
     */
    protected ?ConnectionConfig $connectionConfig;

    /**
     * Create a new ConsumeConfig instance.
     *
     * @param array $config
     * @param ConnectionConfig|null $connectionConfig
     */
    public function __construct(array $config = [], ?ConnectionConfig $connectionConfig = null)
    {
        parent::__construct($config);
        $this->connectionConfig = $connectionConfig;
    }

    /**
     * Get connection config.
     *
     * @return ConnectionConfig|null
     */
    public function getConnectionConfig(): ?ConnectionConfig
    {
        return $this->connectionConfig;
    }
}
