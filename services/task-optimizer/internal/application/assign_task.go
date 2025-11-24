package application

import (
	"context"
	"fmt"
	"task-optimizer/internal/domain"
	"time"

	"go.uber.org/zap"
)

// AssignTaskUseCase orchestrates the task assignment process
type AssignTaskUseCase struct {
	optimizer *domain.OptimizerService
	userRepo  domain.UserRepository
	publisher domain.EventPublisher
	logger    *zap.Logger
}

// NewAssignTaskUseCase creates a new use case instance
func NewAssignTaskUseCase(
	optimizer *domain.OptimizerService,
	userRepo domain.UserRepository,
	publisher domain.EventPublisher,
	logger *zap.Logger,
) *AssignTaskUseCase {
	return &AssignTaskUseCase{
		optimizer: optimizer,
		userRepo:  userRepo,
		publisher: publisher,
		logger:    logger,
	}
}

// Execute performs the complete task assignment workflow
func (uc *AssignTaskUseCase) Execute(ctx context.Context, task domain.Task) error {
	uc.logger.Info("Starting task assignment",
		zap.Int("task_id", task.ID),
		zap.String("title", task.Title),
		zap.Int("priority", task.Priority),
		zap.Strings("skills", task.Skills),
	)

	result, err := uc.optimizer.FindBestAssignee(ctx, task)
	if err != nil {
		uc.logger.Error("Failed to find assignee",
			zap.Int("task_id", task.ID),
			zap.Error(err),
		)
		return fmt.Errorf("failed to find assignee: %w", err)
	}

	uc.logger.Info("Found best assignee",
		zap.Int("task_id", task.ID),
		zap.Int("user_id", result.UserID),
		zap.String("user_name", result.UserName),
		zap.Float64("score", result.TotalScore),
		zap.String("reason", result.Reason),
	)

	if err := uc.userRepo.UpdateUserLoad(ctx, result.UserID, 1); err != nil {
		uc.logger.Error("Failed to update user load",
			zap.Int("user_id", result.UserID),
			zap.Error(err),
		)
	}

	event := domain.TaskAssignedEvent{
		TaskID:     task.ID,
		AssigneeID: result.UserID,
		Score:      result.TotalScore,
		Reason:     result.Reason,
		AssignedAt: time.Now(),
	}

	if err := uc.publisher.PublishTaskAssigned(ctx, event); err != nil {
		uc.logger.Error("Failed to publish event",
			zap.Int("task_id", task.ID),
			zap.Error(err),
		)
		return fmt.Errorf("failed to publish event: %w", err)
	}

	uc.logger.Info("Task assignment completed successfully",
		zap.Int("task_id", task.ID),
		zap.Int("assignee_id", result.UserID),
	)

	return nil
}
