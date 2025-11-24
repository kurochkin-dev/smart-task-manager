package domain

import "context"

// UserRepository defines methods for accessing user data
type UserRepository interface {
	// GetActiveUsers returns all active users with role 'User'
	GetActiveUsers(ctx context.Context) ([]User, error)

	// GetUserByID returns a user by ID
	GetUserByID(ctx context.Context, id int) (*User, error)

	// UpdateUserLoad updates the current load of a user
	UpdateUserLoad(ctx context.Context, userID int, increment int) error
}

// EventPublisher defines methods for publishing events
type EventPublisher interface {
	// PublishTaskAssigned publishes task assignment event
	PublishTaskAssigned(ctx context.Context, event TaskAssignedEvent) error
}
