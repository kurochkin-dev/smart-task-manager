<?php

namespace App\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Events\TaskCreated;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

readonly class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CacheService            $cacheService
    ) {}

    public function getAllTasks(): Collection
    {
        return $this->cacheService->rememberTasks(
            fn() => $this->taskRepository->all()
        );
    }

    public function getTask(int $id): ?Task
    {
        return $this->cacheService->rememberTask(
            $id,
            fn() => $this->taskRepository->find($id)
        );
    }

    public function createTask(array $data): Task
    {
        $task = $this->taskRepository->create($data);
        $this->cacheService->invalidateTask($task);

        event(new TaskCreated($task));

        return $task;
    }

    public function updateTask(Task $task, array $data): Task
    {
        $oldProjectId = $task->project_id;
        $oldAssignedUserId = $task->assigned_user_id;

        $task = $this->taskRepository->update($task, $data);
        $this->cacheService->invalidateTaskWithOldRelations($task, $oldProjectId, $oldAssignedUserId);

        return $task;
    }

    public function deleteTask(Task $task): bool
    {
        $projectId = $task->project_id;
        $assignedUserId = $task->assigned_user_id;
        $taskId = $task->id;

        $result = $this->taskRepository->delete($task);

        if ($result) {
            $this->cacheService->invalidateTask($task);
        }

        return $result;
    }

    public function getTasksByUser(int $userId): Collection
    {
        return $this->cacheService->rememberTasksByUser(
            $userId,
            fn() => $this->taskRepository->findByUser($userId)
        );
    }

    public function getTasksByProject(int $projectId): Collection
    {
        return $this->cacheService->rememberTasksByProject(
            $projectId,
            fn() => $this->taskRepository->findByProject($projectId)
        );
    }

    public function assignTask(int|Task $taskOrId, int $userId, array $metadata = []): Task
    {
        $task = is_int($taskOrId) ? $this->getTask($taskOrId) : $taskOrId;

        if (!$task) {
            throw new \InvalidArgumentException("Task not found");
        }

        $oldAssignedUserId = $task->assigned_user_id;

        $task = $this->taskRepository->assignToUser($task, $userId);
        $this->cacheService->invalidateTaskWithOldRelations($task, null, $oldAssignedUserId);

        if (!empty($metadata)) {
            Log::info('Task assigned via microservice', [
                'task_id' => $task->id,
                'assignee_id' => $userId,
                'metadata' => $metadata,
            ]);
        }

        return $task;
    }
}
