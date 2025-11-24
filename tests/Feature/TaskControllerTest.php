<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_get_tasks_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->count(3)->create();

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_task(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $project = Project::factory()->create();
        $creator = User::factory()->create();

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'high',
            'project_id' => $project->id,
            'created_by' => $creator->id,
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Task created successfully'
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    public function test_can_get_specific_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ]
            ]);
    }

    public function test_can_update_task(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $task = Task::factory()->create();

        $updateData = [
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'status' => 'in_progress',
            'priority' => 'medium',
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => [
                    'title' => 'Updated Task',
                    'status' => 'in_progress',
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'status' => 'in_progress',
        ]);
    }

    public function test_can_delete_task(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_can_get_user_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->count(2)->create(['assigned_user_id' => $user->id]);
        Task::factory()->count(1)->create();

        $response = $this->getJson("/api/tasks/user/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                    ]
                ]
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_get_project_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = Project::factory()->create();
        Task::factory()->count(2)->create(['project_id' => $project->id]);
        Task::factory()->count(1)->create();

        $response = $this->getJson("/api/tasks/project/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                    ]
                ]
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_assign_task_to_user(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $task = Task::factory()->create();
        $user = User::factory()->create();

        $response = $this->postJson("/api/tasks/{$task->id}/assign", [
            'assigned_user_id' => $user->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Task assigned successfully'
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_returns_404_for_nonexistent_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_task_data(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/tasks', [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'project_id', 'created_by']);
    }

    public function test_user_role_cannot_create_tasks(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $project = Project::factory()->create();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'high',
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_assigned_user_can_update_own_task(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $task = Task::factory()->create(['assigned_user_id' => $user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated by assigned user',
            'description' => $task->description,
            'status' => 'in_progress',
            'priority' => $task->priority,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Updated by assigned user',
                ]
            ]);
    }
}
