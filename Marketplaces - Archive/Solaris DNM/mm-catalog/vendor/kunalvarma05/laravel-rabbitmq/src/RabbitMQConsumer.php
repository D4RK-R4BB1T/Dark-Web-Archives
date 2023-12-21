<?php

namespace Kunnu\RabbitMQ;

use PhpAmqpLib\Wire\AMQPTable;
use Illuminate\Support\Collection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{
    /**
     * RabbitMQ Manager.
     *
     * @var RabbitMQManager $manager
     */
    protected RabbitMQManager $manager;

    /**
     * Create a new RabbitMQ Publisher instance.
     *
     * @param RabbitMQManager $manager
     */
    public function __construct(RabbitMQManager $manager)
    {
        $this->manager = $manager;
    }

    public function consume(
        RabbitMQMessageConsumer $messageConsumer,
        string $bindingKey = '',
        string $connectionName = null,
        ConsumeConfig $consumeConfig = null
    ): void {
        $consumeConfig = $consumeConfig ?? new ConsumeConfig();
        $defaultConfig = new Collection($this->manager->getConfig()->get(RabbitMQManager::CONFIG_KEY . ".defaults"));

        $connectionName = $connectionName ?? $this->manager->resolveDefaultConfigName();
        $connection = $this->manager->resolveConnection();

        $channelId = $this->manager->resolveChannelId($consumeConfig->get("channel_id"), $connectionName);
        $channel = $this->manager->resolveChannel($connectionName, $channelId, $connection);

        $connectionConfig = $this->manager->resolveConfig($connectionName);

        // Merge/Override default connection configuration with
        // the configuration specified for this consuming.
        if ($consumeConfig && $consumeConfig->getConnectionConfig()) {
            // consume config > Connection config
            $connectionConfig = $connectionConfig->merge($consumeConfig->getConnectionConfig());
        }

        // Merge the exchange properties
        // Consume config > Connection config > Default config
        $exchangeProperties = array_merge(
            $defaultConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Default properties
            $connectionConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Connection properties
            $consumeConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Consume properties
        );

        // Merge the exchange config
        // Exchange config > Consume config > Connection config > Default config
        $exchangeConfig = array_merge(
            $defaultConfig->get('exchange', []), // Default config
            $connectionConfig->get('exchange', []), // Connection config
            $consumeConfig->get('exchange', ['properties' => $exchangeProperties]), // Consume config,
            $messageConsumer->getExchange() ? $messageConsumer->getExchange()->getConfig()->toArray() : [], // Exchange config
        );

        // Merge message exchange configuration
        if ($messageConsumer->getExchange()) {
            $messageConsumer->getExchange()->setConfig($exchangeConfig);
            $messageConsumer->getExchange()->getConfig()->put('name', $messageConsumer->getExchange()->getName());
        } else {
            $messageConsumer->setExchange(new RabbitMQExchange($exchangeConfig['name'] ?? '', $exchangeConfig));
        }

        // Merge the queue declare properties
        // Consume config > Connection config > Default config
        $queueDeclareProperties = array_merge(
            $defaultConfig->get('queue', ['declare_properties' => []])['declare_properties'] ?? [], // Default properties
            $connectionConfig->get(
                'queue',
                ['declare_properties' => []]
            )['declare_properties'] ?? [], // Connection properties
            $consumeConfig->get(
                'queue',
                ['declare_properties' => []]
            )['declare_properties'] ?? [], // Consume properties
        );

        // Merge the queue bind properties
        // Consume config > Connection config > Default config
        $queueBindProperties = array_merge(
            $defaultConfig->get('queue', ['bind_properties' => []])['bind_properties'] ?? [], // Default properties
            $connectionConfig->get('queue', ['bind_properties' => []])['bind_properties'] ?? [], // Connection properties
            $consumeConfig->get('queue', ['bind_properties' => []])['bind_properties'] ?? [], // Consume properties
        );

        // Merge the queue declare and bind properties
        $queueProperties = array_merge($queueDeclareProperties, $queueBindProperties);

        // Merge the queue config
        // Exchange config > Consume config > Connection config > Default config
        $queueConfig = array_merge(
            $defaultConfig->get('queue', []), // Default config
            $connectionConfig->get('queue', []), // Connection config
            $consumeConfig->get('queue', ['properties' => $queueProperties]), // Consume config,
            $messageConsumer->getQueue() ? $messageConsumer->getQueue()->getConfig()->toArray() : [], // Queue config
        );

        // Merge message queue configuration
        if ($messageConsumer->getQueue()) {
            $messageConsumer->getQueue()->setConfig($queueConfig);
            $messageConsumer->getQueue()->getConfig()->put('name', $messageConsumer->getQueue()->getName());
        } else {
            $messageConsumer->setQueue(new RabbitMQQueue($queueConfig['name'] ?? '', $queueConfig));
        }

        // Merge the consumer properties
        // Consume config > Connection config > Default config
        $consumerProperties = array_merge(
            $defaultConfig->get('consumer', ['properties' => []])['properties'] ?? [], // Default properties
            $connectionConfig->get('consumer', ['properties' => []])['properties'] ?? [], // Connection properties
            $consumeConfig->get('consumer', ['properties' => []])['properties'] ?? [], // Consume properties
        );

        // Merge the consumer config
        // Consumer config > Consume config > Connection config > Default config
        $consumerConfig = array_merge(
            $defaultConfig->get('consumer', []), // Default config
            $connectionConfig->get('consumer', []), // Connection config
            $consumeConfig->get('consumer', ['properties' => $consumerProperties]), // Consume config,
            $messageConsumer->getConfig()->toArray(), // Consumer config
        );

        // Override consumer config with reconciled configuration
        $messageConsumer->setConfig($consumerConfig);

        // Consume config > Connection config > Default config
        $qosConfig = new Collection(array_merge(
            $defaultConfig->get('qos', []), // Default config
            $connectionConfig->get('qos', []), // Connection config
            $consumeConfig->get('qos', []), // Consume config,
        ));

        /* QoS is not attached to any exchange, queue */
        if ($qosConfig->get('enabled')) {
            $channel->basic_qos(
                $qosConfig->get('qos_prefetch_size'),
                $qosConfig->get('qos_prefetch_count'),
                $qosConfig->get('qos_a_global')
            );
        }

        $exchange = $messageConsumer->getExchange();
        $exchangeConfig = $exchange->getConfig();

        if ($exchangeConfig->get('declare')) {
            $channel->exchange_declare(
                $exchange->getName(),
                $exchangeConfig->get('type'),
                $exchangeConfig->get('passive', false),
                $exchangeConfig->get('durable', true),
                $exchangeConfig->get('auto_delete', false),
                $exchangeConfig->get('internal', false),
                $exchangeConfig->get('nowait', false),
                new AMQPTable($exchangeConfig->get('properties', []))
            );
        }

        $queue = $messageConsumer->getQueue();
        $queueConfig = $queue->getConfig();

        if (empty($queueConfig->get('name')) || $queueConfig->get('declare')) {
            if (empty($queueConfig->get('name')) && empty($queue->getName())) {
                $qp['nowait'] = false;
            }

            list($queueName, $messageCount, $consumerCount) = $channel->queue_declare(
                $queue->getName(),
                $queueConfig->get('passive', false),
                $queueConfig->get('durable', true),
                $queueConfig->get('exclusive', true),
                $queueConfig->get('auto_delete', false),
                $queueConfig->get('nowait', false),
                new AMQPTable($queueConfig->get('properties', [])),
                $queueConfig->get('ticket'),
            );

            // If the queue name was generated
            if ($queueName !== $queue->getName()) {
                $queue->setName($queueName);
                $queue->getConfig()->put('name', $queueName);
            }

            $queue->getConfig()->put('messageCount', $messageCount);
            $queue->getConfig()->put('consumerCount', $consumerCount);
        }

        // No queue can be bound to the default exchange.
        if ($exchange->getName()) {
            $channel->queue_bind(
                $queue->getName(),
                $exchange->getName(),
                $bindingKey,
                $queueConfig->get('nowait', false),
                new AMQPTable($queueConfig->get('properties.bind_properties', []))
            );
        }

        // Make an incoming message instance
        $message = new RabbitMQIncomingMessage();

        $message
            ->setExchange($messageConsumer->getExchange())
            ->setQueue($messageConsumer->getQueue());

        $callback = function (AMQPMessage $msg) use ($message, $messageConsumer) {
            // Prepare the message for consumption
            $message
                ->setStream($msg->body)
                ->setAmqpMessage($msg)
                ->setDelivery(
                    new RabbitMQDelivery([
                        'body' => $msg->body,
                        'delivery_info' => $msg->delivery_info,
                    ])
                );
            // Let the consumer handle the message
            $messageConsumer->handle($message);
        };

        $channel->basic_consume(
            $queue->getName(),
            $messageConsumer->getConfig()->get('tag', ''),
            $messageConsumer->getConfig()->get('no_local', false),
            $messageConsumer->getConfig()->get('no_ack', false),
            $messageConsumer->getConfig()->get('exclusive', false),
            $messageConsumer->getConfig()->get('nowait', false),
            $callback,
            $messageConsumer->getConfig()->get('ticket', null),
            $messageConsumer->getConfig()->get('parameters', [])
        );

        while ($channel->is_consuming()) {
            $channel->wait(
                $consumeConfig->get('wait_allowed_methods'),
                $consumeConfig->get('wait_non_blocking', false),
                $consumeConfig->get('wait_timeout'),
            );
        }
    }
}
