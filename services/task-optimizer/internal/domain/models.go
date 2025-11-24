package domain

import "time"

// Task represents a task that needs to be assigned
type Task struct {
	ID          int
	Title       string
	Description string
	Priority    int
	ProjectID   int
	Skills      []string
	CreatedAt   time.Time
}

// User represents a potential assignee
type User struct {
	ID          int
	Name        string
	Email       string
	Role        string
	Skills      []string
	CurrentLoad int
	MaxCapacity int
}

// AssignmentResult contains the result of task assignment calculation
type AssignmentResult struct {
	UserID        int
	UserName      string
	TotalScore    float64
	SkillScore    float64
	LoadScore     float64
	PriorityBonus float64
	Reason        string
}

// TaskCreatedEvent represents incoming event from RabbitMQ
type TaskCreatedEvent struct {
	TaskID      int       `json:"task_id"`
	Title       string    `json:"title"`
	Description string    `json:"description"`
	Priority    int       `json:"priority"`
	ProjectID   int       `json:"project_id"`
	Skills      []string  `json:"skills"`
	CreatedAt   time.Time `json:"created_at"`
}

// TaskAssignedEvent represents outgoing event to RabbitMQ
type TaskAssignedEvent struct {
	TaskID     int       `json:"task_id"`
	AssigneeID int       `json:"assignee_id"`
	Score      float64   `json:"score"`
	Reason     string    `json:"reason"`
	AssignedAt time.Time `json:"assigned_at"`
}

// ToTask converts TaskCreatedEvent to Task domain model
func (e TaskCreatedEvent) ToTask() Task {
	return Task{
		ID:          e.TaskID,
		Title:       e.Title,
		Description: e.Description,
		Priority:    e.Priority,
		ProjectID:   e.ProjectID,
		Skills:      e.Skills,
		CreatedAt:   e.CreatedAt,
	}
}
