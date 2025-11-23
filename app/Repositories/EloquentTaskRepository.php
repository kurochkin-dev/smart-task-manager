<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function all(): Collection
    {
        return Task::with(['project', 'assignedUser', 'creator'])->get();
    }

    public function find(int $id): ?Task
    {
        return Task::with(['project', 'assignedUser', 'creator', 'taskLogs.user'])->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function findByUser(int $userId): Collection
    {
        return Task::where('assigned_user_id', $userId)
            ->with(['project', 'creator'])
            ->get();
    }

    public function findByProject(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)
            ->with(['assignedUser', 'creator'])
            ->get();
    }

    public function assignToUser(Task $task, int $userId): Task
    {
        $task->update(['assigned_user_id' => $userId]);
        return $task->fresh(['assignedUser']);
    }
}
