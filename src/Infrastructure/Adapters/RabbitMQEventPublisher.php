<?php

declare(strict_types=1);

namespace Src\Infrastructure\Adapters;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Domain\Services\EventPublisherInterface;

final class RabbitMQEventPublisher implements EventPublisherInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $exchangeName;

    public function __construct()
    {
        $host = config('rabbitmq.host', 'localhost');
        $port = (int) config('rabbitmq.port', 5672);
        $user = config('rabbitmq.user', 'guest');
        $password = config('rabbitmq.password', 'guest');
        $vhost = config('rabbitmq.vhost', '/');

        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
        $this->exchangeName = config('rabbitmq.exchange', 'user_events');

        // Declare exchange
        $this->channel->exchange_declare(
            $this->exchangeName,
            'topic',
            false,
            true,
            false
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $eventType, array $data): void
    {
        $messageBody = json_encode($data, JSON_THROW_ON_ERROR);
        $message = new AMQPMessage(
            $messageBody,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $routingKey = $eventType;
        $this->channel->basic_publish($message, $this->exchangeName, $routingKey);
    }

    public function __destruct()
    {
        if (isset($this->channel)) {
            $this->channel->close();
        }

        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
