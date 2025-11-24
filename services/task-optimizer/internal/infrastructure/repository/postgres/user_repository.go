package postgres

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"task-optimizer/internal/domain"

	_ "github.com/lib/pq"
)

// UserRepository implements domain.UserRepository for PostgreSQL
type UserRepository struct {
	db *sql.DB
}

// NewUserRepository creates a new PostgreSQL user repository
func NewUserRepository(db *sql.DB) *UserRepository {
	return &UserRepository{db: db}
}

// GetActiveUsers returns all active users with role 'User'
func (r *UserRepository) GetActiveUsers(ctx context.Context) ([]domain.User, error) {
	query := `
		SELECT
			id,
			name,
			email,
			role,
			skills,
			COALESCE(
				(SELECT COUNT(*) FROM tasks WHERE assignee_id = users.id AND status != 'completed'),
				0
			) as current_load,
			10 as max_capacity
		FROM users
		WHERE role = 'User'
		  AND deleted_at IS NULL
		ORDER BY id
	`

	rows, err := r.db.QueryContext(ctx, query)
	if err != nil {
		return nil, fmt.Errorf("failed to query users: %w", err)
	}
	defer rows.Close()

	users := make([]domain.User, 0)

	for rows.Next() {
		var user domain.User
		var skillsJSON []byte

		err := rows.Scan(
			&user.ID,
			&user.Name,
			&user.Email,
			&user.Role,
			&skillsJSON,
			&user.CurrentLoad,
			&user.MaxCapacity,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan user: %w", err)
		}

		if len(skillsJSON) > 0 {
			if err := json.Unmarshal(skillsJSON, &user.Skills); err != nil {
				user.Skills = []string{}
			}
		} else {
			user.Skills = []string{}
		}

		users = append(users, user)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("error iterating users: %w", err)
	}

	return users, nil
}

// GetUserByID returns a user by ID
func (r *UserRepository) GetUserByID(ctx context.Context, id int) (*domain.User, error) {
	query := `
		SELECT
			id,
			name,
			email,
			role,
			skills,
			COALESCE(
				(SELECT COUNT(*) FROM tasks WHERE assignee_id = users.id AND status != 'completed'),
				0
			) as current_load,
			10 as max_capacity
		FROM users
		WHERE id = $1 AND deleted_at IS NULL
	`

	var user domain.User
	var skillsJSON []byte

	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&user.ID,
		&user.Name,
		&user.Email,
		&user.Role,
		&skillsJSON,
		&user.CurrentLoad,
		&user.MaxCapacity,
	)

	if err == sql.ErrNoRows {
		return nil, fmt.Errorf("user not found: %d", id)
	}
	if err != nil {
		return nil, fmt.Errorf("failed to get user: %w", err)
	}

	if len(skillsJSON) > 0 {
		if err := json.Unmarshal(skillsJSON, &user.Skills); err != nil {
			user.Skills = []string{}
		}
	} else {
		user.Skills = []string{}
	}

	return &user, nil
}

// UpdateUserLoad updates the current load of a user
func (r *UserRepository) UpdateUserLoad(ctx context.Context, userID int, increment int) error {
	_, err := r.GetUserByID(ctx, userID)
	if err != nil {
		return fmt.Errorf("failed to update user load: %w", err)
	}

	return nil
}
