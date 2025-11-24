<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Events\TaskCreated;
use App\Models\Task;
use App\Services\CacheService;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskRepositoryInterface $taskRepository;
    private CacheService $cacheService;
    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = Mockery::mock(TaskRepositoryInterface::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->taskService = new TaskService($this->taskRepository, $this->cacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_tasks_calls_repository_and_cache(): void
    {
        $tasks = new Collection([new Task()]);

        $this->cacheService
            ->shouldReceive('rememberTasks')
            ->once()
            ->andReturn($tasks);

        $result = $this->taskService->getAllTasks();

        $this->assertEquals($tasks, $result);
    }

    public function test_get_task_calls_repository_and_cache(): void
    {
        $task = new Task(['id' => 1]);

        $this->cacheService
            ->shouldReceive('rememberTask')
            ->with(1, Mockery::type('callable'))
            ->once()
            ->andReturn($task);

        $result = $this->taskService->getTask(1);

        $this->assertEquals($task, $result);
    }

    public function test_create_task_calls_repository_and_invalidates_cache(): void
    {
        Event::fake();

        $data = ['title' => 'Test Task'];
        $task = new Task($data);

        $this->taskRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($task);

        $this->cacheService
            ->shouldReceive('invalidateTask')
            ->with($task)
            ->once();

        $result = $this->taskService->createTask($data);

        $this->assertEquals($task, $result);
        Event::assertDispatched(TaskCreated::class);
    }

    public function test_update_task_calls_repository_and_invalidates_cache_with_old_relations(): void
    {
        $oldTask = new Task(['id' => 1, 'project_id' => 1, 'assigned_user_id' => 1]);
        $updatedTask = new Task(['id' => 1, 'project_id' => 2, 'assigned_user_id' => 2]);
        $data = ['project_id' => 2, 'assigned_user_id' => 2];

        $this->taskRepository
            ->shouldReceive('update')
            ->with($oldTask, $data)
            ->once()
            ->andReturn($updatedTask);

        $this->cacheService
            ->shouldReceive('invalidateTaskWithOldRelations')
            ->with($updatedTask, 1, 1)
            ->once();

        $result = $this->taskService->updateTask($oldTask, $data);

        $this->assertEquals($updatedTask, $result);
    }

    public function test_delete_task_calls_repository_and_invalidates_cache(): void
    {
        $task = new Task(['id' => 1, 'project_id' => 1, 'assigned_user_id' => 1]);

        $this->taskRepository
            ->shouldReceive('delete')
            ->with($task)
            ->once()
            ->andReturn(true);

        $this->cacheService
            ->shouldReceive('invalidateTask')
            ->with($task)
            ->once();

        $result = $this->taskService->deleteTask($task);

        $this->assertTrue($result);
    }

    public function test_get_tasks_by_user_calls_repository_and_cache(): void
    {
        $userId = 1;
        $tasks = new Collection([new Task()]);

        $this->cacheService
            ->shouldReceive('rememberTasksByUser')
            ->with($userId, Mockery::type('callable'))
            ->once()
            ->andReturn($tasks);

        $result = $this->taskService->getTasksByUser($userId);

        $this->assertEquals($tasks, $result);
    }

    public function test_get_tasks_by_project_calls_repository_and_cache(): void
    {
        $projectId = 1;
        $tasks = new Collection([new Task()]);

        $this->cacheService
            ->shouldReceive('rememberTasksByProject')
            ->with($projectId, Mockery::type('callable'))
            ->once()
            ->andReturn($tasks);

        $result = $this->taskService->getTasksByProject($projectId);

        $this->assertEquals($tasks, $result);
    }

    public function test_assign_task_calls_repository_and_invalidates_cache(): void
    {
        $task = new Task(['id' => 1, 'assigned_user_id' => 1]);
        $newUserId = 2;
        $assignedTask = new Task(['id' => 1, 'assigned_user_id' => 2]);

        $this->taskRepository
            ->shouldReceive('assignToUser')
            ->with($task, $newUserId)
            ->once()
            ->andReturn($assignedTask);

        $this->cacheService
            ->shouldReceive('invalidateTaskWithOldRelations')
            ->with($assignedTask, null, 1)
            ->once();

        $result = $this->taskService->assignTask($task, $newUserId);

        $this->assertEquals($assignedTask, $result);
    }
}

