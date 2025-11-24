package rabbitmq

import (
	"fmt"

	amqp "github.com/rabbitmq/amqp091-go"
	"go.uber.org/zap"
)

// Connection manages RabbitMQ connection and channel
type Connection struct {
	conn    *amqp.Connection
	channel *amqp.Channel
	logger  *zap.Logger
}

// NewConnection creates a new RabbitMQ connection
func NewConnection(url string, logger *zap.Logger) (*Connection, error) {
	conn, err := amqp.Dial(url)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to RabbitMQ: %w", err)
	}

	channel, err := conn.Channel()
	if err != nil {
		conn.Close()
		return nil, fmt.Errorf("failed to open channel: %w", err)
	}

	logger.Info("Connected to RabbitMQ successfully")

	return &Connection{
		conn:    conn,
		channel: channel,
		logger:  logger,
	}, nil
}

// Close closes the connection and channel
func (c *Connection) Close() error {
	if c.channel != nil {
		if err := c.channel.Close(); err != nil {
			c.logger.Error("Failed to close channel", zap.Error(err))
		}
	}
	if c.conn != nil {
		if err := c.conn.Close(); err != nil {
			c.logger.Error("Failed to close connection", zap.Error(err))
			return err
		}
	}
	c.logger.Info("RabbitMQ connection closed")
	return nil
}

// DeclareExchange declares an exchange
func (c *Connection) DeclareExchange(name string) error {
	return c.channel.ExchangeDeclare(
		name,
		"topic",
		true,
		false,
		false,
		false,
		nil,
	)
}

// DeclareQueue declares a queue and binds it to an exchange
func (c *Connection) DeclareQueue(queueName, exchangeName, routingKey string) error {
	queue, err := c.channel.QueueDeclare(
		queueName,
		true,
		false,
		false,
		false,
		nil,
	)
	if err != nil {
		return fmt.Errorf("failed to declare queue: %w", err)
	}

	err = c.channel.QueueBind(
		queue.Name,
		routingKey,
		exchangeName,
		false,
		nil,
	)
	if err != nil {
		return fmt.Errorf("failed to bind queue: %w", err)
	}

	c.logger.Info("Queue declared and bound",
		zap.String("queue", queueName),
		zap.String("exchange", exchangeName),
		zap.String("routing_key", routingKey),
	)

	return nil
}

// GetChannel returns the underlying channel
func (c *Connection) GetChannel() *amqp.Channel {
	return c.channel
}
