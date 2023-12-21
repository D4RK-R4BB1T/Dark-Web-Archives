<?php

namespace Kunnu\RabbitMQ;

use PhpAmqpLib\Wire\AMQPTable;
use Illuminate\Support\Collection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    /**
     * Maximum batch size.
     *
     * @var int
     */
    protected int $maxBatchSize = 200;

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

    /**
     * Set the max batch size.
     *
     * @param integer $size
     * @return self
     */
    public function setMaxBatchSize(int $size): self
    {
        $this->maxBatchSize = $size;

        return $this;
    }

    /**
     * Publish message(s).
     *
     * @param RabbitMQMessage[]|RabbitMQMessage $messages
     * @param string $routingKey
     * @param string $connectionName
     * @param PublishConfig $config
     *
     * @return void
     */
    public function publish(
        $messages,
        string $routingKey = '',
        string $connectionName = null,
        PublishConfig $publishConfig = null
    ): void {
        $messages = !is_array($messages) ? [$messages] : $messages;
        $publishConfig = $publishConfig ?? new PublishConfig();

        $defaultConfig = new Collection($this->manager->getConfig()->get(RabbitMQManager::CONFIG_KEY . ".defaults"));

        $connectionName = $connectionName ?? $this->manager->resolveDefaultConfigName();
        $connection = $this->manager->resolveConnection();

        $channelId = $this->manager->resolveChannelId($publishConfig->get("channel_id"), $connectionName);
        $channel = $this->manager->resolveChannel($connectionName, $channelId, $connection);

        $connectionConfig = $this->manager->resolveConfig($connectionName);

        // Merge/Override default connection configuration with
        // the configuration specified for this publishing.
        if ($publishConfig && $publishConfig->getConnectionConfig()) {
            // Publish config > Connection config
            $connectionConfig = $connectionConfig->merge($publishConfig->getConnectionConfig());
        }

        $readyMessages = [];

        foreach ($messages as $message) {
            // Merge message configuration
            // Message config > Publish config > Connection config > Default config
            $messageConfig = array_merge(
                $defaultConfig->get('message', []), // Default config
                $connectionConfig->get('message', []), // Connection config
                $publishConfig->get('message', []), // Publish config
                $message->getConfig()->toArray(), // Message config
            );
            // Override the message config
            $message->setConfig($messageConfig);

            // Merge the exchange properties
            // Publish config > Connection config > Default config
            $exchangeProperties = array_merge(
                $defaultConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Default properties
                $connectionConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Connection properties
                $publishConfig->get('exchange', ['properties' => []])['properties'] ?? [], // Publish properties
            );

            // Merge the exchange config
            // Exchange config > Publish config > Connection config > Default config
            $exchangeConfig = array_merge(
                $defaultConfig->get('exchange', []), // Default config
                $connectionConfig->get('exchange', []), // Connection config
                $publishConfig->get('exchange', ['properties' => $exchangeProperties]), // Publish config,
                $message->getExchange() ? $message->getExchange()->getConfig()->toArray() : [], // Exchange config
            );

            // Merge message exchange configuration
            if ($message->getExchange()) {
                $message->getExchange()->setConfig($exchangeConfig);
                $message->getExchange()->getConfig()->put('name', $message->getExchange()->getName());
            } else {
                $message->setExchange(new RabbitMQExchange($exchangeConfig['name'] ?? '', $exchangeConfig));
            }

            $readyMessages[] = $message;
        }

        $this->publishBulk($readyMessages, $channel, $routingKey);
    }

    /**
     * @param RabbitMQMessage[] $messages
     * @param AMQPChannel $channel
     * @param string $routingKey
     *
     * @throws RabbitMQException
     */
    protected function publishBulk(array $messages, AMQPChannel $channel, string $routingKey = ''): void
    {
        if (count($messages) === 0) {
            throw new RabbitMQException('No messages to publish to the exchange.');
        }

        /**
         * @var RabbitMQExchange[]
         */
        $uniqueExchanges = (new Collection($messages))
            ->unique(function (RabbitMQMessage $message) {
                return $message->getExchange()->getName();
            })->map(function (RabbitMQMessage $message) {
                return $message->getExchange();
            })->each(function (RabbitMQExchange $exchange) use ($channel) {
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
            });

        $max = $this->maxBatchSize;

        foreach ($messages as $message) {
            // Queue message for batch publish
            $channel->batch_basic_publish(
                new AMQPMessage($message->getStream(), $message->getConfig()->toArray()),
                $message->getExchange()->getName(),
                $routingKey,
            );

            $batchReadyToBePublished = --$max <= 0;

            if ($batchReadyToBePublished) {
                // Publish all the messages in the batch
                $channel->publish_batch();
                // Reset batch counter
                $max = $this->maxBatchSize;
            }
        }

        // Publish all the remaining batches
        $channel->publish_batch();
    }
}
