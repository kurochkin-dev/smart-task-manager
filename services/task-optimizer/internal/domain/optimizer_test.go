package domain

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockUserRepository is a mock implementation of UserRepository
type MockUserRepository struct {
	mock.Mock
}

func (m *MockUserRepository) GetActiveUsers(ctx context.Context) ([]User, error) {
	args := m.Called(ctx)
	return args.Get(0).([]User), args.Error(1)
}

func (m *MockUserRepository) GetUserByID(ctx context.Context, id int) (*User, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*User), args.Error(1)
}

func (m *MockUserRepository) UpdateUserLoad(ctx context.Context, userID int, increment int) error {
	args := m.Called(ctx, userID, increment)
	return args.Error(0)
}

func TestCalculateSkillMatch(t *testing.T) {
	tests := []struct {
		name        string
		userSkills  []string
		taskSkills  []string
		expected    float64
		description string
	}{
		{
			name:        "perfect match",
			userSkills:  []string{"php", "laravel", "go"},
			taskSkills:  []string{"php", "laravel"},
			expected:    1.0,
			description: "user has all required skills",
		},
		{
			name:        "partial match",
			userSkills:  []string{"php", "laravel"},
			taskSkills:  []string{"php", "laravel", "go"},
			expected:    0.666666,
			description: "user has 2 out of 3 required skills",
		},
		{
			name:        "no match",
			userSkills:  []string{"python", "django"},
			taskSkills:  []string{"php", "laravel"},
			expected:    0.0,
			description: "user has none of the required skills",
		},
		{
			name:        "no requirements",
			userSkills:  []string{"php"},
			taskSkills:  []string{},
			expected:    1.0,
			description: "no skills required - everyone matches",
		},
		{
			name:        "case insensitive",
			userSkills:  []string{"PHP", "Laravel"},
			taskSkills:  []string{"php", "laravel"},
			expected:    1.0,
			description: "skill matching is case insensitive",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := calculateSkillMatch(tt.userSkills, tt.taskSkills)
			assert.InDelta(t, tt.expected, result, 0.01, tt.description)
		})
	}
}

func TestCalculateLoadScore(t *testing.T) {
	tests := []struct {
		name        string
		currentLoad int
		maxCapacity int
		expected    float64
		description string
	}{
		{
			name:        "no load",
			currentLoad: 0,
			maxCapacity: 10,
			expected:    1.0,
			description: "user has no tasks - highest score",
		},
		{
			name:        "half load",
			currentLoad: 5,
			maxCapacity: 10,
			expected:    0.5,
			description: "user is at 50% capacity",
		},
		{
			name:        "full load",
			currentLoad: 10,
			maxCapacity: 10,
			expected:    0.0,
			description: "user is at full capacity",
		},
		{
			name:        "over capacity",
			currentLoad: 15,
			maxCapacity: 10,
			expected:    0.0,
			description: "user is over capacity (should not happen)",
		},
		{
			name:        "no capacity",
			currentLoad: 0,
			maxCapacity: 0,
			expected:    0.0,
			description: "user has no capacity configured",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := calculateLoadScore(tt.currentLoad, tt.maxCapacity)
			assert.Equal(t, tt.expected, result, tt.description)
		})
	}
}

func TestCalculatePriorityBonus(t *testing.T) {
	tests := []struct {
		name     string
		priority int
		expected float64
	}{
		{"priority 1", 1, 0.2},
		{"priority 3", 3, 0.6},
		{"priority 5", 5, 1.0},
		{"priority below range", 0, 0.2},
		{"priority above range", 10, 1.0},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := calculatePriorityBonus(tt.priority)
			assert.Equal(t, tt.expected, result)
		})
	}
}

func TestFindBestAssignee(t *testing.T) {
	ctx := context.Background()

	t.Run("assigns to user with best skills and low load", func(t *testing.T) {
		mockRepo := new(MockUserRepository)
		service := NewOptimizerService(mockRepo)

		users := []User{
			{
				ID:          1,
				Name:        "Expert",
				Skills:      []string{"php", "laravel", "go"},
				CurrentLoad: 2,
				MaxCapacity: 10,
			},
			{
				ID:          2,
				Name:        "Junior",
				Skills:      []string{"php"},
				CurrentLoad: 1,
				MaxCapacity: 10,
			},
		}

		task := Task{
			ID:       1,
			Title:    "Complex task",
			Priority: 5,
			Skills:   []string{"php", "laravel"},
		}

		mockRepo.On("GetActiveUsers", ctx).Return(users, nil)

		result, err := service.FindBestAssignee(ctx, task)

		assert.NoError(t, err)
		assert.NotNil(t, result)
		assert.Equal(t, 1, result.UserID, "Expert should be assigned")
		assert.Greater(t, result.TotalScore, 0.5)
		mockRepo.AssertExpectations(t)
	})

	t.Run("prefers less loaded user when skills are equal", func(t *testing.T) {
		mockRepo := new(MockUserRepository)
		service := NewOptimizerService(mockRepo)

		users := []User{
			{
				ID:          1,
				Name:        "Busy",
				Skills:      []string{"php", "laravel"},
				CurrentLoad: 8,
				MaxCapacity: 10,
			},
			{
				ID:          2,
				Name:        "Available",
				Skills:      []string{"php", "laravel"},
				CurrentLoad: 1,
				MaxCapacity: 10,
			},
		}

		task := Task{
			ID:       1,
			Priority: 3,
			Skills:   []string{"php"},
		}

		mockRepo.On("GetActiveUsers", ctx).Return(users, nil)

		result, err := service.FindBestAssignee(ctx, task)

		assert.NoError(t, err)
		assert.Equal(t, 2, result.UserID, "Available user should be assigned")
		mockRepo.AssertExpectations(t)
	})

	t.Run("returns error when no users available", func(t *testing.T) {
		mockRepo := new(MockUserRepository)
		service := NewOptimizerService(mockRepo)

		mockRepo.On("GetActiveUsers", ctx).Return([]User{}, nil)

		task := Task{ID: 1, Priority: 3}

		result, err := service.FindBestAssignee(ctx, task)

		assert.Error(t, err)
		assert.Nil(t, result)
		assert.Equal(t, ErrNoSuitableUsers, err)
		mockRepo.AssertExpectations(t)
	})
}

func TestCalculateScores(t *testing.T) {
	mockRepo := new(MockUserRepository)
	service := NewOptimizerService(mockRepo)

	users := []User{
		{
			ID:          1,
			Name:        "User 1",
			Skills:      []string{"php", "laravel"},
			CurrentLoad: 0,
			MaxCapacity: 10,
		},
		{
			ID:          2,
			Name:        "User 2",
			Skills:      []string{"php"},
			CurrentLoad: 5,
			MaxCapacity: 10,
		},
	}

	task := Task{
		Priority: 5,
		Skills:   []string{"php", "laravel"},
	}

	scores := service.calculateScores(task, users)

	assert.Len(t, scores, 2)

	// User 1 should have higher score (100% skills, 0% load)
	assert.Greater(t, scores[0].TotalScore, scores[1].TotalScore)
	assert.Equal(t, 1.0, scores[0].SkillScore)
	assert.Equal(t, 1.0, scores[0].LoadScore)

	// User 2 should have lower score (50% skills, 50% load)
	assert.Equal(t, 0.5, scores[1].SkillScore)
	assert.Equal(t, 0.5, scores[1].LoadScore)
}
