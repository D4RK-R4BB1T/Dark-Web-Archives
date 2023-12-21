<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Support\Collection;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMQDelivery
{
    protected Collection $config;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @return Collection
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return \Anik\Amqp\Delivery
     */
    public function setConfig(array $config): self
    {
        $this->config = new Collection($config);

        return $this;
    }

    /**
     * Acknowledge a message.
     *
     * @throws RabbitMQException
     * @return bool
     *
     */
    public function acknowledge(): bool
    {
        $config = $this->getConfig();
        $info = $config->get('delivery_info', []);
        /**
         * @var AMQPChannel
         */
        $channel = $info['channel'] ?? null;

        if (!$channel) {
            throw new RabbitMQException('Delivery info or channel is not set');
        }

        $channel->basic_ack(
            $info['delivery_tag'] ?? null
        );

        if ($config->get('body') === 'quit') {
            $channel->basic_cancel(
                $info['consumer_tag'] ?? null
            );
        }

        return true;
    }

    /**
     * Rejects message w/ requeue.
     *
     * @param bool $requeue
     *
     * @throws RabbitMQException
     * @return bool
     *
     */
    public function reject($requeue = false): bool
    {
        $config = $this->getConfig();
        $info = $config->get('delivery_info');
        /**
         * @var AMQPChannel
         */
        $channel = $info['channel'] ?? null;

        if (!$channel) {
            throw new RabbitMQException('Delivery info or channel is not set');
        }

        $channel->basic_reject(
            $info['delivery_tag'] ?? null,
            $requeue
        );

        return true;
    }
}
