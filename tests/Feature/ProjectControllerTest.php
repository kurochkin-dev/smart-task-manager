<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_projects_list(): void
    {
        Project::factory()->count(3)->create();

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_project(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Проект успешно создан'
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => 'active',
        ]);
    }

    public function test_can_get_specific_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ]
            ]);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create();

        $updateData = [
            'name' => 'Updated Project',
            'description' => 'Updated Description',
            'status' => 'completed',
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Проект успешно обновлен',
                'data' => [
                    'name' => 'Updated Project',
                    'status' => 'completed',
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
            'status' => 'completed',
        ]);
    }

    public function test_can_delete_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Проект успешно удален'
            ]);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_returns_404_for_nonexistent_project(): void
    {
        $response = $this->getJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_project_data(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => '',
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }
}
