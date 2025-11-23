<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 *
 * @method Project create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method Project make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['active', 'completed', 'paused']),
            'start_date' => fake()->date(),
            'end_date' => fake()->date('Y-m-d', '+1 year'),
        ];
    }
}
