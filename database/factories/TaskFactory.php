<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'estimated_hours' => fake()->numberBetween(1, 40),
            'actual_hours' => fake()->numberBetween(0, 50),
            'required_skills' => fake()->randomElements(['PHP', 'Laravel', 'JavaScript', 'React', 'Go', 'PostgreSQL'], 2),
            'complexity' => fake()->numberBetween(1, 5),
            'due_date' => fake()->date('Y-m-d', '+3 months'),
            'project_id' => Project::factory(),
            'assigned_user_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
