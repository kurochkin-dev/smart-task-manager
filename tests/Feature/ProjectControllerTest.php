<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Project;
use App\Models\User;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_projects_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

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
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

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
                'message' => 'Project created successfully'
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => 'active',
        ]);
    }

    public function test_can_get_specific_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

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
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

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
                'message' => 'Project updated successfully',
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
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Project deleted successfully'
            ]);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_returns_404_for_nonexistent_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_project_data(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/projects', [
            'name' => '',
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }

    public function test_user_role_cannot_create_projects(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/projects', [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_role_cannot_delete_projects(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }
}
