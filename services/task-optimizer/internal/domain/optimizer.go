package domain

import (
	"context"
	"errors"
	"fmt"
	"sort"
	"strings"
)

var (
	ErrNoSuitableUsers = errors.New("no suitable users found")
)

// OptimizerService contains the core business logic for task assignment
type OptimizerService struct {
	userRepo UserRepository
}

// NewOptimizerService creates a new optimizer service
func NewOptimizerService(userRepo UserRepository) *OptimizerService {
	return &OptimizerService{
		userRepo: userRepo,
	}
}

// FindBestAssignee finds the best user to assign a task to
func (s *OptimizerService) FindBestAssignee(ctx context.Context, task Task) (*AssignmentResult, error) {
	users, err := s.userRepo.GetActiveUsers(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to get users: %w", err)
	}

	if len(users) == 0 {
		return nil, ErrNoSuitableUsers
	}

	scores := s.calculateScores(task, users)

	sort.Slice(scores, func(i, j int) bool {
		return scores[i].TotalScore > scores[j].TotalScore
	})

	best := scores[0]
	return &best, nil
}

// calculateScores calculates assignment scores for all users
func (s *OptimizerService) calculateScores(task Task, users []User) []AssignmentResult {
	results := make([]AssignmentResult, 0, len(users))

	for _, user := range users {
		skillScore := calculateSkillMatch(user.Skills, task.Skills)
		loadScore := calculateLoadScore(user.CurrentLoad, user.MaxCapacity)
		priorityBonus := calculatePriorityBonus(task.Priority)

		totalScore := (skillScore * 0.4) + (loadScore * 0.4) + (priorityBonus * 0.2)

		result := AssignmentResult{
			UserID:        user.ID,
			UserName:      user.Name,
			TotalScore:    totalScore,
			SkillScore:    skillScore,
			LoadScore:     loadScore,
			PriorityBonus: priorityBonus,
			Reason: fmt.Sprintf(
				"Skill match: %.0f%%, Load: %d/%d, Priority: %d",
				skillScore*100, user.CurrentLoad, user.MaxCapacity, task.Priority,
			),
		}

		results = append(results, result)
	}

	return results
}

func calculateSkillMatch(userSkills, taskSkills []string) float64 {
	if len(taskSkills) == 0 {
		return 1.0
	}

	matches := 0
	for _, taskSkill := range taskSkills {
		for _, userSkill := range userSkills {
			if strings.EqualFold(taskSkill, userSkill) {
				matches++
				break
			}
		}
	}

	return float64(matches) / float64(len(taskSkills))
}

func calculateLoadScore(currentLoad, maxCapacity int) float64 {
	if maxCapacity == 0 {
		return 0.0
	}

	if currentLoad >= maxCapacity {
		return 0.0
	}

	loadPercentage := float64(currentLoad) / float64(maxCapacity)
	return 1.0 - loadPercentage
}

func calculatePriorityBonus(priority int) float64 {
	if priority < 1 {
		priority = 1
	}
	if priority > 5 {
		priority = 5
	}
	return float64(priority) / 5.0
}
