<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function invalidateTask(Task $task): void
    {
        Cache::forget("task:{$task->id}");
        Cache::forget('tasks:list');

        if ($task->project_id) {
            Cache::forget("tasks:project:{$task->project_id}");
        }

        if ($task->assigned_user_id) {
            Cache::forget("tasks:user:{$task->assigned_user_id}");
        }
    }

    public function invalidateTaskWithOldRelations(Task $task, ?int $oldProjectId, ?int $oldAssignedUserId): void
    {
        Cache::forget("task:{$task->id}");
        Cache::forget('tasks:list');

        if ($oldProjectId) {
            Cache::forget("tasks:project:{$oldProjectId}");
        }

        if ($task->project_id && $task->project_id !== $oldProjectId) {
            Cache::forget("tasks:project:{$task->project_id}");
        }

        if ($oldAssignedUserId) {
            Cache::forget("tasks:user:{$oldAssignedUserId}");
        }

        if ($task->assigned_user_id && $task->assigned_user_id !== $oldAssignedUserId) {
            Cache::forget("tasks:user:{$task->assigned_user_id}");
        }
    }

    public function invalidateUser(User $user): void
    {
        Cache::forget("user:{$user->id}");
        Cache::forget("user:{$user->id}:workload");
        Cache::forget('users:list');
    }

    public function invalidateProject(Project $project): void
    {
        Cache::forget("project:{$project->id}");
        Cache::forget("tasks:project:{$project->id}");
        Cache::forget('projects:list');
    }

    public function invalidateProjectWithTasks(int $projectId): void
    {
        Cache::forget("project:{$projectId}");
        Cache::forget("tasks:project:{$projectId}");
        Cache::forget('projects:list');
        Cache::forget('tasks:list');
    }

    public function rememberTasks(callable $callback): mixed
    {
        return Cache::remember('tasks:list', config('cache_ttl.lists'), $callback);
    }

    public function rememberTask(int $id, callable $callback): mixed
    {
        return Cache::remember("task:{$id}", config('cache_ttl.items'), $callback);
    }

    public function rememberUsers(callable $callback): mixed
    {
        return Cache::remember('users:list', config('cache_ttl.lists'), $callback);
    }

    public function rememberUser(int $id, callable $callback): mixed
    {
        return Cache::remember("user:{$id}", config('cache_ttl.items'), $callback);
    }

    public function rememberProjects(callable $callback): mixed
    {
        return Cache::remember('projects:list', config('cache_ttl.lists'), $callback);
    }

    public function rememberProject(int $id, callable $callback): mixed
    {
        return Cache::remember("project:{$id}", config('cache_ttl.items'), $callback);
    }

    public function rememberUserWorkload(int $userId, callable $callback): mixed
    {
        return Cache::remember("user:{$userId}:workload", config('cache_ttl.workload'), $callback);
    }

    public function rememberTasksByUser(int $userId, callable $callback): mixed
    {
        return Cache::remember("tasks:user:{$userId}", config('cache_ttl.lists'), $callback);
    }

    public function rememberTasksByProject(int $projectId, callable $callback): mixed
    {
        return Cache::remember("tasks:project:{$projectId}", config('cache_ttl.lists'), $callback);
    }
}
