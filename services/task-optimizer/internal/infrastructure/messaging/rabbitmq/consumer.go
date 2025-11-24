package rabbitmq

import (
	"context"
	"encoding/json"
	"fmt"
	"task-optimizer/internal/domain"

	amqp "github.com/rabbitmq/amqp091-go"
	"go.uber.org/zap"
)

// TaskEventHandler defines the handler for task created events
type TaskEventHandler func(ctx context.Context, event domain.TaskCreatedEvent) error

// Consumer handles consuming messages from RabbitMQ
type Consumer struct {
	conn    *Connection
	logger  *zap.Logger
	handler TaskEventHandler
}

// NewConsumer creates a new RabbitMQ consumer
func NewConsumer(conn *Connection, handler TaskEventHandler, logger *zap.Logger) *Consumer {
	return &Consumer{
		conn:    conn,
		logger:  logger,
		handler: handler,
	}
}

// StartConsuming starts consuming messages from the specified queue
func (c *Consumer) StartConsuming(ctx context.Context, queueName string) error {
	msgs, err := c.conn.GetChannel().Consume(
		queueName,
		"",
		false,
		false,
		false,
		false,
		nil,
	)
	if err != nil {
		return fmt.Errorf("failed to register consumer: %w", err)
	}

	c.logger.Info("Started consuming messages", zap.String("queue", queueName))

	go c.consume(ctx, msgs)

	return nil
}

func (c *Consumer) consume(ctx context.Context, msgs <-chan amqp.Delivery) {
	for {
		select {
		case <-ctx.Done():
			c.logger.Info("Consumer stopped")
			return

		case msg, ok := <-msgs:
			if !ok {
				c.logger.Warn("Message channel closed")
				return
			}

			c.processMessage(ctx, msg)
		}
	}
}

func (c *Consumer) processMessage(ctx context.Context, msg amqp.Delivery) {
	c.logger.Debug("Received message",
		zap.String("routing_key", msg.RoutingKey),
		zap.ByteString("body", msg.Body),
	)

	var event domain.TaskCreatedEvent
	if err := json.Unmarshal(msg.Body, &event); err != nil {
		c.logger.Error("Failed to unmarshal message",
			zap.Error(err),
			zap.ByteString("body", msg.Body),
		)
		msg.Nack(false, false)
		return
	}

	if err := c.handler(ctx, event); err != nil {
		c.logger.Error("Failed to handle event",
			zap.Error(err),
			zap.Int("task_id", event.TaskID),
		)
		msg.Nack(false, true)
		return
	}

	if err := msg.Ack(false); err != nil {
		c.logger.Error("Failed to acknowledge message", zap.Error(err))
	}

	c.logger.Info("Message processed successfully",
		zap.Int("task_id", event.TaskID),
	)
}
