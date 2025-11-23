<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_tasks_list(): void
    {
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
                'message' => 'Задача успешно создана'
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    public function test_can_get_specific_task(): void
    {
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
                'message' => 'Задача успешно обновлена',
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
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Задача успешно удалена'
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_can_get_user_tasks(): void
    {
        $user = User::factory()->create();
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
                'message' => 'Задача успешно назначена'
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_returns_404_for_nonexistent_task(): void
    {
        $response = $this->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_task_data(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'project_id', 'created_by']);
    }
}
