<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private $channel = null;

    public function __construct()
    {
    }

    private function connect(): void
    {
        if ($this->connection !== null) {
            return;
        }

        $this->connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.host'),
            config('queue.connections.rabbitmq.port'),
            config('queue.connections.rabbitmq.login'),
            config('queue.connections.rabbitmq.password'),
            config('queue.connections.rabbitmq.vhost')
        );

        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare(
            'tasks',
            'topic',
            false,
            true,
            false
        );
    }

    /**
     * Publish a message to RabbitMQ
     *
     * @param string $exchange
     * @param string $routingKey
     * @param array $data
     * @return void
     */
    public function publish(string $exchange, string $routingKey, array $data): void
    {
        $this->connect();

        $message = new AMQPMessage(
            json_encode($data),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channel->basic_publish($message, $exchange, $routingKey);

        Log::info('Published message to RabbitMQ', [
            'exchange' => $exchange,
            'routing_key' => $routingKey,
            'data' => $data,
        ]);
    }

    /**
     * Consume messages from a queue
     *
     * @param string $queue
     * @param callable $callback
     * @return void
     */
    public function consume(string $queue, callable $callback): void
    {
        $this->connect();

        $this->channel->queue_declare(
            $queue,
            false,
            true,
            false,
            false
        );

        $this->channel->queue_bind($queue, 'tasks', $queue);

        Log::info('Starting to consume messages', ['queue' => $queue]);

        $this->channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function ($msg) use ($callback) {
                try {
                    $callback($msg);
                    $msg->ack();
                } catch (\Exception $e) {
                    Log::error('Failed to process message', [
                        'error' => $e->getMessage(),
                        'body' => $msg->body,
                    ]);
                    $msg->nack(true);
                }
            }
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
