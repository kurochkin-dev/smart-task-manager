package rabbitmq

import (
	"context"
	"encoding/json"
	"fmt"
	"task-optimizer/internal/domain"

	amqp "github.com/rabbitmq/amqp091-go"
	"go.uber.org/zap"
)

// Publisher handles publishing messages to RabbitMQ
type Publisher struct {
	conn         *Connection
	exchangeName string
	logger       *zap.Logger
}

// NewPublisher creates a new RabbitMQ publisher
func NewPublisher(conn *Connection, exchangeName string, logger *zap.Logger) *Publisher {
	return &Publisher{
		conn:         conn,
		exchangeName: exchangeName,
		logger:       logger,
	}
}

// PublishTaskAssigned publishes a task assigned event
func (p *Publisher) PublishTaskAssigned(ctx context.Context, event domain.TaskAssignedEvent) error {
	body, err := json.Marshal(event)
	if err != nil {
		return fmt.Errorf("failed to marshal event: %w", err)
	}

	err = p.conn.GetChannel().PublishWithContext(
		ctx,
		p.exchangeName,
		"task.assigned",
		false,
		false,
		amqp.Publishing{
			ContentType:  "application/json",
			Body:         body,
			DeliveryMode: amqp.Persistent,
		},
	)

	if err != nil {
		return fmt.Errorf("failed to publish message: %w", err)
	}

	p.logger.Info("Published task assigned event",
		zap.Int("task_id", event.TaskID),
		zap.Int("assignee_id", event.AssigneeID),
		zap.Float64("score", event.Score),
	)

	return nil
}
