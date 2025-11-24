package consumer

import (
	"context"
	"fmt"
	"task-optimizer/internal/application"
	"task-optimizer/internal/domain"

	"go.uber.org/zap"
)

// TaskEventHandler handles incoming task events
type TaskEventHandler struct {
	assignTaskUC *application.AssignTaskUseCase
	logger       *zap.Logger
}

// NewTaskEventHandler creates a new task event handler
func NewTaskEventHandler(
	assignTaskUC *application.AssignTaskUseCase,
	logger *zap.Logger,
) *TaskEventHandler {
	return &TaskEventHandler{
		assignTaskUC: assignTaskUC,
		logger:       logger,
	}
}

// HandleTaskCreated handles the task.created event
func (h *TaskEventHandler) HandleTaskCreated(ctx context.Context, event domain.TaskCreatedEvent) error {
	h.logger.Info("Handling task created event",
		zap.Int("task_id", event.TaskID),
		zap.String("title", event.Title),
	)

	if err := h.validateEvent(event); err != nil {
		h.logger.Error("Invalid event",
			zap.Error(err),
			zap.Int("task_id", event.TaskID),
		)
		return fmt.Errorf("validation failed: %w", err)
	}

	task := event.ToTask()

	if err := h.assignTaskUC.Execute(ctx, task); err != nil {
		h.logger.Error("Failed to execute assign task use case",
			zap.Error(err),
			zap.Int("task_id", event.TaskID),
		)
		return err
	}

	return nil
}

// validateEvent validates the task created event
func (h *TaskEventHandler) validateEvent(event domain.TaskCreatedEvent) error {
	if event.TaskID <= 0 {
		return fmt.Errorf("invalid task_id: %d", event.TaskID)
	}

	if event.Title == "" {
		return fmt.Errorf("title cannot be empty")
	}

	if event.Priority < 1 || event.Priority > 5 {
		return fmt.Errorf("priority must be between 1 and 5, got: %d", event.Priority)
	}

	if event.ProjectID <= 0 {
		return fmt.Errorf("invalid project_id: %d", event.ProjectID)
	}

	return nil
}
